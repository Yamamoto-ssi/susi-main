<?php
declare(strict_types=1);
require_once __DIR__.'/../config/auth.php';
$userId=require_roles(['Buyer','Staff','Administrator']);
require_once __DIR__.'/../config/db.php';

$notice=$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();

 // A property can only be deleted by the account that owns it. Keep listings
 // that already have auction, bid, or viewing activity so records are not orphaned.
 if (($_POST['action'] ?? '') === 'delete_property') {
  $propertyId = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);
  if (!$propertyId || $propertyId <= 0) {
   $error = 'Invalid property selected.';
  } else {
   $q = $pdo->prepare("SELECT id, title FROM properties WHERE id = ? AND user_id = ? LIMIT 1");
   $q->execute([$propertyId, $userId]);
   $ownedProperty = $q->fetch();
   if (!$ownedProperty) {
    $error = 'That property was not found in your account.';
   } else {
    $q = $pdo->prepare("SELECT
      (SELECT COUNT(*) FROM bids WHERE property_id = ?) AS bid_count,
      (SELECT COUNT(*) FROM auctions WHERE property_id = ?) AS auction_count,
      (SELECT COUNT(*) FROM viewing_bookings WHERE property_id = ?) AS viewing_count");
    $q->execute([$propertyId, $propertyId, $propertyId]);
    $activity = $q->fetch();
    if ((int)$activity['bid_count'] > 0 || (int)$activity['auction_count'] > 0 || (int)$activity['viewing_count'] > 0) {
     $error = 'This property cannot be deleted because it already has auction, bid, or viewing activity.';
    } else {
     $q = $pdo->prepare("DELETE FROM properties WHERE id = ? AND user_id = ?");
     $q->execute([$propertyId, $userId]);
     $notice = $q->rowCount() ? 'Property deleted successfully.' : 'The property could not be deleted.';
    }
   }
  }
 } else {
  $title=trim($_POST['title']??''); $description=trim($_POST['description']??''); $location=trim($_POST['location']??'');
  $lot=filter_input(INPUT_POST,'lot_area',FILTER_VALIDATE_FLOAT); $floor=filter_input(INPUT_POST,'floor_area',FILTER_VALIDATE_FLOAT); $price=filter_input(INPUT_POST,'price',FILTER_VALIDATE_FLOAT);
  if($title===''||$description===''||$location===''||!$lot||!$floor||!$price||$lot<=0||$floor<=0||$price<=0) $error='Complete all fields with valid positive area and price values.';
  else {
   $q=$pdo->prepare("INSERT INTO properties (user_id,title,description,location,lot_area,floor_area,price,status,is_approved) VALUES (?,?,?,?,?,?,?,'Available',0)");
   $q->execute([$userId,$title,$description,$location,$lot,$floor,$price]);
   $notice='Property submitted successfully. It is awaiting staff approval.';
  }
 }
}
$q=$pdo->prepare("SELECT COUNT(*) total, COALESCE(SUM(is_approved=0),0) pending, COALESCE(SUM(is_approved=1),0) approved, COALESCE(SUM(status='Sold'),0) sold FROM properties WHERE user_id=?");
$q->execute([$userId]); $stats=$q->fetch();
$q=$pdo->prepare("SELECT p.*,(SELECT COUNT(*) FROM bids b WHERE b.property_id=p.id) bid_count FROM properties p WHERE p.user_id=? ORDER BY p.created_at DESC");
$q->execute([$userId]); $properties=$q->fetchAll();
$q=$pdo->prepare("SELECT a.*,p.title FROM auctions a JOIN properties p ON p.id=a.property_id WHERE p.user_id=? ORDER BY a.created_at DESC");
$q->execute([$userId]); $auctions=$q->fetchAll();
$q=$pdo->prepare("SELECT v.*,p.title FROM viewing_bookings v JOIN properties p ON p.id=v.property_id WHERE p.user_id=? ORDER BY v.scheduled_date DESC LIMIT 10");
$q->execute([$userId]); $viewings=$q->fetchAll();
$q=$pdo->prepare("SELECT username,role FROM users WHERE id=?"); $q->execute([$userId]); $account=$q->fetch()?:['username'=>'Account','role'=>'Buyer'];
function money($n){return '₱'.number_format((float)$n,2);}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>HomeBid Seller Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
    .dash-main {
        height: 100vh;
        overflow: auto;
        flex: 1;
        padding: 28px clamp(16px, 3vw, 42px)
    }

    .dash-wrap {
        max-width: 1400px;
        margin: auto
    }

    .dash-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px
    }

    .dash-top h1 {
        font-size: 26px
    }

    .muted2 {
        color: #64748b;
        margin-top: 5px
    }

    .dash-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin: 20px 0
    }

    .dash-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px;
        min-width: 0;
        margin-bottom: 18px
    }

    .dash-card small {
        color: #64748b
    }

    .dash-card strong.num {
        display: block;
        font-size: 26px;
        margin-top: 8px
    }

    .dash-section h2 {
        font-size: 18px;
        margin-bottom: 12px
    }

    .dash-table {
        width: 100%;
        border-collapse: collapse
    }

    .dash-table th,
    .dash-table td {
        text-align: left;
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px
    }

    .dash-table th {
        color: #64748b;
        font-size: 11px
    }

    .dash-scroll {
        overflow: auto
    }

    .dash-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 14px
    }

    .dash-form label {
        display: grid;
        gap: 6px;
        font-weight: 600
    }

    .dash-form input,
    .dash-form textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font: inherit
    }

    .dash-form .wide {
        grid-column: 1/-1
    }

    .dash-btn {
        border: 0;
        border-radius: 8px;
        background: #b7791f;
        color: #fff;
        padding: 11px 16px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-block
    }

    .delete-property-btn {
        border: 1px solid #fecaca;
        border-radius: 7px;
        background: #fef2f2;
        color: #b91c1c;
        padding: 7px 11px;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }
    .delete-property-btn:hover { background: #fee2e2; }
    .dash-alert {
        padding: 12px;
        border-radius: 8px;
        margin: 12px 0;
        background: #ecfdf5;
        color: #166534
    }

    .dash-error {
        background: #fef2f2;
        color: #991b1b
    }

    .pill2 {
        padding: 4px 8px;
        border-radius: 20px;
        background: #fef3c7;
        color: #92400e;
        font-size: 11px
    }

    .pill2.ok {
        background: #dcfce7;
        color: #166534
    }

    @media(max-width:850px) {
        .dash-grid {
            grid-template-columns: repeat(2, 1fr)
        }

        .dash-form {
            grid-template-columns: 1fr
        }

        .dash-main {
            height: auto;
            min-height: 100vh
        }

        .dash-table {
            min-width: 650px
        }
    }
    </style>
</head>

<body>
    <aside class="side">
        <div class="logo"><span class="mk"><i></i></span>
            <div><b>HOME<span>BID</span></b><small>SELLER PORTAL</small></div>
        </div>
        <div class="cap">ACCOUNT</div>
        <div class="ws">
            <div class="av"><?=strtoupper(substr(h($account['username']),0,2))?></div>
            <div><b><?=h($account['username'])?></b><small><?=h($account['role'])?></small></div>
        </div>
        <div class="cap">MENU</div>
        <nav class="nav"><a href="#overview">Overview</a><a href="#properties">My Properties</a><a href="#submit">Submit
                Property</a><a href="#auctions">My Auctions</a><a href="#viewings">Viewing Requests</a><a
                href="../index.php">HomeBid Home</a>
            <form method="post" action="../logout.php" style="margin:12px 0"><input type="hidden" name="csrf_token"
                    value="<?=h(csrf_token())?>"><button type="submit" class="dash-btn" style="width:100%">Log
                    out</button></form>
        </nav>
    </aside>
    <main class="dash-main">
        <div class="dash-wrap">
            <header class="dash-top" id="overview">
                <div>
                    <h1>Welcome, <?=h($account['username'])?></h1>
                    <p class="muted2">Manage your property listings and auction activity.</p>
                </div><a class="dash-btn" href="#submit">+ Add Property</a>
            </header>
            <?php if($notice):?><div class="dash-alert"><?=h($notice)?></div><?php endif;?><?php if($error):?><div
                class="dash-alert dash-error"><?=h($error)?></div><?php endif;?>
            <div class="dash-grid">
                <div class="dash-card"><small>Total properties</small><strong
                        class="num"><?=(int)$stats['total']?></strong></div>
                <div class="dash-card"><small>Awaiting approval</small><strong
                        class="num"><?=(int)$stats['pending']?></strong></div>
                <div class="dash-card"><small>Approved listings</small><strong
                        class="num"><?=(int)$stats['approved']?></strong></div>
                <div class="dash-card"><small>Sold properties</small><strong
                        class="num"><?=(int)$stats['sold']?></strong></div>
            </div>
            <section class="dash-section" id="submit">
                <div class="dash-card">
                    <h2>Submit a property</h2>
                    <p class="muted2">New listings are connected to your signed-in account and require staff approval.
                    </p>
                    <form method="post" class="dash-form"><input type="hidden" name="csrf_token"
                            value="<?=h(csrf_token())?>"><label>Property title<input name="title" maxlength="150"
                                required></label><label>Location<input name="location" maxlength="255"
                                placeholder="City, Province" required></label><label>Lot area (m²)<input name="lot_area"
                                type="number" min="0.01" step="0.01" required></label><label>Floor area (m²)<input
                                name="floor_area" type="number" min="0.01" step="0.01" required></label><label>Starting
                            price (₱)<input name="price" type="number" min="0.01" step="0.01" required></label><label
                            class="wide">Description<textarea name="description" rows="4" required></textarea></label>
                        <div class="wide"><button class="dash-btn" type="submit">Submit for approval</button></div>
                    </form>
                </div>
            </section>
            <section class="dash-section" id="properties">
                <h2>My properties</h2>
                <div class="dash-card dash-scroll">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>PROPERTY</th>
                                <th>LOCATION</th>
                                <th>PRICE</th>
                                <th>LISTING STATUS</th>
                                <th>APPROVAL</th>
                                <th>BIDS</th>
                                <th>ADDED</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody><?php if(!$properties):?><tr>
                                <td colspan="8">No listings yet. Submit your first property above.</td>
                            </tr><?php endif;?><?php foreach($properties as $p):?><tr>
                                <td><b><?=h($p['title'])?></b></td>
                                <td><?=h($p['location'])?></td>
                                <td><?=money($p['price'])?></td>
                                <td><?=h($p['status'])?></td>
                                <td><span
                                        class="pill2 <?=$p['is_approved']?'ok':''?>"><?=$p['is_approved']?'Approved':'Pending'?></span>
                                </td>
                                <td><?=(int)$p['bid_count']?></td>
                                <td><?=h(date('M j, Y',strtotime($p['created_at'])))?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Delete this property permanently? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
                                        <input type="hidden" name="action" value="delete_property">
                                        <input type="hidden" name="property_id" value="<?=(int)$p['id']?>">
                                        <button type="submit" class="delete-property-btn">Delete</button>
                                    </form>
                                </td>
                            </tr><?php endforeach;?></tbody>
                    </table>
                </div>
            </section>
            <section class="dash-section" id="auctions">
                <h2>My auctions</h2>
                <div class="dash-card dash-scroll">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>PROPERTY</th>
                                <th>STARTING BID</th>
                                <th>CURRENT BID</th>
                                <th>STARTS</th>
                                <th>ENDS</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody><?php if(!$auctions):?><tr>
                                <td colspan="6">No auctions are linked to your listings yet.</td>
                            </tr><?php endif;?><?php foreach($auctions as $a):?><tr>
                                <td><?=h($a['title'])?></td>
                                <td><?=money($a['starting_bid'])?></td>
                                <td><?=money($a['current_bid'])?></td>
                                <td><?=h($a['start_time'])?></td>
                                <td><?=h($a['end_time'])?></td>
                                <td><?=h(ucfirst($a['status']))?></td>
                            </tr><?php endforeach;?></tbody>
                    </table>
                </div>
            </section>
            <section class="dash-section" id="viewings">
                <h2>Viewing requests</h2>
                <div class="dash-card dash-scroll">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>PROPERTY</th>
                                <th>SCHEDULE</th>
                                <th>STATUS</th>
                                <th>REQUESTED</th>
                            </tr>
                        </thead>
                        <tbody><?php if(!$viewings):?><tr>
                                <td colspan="4">No viewing requests for your properties.</td>
                            </tr><?php endif;?><?php foreach($viewings as $v):?><tr>
                                <td><?=h($v['title'])?></td>
                                <td><?=h($v['scheduled_date'])?></td>
                                <td><?=h($v['status'])?></td>
                                <td><?=h($v['created_at'])?></td>
                            </tr><?php endforeach;?></tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>

</html>