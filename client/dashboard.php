<?php
declare(strict_types=1);
require_once __DIR__.'/../config/auth.php';
$userId=require_roles(['Buyer','Client','Staff','Administrator']);
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
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Seller Dashboard | HomeBid</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>
<body>
<aside class="sidebar" id="sidebar">
 <div class="brand"><div class="brand-mark">⌂</div><div><strong>HOME<span>BID</span></strong><small>SELLER PORTAL</small></div><button class="mobile-close" onclick="toggleSidebar()">×</button></div>
 <div class="workspace"><small>WORKSPACE</small><div class="workspace-user"><span class="avatar small"><?=strtoupper(substr(h($account['username']),0,2))?></span><span><strong><?=h($account['username'])?></strong><small><?=h($account['role'])?> account</small></span></div></div>
 <nav class="side-nav">
  <small class="nav-label">MAIN MENU</small>
  <a class="active" href="#overview"><span>▦</span> Overview</a>
  <a href="#properties"><span>⌂</span> My Properties <b><?=(int)$stats['total']?></b></a>
  <a href="#approvals"><span>✓</span> Approvals <b><?=(int)$stats['pending']?></b></a>
  <a href="#auctions"><span>⚒</span> Manage Auctions</a>
  <a href="#viewings"><span>◷</span> Viewing Requests</a>
  <a href="#activity"><span>♧</span> Recent Activity</a>
 </nav>
 <div class="support-card"><div class="support-icon">◇</div><strong>Need assistance?</strong><p>Our HomeBid support team is ready to help.</p><a href="../index.php#contact">Contact support</a></div>
 <div class="profile"><span class="avatar"><?=strtoupper(substr(h($account['username']),0,2))?></span><span><strong><?=h($account['username'])?></strong><small>Seller account</small></span><form method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><button title="Log out" aria-label="Log out">↗</button></form></div>
</aside>
<div class="main-shell">
<header class="topbar"><button class="menu-button" onclick="toggleSidebar()">☰</button><div class="crumb">Seller Portal <span>›</span> <strong>Overview</strong></div><div class="top-actions"><label class="search-box"><span>⌕</span><input id="globalSearch" placeholder="Search properties..."></label><a class="bell" href="#activity" title="Recent activity">♧<i></i></a><a class="btn primary small-primary" href="#submit">＋ <span>Add Property</span></a></div></header>
<main class="content" id="overview">
 <section class="welcome"><div><p class="eyebrow"><?=strtoupper(date('l, d F Y'))?></p><h1>Welcome back, <?=h($account['username'])?>.</h1><span>Here’s what’s happening across your property portfolio.</span></div><div class="welcome-actions"><a class="btn secondary" href="#properties">View portfolio</a><a class="btn primary" href="#submit">＋ Submit property</a></div></section>
 <?php if($notice):?><div class="alert success"><?=h($notice)?></div><?php endif;?><?php if($error):?><div class="alert error"><?=h($error)?></div><?php endif;?>
 <section class="stats-grid">
  <article class="stat-card"><div class="stat-icon navy">⌂</div><div class="stat-top">Total Properties <span>•••</span></div><strong><?=(int)$stats['total']?></strong><small>All your submitted listings</small></article>
  <article class="stat-card"><div class="stat-icon amber">◷</div><div class="stat-top">Pending Approval <span>•••</span></div><strong><?=(int)$stats['pending']?></strong><small>Awaiting administrator review</small></article>
  <article class="stat-card"><div class="stat-icon green">✓</div><div class="stat-top">Approved <span>•••</span></div><strong><?=(int)$stats['approved']?></strong><small>Authorized property listings</small></article>
  <article class="stat-card"><div class="stat-icon blue">⌁</div><div class="stat-top">Ongoing Biddings <span>•••</span></div><strong><?=count(array_filter($auctions,fn($a)=>strtolower((string)$a['status'])==='active'))?></strong><small>Currently active auctions</small></article>
  <article class="stat-card"><div class="stat-icon purple">♜</div><div class="stat-top">Completed / Sold <span>•••</span></div><strong><?=(int)$stats['sold']?></strong><small>Successfully completed listings</small></article>
  <article class="stat-card"><div class="stat-icon red">×</div><div class="stat-top">Rejected <span>•••</span></div><strong><?=isset($stats['rejected'])?(int)$stats['rejected']:0?></strong><small>Listings needing your attention</small></article>
 </section>
 <section class="dashboard-grid">
  <article class="panel portfolio"><div class="panel-head"><div><h2>Portfolio overview</h2><p>Property submission and auction summary</p></div><span class="period-tag">All time</span></div>
   <div class="portfolio-summary"><div><small>Properties submitted</small><strong><?=(int)$stats['total']?></strong></div><div><small>Approval rate</small><strong><?=((int)$stats['total']>0?round(((int)$stats['approved']/(int)$stats['total'])*100):0)?>%</strong></div><div><small>Sold properties</small><strong><?=(int)$stats['sold']?></strong></div></div>
   <div class="bar-chart" aria-label="Property status summary"><div class="bar-row"><span>Approved</span><div><i style="width:<?=((int)$stats['total']>0?((int)$stats['approved']/(int)$stats['total']*100):0)?>%"></i></div><b><?=(int)$stats['approved']?></b></div><div class="bar-row"><span>Pending</span><div><i class="gold-bar" style="width:<?=((int)$stats['total']>0?((int)$stats['pending']/(int)$stats['total']*100):0)?>%"></i></div><b><?=(int)$stats['pending']?></b></div><div class="bar-row"><span>Sold</span><div><i class="green-bar" style="width:<?=((int)$stats['total']>0?((int)$stats['sold']/(int)$stats['total']*100):0)?>%"></i></div><b><?=(int)$stats['sold']?></b></div></div>
  </article>
  <article class="panel live-card"><div class="panel-head"><div><span class="live-label">AUCTION ACTIVITY</span><h2><?=count($auctions)?> linked auctions</h2><p>Auctions connected to your properties</p></div><span class="auction-mark">⚒</span></div>
   <?php if($auctions):$featuredAuction=$auctions[0];?><div class="bid-amount"><span>LATEST AUCTION</span><strong><?=h($featuredAuction['title'])?></strong><small><?=h(ucfirst((string)$featuredAuction['status']))?> · Current bid <?=money($featuredAuction['current_bid'])?></small></div><?php else:?><div class="empty-auction">No auctions have been created for your listings yet.</div><?php endif;?>
   <div class="bid-stats"><span><b><?=count($auctions)?></b><small>Total auctions</small></span><span><b><?=array_sum(array_map(fn($a)=>0,$auctions))?></b><small>Bid count shown below</small></span></div><a class="dark-button" href="#auctions">View auction activity <span>→</span></a>
  </article>
 </section>
 <section class="bottom-grid">
  <article class="panel"><div class="panel-head"><div><h2>Recent properties</h2><p>Your latest property submissions</p></div><a class="text-link" href="#properties">View all →</a></div><div class="table-wrap"><table><thead><tr><th>PROPERTY</th><th>STARTING PRICE</th><th>STATUS</th><th>ADDED</th><th></th></tr></thead><tbody>
  <?php if(!$properties):?><tr><td colspan="5" class="empty">No properties submitted yet.</td></tr><?php endif;?>
  <?php foreach(array_slice($properties,0,4) as $p):?><tr><td><div class="property-cell"><div class="property-thumb">⌂</div><span><strong><?=h($p['title'])?></strong><small><?=h($p['location'])?></small></span></div></td><td><strong><?=money($p['price'])?></strong></td><td><span class="badge <?=$p['is_approved']?'badge-approved':'badge-pending'?>"><i></i><?=$p['is_approved']?'Approved':'Pending'?></span></td><td><?=h(date('M j, Y',strtotime($p['created_at'])))?></td><td><a class="row-link" href="#properties">›</a></td></tr><?php endforeach;?>
  </tbody></table></div></article>
  <article class="panel activity" id="activity"><div class="panel-head"><div><h2>Recent activity</h2><p>Latest updates on your listings</p></div></div>
   <?php if(!$properties&&!$auctions&&!$viewings):?><p class="empty-activity">Your property activity will appear here.</p><?php endif;?>
   <?php foreach(array_slice($properties,0,3) as $p):?><div class="activity-item"><div class="activity-icon <?=((int)$p['is_approved']===1?'green':'amber')?>"><?=((int)$p['is_approved']===1?'✓':'◷')?></div><span><strong><?=((int)$p['is_approved']===1?'Property approved':'Property submitted')?></strong><p><?=h($p['title'])?></p><small><?=h(date('M j, Y',strtotime($p['created_at'])))?></small></span></div><?php endforeach;?>
   <?php foreach(array_slice($viewings,0,2) as $v):?><div class="activity-item"><div class="activity-icon blue">◷</div><span><strong>Viewing request</strong><p><?=h($v['title'])?> · <?=h($v['status'])?></p><small><?=h($v['scheduled_date'])?></small></span></div><?php endforeach;?>
  </article>
 </section>
 <div class="admin-notice"><div>◇</div><span><strong>Important reminder</strong><p>Only administrator-approved properties can proceed to auction. Check your approval status and update any listing that needs revision.</p></span><a href="#approvals">Review approvals</a></div>
 <section class="section-block" id="submit"><div class="section-title"><div><p class="eyebrow">PROPERTY MANAGEMENT</p><h2>Submit a property</h2><small>Provide the property details for administrator review.</small></div></div><article class="panel form-panel"><form method="post" class="property-form"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><label>Property title<input name="title" maxlength="150" required placeholder="e.g. Modern family home"></label><label>Property address<input name="location" maxlength="255" required placeholder="City, Province"></label><label>Lot area (m²)<input name="lot_area" type="number" min="0.01" step="0.01" required></label><label>Floor area (m²)<input name="floor_area" type="number" min="0.01" step="0.01" required></label><label>Starting bid price (₱)<input name="price" type="number" min="0.01" step="0.01" required></label><label class="full">Property description<textarea name="description" rows="4" required></textarea></label><div class="full form-note">◇ Your property will remain unpublished until an administrator approves the submission.</div><div class="full"><button class="btn primary" type="submit">Submit for approval →</button></div></form></article></section>
 <section class="section-block" id="properties"><div class="section-title"><div><p class="eyebrow">YOUR PORTFOLIO</p><h2>My Properties</h2><small>Manage your submitted property listings.</small></div></div><article class="panel table-panel"><div class="table-wrap"><table><thead><tr><th>PROPERTY</th><th>LOCATION</th><th>PRICE</th><th>APPROVAL</th><th>STATUS</th><th>BIDS</th><th>ACTION</th></tr></thead><tbody><?php if(!$properties):?><tr><td colspan="7" class="empty">No listings found.</td></tr><?php endif;?><?php foreach($properties as $p):?><tr><td><strong><?=h($p['title'])?></strong></td><td><?=h($p['location'])?></td><td><?=money($p['price'])?></td><td><span class="badge <?=$p['is_approved']?'badge-approved':'badge-pending'?>"><?=$p['is_approved']?'Approved':'Pending'?></span></td><td><?=h($p['status'])?></td><td><?=(int)$p['bid_count']?></td><td><form method="post" onsubmit="return confirm('Delete this property permanently? This cannot be undone.');"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="action" value="delete_property"><input type="hidden" name="property_id" value="<?=(int)$p['id']?>"><button class="delete-btn">Delete</button></form></td></tr><?php endforeach;?></tbody></table></div></article></section>
 <section class="section-block" id="approvals"><div class="section-title"><div><p class="eyebrow">ADMINISTRATOR REVIEW</p><h2>Approval status</h2><small>Track which listings are approved and which are awaiting review.</small></div></div><article class="panel table-panel"><div class="table-wrap"><table><thead><tr><th>PROPERTY</th><th>SUBMITTED</th><th>REVIEW STATUS</th></tr></thead><tbody><?php foreach($properties as $p):?><tr><td><?=h($p['title'])?></td><td><?=h(date('M j, Y',strtotime($p['created_at'])))?></td><td><span class="badge <?=$p['is_approved']?'badge-approved':'badge-pending'?>"><?=$p['is_approved']?'Approved':'Pending'?></span></td></tr><?php endforeach;?><?php if(!$properties):?><tr><td colspan="3" class="empty">No submissions to review.</td></tr><?php endif;?></tbody></table></div></article></section>
 <section class="section-block" id="auctions"><div class="section-title"><div><p class="eyebrow">BIDDING MANAGEMENT</p><h2>Manage Auctions</h2><small>Monitor auction schedules and current bids for your properties.</small></div></div><article class="panel table-panel"><div class="table-wrap"><table><thead><tr><th>PROPERTY</th><th>STARTING BID</th><th>CURRENT BID</th><th>STARTS</th><th>ENDS</th><th>STATUS</th></tr></thead><tbody><?php if(!$auctions):?><tr><td colspan="6" class="empty">No auctions linked to your properties yet.</td></tr><?php endif;?><?php foreach($auctions as $a):?><tr><td><?=h($a['title'])?></td><td><?=money($a['starting_bid'])?></td><td><?=money($a['current_bid'])?></td><td><?=h($a['start_time'])?></td><td><?=h($a['end_time'])?></td><td><?=h(ucfirst((string)$a['status']))?></td></tr><?php endforeach;?></tbody></table></div></article></section>
 <section class="section-block" id="viewings"><div class="section-title"><div><p class="eyebrow">PROPERTY VISITS</p><h2>Viewing requests</h2><small>Appointments requested for your listings.</small></div></div><article class="panel table-panel"><div class="table-wrap"><table><thead><tr><th>PROPERTY</th><th>SCHEDULE</th><th>STATUS</th><th>REQUESTED</th></tr></thead><tbody><?php if(!$viewings):?><tr><td colspan="4" class="empty">No viewing requests yet.</td></tr><?php endif;?><?php foreach($viewings as $v):?><tr><td><?=h($v['title'])?></td><td><?=h($v['scheduled_date'])?></td><td><?=h($v['status'])?></td><td><?=h($v['created_at'])?></td></tr><?php endforeach;?></tbody></table></div></article></section>
 <footer class="footer">© <?=date('Y')?> HomeBid · Seller Portal</footer>
</main></div>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('sidebar-open')}
document.querySelectorAll('.side-nav a').forEach(a=>a.addEventListener('click',()=>{document.querySelectorAll('.side-nav a').forEach(x=>x.classList.remove('active'));a.classList.add('active');document.getElementById('sidebar').classList.remove('sidebar-open')}));
const search=document.getElementById('globalSearch'); if(search) search.addEventListener('input',()=>{const q=search.value.toLowerCase();document.querySelectorAll('#properties tbody tr').forEach(row=>row.style.display=row.innerText.toLowerCase().includes(q)?'':'none')});
</script>
</body></html>