<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
$staffId = require_roles(['Staff','Administrator']);
require_once __DIR__ . '/../config/db.php';
$isAdmin = (($_SESSION['role'] ?? '') === 'Administrator');
$module = $staffModule ?? 'properties';
$pages = [
 'approvals'=>['Approvals','Review and approve property submissions.'],
 'properties'=>['Properties','Monitor all HomeBid property listings.'],
 'bidding'=>['Bidding','Monitor auctions and bidding activity.'],
 'staff'=>['Staff','Manage staff accounts.'],
 'buyers'=>['Buyers','View registered buyer accounts.'],
 'reports'=>['Reports','Review platform activity and performance.'],
 'notification'=>['Notifications','Review inquiries, reservations, and viewing requests.'],
 'roles_sec'=>['Roles & Security','Manage account roles and access.']
];
if (!isset($pages[$module])) { http_response_code(404); exit('Page not found.'); }
[$pageTitle,$pageDescription] = $pages[$module];
$notice = ''; $error = '';
function sm_h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function sm_money($v): string { return '₱' . number_format((float)$v, 2); }
function sm_log(mysqli $conn, int $userId, string $action, string $type, ?int $recordId): void {
 $q = hb_query($conn, 'INSERT INTO activity_logs (user_id,action,entity_type,record_id,ip_address) VALUES (?,?,?,?,?)', [$userId,$action,$type,$recordId,$_SERVER['REMOTE_ADDR']??null]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 verify_csrf();
 $action = (string)($_POST['action'] ?? '');
 $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
 try {
  if ($module === 'approvals' && in_array($action,['approve'],true) && $id) {
   $q = hb_query($conn, 'UPDATE properties SET is_approved=1 WHERE id=? AND is_approved=0', [$id]);
   if (mysqli_affected_rows($conn)) { sm_log($conn,$staffId,'Approved property listing','property',$id); $notice='Property approved.'; }
   else $error='The property was already reviewed or could not be found.';
  } elseif ($module === 'properties' && $action === 'feature' && $id) {
   $q = hb_query($conn, 'UPDATE properties SET is_featured=1-is_featured WHERE id=?', [$id]);
   sm_log($conn,$staffId,'Changed property featured status','property',$id); $notice='Featured status updated.';
  } elseif ($module === 'properties' && $action === 'status' && $id) {
   $status=(string)($_POST['status']??'');
   if (!in_array($status,['Available','Reserved','Sold','Auction'],true)) throw new RuntimeException('Invalid property status.');
   $q = hb_query($conn, 'UPDATE properties SET status=? WHERE id=?', [$status,$id]);
   sm_log($conn,$staffId,'Updated property status to '.$status,'property',$id); $notice='Property status updated.';
  } elseif ($module === 'bidding' && $action === 'auction_status' && $id) {
   $status=(string)($_POST['status']??'');
   if (!in_array($status,['upcoming','active','ended','cancelled'],true)) throw new RuntimeException('Invalid auction status.');
   $q = hb_query($conn, 'UPDATE auctions SET status=? WHERE id=?', [$status,$id]);
   sm_log($conn,$staffId,'Updated auction status to '.$status,'auction',$id); $notice='Auction status updated.';
  } elseif ($module === 'notification' && $action === 'inquiry_status' && $id) {
   $status=(string)($_POST['status']??'');
   if (!in_array($status,['pending','responded','closed'],true)) throw new RuntimeException('Invalid inquiry status.');
   $q = hb_query($conn, 'UPDATE inquiries SET status=? WHERE id=?', [$status,$id]);
   sm_log($conn,$staffId,'Updated inquiry status','inquiry',$id); $notice='Inquiry updated.';
  } elseif ($module === 'staff' && $action === 'add_staff' && $isAdmin) {
   $username=trim((string)($_POST['username']??'')); $email=trim((string)($_POST['email']??'')); $password=(string)($_POST['password']??'');
   if ($username==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<8) throw new RuntimeException('Enter a username, valid email, and password with at least 8 characters.');
   $q = hb_query($conn, "INSERT INTO users (username,email,password,role,is_verified) VALUES (?,?,?,'Staff',1)", [$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
   $newId=(int)mysqli_insert_id($conn); sm_log($conn,$staffId,'Created staff account','user',$newId); $notice='Staff account created.';
  } elseif ($module === 'roles_sec' && $action === 'change_role' && $id && $isAdmin) {
   $role=(string)($_POST['role']??'');
   if (!in_array($role,['Administrator','Staff','Buyer'],true)) throw new RuntimeException('Invalid role.');
   if ($id === $staffId) throw new RuntimeException('You cannot change your own role.');
   $q = hb_query($conn, 'UPDATE users SET role=? WHERE id=?', [$role,$id]);
   sm_log($conn,$staffId,'Changed user role to '.$role,'user',$id); $notice='Account role updated.';
  } else {
   $error = 'That action is not available for your account.';
  }
 } catch (mysqli_sql_exception $e) {
  error_log('HomeBid staff module action failed: '.'Database operation failed');
  $error = 'The change could not be saved. Check that the account details are not already in use.';
 } catch (RuntimeException $e) { $error='Database operation failed'; }
}
$rows=[]; $columns=[]; $stats=[];
try {
 switch ($module) {
  case 'approvals':
   $stats=[['Pending approvals',(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM properties WHERE is_approved=0'))],['Approved listings',(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM properties WHERE is_approved=1'))],['Properties total',(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM properties'))]];
   $rows=hb_fetch_all(hb_query($conn, "SELECT p.id,p.title,p.location,p.price,p.status,p.is_approved,p.created_at,u.username,u.email FROM properties p LEFT JOIN users u ON u.id=p.user_id WHERE p.is_approved=0 ORDER BY p.created_at ASC"));
   $columns=['id'=>'ID','title'=>'Property','username'=>'Submitted by','email'=>'Email','location'=>'Location','price'=>'Price','created_at'=>'Submitted'];
   break;
  case 'properties':
   $stats=[['Total properties',(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM properties'))],['Pending review',(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM properties WHERE is_approved=0'))],['Featured',(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM properties WHERE is_featured=1'))]];
   $rows=hb_fetch_all(hb_query($conn, 'SELECT p.id,p.title,p.location,p.price,p.status,p.is_approved,p.is_featured,u.username FROM properties p LEFT JOIN users u ON u.id=p.user_id ORDER BY p.created_at DESC'));
   $columns=['id'=>'ID','title'=>'Property','username'=>'Owner','location'=>'Location','price'=>'Price','status'=>'Status','is_approved'=>'Approval','is_featured'=>'Featured'];
   break;
  case 'bidding':
   $stats=[['Auctions',(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM auctions'))],['Active',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM auctions WHERE status='active'"))],['Total bids',(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM bids'))]];
   $rows=hb_fetch_all(hb_query($conn, 'SELECT a.id,p.title,a.starting_bid,a.current_bid,a.start_time,a.end_time,a.status,COALESCE(u.username,"—") highest_bidder,(SELECT COUNT(*) FROM bids b WHERE b.property_id=a.property_id) bid_count FROM auctions a JOIN properties p ON p.id=a.property_id LEFT JOIN users u ON u.id=a.highest_bidder_id ORDER BY a.created_at DESC'));
   $columns=['id'=>'Auction ID','title'=>'Property','starting_bid'=>'Starting bid','current_bid'=>'Current bid','highest_bidder'=>'Highest bidder','bid_count'=>'Bids','start_time'=>'Starts','end_time'=>'Ends','status'=>'Status'];
   break;
  case 'staff':
   if (!$isAdmin) { http_response_code(403); exit('Only administrators can manage staff accounts.'); }
   $stats=[['Staff accounts',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM users WHERE role='Staff'"))],['Administrators',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM users WHERE role='Administrator'"))],['Verified staff',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM users WHERE role='Staff' AND is_verified=1"))]];
   $rows=hb_fetch_all(hb_query($conn, "SELECT id,username,email,is_verified,created_at FROM users WHERE role='Staff' ORDER BY created_at DESC"));
   $columns=['id'=>'ID','username'=>'Staff member','email'=>'Email','is_verified'=>'Verified','created_at'=>'Created'];
   break;
  case 'buyers':
   $stats=[['Buyers',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM users WHERE role='Buyer'"))],['Verified buyers',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM users WHERE role='Buyer' AND is_verified=1"))],['Buyer bids',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM bids b JOIN users u ON u.id=b.user_id WHERE u.role='Buyer'"))]];
   $rows=hb_fetch_all(hb_query($conn, "SELECT u.id,u.username,u.email,u.is_verified,u.created_at,(SELECT COUNT(*) FROM bids b WHERE b.user_id=u.id) bid_count FROM users u WHERE u.role='Buyer' ORDER BY u.created_at DESC"));
   $columns=['id'=>'ID','username'=>'Buyer','email'=>'Email','is_verified'=>'Verified','bid_count'=>'Bids placed','created_at'=>'Joined'];
   break;
  case 'reports':
   $stats=[['Properties',(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM properties'))],['Auctions',(int)hb_fetch_value(hb_query($conn, 'SELECT COUNT(*) FROM auctions'))],['Payments received',(float)hb_fetch_value(hb_query($conn, "SELECT COALESCE(SUM(amount),0) FROM checkout_payments WHERE status='paid'"))]];
   $rows=hb_fetch_all(hb_query($conn, "SELECT DATE(created_at) report_date,COUNT(*) total_bids,COUNT(DISTINCT user_id) bidders,MAX(bid_amount) highest_bid FROM bids GROUP BY DATE(created_at) ORDER BY report_date DESC LIMIT 30"));
   $columns=['report_date'=>'Date','total_bids'=>'Bids','bidders'=>'Unique bidders','highest_bid'=>'Highest bid'];
   if (isset($_GET['export']) && $_GET['export']==='csv') {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="homebid-bid-report.csv"');
    $out=fopen('php://output','w'); fputcsv($out,array_values($columns)); foreach($rows as $row) fputcsv($out,array_values($row)); fclose($out); exit;
   }
   break;
  case 'notification':
   $stats=[['Open inquiries',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM inquiries WHERE status='pending'"))],['Pending reservations',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM reservations WHERE status='Pending'"))],['Viewing requests',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM viewing_bookings WHERE status='Pending'"))]];
   $rows=hb_fetch_all(hb_query($conn, "SELECT i.id,i.subject,i.message,i.status,i.created_at,u.username,u.email,p.title property_title FROM inquiries i JOIN users u ON u.id=i.user_id LEFT JOIN properties p ON p.id=i.property_id ORDER BY i.created_at DESC LIMIT 100"));
   $columns=['id'=>'ID','subject'=>'Inquiry','username'=>'From','email'=>'Email','property_title'=>'Property','message'=>'Message','status'=>'Status','created_at'=>'Received'];
   break;
  case 'roles_sec':
   if (!$isAdmin) { http_response_code(403); exit('Only administrators can change account roles.'); }
   $stats=[['Administrators',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM users WHERE role='Administrator'"))],['Staff',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM users WHERE role='Staff'"))],['Buyers',(int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM users WHERE role='Buyer'"))]];
   $rows=hb_fetch_all(hb_query($conn, 'SELECT id,username,email,role,is_verified,created_at FROM users ORDER BY created_at DESC'));
   $columns=['id'=>'ID','username'=>'Account','email'=>'Email','role'=>'Role','is_verified'=>'Verified','created_at'=>'Created'];
   break;
 }
} catch (mysqli_sql_exception $e) {
 error_log('HomeBid staff module query failed: '.'Database operation failed');
 $error = $error ?: 'Some dashboard information could not be loaded. Verify that your database tables are up to date.';
}
$nav=[['dashboard.php','Dashboard','dashboard'],['approvals.php','Approvals','approvals'],['properties.php','Properties','properties'],['bidding.php','Bidding','bidding'],['staff.php','Staff','staff'],['buyers.php','Buyers','buyers'],['reports.php','Reports','reports'],['notification.php','Notifications','notification'],['roles_sec.php','Roles & Security','roles_sec']];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=sm_h($pageTitle)?> | HomeBid Staff</title>
    <link rel="stylesheet" href="style_admin.css">
    <style>
    .module-main {
        flex: 1;
        min-width: 0;
        height: 100vh;
        overflow: auto
    }

    .module-side {
        min-height: 100vh;
        overflow: auto
    }

    .module-content {
        max-width: 1450px;
        margin: auto;
        padding: 30px
    }

    .module-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 22px
    }

    .module-head h1 {
        font-size: 26px
    }

    .module-sub {
        color: #64748b;
        margin-top: 6px
    }

    .module-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
        margin-bottom: 20px
    }

    .module-stat {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px
    }

    .module-stat small {
        color: #64748b
    }

    .module-stat strong {
        display: block;
        font-size: 25px;
        margin-top: 8px
    }

    .module-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 18px
    }

    .module-scroll {
        overflow: auto
    }

    .module-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 760px
    }

    .module-table th,
    .module-table td {
        text-align: left;
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px;
        vertical-align: top
    }

    .module-table th {
        font-size: 11px;
        color: #64748b;
        text-transform: uppercase
    }

    .module-alert {
        padding: 12px 14px;
        border-radius: 8px;
        margin-bottom: 16px;
        background: #ecfdf5;
        color: #166534
    }

    .module-alert.error {
        background: #fef2f2;
        color: #991b1b
    }

    .module-form {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap
    }

    .module-form input,
    .module-form select {
        padding: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        max-width: 220px
    }

    .module-btn {
        padding: 8px 11px;
        border: 0;
        border-radius: 7px;
        background: #0b1736;
        color: white;
        font-weight: 700;
        cursor: pointer
    }

    .module-btn.alt {
        background: #d97706
    }

    .module-btn.light {
        background: #e2e8f0;
        color: #0f172a
    }

    .module-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap
    }

    .module-add {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px
    }

    .module-add input {
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        min-width: 0
    }

    .module-empty {
        padding: 22px;
        text-align: center;
        color: #64748b
    }

    @media(max-width:760px) {
        .module-content {
            padding: 18px
        }

        .module-head {
            flex-direction: column
        }

        .module-add {
            grid-template-columns: 1fr
        }

        .module-side {
            display: none
        }
    }
    </style>
</head>

<body>
    <aside class="module-side">
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
                    <div class="workspace-title"><?=sm_h($_SESSION['username']??'Staff account')?></div>
                    <div class="workspace-desc"><?=sm_h($_SESSION['role']??'Staff')?></div>
                </div>
            </div>
            <div class="nav-section-title">Workspace</div>
            <ul class="nav-list"><?php foreach($nav as [$href,$label,$key]):?><li class="nav-item"><a
                        class="nav-link <?=$module===$key?'active':''?>" href="<?=$href?>"><?=sm_h($label)?></a></li>
                <?php endforeach;?></ul>
        </div>
        <div class="sidebar-bottom"><a class="nav-link" href="../index.php">View HomeBid</a>
            <form method="post" action="../logout.php"><input type="hidden" name="csrf_token"
                    value="<?=sm_h(csrf_token())?>"><button class="module-btn" style="width:100%;margin-top:10px"
                    type="submit">Log out</button></form>
        </div>
    </aside>
    <main class="module-main">
        <header class="top-header">
            <div class="breadcrumb">HomeBid &gt; <span><?=sm_h($pageTitle)?></span></div>
            <div class="header-right"><span><?=sm_h($_SESSION['username']??'Staff')?></span></div>
        </header>
        <div class="module-content">
            <div class="module-head">
                <div>
                    <div class="pre-heading">STAFF WORKSPACE</div>
                    <h1><?=sm_h($pageTitle)?></h1>
                    <p class="module-sub"><?=sm_h($pageDescription)?></p>
                </div><?php if($module==='reports'):?><a class="module-btn" href="reports.php?export=csv">Export
                    CSV</a><?php endif;?>
            </div>
            <?php if($notice):?><div class="module-alert"><?=sm_h($notice)?></div><?php endif;?><?php if($error):?><div
                class="module-alert error"><?=sm_h($error)?></div><?php endif;?>
            <div class="module-stats"><?php foreach($stats as [$label,$value]):?><div class="module-stat">
                    <small><?=sm_h($label)?></small><strong><?=is_float($value)?sm_money($value):number_format((float)$value)?></strong>
                </div><?php endforeach;?></div>
            <?php if($module==='staff' && $isAdmin):?><section class="module-card">
                <h2 style="margin-bottom:12px">Create staff account</h2>
                <form method="post" class="module-add"><input type="hidden" name="csrf_token"
                        value="<?=sm_h(csrf_token())?>"><input type="hidden" name="action" value="add_staff"><input
                        name="username" placeholder="Staff username" required maxlength="50"><input name="email"
                        type="email" placeholder="Staff email" required><input name="password" type="password"
                        placeholder="Temporary password (8+ characters)" minlength="8" required><button
                        class="module-btn" type="submit">Create staff account</button></form>
            </section><?php endif;?>
            <section class="module-card">
                <div class="module-scroll">
                    <table class="module-table">
                        <thead>
                            <tr><?php foreach($columns as $label):?><th><?=sm_h($label)?></th>
                                <?php endforeach;?><?php if(in_array($module,['approvals','properties','bidding','roles_sec','notification'],true)):?>
                                <th>Actions</th><?php endif;?></tr>
                        </thead>
                        <tbody>
                            <?php if(!$rows):?><tr>
                                <td class="module-empty" colspan="<?=count($columns)+1?>">No records found.</td>
                            </tr><?php endif;?>
                            <?php foreach($rows as $row):?><tr><?php foreach($columns as $key=>$label):?><td>
                                    <?php $v=$row[$key]??''; if(in_array($key,['price','starting_bid','current_bid','highest_bid'],true)) echo sm_h(sm_money($v)); elseif($key==='is_approved'||$key==='is_featured'||$key==='is_verified') echo ((int)$v?'Yes':'No'); else echo nl2br(sm_h($v));?>
                                </td><?php endforeach;?>
                                <?php if(in_array($module,['approvals','properties','bidding','roles_sec','notification'],true)):?>
                                <td>
                                    <div class="module-actions">
                                        <?php if($module==='approvals' && !$row['is_approved']??true):?><form
                                            method="post"><input type="hidden" name="csrf_token"
                                                value="<?=sm_h(csrf_token())?>"><input type="hidden" name="action"
                                                value="approve"><input type="hidden" name="id"
                                                value="<?=(int)$row['id']?>"><button class="module-btn"
                                                type="submit">Approve</button></form><?php endif;?>
                                        <?php if($module==='properties'):?><form method="post" class="module-form">
                                            <input type="hidden" name="csrf_token"
                                                value="<?=sm_h(csrf_token())?>"><input type="hidden" name="action"
                                                value="status"><input type="hidden" name="id"
                                                value="<?=(int)$row['id']?>"><select
                                                name="status"><?php foreach(['Available','Reserved','Sold','Auction'] as $s):?>
                                                <option <?=$row['status']===$s?'selected':''?>><?=$s?></option>
                                                <?php endforeach;?></select><button class="module-btn"
                                                type="submit">Save</button></form>
                                        <form method="post"><input type="hidden" name="csrf_token"
                                                value="<?=sm_h(csrf_token())?>"><input type="hidden" name="action"
                                                value="feature"><input type="hidden" name="id"
                                                value="<?=(int)$row['id']?>"><button class="module-btn alt"
                                                type="submit"><?=((int)$row['is_featured']?'Unfeature':'Feature')?></button>
                                        </form><?php endif;?>
                                        <?php if($module==='bidding'):?><form method="post" class="module-form"><input
                                                type="hidden" name="csrf_token" value="<?=sm_h(csrf_token())?>"><input
                                                type="hidden" name="action" value="auction_status"><input type="hidden"
                                                name="id" value="<?=(int)$row['id']?>"><select
                                                name="status"><?php foreach(['upcoming','active','ended','cancelled'] as $s):?>
                                                <option <?=$row['status']===$s?'selected':''?>><?=$s?></option>
                                                <?php endforeach;?></select><button class="module-btn"
                                                type="submit">Update</button></form><?php endif;?>
                                        <?php if($module==='notification'):?><form method="post" class="module-form">
                                            <input type="hidden" name="csrf_token"
                                                value="<?=sm_h(csrf_token())?>"><input type="hidden" name="action"
                                                value="inquiry_status"><input type="hidden" name="id"
                                                value="<?=(int)$row['id']?>"><select
                                                name="status"><?php foreach(['pending','responded','closed'] as $s):?>
                                                <option <?=$row['status']===$s?'selected':''?>><?=$s?></option>
                                                <?php endforeach;?></select><button class="module-btn"
                                                type="submit">Update</button></form><?php endif;?>
                                        <?php if($module==='roles_sec' && $isAdmin && (int)$row['id']!==$staffId):?>
                                        <form method="post" class="module-form"><input type="hidden" name="csrf_token"
                                                value="<?=sm_h(csrf_token())?>"><input type="hidden" name="action"
                                                value="change_role"><input type="hidden" name="id"
                                                value="<?=(int)$row['id']?>"><select
                                                name="role"><?php foreach(['Buyer','Staff','Administrator'] as $r):?>
                                                <option <?=$row['role']===$r?'selected':''?>><?=$r?></option>
                                                <?php endforeach;?></select><button class="module-btn"
                                                type="submit">Save role</button></form><?php endif;?>
                                    </div>
                                </td><?php endif;?></tr><?php endforeach;?></tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>

</html>