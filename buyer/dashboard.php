<?php
declare(strict_types=1);
require_once __DIR__.'/../config/auth.php';
$userId=require_roles(['Buyer']);
require_once __DIR__.'/../config/db.php';

function bh($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}
function buyer_money($v):string{return '₱'.number_format((float)$v,2);}
function buyer_rows(mysqli $conn,string $sql,array $args=[]):array{$s = hb_query($conn, $sql, $args);return hb_fetch_all($s);}
function buyer_one(mysqli $conn,string $sql,array $args=[]):array{$s = hb_query($conn, $sql, $args);return hb_fetch_one($s)?:[];}
function buyer_icon(string $name,int $size=18):string{
$paths=[
'home'=>'<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
'search'=>'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
'gavel'=>'<path d="m14 13-7 7-3-3 7-7m4-6 5 5m-7-3 5 5m-6-8 9 9"/>',
'heart'=>'<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/>',
'bell'=>'<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
'arrow'=>'<path d="M5 12h14m-6-6 6 6-6 6"/>',
'chevron'=>'<path d="m9 18 6-6-6-6"/>',
'building'=>'<path d="M4 21V7l8-4 8 4v14M8 10h2m4 0h2M8 14h2m4 0h2M9 21v-3h6v3"/>',
'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
'check'=>'<path d="m5 12 4 4L19 6"/>',
'menu'=>'<path d="M4 7h16M4 12h16M4 17h16"/>',
'logout'=>'<path d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>',
'filter'=>'<path d="M4 6h16M7 12h10m-7 6h4"/>'
];
return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.($paths[$name]??$paths['home']).'</svg>';
}
function buyer_badge(string $status):string{$v=strtolower($status);$cls=in_array($v,['active','approved','winning','won'],true)?'green':(in_array($v,['upcoming','pending'],true)?'amber':'gray');return '<span class="status '.$cls.'">'.bh(ucfirst($status)).'</span>';}

$account=buyer_one($conn,'SELECT username,email FROM users WHERE id=?',[$userId]);
$username=$account['username']??'Buyer';
$initials=strtoupper(substr(preg_replace('/[^a-z0-9]/i','',$username)?:'HB',0,2));
$tab=$_GET['tab']??'home';
$allowed=['home','browse','my-bids'];
if(!in_array($tab,$allowed,true))$tab='home';
$search=trim((string)($_GET['q']??''));
$stats=buyer_one($conn,"SELECT
 (SELECT COUNT(*) FROM auctions a JOIN properties p ON p.id=a.property_id WHERE p.is_approved=1 AND p.status='Available' AND a.status='active') active_auctions,
 (SELECT COUNT(*) FROM properties WHERE is_approved=1 AND status='Available') available_properties,
 (SELECT COUNT(*) FROM bids WHERE user_id=?) bids_placed,
 (SELECT COALESCE(MAX(bid_amount),0) FROM bids WHERE user_id=?) highest_bid",[$userId,$userId]);
$auctions=buyer_rows($conn,"SELECT a.id auction_id,a.status auction_status,a.starting_bid,a.current_bid,a.start_time,a.end_time,a.property_id,
 p.title,p.location,p.description,p.lot_area,p.floor_area,p.price,p.image_path,
 (SELECT COUNT(*) FROM bids b WHERE b.property_id=p.id) bid_count
 FROM auctions a JOIN properties p ON p.id=a.property_id
 WHERE p.is_approved=1 AND p.status='Available' AND a.status IN ('active','upcoming')
 AND (p.title LIKE ? OR p.location LIKE ? OR p.description LIKE ?)
 ORDER BY CASE WHEN a.status='active' THEN 0 ELSE 1 END,a.end_time ASC LIMIT 12",
 ['%'.$search.'%','%'.$search.'%','%'.$search.'%']);
$myBids=buyer_rows($conn,"SELECT b.id,b.bid_amount,b.status bid_status,b.created_at,p.title,p.location,a.status auction_status,a.current_bid,a.end_time,p.image_path
 FROM bids b JOIN properties p ON p.id=b.property_id
 LEFT JOIN auctions a ON a.property_id=p.id
 WHERE b.user_id=? ORDER BY b.created_at DESC LIMIT 10",[$userId]);
$recentBids=array_slice($myBids,0,4);
$tabTitles=['home'=>'Discover properties','browse'=>'Browse properties','my-bids'=>'My bidding activity'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=bh($tabTitles[$tab])?> | HomeBid</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="styles.css?v=buyer-1"></head><body>
<aside class="sidebar" id="sidebar"><a class="brand" href="dashboard.php"><span class="brand-symbol">⌂</span><span><b>HomeBid</b><small>BUYER PORTAL</small></span></a><div class="side-caption">YOUR SPACE</div><div class="account-mini"><span class="avatar"><?=bh($initials)?></span><span><b><?=bh($username)?></b><small>Buyer account</small></span></div><div class="side-caption">MENU</div><nav><a class="<?=$tab==='home'?'active':''?>" href="dashboard.php"><?=buyer_icon('home')?><span>Overview</span></a><a class="<?=$tab==='browse'?'active':''?>" href="?tab=browse"><?=buyer_icon('search')?><span>Browse properties</span><b><?=number_format((int)$stats['available_properties'])?></b></a><a class="<?=$tab==='my-bids'?'active':''?>" href="?tab=my-bids"><?=buyer_icon('gavel')?><span>My bids</span><b><?=number_format((int)$stats['bids_placed'])?></b></a><a href="../index.php#contact"><?=buyer_icon('bell')?><span>Help & support</span></a></nav><div class="side-bottom"><div class="help-card"><span>Need a hand?</span><p>Our HomeBid team is here to help with your property journey.</p><a href="../index.php#contact">Contact support <?=buyer_icon('arrow',13)?></a></div><div class="side-profile"><span class="avatar"><?=bh($initials)?></span><span><b><?=bh($username)?></b><small><?=bh($account['email']??'')?></small></span><form method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?=bh(csrf_token())?>"><button title="Log out"><?=buyer_icon('logout',17)?></button></form></div></div></aside>
<div class="main"><header class="topbar"><button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><?=buyer_icon('menu')?></button><div class="crumb">Buyer Portal <span>/</span> <b><?=bh($tabTitles[$tab])?></b></div><form class="top-search" method="get"><input type="hidden" name="tab" value="browse"><?=buyer_icon('search',16)?><input name="q" value="<?=bh($search)?>" placeholder="Search by property or location..."><button>Search</button></form><a class="top-help" href="../index.php#contact">Help</a><span class="top-avatar"><?=bh($initials)?></span></header><main class="content">
<?php if($tab==='home'):?>
<section class="hero"><div class="hero-copy"><span class="eyebrow">YOUR NEXT CHAPTER STARTS HERE</span><h1>Find a place<br>to call <em>home.</em></h1><p>Explore verified properties, follow live auctions, and take the next step toward your dream home.</p><a class="btn gold" href="?tab=browse">Explore properties <?=buyer_icon('arrow',15)?></a></div><div class="hero-art"><div class="hero-house"><div class="sun"></div><div class="house-roof"></div><div class="house-body"><span></span><span></span><i></i></div><div class="house-ground"></div></div><div class="hero-note"><span class="note-icon">⌂</span><span><b>Find your perfect place</b><small>New opportunities are waiting</small></span></div></div></section>
<div class="section-heading"><div><span class="eyebrow">YOUR HOMEBID OVERVIEW</span><h2>Welcome back, <?=bh($username)?>!</h2><p>Here’s a quick look at your HomeBid activity.</p></div><a class="text-link" href="?tab=my-bids">View my bids <?=buyer_icon('arrow',14)?></a></div>
<section class="stats"><?php foreach([['Active auctions',(int)$stats['active_auctions'],'Live opportunities','gavel'],['Available properties',(int)$stats['available_properties'],'Approved listings','building'],['Bids placed',(int)$stats['bids_placed'],'Your bidding activity','check'],['Highest bid placed',buyer_money($stats['highest_bid']),'Your top offer','trend']] as [$label,$value,$hint,$icon]):?><article class="stat"><span class="stat-icon"><?=buyer_icon($icon??'home')?></span><small><?=bh($label)?></small><strong><?=is_numeric($value)&&$label!=='Highest bid placed'?number_format((int)$value):bh($value)?></strong><span class="stat-hint"><?=bh($hint)?></span></article><?php endforeach;?></section>
<section class="section-block"><div class="section-heading"><div><span class="eyebrow">HANDPICKED FOR YOU</span><h2>Featured opportunities</h2><p>Explore properties currently available on HomeBid.</p></div><a class="text-link" href="?tab=browse">See all properties <?=buyer_icon('arrow',14)?></a></div><div class="property-grid">
<?php if(!$auctions):?><div class="empty-state">There are no approved auction listings available right now. Please check again soon.</div><?php endif;?>
<?php foreach(array_slice($auctions,0,3) as $p):?><article class="property-card"><div class="property-photo"><?php if(!empty($p['image_path'])):?><img src="<?=bh(str_starts_with($p['image_path'],'http')?$p['image_path']:'../'.ltrim($p['image_path'],'/'))?>" alt="<?=bh($p['title'])?>"><?php else:?><div class="photo-placeholder">⌂</div><?php endif;?><?=buyer_badge($p['auction_status'])?><span class="bid-count"><?=number_format((int)$p['bid_count'])?> bids</span></div><div class="property-info"><span class="location"><?=buyer_icon('home',13)?> <?=bh($p['location'])?></span><h3><?=bh($p['title'])?></h3><div class="specs"><span><?=number_format((float)$p['lot_area'],0)?> m² lot</span><span><?=number_format((float)$p['floor_area'],0)?> m² floor</span></div><div class="price-line"><div><small>Current bid</small><b><?=buyer_money($p['current_bid']??$p['starting_bid'])?></b></div><div><small>Starting bid</small><span><?=buyer_money($p['starting_bid'])?></span></div></div><a class="card-link" href="../index.php#properties">View listing <?=buyer_icon('arrow',14)?></a></div></article><?php endforeach;?></div></section>
<section class="bottom-grid"><article class="activity-panel"><div class="panel-heading"><div><h2>Recent bidding activity</h2><p>Your latest bids on HomeBid</p></div><a href="?tab=my-bids">View all</a></div><?php if(!$recentBids):?><div class="empty-state compact">You haven't placed any bids yet. Browse properties to find your next opportunity.</div><?php else:foreach($recentBids as $b):?><div class="bid-row"><span class="bid-icon"><?=buyer_icon('gavel',16)?></span><span class="bid-detail"><b><?=bh($b['title'])?></b><small><?=bh($b['location'])?> · <?=date('M j, Y',strtotime($b['created_at']))?></small></span><span class="bid-price"><?=buyer_money($b['bid_amount'])?><small><?=buyer_badge($b['bid_status'])?></small></span></div><?php endforeach;endif;?></article><article class="tip-panel"><span class="tip-mark">✦</span><span class="eyebrow">A SMARTER WAY TO BID</span><h2>Ready to find your next home?</h2><p>Review each property's details and auction schedule before placing a bid. Stay informed as the auction progresses.</p><a class="btn dark" href="?tab=browse">Browse all properties <?=buyer_icon('arrow',14)?></a></article></section>
<?php elseif($tab==='browse'):?>
<section class="page-intro"><span class="eyebrow">FIND YOUR NEXT HOME</span><h1>Browse properties</h1><p>Explore approved listings and current auction opportunities.</p></section><form class="filter-bar" method="get"><input type="hidden" name="tab" value="browse"><label><?=buyer_icon('search',16)?><input name="q" value="<?=bh($search)?>" placeholder="Search property name, location, or details"></label><button class="btn gold">Search properties</button><a href="?tab=browse">Clear</a></form><div class="browse-meta"><b><?=count($auctions)?> opportunities</b><span>Showing approved and available listings</span></div><div class="property-grid browse-grid"><?php if(!$auctions):?><div class="empty-state">No matching auction properties found. Try another search.</div><?php endif;?><?php foreach($auctions as $p):?><article class="property-card"><div class="property-photo"><?php if(!empty($p['image_path'])):?><img src="<?=bh(str_starts_with($p['image_path'],'http')?$p['image_path']:'../'.ltrim($p['image_path'],'/'))?>" alt="<?=bh($p['title'])?>"><?php else:?><div class="photo-placeholder">⌂</div><?php endif;?><?=buyer_badge($p['auction_status'])?><span class="bid-count"><?=number_format((int)$p['bid_count'])?> bids</span></div><div class="property-info"><span class="location"><?=buyer_icon('home',13)?> <?=bh($p['location'])?></span><h3><?=bh($p['title'])?></h3><div class="specs"><span><?=number_format((float)$p['lot_area'],0)?> m² lot</span><span><?=number_format((float)$p['floor_area'],0)?> m² floor</span></div><div class="price-line"><div><small>Current bid</small><b><?=buyer_money($p['current_bid']??$p['starting_bid'])?></b></div><div><small>Starting bid</small><span><?=buyer_money($p['starting_bid'])?></span></div></div><a class="card-link" href="../index.php#properties">View listing <?=buyer_icon('arrow',14)?></a></div></article><?php endforeach;?></div>
<?php else:?>
<section class="page-intro"><span class="eyebrow">YOUR ACTIVITY</span><h1>My bidding activity</h1><p>Keep track of the offers you've placed on HomeBid properties.</p></section><article class="activity-panel full"><div class="panel-heading"><div><h2>Bid history</h2><p><?=count($myBids)?> recent records</p></div></div><?php if(!$myBids):?><div class="empty-state">No bids yet. Explore available properties to get started.</div><?php else:?><div class="table-wrap"><table><thead><tr><th>Property</th><th>Your bid</th><th>Current bid</th><th>Auction</th><th>Bid date</th><th>Status</th></tr></thead><tbody><?php foreach($myBids as $b):?><tr><td><b><?=bh($b['title'])?></b><small><?=bh($b['location'])?></small></td><td><?=buyer_money($b['bid_amount'])?></td><td><?=buyer_money($b['current_bid']??0)?></td><td><?=buyer_badge($b['auction_status']??'unknown')?></td><td><?=date('M j, Y g:i a',strtotime($b['created_at']))?></td><td><?=buyer_badge($b['bid_status'])?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></article>
<?php endif;?>
<footer>© <?=date('Y')?> HomeBid. A smarter way to find your next home.</footer></main></div></body></html>