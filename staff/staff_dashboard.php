<?php
declare(strict_types=1);
require_once __DIR__.'/../config/auth.php';
$staffId=require_roles(['Staff','Administrator']);
require_once __DIR__.'/../config/db.php';
$message='';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $id=filter_input(INPUT_POST,'property_id',FILTER_VALIDATE_INT);
 $decision=$_POST['decision']??'';
 $auctionId=filter_input(INPUT_POST,'auction_id',FILTER_VALIDATE_INT);
 if($auctionId && ($_POST['action']??'')==='review_auction'){
  $auctionDecision=$_POST['auction_decision']??'';$feedback=trim((string)($_POST['auction_feedback']??''));
  if(!in_array($auctionDecision,['approve','admin_approval','needs_revision'],true)) $error='Choose a valid auction review decision.';
  elseif($auctionDecision==='needs_revision' && $feedback==='') $error='Please explain what needs to be revised in the auction.';
  else {$auctionStatus=$auctionDecision==='approve'?'approved':$auctionDecision;
   $auctionRow=hb_fetch_one(hb_query($conn,'SELECT property_id FROM auctions WHERE id=?',[$auctionId]));
   hb_query($conn,"UPDATE auctions SET review_status=?,review_feedback=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND review_status<>'approved'",[$auctionStatus,$auctionDecision==='needs_revision'?$feedback:null,$staffId,$auctionId]);
   $auctionChanged=mysqli_affected_rows($conn)>0;
   if($auctionDecision==='approve' && $auctionRow){$propertyId=(int)$auctionRow['property_id'];hb_query($conn,"UPDATE properties SET is_approved=1,review_status='approved',review_feedback=NULL,reviewed_by=?,reviewed_at=NOW(),status='Auction' WHERE id=?",[$staffId,$propertyId]);}
   if($auctionChanged){hb_query($conn,'INSERT INTO activity_logs (user_id,action,entity_type,record_id,ip_address) VALUES (?,?,?,?,?)',[$staffId,$auctionDecision==='approve'?'Approved auction submission':($auctionDecision==='admin_approval'?'Forwarded auction for administrator approval':'Requested auction revision'),'auction',$auctionId,$_SERVER['REMOTE_ADDR']??null]);$message=$auctionDecision==='approve'?'Auction and its property approved successfully.':($auctionDecision==='admin_approval'?'Auction forwarded to the administrator.':'Auction revision request sent to the seller.');}else $error='This auction was already approved or could not be found.';
  }
 }
 if($id && in_array($decision,['approve','admin_approval','needs_revision'],true)){
  $feedback=trim((string)($_POST['feedback']??''));
  if($decision==='needs_revision' && $feedback==='') $error='Please explain what the seller needs to revise.';
  else {$status=$decision==='approve'?'approved':$decision;$approved=$decision==='approve'?1:0;
   hb_query($conn,'UPDATE properties SET is_approved=?,review_status=?,review_feedback=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND is_approved=0',[$approved,$status,$decision==='needs_revision'?$feedback:null,$staffId,$id]);
   $propertyChanged=mysqli_affected_rows($conn)>0;
   if($propertyChanged){if($decision==='approve'){hb_query($conn,"UPDATE properties SET status=IF(EXISTS(SELECT 1 FROM auctions WHERE property_id=?),'Auction','Available') WHERE id=?",[$id,$id]);hb_query($conn,"UPDATE auctions SET review_status='approved',review_feedback=NULL,reviewed_by=?,reviewed_at=NOW() WHERE property_id=?",[$staffId,$id]);}$action=$decision==='approve'?'Approved property submission':($decision==='admin_approval'?'Forwarded property for administrator approval':'Requested property revision');hb_query($conn,'INSERT INTO activity_logs (user_id,action,entity_type,record_id,ip_address) VALUES (?,?,?,?,?)',[$staffId,$action,'property',$id,$_SERVER['REMOTE_ADDR']??null]);$message=$decision==='approve'?'Property approved successfully.':($decision==='admin_approval'?'Submission forwarded to the administrator.':'Revision request sent to the seller.');}else $error='This submission was already reviewed or could not be found.';
  }
 }
}
$counts=hb_fetch_one(hb_query($conn, "SELECT COUNT(*) total,COALESCE(SUM(is_approved=0),0) pending,COALESCE(SUM(is_approved=1),0) approved,COALESCE(SUM(status='Sold'),0) sold FROM properties"));
$auctionCounts=hb_fetch_one(hb_query($conn, "SELECT COUNT(*) total,COALESCE(SUM(status='active'),0) active FROM auctions"));
$bidCount=(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM bids'));
$pending=hb_fetch_all(hb_query($conn, "SELECT p.*,u.username,u.email FROM properties p LEFT JOIN users u ON u.id=p.user_id WHERE p.is_approved=0 AND p.review_status IN ('pending','admin_approval') ORDER BY p.created_at ASC"));
$recent=hb_fetch_all(hb_query($conn, 'SELECT p.*,u.username FROM properties p LEFT JOIN users u ON u.id=p.user_id ORDER BY p.created_at DESC LIMIT 12'));
$auctions=hb_fetch_all(hb_query($conn, 'SELECT a.*,p.title,p.is_approved FROM auctions a JOIN properties p ON p.id=a.property_id ORDER BY a.created_at DESC LIMIT 30'));
$pendingAuctions=hb_fetch_all(hb_query($conn, "SELECT a.*,p.title,p.location,p.is_approved,u.username FROM auctions a JOIN properties p ON p.id=a.property_id LEFT JOIN users u ON u.id=p.user_id WHERE a.review_status IN ('pending','admin_approval','needs_revision') ORDER BY a.created_at ASC"));
$logs=hb_fetch_all(hb_query($conn, 'SELECT l.*,u.username FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 12'));
function sh($v){return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard | HomeBid Staff</title>
    <link rel="stylesheet" href="style_admin.css">
    <style>
    .staff-main {
        flex: 1;
        min-width: 0;
        height: 100vh;
        overflow: auto
    }

    .staff-side {
        min-height: 100vh;
        overflow: auto
    }

    .staff-content {
        max-width: 1500px;
        margin: auto;
        padding: 30px
    }

    .staff-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 22px
    }

    .staff-head h1 {
        font-size: 26px;
        margin: 4px 0
    }

    .staff-sub {
        color: #64748b;
        margin-top: 6px
    }

    .staff-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
        gap: 14px;
        margin-bottom: 20px
    }

    .staff-stat,
    .staff-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px
    }

    .staff-stat {
        padding: 18px
    }

    .staff-stat small {
        color: #64748b
    }

    .staff-stat strong {
        display: block;
        font-size: 25px;
        margin-top: 8px;
        color: #0f172a
    }

    .staff-card {
        padding: 20px;
        margin-bottom: 18px
    }

    .staff-card-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 14px
    }

    .staff-card h2 {
        font-size: 18px;
        margin: 0
    }

    .staff-scroll {
        overflow: auto
    }

    .staff-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 850px
    }

    .staff-table th,
    .staff-table td {
        text-align: left;
        padding: 13px 12px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px;
        vertical-align: top
    }

    .staff-table th {
        font-size: 11px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap
    }

    .staff-table tbody tr:hover {
        background: #f8fafc
    }

    .staff-muted {
        color: #64748b
    }

    .staff-pill {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 700;
        background: #f1f5f9;
        color: #475569
    }

    .staff-pill.pending {
        background: #fff7ed;
        color: #9a3412
    }

    .staff-pill.approved {
        background: #ecfdf5;
        color: #166534
    }

    .staff-pill.revision {
        background: #fef2f2;
        color: #991b1b
    }

    .staff-pill.admin {
        background: #eff6ff;
        color: #1d4ed8
    }

    .staff-review {
        display: grid;
        gap: 8px;
        min-width: 205px
    }

    .staff-review select,
    .staff-review textarea {
        width: 100%;
        padding: 9px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        font: inherit;
        font-size: 12px;
        box-sizing: border-box
    }

    .staff-review textarea {
        resize: vertical;
        min-height: 58px
    }

    .staff-btn {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        padding: 9px 12px;
        border: 0;
        border-radius: 8px;
        background: #0b1736;
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        text-decoration: none;
        cursor: pointer
    }

    .staff-btn:hover {
        filter: brightness(1.12)
    }

    .staff-btn.light {
        background: #e2e8f0;
        color: #0f172a
    }

    .staff-alert {
        padding: 12px 14px;
        border-radius: 9px;
        margin-bottom: 16px;
        background: #ecfdf5;
        color: #166534
    }

    .staff-alert.error {
        background: #fef2f2;
        color: #991b1b
    }

    .staff-empty {
        text-align: center;
        padding: 28px;
        color: #64748b
    }

    .staff-nav-link {
        display: block
    }

    @media(max-width:760px) {
        .staff-content {
            padding: 18px
        }

        .staff-head {
            flex-direction: column
        }

        .staff-side {
            display: none
        }

        .staff-main {
            height: auto;
            min-height: 100vh
        }
    }
    
    /* Minimal responsive staff workspace */
    .staff-side{background:#172b3b!important;border-right:0!important}
    .staff-head h1{font-family:'DM Sans',sans-serif;letter-spacing:-.04em}
    .staff-card-head{border-bottom-color:#edf0ed}
    .staff-scroll{max-width:100%;overflow-x:auto}
    @media(max-width:900px){
      body{display:block!important;height:auto!important;min-height:100vh;overflow:auto!important}
      .staff-side{width:100%!important;min-height:0!important;height:auto!important;position:relative;display:block!important}
      .staff-side .sidebar-top{padding:15px 18px}
      .staff-side .brand-container{margin-bottom:12px}
      .staff-side .nav-list{display:flex;flex-wrap:wrap;gap:7px;padding:0;margin:10px 0}
      .staff-side .nav-item{margin:0}
      .staff-side .nav-link{padding:9px 12px;border-radius:8px}
      .staff-main{height:auto!important;min-height:0!important;overflow:visible!important}
      .staff-content{padding:22px 16px 35px!important}
    }
    @media(max-width:560px){
      .staff-side .sidebar-top{padding:12px}
      .staff-side .nav-list{gap:5px}
      .staff-side .nav-link{font-size:12px;padding:8px 10px}
      .staff-content{padding:17px 13px 28px!important}
      .staff-head{flex-direction:column;align-items:flex-start}
      .staff-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
      .staff-card{overflow:hidden}
    }
</style>
</head>

<body>
    <aside class="staff-side">
        <div class="sidebar-top">
            <div class="brand-container">
                <div class="brand-logo">HB</div>
                <div>
                    <div class="brand-name">HomeBid</div>
                    <div class="brand-sub">STAFF WORKSPACE</div>
                </div>
            </div>
            <div class="workspace-box">
                <div>
                    <div class="workspace-title"><?=sh($_SESSION['username']??'Staff account')?></div>
                    <div class="workspace-desc"><?=sh($_SESSION['role']??'Staff')?></div>
                </div>
            </div>
            <div class="nav-section-title">Workspace</div>
            <ul class="nav-list">
                <li class="nav-item"><a class="nav-link active" href="staff_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="approvals.php">Approvals</a></li>
                <li class="nav-item"><a class="nav-link" href="properties.php">Properties</a></li>
                <li class="nav-item"><a class="nav-link" href="bidding.php">Bidding</a></li>
                <li class="nav-item"><a class="nav-link" href="buyers.php">Buyers</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="../receipt/management.php">Paid receipts</a></li>
                <li class="nav-item"><a class="nav-link" href="notification.php">Notifications</a></li>
                <li class="nav-item"><a class="nav-link" href="roles_sec.php">Roles &amp; Security</a></li>
            </ul>
        </div>
        <div class="sidebar-bottom"><a class="nav-link" href="../index.php">View HomeBid</a>
            <form method="post" action="../logout.php"><input type="hidden" name="csrf_token"
                    value="<?=sh(csrf_token())?>"><button class="module-btn" style="width:100%;margin-top:10px"
                    type="submit">Log out</button></form>
        </div>
    </aside>
    <main class="staff-main">
        <header class="top-header">
            <div class="breadcrumb">HomeBid &gt; <span>Dashboard</span></div>
            <div class="header-right"><span><?=sh($_SESSION['username']??'Staff')?></span></div>
        </header>
        <div class="staff-content">
            <div class="staff-head">
                <div>
                    <div class="pre-heading">STAFF WORKSPACE</div>
                    <h1>Dashboard</h1>
                    <p class="staff-sub">Review seller submissions and monitor HomeBid activity.</p>
                </div>
            </div>
            <?php if($message):?><div class="staff-alert"><?=sh($message)?></div><?php endif;?>
            <?php if($error):?><div class="staff-alert error"><?=sh($error)?></div><?php endif;?>
            <div class="staff-stats">
                <div class="staff-stat"><small>Total properties</small><strong><?=(int)$counts['total']?></strong></div>
                <div class="staff-stat"><small>Awaiting review</small><strong><?=(int)$counts['pending']?></strong>
                </div>
                <div class="staff-stat"><small>Approved listings</small><strong><?=(int)$counts['approved']?></strong>
                </div>
                <div class="staff-stat"><small>Active
                        auctions</small><strong><?=(int)$auctionCounts['active']?></strong></div>
                <div class="staff-stat"><small>Total bids placed</small><strong><?=$bidCount?></strong></div>
                <div class="staff-stat"><small>Sold properties</small><strong><?=(int)$counts['sold']?></strong></div>
            </div>
            <section class="staff-card" id="approvals">
                <div class="staff-card-head">
                    <div>
                        <h2>Property approvals</h2>
                        <p class="staff-sub">Review new property listings submitted by sellers.</p>
                    </div><span class="staff-pill pending"><?=(int)$counts['pending']?> awaiting review</span>
                </div>
                <div class="staff-scroll">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Seller</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Submitted</th>
                                <th>Review decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!$pending):?><tr>
                                <td class="staff-empty" colspan="6">There are no pending property submissions.</td>
                            </tr><?php endif;?>
                            <?php foreach($pending as $p):?><tr>
                                <td><strong><?=sh($p['title'])?></strong><br><span class="staff-muted">Property
                                        #<?=(int)$p['id']?></span></td>
                                <td><?=sh($p['username']?:'Unknown')?><br><span
                                        class="staff-muted"><?=sh($p['email'])?></span></td>
                                <td><?=sh($p['location'])?></td>
                                <td>₱<?=number_format((float)$p['price'],2)?></td>
                                <td><?=sh($p['created_at'])?></td>
                                <td>
                                    <form method="post" class="staff-review"><input type="hidden" name="csrf_token"
                                            value="<?=sh(csrf_token())?>"><input type="hidden" name="property_id"
                                            value="<?=(int)$p['id']?>"><select name="decision" required>
                                            <option value="">Choose a decision</option>
                                            <option value="approve">Accept / Approve</option>
                                            <option value="admin_approval">Forward to Admin</option>
                                            <option value="needs_revision">Request revision</option>
                                        </select><textarea name="feedback"
                                            placeholder="Explain what needs to change (required for revision)"></textarea><button
                                            class="staff-btn" type="submit">Submit decision</button></form>
                                </td>
                            </tr><?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="staff-card" id="auction-approvals">
                <div class="staff-card-head">
                    <div>
                        <h2>Auction approvals</h2>
                        <p class="staff-sub">Review auction schedules and linked property submissions. Approving either
                            one approves both.</p>
                    </div><span class="staff-pill pending"><?=(int)count($pendingAuctions)?> awaiting review</span>
                </div>
                <div class="staff-scroll">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Property / Auction</th>
                                <th>Seller</th>
                                <th>Property status</th>
                                <th>Auction review</th>
                                <th>Schedule</th>
                                <th>Review decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!$pendingAuctions):?><tr>
                                <td class="staff-empty" colspan="6">There are no auctions awaiting review.</td>
                            </tr><?php endif;?>
                            <?php foreach($pendingAuctions as $a):?><tr>
                                <td><strong><?=sh($a['title'])?></strong><br><span class="staff-muted">Auction
                                        #<?=(int)$a['id']?></span></td>
                                <td><?=sh($a['username']?:'Unknown')?></td>
                                <td><span
                                        class="staff-pill <?=$a['is_approved']?'approved':'pending'?>"><?=$a['is_approved']?'Approved':'Awaiting approval'?></span>
                                </td>
                                <td><span
                                        class="staff-pill <?=($a['review_status']==='needs_revision'?'revision':($a['review_status']==='admin_approval'?'admin':'pending'))?>"><?=sh(ucwords(str_replace('_',' ',$a['review_status'])))?></span><?php if(!empty($a['review_feedback'])):?><br><span
                                        class="staff-muted"><?=sh($a['review_feedback'])?></span><?php endif;?></td>
                                <td><?=sh($a['start_time'])?><br><span class="staff-muted">to
                                        <?=sh($a['end_time'])?></span></td>
                                <td>
                                    <form method="post" class="staff-review"><input type="hidden" name="csrf_token"
                                            value="<?=sh(csrf_token())?>"><input type="hidden" name="action"
                                            value="review_auction"><input type="hidden" name="auction_id"
                                            value="<?=(int)$a['id']?>"><select name="auction_decision" required>
                                            <option value="">Choose a decision</option>
                                            <option value="approve">Accept / Approve auction</option>
                                            <option value="admin_approval">Forward to Admin</option>
                                            <option value="needs_revision">Request revision</option>
                                        </select><textarea name="auction_feedback"
                                            placeholder="Explain what needs to change (required for revision)"></textarea><button
                                            class="staff-btn" type="submit">Submit decision</button></form>
                                </td>
                            </tr><?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="staff-card" id="listings">
                <div class="staff-card-head">
                    <div>
                        <h2>Recent properties</h2>
                        <p class="staff-sub">Latest property submissions across HomeBid.</p>
                    </div>
                </div>
                <div class="staff-scroll">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Property</th>
                                <th>Seller</th>
                                <th>Price</th>
                                <th>Listing status</th>
                                <th>Approval</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!$recent):?><tr>
                                <td class="staff-empty" colspan="6">No property listings found.</td>
                            </tr><?php endif;?>
                            <?php foreach($recent as $p):?><tr>
                                <td>#<?=(int)$p['id']?></td>
                                <td><strong><?=sh($p['title'])?></strong></td>
                                <td><?=sh($p['username']?:'Unknown')?></td>
                                <td>₱<?=number_format((float)$p['price'],2)?></td>
                                <td><?=sh($p['status'])?></td>
                                <td><span
                                        class="staff-pill <?=($p['review_status']==='approved'?'approved':($p['review_status']==='needs_revision'?'revision':($p['review_status']==='admin_approval'?'admin':'pending')))?>"><?=sh(ucwords(str_replace('_',' ',$p['review_status']??($p['is_approved']?'approved':'pending'))))?></span><?php if(!empty($p['review_feedback'])):?><br><span
                                        class="staff-muted"><?=sh($p['review_feedback'])?></span><?php endif;?></td>
                            </tr><?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="staff-card" id="auctions">
                <div class="staff-card-head">
                    <div>
                        <h2>Auction monitoring</h2>
                        <p class="staff-sub">Track current bidding activity and auction schedules.</p>
                    </div>
                </div>
                <div class="staff-scroll">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Starting bid</th>
                                <th>Current bid</th>
                                <th>Starts</th>
                                <th>Ends</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!$auctions):?><tr>
                                <td class="staff-empty" colspan="6">No auctions have been created yet.</td>
                            </tr><?php endif;?>
                            <?php foreach($auctions as $a):?><tr>
                                <td><?=sh($a['title'])?></td>
                                <td>₱<?=number_format((float)$a['starting_bid'],2)?></td>
                                <td>₱<?=number_format((float)$a['current_bid'],2)?></td>
                                <td><?=sh($a['start_time'])?></td>
                                <td><?=sh($a['end_time'])?></td>
                                <td><span class="staff-pill"><?=sh(ucfirst((string)$a['status']))?></span></td>
                            </tr><?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="staff-card" id="activity">
                <div class="staff-card-head">
                    <div>
                        <h2>Recent activity</h2>
                        <p class="staff-sub">A record of recent staff review actions.</p>
                    </div>
                </div>
                <div class="staff-scroll">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Staff member</th>
                                <th>Action</th>
                                <th>Record</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!$logs):?><tr>
                                <td class="staff-empty" colspan="4">No activity has been recorded yet.</td>
                            </tr><?php endif;?>
                            <?php foreach($logs as $log):?><tr>
                                <td><?=sh($log['username']?:'System')?></td>
                                <td><?=sh($log['action'])?></td>
                                <td><?=sh($log['entity_type'])?> #<?=(int)$log['record_id']?></td>
                                <td><?=sh($log['created_at'])?></td>
                            </tr><?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>

</html>