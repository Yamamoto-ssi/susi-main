<?php
declare(strict_types=1);
require_once __DIR__.'/../config/auth.php'; $userId=require_roles(['Buyer','Client','Staff','Administrator']); require_once __DIR__.'/../config/db.php';
$page=$_GET['page']??'overview'; $pages=['overview','properties','approvals','auctions','live','results','notifications']; if(!in_array($page,$pages,true))$page='overview'; $notice=$error='';
function hb_store_property_image(array $file): string {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Please choose valid image files.');
    if (($file['size'] ?? 0) <= 0 || $file['size'] > 8 * 1024 * 1024) throw new RuntimeException('Each image must be smaller than 8 MB.');
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) throw new RuntimeException('One of the uploaded files is not a valid image.');
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $type = $imageInfo[2] ?? 0;
    if (!isset($allowed[$type])) throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    $directory = __DIR__ . '/../assets/images';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('The assets/images folder could not be created.');
    $filename = 'property_' . bin2hex(random_bytes(12)) . '.' . $allowed[$type];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) throw new RuntimeException('A property image could not be saved. Check folder permissions.');
    return 'assets/images/' . $filename;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete_property') {
        $id = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);
        if (!$id) $error = 'Invalid property selected.';
        else {
            $q = hb_query($conn, 'SELECT id FROM properties WHERE id=? AND user_id=?', [$id, $userId]);
            if (!hb_fetch_one($q)) $error = 'Property not found.';
            else {
                $q = hb_query($conn, 'SELECT (SELECT COUNT(*) FROM bids WHERE property_id=?) b,(SELECT COUNT(*) FROM auctions WHERE property_id=?) a,(SELECT COUNT(*) FROM viewing_bookings WHERE property_id=?) v', [$id,$id,$id]);
                $x = hb_fetch_one($q);
                if ($x['b'] + $x['a'] + $x['v']) $error = 'This property has bidding, auction, or viewing activity and cannot be deleted.';
                else { hb_query($conn, 'DELETE FROM properties WHERE id=? AND user_id=?', [$id,$userId]); $notice = 'Property deleted.'; }
            }
        }
        $page = 'properties';
    } else {
        $t=trim($_POST['title']??''); $d=trim($_POST['description']??''); $l=trim($_POST['location']??'');
        $lot=(float)($_POST['lot_area']??0); $floor=(float)($_POST['floor_area']??0); $price=(float)($_POST['price']??0);
        $savedFiles=[];
        try {
            if(!$t||!$d||!$l||$lot<=0||$floor<=0||$price<=0) throw new RuntimeException('Complete all property fields with valid positive values.');
            if(!isset($_FILES['thumbnail'])||$_FILES['thumbnail']['error']!==UPLOAD_ERR_OK) throw new RuntimeException('Please upload a main property thumbnail.');
            $extraFiles=[];
            if(isset($_FILES['photos'])&&is_array($_FILES['photos']['name'])) {
                foreach($_FILES['photos']['name'] as $i=>$name) {
                    if($_FILES['photos']['error'][$i]===UPLOAD_ERR_NO_FILE) continue;
                    $extraFiles[]=['name'=>$name,'type'=>$_FILES['photos']['type'][$i],'tmp_name'=>$_FILES['photos']['tmp_name'][$i],'error'=>$_FILES['photos']['error'][$i],'size'=>$_FILES['photos']['size'][$i]];
                }
            }
            if(count($extraFiles)>3) throw new RuntimeException('You can upload up to 3 additional photos (4 images including the thumbnail).');
            $thumbnailPath=hb_store_property_image($_FILES['thumbnail']); $savedFiles[]=$thumbnailPath;
            foreach($extraFiles as $file) $savedFiles[]=hb_store_property_image($file);
            hb_query($conn,"INSERT INTO properties(user_id,title,description,location,lot_area,floor_area,price,status,is_approved,image_path) VALUES(?,?,?,?,?,?,?,'Available',0,?)",[$userId,$t,$d,$l,$lot,$floor,$price,$thumbnailPath]);
            $propertyId=mysqli_insert_id($conn);
            for($i=1;$i<count($savedFiles);$i++) hb_query($conn,'INSERT INTO property_images(property_id,image_path) VALUES(?,?)',[$propertyId,$savedFiles[$i]]);
            $notice='Property and photos submitted. Awaiting administrator approval.';
        } catch(Throwable $e) {
            foreach($savedFiles as $path) { $fullPath=dirname(__DIR__).'/'.$path; if(is_file($fullPath)) @unlink($fullPath); }
            $error=$e instanceof RuntimeException?$e->getMessage():'The property could not be saved. Please check the database tables and try again.';
            error_log('HomeBid property submission error: '.$e->getMessage());
        }
        $page='properties';
    }
}
$q = hb_query($conn, "SELECT COUNT(*) total,SUM(is_approved=0) pending,SUM(is_approved=1) approved,SUM(status='Sold') sold,SUM(status='Rejected') rejected FROM properties WHERE user_id=?", [$userId]);$stats=hb_fetch_one($q)?:[];foreach(['total','pending','approved','sold','rejected'] as $k)$stats[$k]=(int)($stats[$k]??0);
$q = hb_query($conn, 'SELECT p.*,(SELECT COUNT(*) FROM bids b WHERE b.property_id=p.id) bid_count FROM properties p WHERE p.user_id=? ORDER BY p.created_at DESC', [$userId]);$properties=hb_fetch_all($q);
$q = hb_query($conn, 'SELECT a.*,p.title,p.location,(SELECT COUNT(*) FROM bids b WHERE b.property_id=p.id) bid_count FROM auctions a JOIN properties p ON p.id=a.property_id WHERE p.user_id=? ORDER BY a.created_at DESC', [$userId]);$auctions=hb_fetch_all($q);
$q = hb_query($conn, 'SELECT v.*,p.title FROM viewing_bookings v JOIN properties p ON p.id=v.property_id WHERE p.user_id=? ORDER BY v.scheduled_date DESC LIMIT 8', [$userId]);$viewings=hb_fetch_all($q);
$q = hb_query($conn, 'SELECT username,role FROM users WHERE id=?', [$userId]);$account=hb_fetch_one($q)?:['username'=>'Seller','role'=>'Buyer'];$initials=strtoupper(substr($account['username'],0,2));
$active=array_values(array_filter($auctions,fn($a)=>strtolower((string)$a['status'])==='active'));$featured=$active[0]??($auctions[0]??null);$bidTotal=array_sum(array_column($properties,'bid_count'));$highest=(float)($featured['current_bid']??$featured['starting_bid']??0);
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}function peso($v){return '₱'.number_format((float)$v,2);}function ico($n,$s=18){$p=['grid'=>'<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>','home'=>'<path d="m3 11 9-7 9 7M5 10v10h14V10M9 20v-6h6v6"/>','check'=>'<path d="m20 6-11 11-5-5"/><circle cx="12" cy="12" r="9"/>','gavel'=>'<path d="m14 5 5 5M11 8l5 5M6 13l5 5M3 21h11M13 4l-8 8 6 6 8-8"/>','pulse'=>'<path d="M3 12h4l3-6 4 12 3-6h4"/><circle cx="12" cy="12" r="9"/>','trophy'=>'<path d="M8 4h8v5a4 4 0 0 1-8 0V4ZM10 13v4m4-4v4M7 20h10M8 6H4v2a4 4 0 0 0 4 4M16 6h4v2a4 4 0 0 1-4 4"/>','bell'=>'<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>','plus'=>'<path d="M12 5v14M5 12h14"/>','search'=>'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>','chevron'=>'<path d="m9 18 6-6-6-6"/>','arrow'=>'<path d="M5 12h14m-5-5 5 5-5 5"/>','building'=>'<path d="M4 21V7l8-4 8 4v14M8 10h2m4 0h2M8 14h2m4 0h2M9 21v-3h6v3"/>','clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>','users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8M22 21v-2a4 4 0 0 0-3-4"/>','trend'=>'<path d="m3 17 6-6 4 4 8-9m-6 0h6v6"/>','shield'=>'<path d="m12 3-8 3v5c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>','menu'=>'<path d="M4 7h16M4 12h16M4 17h16"/>','x'=>'<path d="m6 6 12 12M18 6 6 18"/>','file'=>'<path d="M6 3h8l4 4v14H6zM14 3v5h5M9 13h6m-6 4h6"/>','dots'=>'<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>'];return '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.($p[$n]??$p['file']).'</svg>';}
function badge($s){$c=strtolower($s);if(!in_array($c,['approved','pending','live','active','rejected']))$c='pending';return '<span class="badge badge-'.$c.'"><i></i>'.e($s).'</span>';}
$nav=[['overview','Overview','grid',''],['properties','My Properties','home',$stats['total']],['approvals','Approvals','check',$stats['pending']],['auctions','Manage Auctions','gavel',''],['live','Live Bidding','pulse',count($active)],['results','Auction Results','trophy',$stats['sold']],['notifications','Notifications','bell','']];$titles=['overview'=>'Overview','properties'=>'My Properties','approvals'=>'Approvals','auctions'=>'Manage Auctions','live'=>'Live Bidding','results'=>'Auction Results','notifications'=>'Notifications'];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=e($titles[$page])?> | HomeBid</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=figma-2">
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-mark"><?=ico('building',21)?></div>
                <div><strong>HOME<span>BID</span></strong><small>SELLER PORTAL</small></div><button class="mobile-close"
                    onclick="toggleMenu()"><?=ico('x')?></button>
            </div>
            <div class="workspace"><small>WORKSPACE</small><button><span
                        class="avatar small"><?=e($initials)?></span><span><strong><?=e($account['username'])?></strong><small>Verified
                            seller</small></span><?=ico('chevron',14)?></button></div>
            <nav><small class="nav-label">MAIN MENU</small><?php foreach($nav as [$id,$label,$icon,$count]):?><a
                    class="<?=$page===$id?'active':''?>"
                    href="?page=<?=$id?>"><?=ico($icon)?><span><?=e($label)?></span><?php if($count!==''):?><b><?=e($count)?></b><?php endif;?></a><?php endforeach;?>
            </nav>
            <div class="support-card">
                <div><?=ico('shield')?></div><strong>Need assistance?</strong>
                <p>Our auction support team is ready to help.</p><a href="../index.php#contact">Contact support</a>
            </div>
            <div class="profile"><span
                    class="avatar"><?=e($initials)?></span><span><strong><?=e($account['username'])?></strong><small><?=e($account['role'])?></small></span>
                <form class="logout-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token"
                        value="<?=e(csrf_token())?>"><button title="Log out"><?=ico('dots')?></button></form>
            </div>
        </aside>
        <main>
            <header><button class="menu-button" onclick="toggleMenu()"><?=ico('menu')?></button>
                <div class="breadcrumbs"><span>Seller
                        Portal</span><?=ico('chevron',13)?><strong><?=e($titles[$page])?></strong></div>
                <div class="header-actions"><label class="search"><?=ico('search')?><input id="globalSearch"
                            placeholder="Search properties, auctions..."><kbd>⌘ K</kbd></label><a class="bell"
                        href="?page=notifications"><?=ico('bell')?><span></span></a><button
                        class="primary small-primary" onclick="openForm()"><?=ico('plus')?> <span>Add
                            Property</span></button></div>
            </header>
            <div class="content">
                <?php if($notice):?><div class="flash success"><?=e($notice)?></div><?php endif;?><?php if($error):?>
                <div class="flash error"><?=e($error)?></div><?php endif;?>
                <?php if($page==='overview'):?><section class="welcome">
                    <div>
                        <p><?=strtoupper(date('l, d F'))?></p>
                        <h1>Good morning, <?=e($account['username'])?>.</h1><span>Here’s what’s happening across your
                            property portfolio today.</span>
                    </div>
                    <div class="welcome-actions"><a class="secondary" href="?page=results"><?=ico('file')?> Export
                            report</a><button class="primary" onclick="openForm()"><?=ico('plus')?> Submit
                            property</button></div>
                </section>
                <section class="stats-grid">
                    <?php $cards=[['Total Properties',$stats['total'],'Your submitted listings','building','navy'],['Pending Approval',$stats['pending'],'Awaiting administrator review','clock','amber'],['Approved',$stats['approved'],'Approved property listings','check','green'],['Ongoing Biddings',count($active),$bidTotal.' total bids','pulse','blue'],['Completed',$stats['sold'],'Successfully completed','trophy','purple'],['Rejected',$stats['rejected'],'Review administrator feedback','x','red']];foreach($cards as [$l,$v,$n,$i,$t]):?>
                    <article class="stat-card">
                        <div class="stat-icon <?=$t?>"><?=ico($i)?></div>
                        <div class="stat-top"><span><?=e($l)?></span><?=ico('dots')?></div>
                        <strong><?=e($v)?></strong><small><?=e($n)?></small>
                    </article><?php endforeach;?></section>
                <section class="dashboard-grid">
                    <article class="panel portfolio">
                        <div class="panel-head">
                            <div>
                                <h2>Portfolio overview</h2>
                                <p>Submission and auction performance</p>
                            </div><select>
                                <option>All time</option>
                            </select>
                        </div>
                        <div class="chart-metrics"><span><i class="gold"></i>Auction
                                value<strong><?=peso(array_sum(array_map(fn($a)=>(float)($a['current_bid']??$a['starting_bid']??0),$auctions)))?></strong></span><span><i></i>Properties
                                sold<strong><?=$stats['sold']?></strong></span>
                            <div><b><?=$stats['total']?round($stats['approved']/$stats['total']*100):0?>%</b> approval
                                rate</div>
                        </div>
                        <div class="chart">
                            <div class="y-labels">
                                <span>₱800k</span><span>₱600k</span><span>₱400k</span><span>₱200k</span><span>₱0</span>
                            </div>
                            <div class="chart-area">
                                <div class="grid-lines"><i></i><i></i><i></i><i></i><i></i></div><svg
                                    viewBox="0 0 640 190" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="area" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0" stop-color="#bd934d" stop-opacity=".22" />
                                            <stop offset="1" stop-color="#bd934d" stop-opacity="0" />
                                        </linearGradient>
                                    </defs>
                                    <path
                                        d="M0 154 C70 139 85 100 130 111 S210 146 260 89 S340 42 390 72 S480 108 520 51 S595 20 640 36 L640 190 L0 190Z"
                                        fill="url(#area)" />
                                    <path
                                        d="M0 154 C70 139 85 100 130 111 S210 146 260 89 S340 42 390 72 S480 108 520 51 S595 20 640 36"
                                        fill="none" stroke="#b78c43" stroke-width="3" />
                                </svg>
                                <div class="x-labels">
                                    <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="panel live-card">
                        <div class="panel-head">
                            <div><span class="live-label"><i></i> LIVE AUCTION</span>
                                <h2><?=e($featured['title']??'No active auction yet')?></h2>
                                <p><?=$featured?'Auction status: '.e($featured['status']):'An approved property can be scheduled for auction.'?>
                                </p>
                            </div><a class="icon-button" href="?page=live"><?=ico('arrow')?></a>
                        </div>
                        <div class="bid-amount"><span>CURRENT HIGHEST
                                BID</span><strong><?=peso($highest)?></strong><small><?=ico('trend',14)?> <?=$bidTotal?>
                                bids recorded for your properties</small></div>
                        <div class="bid-stats"><span><?=ico('users')?><b><?=$bidTotal?></b><small>Total
                                    bids</small></span><span><?=ico('gavel')?><b><?=count($auctions)?></b><small>Auctions</small></span><span><?=ico('clock')?><b><?=count($active)?></b><small>Active
                                    now</small></span></div><a class="dark-button" href="?page=live">Monitor live
                            bidding <?=ico('arrow')?></a>
                    </article>
                </section>
                <section class="bottom-grid">
                    <article class="panel">
                        <div class="panel-head">
                            <div>
                                <h2>Recent properties</h2>
                                <p>Your latest property submissions</p>
                            </div><a class="text-button" href="?page=properties">View all <?=ico('arrow',14)?></a>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>PROPERTY</th>
                                        <th>STARTING BID</th>
                                        <th>STATUS</th>
                                        <th>UPDATED</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody><?php if(!$properties):?><tr>
                                        <td colspan="5" class="empty-row">No properties submitted yet.</td>
                                    </tr><?php endif;?><?php foreach(array_slice($properties,0,4) as $p):?><tr>
                                        <td>
                                            <div class="property-cell">
                                                <div class="list-icon"><?=ico('home')?></div>
                                                <span><strong><?=e($p['title'])?></strong><small><?=e($p['location'])?></small></span>
                                            </div>
                                        </td>
                                        <td><strong><?=peso($p['price'])?></strong></td>
                                        <td><?=badge(!empty($p['is_approved'])?'Approved':(strtolower($p['status'])==='rejected'?'Rejected':'Pending'))?>
                                        </td>
                                        <td><?=e(date('M j, Y',strtotime($p['created_at'])))?></td>
                                        <td><a class="row-button" href="?page=properties"><?=ico('chevron')?></a></td>
                                    </tr><?php endforeach;?></tbody>
                            </table>
                        </div>
                    </article>
                    <article class="panel activity">
                        <div class="panel-head">
                            <div>
                                <h2>Recent activity</h2>
                                <p>Latest portfolio updates</p>
                            </div><?=ico('dots')?>
                        </div><?php foreach(array_slice($properties,0,4) as $p):?><div class="activity-item">
                            <div class="activity-icon <?=!empty($p['is_approved'])?'green':'amber'?>">
                                <?=ico(!empty($p['is_approved'])?'check':'file')?></div>
                            <span><strong><?=!empty($p['is_approved'])?'Approval received':'Property submitted'?></strong>
                                <p><?=e($p['title'])?></p>
                                <small><?=e(date('M j, Y',strtotime($p['created_at'])))?></small>
                            </span>
                        </div><?php endforeach;?><?php if(!$properties):?><div class="simple-empty">Your latest
                            portfolio updates will appear here.</div><?php endif;?>
                    </article>
                </section>
                <div class="admin-notice">
                    <div><?=ico('shield')?></div><span><strong>Administrator announcement</strong>
                        <p>Only approved properties can be scheduled or published for bidding.</p>
                    </span><a href="?page=approvals">View details</a>
                </div>
                <?php elseif($page==='properties'):?><section class="page-section">
                    <div class="page-title">
                        <div>
                            <p>PROPERTY MANAGEMENT</p>
                            <h1>My Properties</h1><span>Manage submissions, updates, and approval progress.</span>
                        </div><button class="primary" onclick="openForm()"><?=ico('plus')?> Submit property</button>
                    </div>
                    <div class="filter-row"><label class="search wide"><?=ico('search')?><input id="filterSearch"
                                placeholder="Search by property name or location..."></label><select id="filterStatus">
                            <option value="">All statuses</option>
                            <option>Pending</option>
                            <option>Approved</option>
                            <option>Rejected</option>
                        </select></div>
                    <article class="panel property-table">
                        <div class="table-wrap">
                            <table id="propertyTable">
                                <thead>
                                    <tr>
                                        <th>PROPERTY</th>
                                        <th>LOCATION</th>
                                        <th>STARTING BID</th>
                                        <th>APPROVAL STATUS</th>
                                        <th>LAST UPDATED</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>
                                <tbody><?php if(!$properties):?><tr>
                                        <td colspan="6" class="empty-row">No properties submitted yet.</td>
                                    </tr>
                                    <?php endif;?><?php foreach($properties as $p):$st=!empty($p['is_approved'])?'Approved':(strtolower($p['status'])==='rejected'?'Rejected':'Pending');?>
                                    <tr data-status="<?=$st?>">
                                        <td><strong><?=e($p['title'])?></strong><small class="subline">Property
                                                #<?=$p['id']?></small></td>
                                        <td><?=e($p['location'])?></td>
                                        <td><?=peso($p['price'])?></td>
                                        <td><?=badge($st)?></td>
                                        <td><?=e(date('M j, Y',strtotime($p['created_at'])))?></td>
                                        <td>
                                            <form class="logout-form" method="post"
                                                onsubmit="return confirm('Delete this property?')"><input type="hidden"
                                                    name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden"
                                                    name="action" value="delete_property"><input type="hidden"
                                                    name="property_id" value="<?=$p['id']?>"><button
                                                    class="delete-action">Delete</button></form>
                                        </td>
                                    </tr><?php endforeach;?></tbody>
                            </table>
                        </div>
                        <div class="table-footer">Showing <?=count($properties)?> properties</div>
                    </article>
                </section>
                <?php elseif($page==='approvals'):?><section class="page-section">
                    <div class="page-title">
                        <div>
                            <p>COMPLIANCE CENTER</p>
                            <h1>Approval Management</h1><span>Track review progress and respond to administrator
                                feedback.</span>
                        </div>
                    </div>
                    <div class="approval-callout"><?=ico('shield',25)?><div><strong>Administrator approval is
                                required</strong>
                            <p>Only approved properties can be scheduled or published for bidding. Approval decisions
                                are made exclusively by authorized administrators.</p>
                        </div>
                    </div>
                    <div class="approval-grid">
                        <?php foreach($properties as $p):$st=!empty($p['is_approved'])?'Approved':(strtolower($p['status'])==='rejected'?'Rejected':'Pending');?>
                        <article class="panel approval-card">
                            <div class="approval-card-top">
                                <div class="stat-icon <?=$st==='Approved'?'green':'amber'?>">
                                    <?=ico($st==='Approved'?'check':'clock')?></div><?=badge($st)?>
                            </div>
                            <h2><?=e($p['title'])?></h2><small>Submitted
                                <?=e(date('d M Y',strtotime($p['created_at'])))?></small>
                            <div class="feedback"><strong>PROPERTY REVIEW STATUS</strong>
                                <p><?=$st==='Approved'?'This property is approved and can proceed to auction scheduling.':'Your property is awaiting administrator review.'?>
                                </p>
                            </div><a class="secondary" href="?page=properties">View details <?=ico('arrow')?></a>
                        </article><?php endforeach;?><?php if(!$properties):?><div class="simple-empty">No property
                            submissions to review yet.</div><?php endif;?></div>
                </section>
                <?php elseif($page==='auctions'||$page==='live'):?><section class="page-section">
                    <div class="page-title">
                        <div>
                            <p><?=$page==='live'?'LIVE AUCTION MONITOR':'AUCTION MANAGEMENT'?></p>
                            <h1><?=$titles[$page]?></h1><span>Review auction schedules and current bidding
                                status.</span>
                        </div>
                    </div><?php if($page==='live'&&$featured):?><div class="live-hero"><span>CURRENT HIGHEST
                            BID</span><strong><?=peso($highest)?></strong>
                        <p><?=ico('trend')?> <?=$bidTotal?> bids recorded</p>
                        <div><span><b><?=$bidTotal?></b>Total bids</span><span><b><?=count($active)?></b>Active
                                auctions</span><span><b><?=count($auctions)?></b>All auctions</span></div>
                    </div><?php endif;?><article class="panel property-table">
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>PROPERTY</th>
                                        <th>LOCATION</th>
                                        <th>STARTING BID</th>
                                        <th>CURRENT BID</th>
                                        <th>STARTS</th>
                                        <th>ENDS</th>
                                        <th>STATUS</th>
                                    </tr>
                                </thead>
                                <tbody><?php if(!$auctions):?><tr>
                                        <td colspan="7" class="empty-row">No auctions have been created for your
                                            properties yet.</td>
                                    </tr><?php endif;?><?php foreach($auctions as $a):?><tr>
                                        <td><?=e($a['title'])?></td>
                                        <td><?=e($a['location'])?></td>
                                        <td><?=peso($a['starting_bid']??0)?></td>
                                        <td><?=peso($a['current_bid']??$a['starting_bid']??0)?></td>
                                        <td><?=e($a['start_time']??'—')?></td>
                                        <td><?=e($a['end_time']??'—')?></td>
                                        <td><?=badge(ucfirst($a['status']))?></td>
                                    </tr><?php endforeach;?></tbody>
                            </table>
                        </div>
                    </article>
                </section>
                <?php elseif($page==='results'):?><section class="page-section">
                    <div class="page-title">
                        <div>
                            <p>AUCTION PERFORMANCE</p>
                            <h1>Auction Results</h1><span>Review completed auctions for your properties.</span>
                        </div>
                    </div>
                    <article class="panel list-page">
                        <?php $sold=array_filter($properties,fn($p)=>strtolower($p['status'])==='sold');if(!$sold)echo '<div class="simple-empty">Completed auction results will appear here once a property is sold.</div>';foreach($sold as $p):?>
                        <div class="list-row">
                            <div class="list-icon"><?=ico('trophy')?></div><span><strong><?=e($p['title'])?></strong>
                                <p><?=e($p['location'])?></p>
                            </span><small><?=peso($p['price'])?> · Sold</small>
                        </div><?php endforeach;?></article>
                </section>
                <?php else:?><section class="page-section">
                    <div class="page-title">
                        <div>
                            <p>SELLER PORTAL</p>
                            <h1>Notifications</h1><span>Stay updated on approvals, bids, schedules, and
                                announcements.</span>
                        </div>
                    </div>
                    <article class="panel list-page"><?php foreach(array_slice($properties,0,8) as $p):?><div
                            class="list-row">
                            <div class="list-icon"><?=ico('bell')?></div>
                            <span><strong><?=!empty($p['is_approved'])?'Approval received':'Property submitted'?></strong>
                                <p><?=e($p['title'])?> · <?=!empty($p['is_approved'])?'Approved':'Awaiting review'?></p>
                            </span><small><?=e(date('M j, Y',strtotime($p['created_at'])))?></small>
                        </div><?php endforeach;?><?php if(!$properties):?><div class="simple-empty">You are all caught
                            up. New updates will appear here.</div><?php endif;?></article>
                </section><?php endif;?>
            </div>
        </main>
    </div>
    <div class="modal-layer" id="propertyModal" hidden><button class="modal-backdrop" onclick="closeForm()"></button>
        <section class="modal">
            <div class="modal-head">
                <div>
                    <p>NEW SUBMISSION</p>
                    <h2>Submit property for bidding</h2><span>Complete the details below. All properties require
                        approval.</span>
                </div><button onclick="closeForm()" type="button"><?=ico('x')?></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="form-grid"><label class="full">Property title<input name="title" required maxlength="150"
                            placeholder="e.g. Bayside Family Residence"></label><label class="full">Property
                        address<input name="location" required maxlength="255"
                            placeholder="Street, city, province"></label><label>Lot area (sq m)<input name="lot_area"
                            required type="number" min=".01" step=".01"></label><label>Floor area (sq m)<input
                            name="floor_area" required type="number" min=".01" step=".01"></label><label>Starting bid
                        price<input name="price" required type="number" min=".01" step=".01"></label><label
                        class="full">Property description<textarea name="description" required></textarea></label>
                    <label class="full">Main property thumbnail (required)
                        <input type="file" name="thumbnail" id="thumbnailInput" accept="image/jpeg,image/png,image/webp" required>
                        <small class="upload-help">JPG, PNG, or WEBP. Maximum 8 MB.</small>
                        <div id="thumbnailPreview" class="image-preview-grid"></div>
                    </label>
                    <label class="full">Other property photos (up to 3)
                        <input type="file" name="photos[]" id="photosInput" accept="image/jpeg,image/png,image/webp" multiple>
                        <small class="upload-help">Select up to 3 additional photos. Maximum 8 MB each.</small>
                        <div id="photosPreview" class="image-preview-grid"></div>
                    </label></div>
                <div class="form-notice"><?=ico('shield')?><p>Submitted properties remain unpublished until an
                        administrator approves them.</p>
                </div>
                <div class="modal-actions"><button type="button" class="secondary"
                        onclick="closeForm()">Cancel</button><button class="primary" type="submit"><?=ico('arrow')?>
                        Submit for approval</button></div><input type="hidden" name="csrf_token"
                    value="<?=e(csrf_token())?>">
            </form>
        </section>
    </div>
    <script>
    function toggleMenu() {
        document.getElementById('sidebar').classList.toggle('sidebar-open')
    }

    function openForm() {
        document.getElementById('propertyModal').hidden = false;
        document.body.style.overflow = 'hidden'
    }

    function closeForm() {
        document.getElementById('propertyModal').hidden = true;
        document.body.style.overflow = ''
    }
    function previewImageFiles(input, preview, maxFiles) {
        if (!input || !preview) return;
        input.addEventListener('change', () => {
            preview.innerHTML = '';
            const files = Array.from(input.files || []);
            if (files.length > maxFiles) { alert('You can select up to ' + maxFiles + ' image(s) here.'); input.value = ''; return; }
            files.forEach(file => {
                if (file.type !== 'image/jpeg' && file.type !== 'image/png' && file.type !== 'image/webp') return;
                const reader = new FileReader();
                reader.onload = event => {
                    const item = document.createElement('div'); item.className = 'image-preview-item';
                    const image = document.createElement('img'); image.src = event.target.result; image.alt = file.name;
                    const caption = document.createElement('span'); caption.textContent = file.name;
                    item.append(image, caption); preview.appendChild(item);
                };
                reader.readAsDataURL(file);
            });
        });
    }
    previewImageFiles(document.getElementById('thumbnailInput'), document.getElementById('thumbnailPreview'), 1);
    previewImageFiles(document.getElementById('photosInput'), document.getElementById('photosPreview'), 3);
    const fs = document.getElementById('filterSearch'),
        st = document.getElementById('filterStatus');

    function filter() {
        document.querySelectorAll('#propertyTable tbody tr').forEach(r => r.style.display = (!fs || r.innerText
            .toLowerCase().includes(fs.value.toLowerCase())) && (!st || !st.value || r.dataset.status === st
            .value) ? '' : 'none')
    }
    if (fs) fs.addEventListener('input', filter);
    if (st) st.addEventListener('change', filter);
    const gs = document.getElementById('globalSearch');
    if (gs) gs.addEventListener('keydown', e => {
        if (e.key === 'Enter') location.href = '?page=properties&q=' + encodeURIComponent(gs.value)
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeForm()
    });
    </script>
</body>

</html>