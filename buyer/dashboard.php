<?php
declare(strict_types=1);
require_once __DIR__.'/../config/auth.php';
$userId=require_roles(['Buyer']);
require_once __DIR__.'/../config/db.php';
//aasafsadfsadf
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
'filter'=>'<path d="M4 6h16M7 12h10m-7 6h4"/>',
'trend'=>'<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>'
];
return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.($paths[$name]??$paths['home']).'</svg>';
}
function buyer_badge(string $status):string{$v=strtolower($status);$cls=in_array($v,['active','approved','winning','won'],true)?'green':(in_array($v,['upcoming','pending'],true)?'amber':'gray');return '<span class="status '.$cls.'">'.bh(ucfirst($status)).'</span>';}

$account=buyer_one($conn,'SELECT username,email FROM users WHERE id=?',[$userId]);
$approvedSeller=buyer_one($conn,"SELECT id FROM seller_applications WHERE user_id=? AND status='approved' ORDER BY reviewed_at DESC LIMIT 1",[$userId]);
$username=$account['username']??'Buyer';
$initials=strtoupper(substr(preg_replace('/[^a-z0-9]/i','',$username)?:'HB',0,2));
$tab=$_GET['tab']??'home';
$allowed=['home','browse','my-bids','notifications','activity'];
if(!in_array($tab,$allowed,true))$tab='home';
$search=trim((string)($_GET['q']??''));
$filterRegion=trim((string)($_GET['region']??''));
$filterProvince=trim((string)($_GET['province']??''));
$filterCity=trim((string)($_GET['city']??''));
$allowedPropertyTags=['Furnished','Unfurnished','Gated Community','Near School','Near Hospital','Near Mall','Near Public Transport','With Swimming Pool','With Garden','With Garage','Corner Lot','Newly Renovated','Flood-Free','Pet-Friendly','Ready for Occupancy'];
$filterTags=$_GET['tags']??[];
if(!is_array($filterTags)) $filterTags=[];
$filterTags=array_values(array_unique(array_filter($filterTags,fn($tag)=>is_string($tag)&&in_array($tag,$allowedPropertyTags,true))));
$regionOptions=buyer_rows($conn,"SELECT DISTINCT region FROM properties WHERE is_approved=1 AND review_status='approved' AND region IS NOT NULL AND region<>'' ORDER BY region");
$provinceSql="SELECT DISTINCT province FROM properties WHERE is_approved=1 AND review_status='approved' AND province IS NOT NULL AND province<>''";
$provinceArgs=[];
if($filterRegion!==''){$provinceSql.=" AND region=?";$provinceArgs[]=$filterRegion;}
$provinceOptions=buyer_rows($conn,$provinceSql." ORDER BY province",$provinceArgs);
$citySql="SELECT DISTINCT city FROM properties WHERE is_approved=1 AND review_status='approved' AND city IS NOT NULL AND city<>''";
$cityArgs=[];
if($filterRegion!==''){$citySql.=" AND region=?";$cityArgs[]=$filterRegion;}
if($filterProvince!==''){$citySql.=" AND province=?";$cityArgs[]=$filterProvince;}
$cityOptions=buyer_rows($conn,$citySql." ORDER BY city",$cityArgs);
$browseSql="SELECT a.id auction_id,a.status auction_status,a.starting_bid,a.current_bid,a.start_time,a.end_time,p.id AS property_id,
 p.title,p.location,p.description,p.region,p.province,p.city,p.lot_area,p.floor_area,p.price,p.image_path,
 (SELECT COUNT(*) FROM bids b WHERE b.property_id=p.id) bid_count
 FROM properties p
 LEFT JOIN auctions a ON a.id=(SELECT a2.id FROM auctions a2 WHERE a2.property_id=p.id ORDER BY a2.id DESC LIMIT 1)
 WHERE p.is_approved=1 AND p.review_status='approved'
 AND ((p.status='Available') OR (p.status='Auction' AND a.review_status='approved' AND a.status IN ('active','upcoming')))
 AND (p.title LIKE ? OR p.location LIKE ? OR p.description LIKE ?)";
$browseArgs=['%'.$search.'%','%'.$search.'%','%'.$search.'%'];
if($filterRegion!==''){$browseSql.=" AND p.region=?";$browseArgs[]=$filterRegion;}
if($filterProvince!==''){$browseSql.=" AND p.province=?";$browseArgs[]=$filterProvince;}
if($filterCity!==''){$browseSql.=" AND p.city=?";$browseArgs[]=$filterCity;}
foreach($filterTags as $selectedTag){$browseSql.=" AND EXISTS (SELECT 1 FROM property_tags pt WHERE pt.property_id=p.id AND pt.tag_name=?)";$browseArgs[]=$selectedTag;}
$browseSql.=" ORDER BY CASE WHEN a.status='active' THEN 0 WHEN a.status='upcoming' THEN 1 ELSE 2 END, COALESCE(a.end_time,p.created_at) ASC LIMIT 100";
$auctions=buyer_rows($conn,$browseSql,$browseArgs);
$stats=buyer_one($conn,"SELECT
 (SELECT COUNT(*) FROM auctions a JOIN properties p ON p.id=a.property_id WHERE p.is_approved=1 AND p.review_status='approved' AND a.review_status='approved' AND p.status='Auction' AND a.status='active') active_auctions,
 (SELECT COUNT(*) FROM properties WHERE is_approved=1 AND review_status='approved' AND status='Available') available_properties,
 (SELECT COUNT(*) FROM bids WHERE user_id=?) bids_placed,
 (SELECT COALESCE(MAX(bid_amount),0) FROM bids WHERE user_id=?) highest_bid",[$userId,$userId]);
$myBids=buyer_rows($conn,"SELECT b.id,b.bid_amount,b.status bid_status,b.created_at,p.title,p.location,a.id auction_id,a.highest_bidder_id,a.status auction_status,a.current_bid,a.end_time,p.image_path,
  EXISTS(SELECT 1 FROM checkout_payments cp WHERE cp.auction_id=a.id AND cp.user_id=b.user_id AND cp.status='paid') AS auction_paid,
  (SELECT cp.id FROM checkout_payments cp WHERE cp.auction_id=a.id AND cp.user_id=b.user_id AND cp.status='paid' ORDER BY cp.id DESC LIMIT 1) AS auction_payment_id
  FROM bids b JOIN properties p ON p.id=b.property_id
  LEFT JOIN auctions a ON a.id=(SELECT a2.id FROM auctions a2 WHERE a2.property_id=p.id ORDER BY a2.id DESC LIMIT 1)
  WHERE b.user_id=? ORDER BY b.created_at DESC LIMIT 100",[$userId]);
$recentBids=array_slice($myBids,0,4);
$activityLog=buyer_rows($conn,"SELECT activity,entity_type,record_id,created_at FROM (
 SELECT action activity,entity_type,record_id,created_at FROM activity_logs WHERE user_id=?
 UNION ALL
 SELECT CONCAT('Placed a bid of ₱',FORMAT(b.bid_amount,2),' on ',p.title),'Bid',b.id,b.created_at
 FROM bids b JOIN properties p ON p.id=b.property_id WHERE b.user_id=?
 UNION ALL
 SELECT CONCAT('Created a reservation for ',p.title,' (',r.status,')'),'Reservation',r.id,r.created_at
 FROM reservations r JOIN properties p ON p.id=r.property_id WHERE r.user_id=?
 UNION ALL
 SELECT CONCAT('Requested a property viewing for ',p.title,' (',v.status,')'),'Viewing',v.id,v.created_at
 FROM viewing_bookings v JOIN properties p ON p.id=v.property_id WHERE v.user_id=?
 UNION ALL
 SELECT CONCAT('Booked a property visit for ',p.title,' (',b.status,')'),'Booking',b.id,b.created_at
 FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.user_id=?
 UNION ALL
 SELECT CONCAT('Sent an inquiry: ',i.subject),'Inquiry',i.id,i.created_at
 FROM inquiries i WHERE i.user_id=?
 UNION ALL
 SELECT CONCAT('Payment ',pay.status,' for ',p.title,' — ₱',FORMAT(pay.amount,2)),'Payment',pay.id,pay.created_at
 FROM checkout_payments pay JOIN properties p ON p.id=pay.property_id WHERE pay.user_id=?
 UNION ALL
 SELECT CONCAT(n.title,': ',n.message),'Notification',n.id,n.created_at
 FROM notifications n WHERE n.user_id=?
) buyer_activity ORDER BY created_at DESC LIMIT 40",
 [$userId,$userId,$userId,$userId,$userId,$userId,$userId,$userId]);
$notifications=buyer_rows($conn,'SELECT id,title,message,is_read,related_entity,related_id,created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50',[$userId]);
$tabTitles=['home'=>'Discover properties','browse'=>'Browse properties','my-bids'=>'My bidding activity','notifications'=>'Notifications','activity'=>'Activity log'];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=bh($tabTitles[$tab])?> | HomeBid</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=buyer-filters-fixed-20261117">
</head>

<body>
    <aside class="sidebar" id="sidebar"><a class="brand" href="dashboard.php"><span
                class="brand-symbol">⌂</span><span><b>HomeBid</b><small>BUYER PORTAL</small></span></a>
        <div class="side-caption">YOUR SPACE</div>
        <div class="account-mini"><span
                class="avatar"><?=bh($initials)?></span><span><b><?=bh($username)?></b><small>Buyer
                    account</small></span></div>
        <div class="side-caption">MENU</div>
        <nav><a class="<?=$tab==='home'?'active':''?>"
                href="dashboard.php"><?=buyer_icon('home')?><span>Overview</span></a><a
                class="<?=$tab==='browse'?'active':''?>" href="?tab=browse"><?=buyer_icon('search')?><span>Browse
                    properties</span><b><?=number_format((int)$stats['available_properties'])?></b></a><a
                class="<?=$tab==='my-bids'?'active':''?>" href="?tab=my-bids"><?=buyer_icon('gavel')?><span>My
                    bids</span><b><?=number_format((int)$stats['bids_placed'])?></b></a><a class="<?=$tab==='notifications'?'active':''?>" href="?tab=notifications"><?=buyer_icon('bell')?><span>Notifications</span><?php $unreadCount=count(array_filter($notifications,fn($n)=>(int)$n['is_read']===0));if($unreadCount):?><b><?=number_format($unreadCount)?></b><?php endif;?></a><a class="<?=$tab==='activity'?'active':''?>" href="?tab=activity"><?=buyer_icon('clock')?><span>Activity log</span></a><a href="profile.php"><?=buyer_icon('home')?><span>Profile &amp; settings</span></a><a href="seller-application.php"><?=buyer_icon('building')?><span>Become a seller</span></a><a
                href="../index.php#contact"><?=buyer_icon('bell')?><span>Help &amp; support</span></a></nav>
        <div class="side-bottom">
            <div class="help-card"><span>Need a hand?</span>
                <p>Our HomeBid team is here to help with your property journey.</p><a
                    href="../index.php#contact">Contact support <?=buyer_icon('arrow',13)?></a>
            </div>
            <div class="side-profile"><span
                    class="avatar"><?=bh($initials)?></span><span><b><?=bh($username)?></b><small><?=bh($account['email']??'')?></small></span>
                <form method="post" action="../logout.php"><input type="hidden" name="csrf_token"
                        value="<?=bh(csrf_token())?>"><button title="Log out"><?=buyer_icon('logout',17)?></button>
                </form>
            </div>
        </div>
    </aside>
    <div class="main">
        <header class="topbar"><button class="menu-toggle"
                onclick="document.getElementById('sidebar').classList.toggle('open')"><?=buyer_icon('menu')?></button>
            <div class="crumb">Buyer Portal <span>/</span> <b><?=bh($tabTitles[$tab])?></b></div>
            <form class="top-search" method="get"><input type="hidden" name="tab"
                    value="browse"><?=buyer_icon('search',16)?><input name="q" value="<?=bh($search)?>"
                    placeholder="Search by property or location..."><button>Search</button></form><?php if($approvedSeller):?><form method="post" action="../config/switch_account_mode.php" style="display:inline-flex;margin:0"><input type="hidden" name="csrf_token" value="<?=bh(csrf_token())?>"><input type="hidden" name="mode" value="Client"><button type="submit" style="border:1px solid #d6a94c;background:#fff8e8;color:#765316;border-radius:8px;padding:9px 12px;font-weight:700;cursor:pointer">Switch to Seller</button></form><?php endif;?><a class="top-help"
                href="../index.php#contact">Help</a><span class="top-avatar"><?=bh($initials)?></span>
        </header>
        <main class="content">
            <?php if($tab==='home'):?>
            <section class="hero">
                <div class="hero-copy"><span class="eyebrow">YOUR NEXT CHAPTER STARTS HERE</span>
                    <h1>Find a place<br>to call <em>home.</em></h1>
                    <p>Explore verified properties, follow live auctions, and take the next step toward your dream home.
                    </p><div class="hero-actions"><a class="btn gold" href="?tab=browse">Explore properties <?=buyer_icon('arrow',15)?></a><a class="btn" href="seller-application.php">Become a seller <?=buyer_icon('arrow',15)?></a></div>
                </div>
                <div class="hero-art">
                    <div class="hero-house">
                        <div class="sun"></div>
                        <div class="house-roof"></div>
                        <div class="house-body"><span></span><span></span><i></i></div>
                        <div class="house-ground"></div>
                    </div>
                    <div class="hero-note"><span class="note-icon">⌂</span><span><b>Find your perfect
                                place</b><small>New opportunities are waiting</small></span></div>
                </div>
            </section>
            <div class="section-heading">
                <div><span class="eyebrow">YOUR HOMEBID OVERVIEW</span>
                    <h2>Welcome back, <?=bh($username)?>!</h2>
                    <p>Here’s a quick look at your HomeBid activity.</p>
                </div><a class="text-link" href="?tab=my-bids">View my bids <?=buyer_icon('arrow',14)?></a>
            </div>
            <section class="stats">
                <?php foreach([['Active auctions',(int)$stats['active_auctions'],'Live opportunities','gavel'],['Available properties',(int)$stats['available_properties'],'Approved listings','building'],['Bids placed',(int)$stats['bids_placed'],'Your bidding activity','check'],['Highest bid placed',buyer_money($stats['highest_bid']),'Your top offer','trend']] as [$label,$value,$hint,$icon]):?>
                <article class="stat"><span
                        class="stat-icon"><?=buyer_icon($icon??'home')?></span><small><?=bh($label)?></small><strong><?=is_numeric($value)&&$label!=='Highest bid placed'?number_format((int)$value):bh($value)?></strong><span
                        class="stat-hint"><?=bh($hint)?></span></article><?php endforeach;?></section>
            <section class="section-block">
                <div class="section-heading">
                    <div><span class="eyebrow">HANDPICKED FOR YOU</span>
                        <h2>Featured opportunities</h2>
                        <p>Explore approved property listings and live auction opportunities on HomeBid.</p>
                    </div><a class="text-link" href="?tab=browse">See all properties <?=buyer_icon('arrow',14)?></a>
                </div>
                <div class="property-grid">
                    <?php if(!$auctions):?><div class="empty-state">There are no approved properties available right now. Please check again soon.</div><?php endif;?>
                    <?php foreach(array_slice($auctions,0,3) as $p):?><article class="property-card">
                        <div class="property-photo"><?php if(!empty($p['image_path'])):?><img
                                src="<?=bh(str_starts_with($p['image_path'],'http')?$p['image_path']:'../'.ltrim($p['image_path'],'/'))?>"
                                alt="<?=bh($p['title'])?>"><?php else:?><div class="photo-placeholder">⌂</div>
                            <?php endif;?><?=!empty($p['auction_id'])?buyer_badge($p['auction_status']):buyer_badge('Available')?><span
                                class="bid-count"><?=number_format((int)$p['bid_count'])?> bids</span></div>
                        <div class="property-info"><span class="location"><?=buyer_icon('home',13)?>
                                <?=bh($p['location'])?></span>
                            <h3><?=bh($p['title'])?></h3>
                            <div class="specs"><span><?=number_format((float)$p['lot_area'],0)?> m²
                                    lot</span><span><?=number_format((float)$p['floor_area'],0)?> m² floor</span></div>
                            <div class="price-line">
                                <div><small><?=!empty($p['auction_id'])?'Current bid':'Asking price'?></small><b><?=buyer_money(!empty($p['auction_id'])?($p['current_bid']?:$p['starting_bid']):$p['price'])?></b></div>
                                <?php if(!empty($p['auction_id'])):?><div><small>Starting bid</small><span><?=buyer_money($p['starting_bid'])?></span></div><?php endif;?>
                            </div><a class="card-link" href="../property-view.php?id=<?= (int)$p['property_id'] ?>">View listing
                                <?=buyer_icon('arrow',14)?></a>
                        </div>
                    </article><?php endforeach;?></div>
            </section>
            <section class="bottom-grid">
                <article class="activity-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Recent bidding activity</h2>
                            <p>Your latest bids on HomeBid</p>
                        </div><a href="?tab=my-bids">View all</a>
                    </div><?php if(!$recentBids):?><div class="empty-state compact">You haven't placed any bids yet.
                        Browse properties to find your next opportunity.</div><?php else:foreach($recentBids as $b):?>
                    <div class="bid-row"><span class="bid-icon"><?=buyer_icon('gavel',16)?></span><span
                            class="bid-detail"><b><?=bh($b['title'])?></b><small><?=bh($b['location'])?> ·
                                <?=date('M j, Y',strtotime($b['created_at']))?></small></span><span
                            class="bid-price"><?=buyer_money($b['bid_amount'])?><small><?=buyer_badge($b['bid_status'])?></small></span>
                    </div><?php endforeach;endif;?>
                </article>
                <article class="tip-panel"><span class="tip-mark">✦</span><span class="eyebrow">A SMARTER WAY TO
                        BID</span>
                    <h2>Ready to find your next home?</h2>
                    <p>Review each property's details and auction schedule before placing a bid. Stay informed as the
                        auction progresses.</p><a class="btn dark" href="?tab=browse">Browse all properties
                        <?=buyer_icon('arrow',14)?></a>
                </article>
            </section>
            <?php elseif($tab==='browse'):?>
            <section class="page-intro"><span class="eyebrow">FIND YOUR NEXT HOME</span>
                <h1>Browse properties</h1>
                <p>Explore approved listings and current auction opportunities.</p>
            </section>
            <form class="filter-bar browse-filter-form" method="get"><input type="hidden" name="tab" value="browse">
                <label class="browse-search"><?=buyer_icon('search',16)?><input name="q" value="<?=bh($search)?>" placeholder="Search property name, location, or details"></label>
                <label>Region<select name="region" id="browseRegion"><option value="">All regions</option><?php foreach($regionOptions as $option):?><option value="<?=bh($option['region'])?>" <?=$filterRegion===$option['region']?'selected':''?>><?=bh($option['region'])?></option><?php endforeach;?></select></label>
                <label>Province<select name="province" id="browseProvince"><option value="">All provinces</option><?php foreach($provinceOptions as $option):?><option value="<?=bh($option['province'])?>" <?=$filterProvince===$option['province']?'selected':''?>><?=bh($option['province'])?></option><?php endforeach;?></select></label>
                <label>City / Municipality<select name="city"><option value="">All cities</option><?php foreach($cityOptions as $option):?><option value="<?=bh($option['city'])?>" <?=$filterCity===$option['city']?'selected':''?>><?=bh($option['city'])?></option><?php endforeach;?></select></label>
                <fieldset class="browse-tag-filter"><legend>Property tags</legend><div class="browse-tag-options"><?php foreach($allowedPropertyTags as $tagOption):?><label><input type="checkbox" name="tags[]" value="<?=bh($tagOption)?>" <?=in_array($tagOption,$filterTags,true)?'checked':''?>><span><?=bh($tagOption)?></span></label><?php endforeach;?></div></fieldset>
                <div class="browse-filter-actions"><button class="btn gold" type="submit">Apply filters</button><a href="?tab=browse">Clear filters</a></div>
            </form>
            <div class="browse-meta"><b><?=count($auctions)?> opportunities</b><span>Showing approved listings matching your filters</span></div>
            <div class="property-grid browse-grid"><?php if(!$auctions):?><div class="empty-state">No matching approved properties found. Try another search.</div><?php endif;?><?php foreach($auctions as $p):?><article
                    class="property-card">
                    <div class="property-photo"><?php if(!empty($p['image_path'])):?><img
                            src="<?=bh(str_starts_with($p['image_path'],'http')?$p['image_path']:'../'.ltrim($p['image_path'],'/'))?>"
                            alt="<?=bh($p['title'])?>"><?php else:?><div class="photo-placeholder">⌂</div>
                        <?php endif;?><?=!empty($p['auction_id'])?buyer_badge($p['auction_status']):buyer_badge('Available')?><span
                            class="bid-count"><?=number_format((int)$p['bid_count'])?> bids</span></div>
                    <div class="property-info"><span class="location"><?=buyer_icon('home',13)?>
                            <?=bh($p['location'])?></span>
                        <h3><?=bh($p['title'])?></h3>
                        <div class="specs"><span><?=number_format((float)$p['lot_area'],0)?> m²
                                lot</span><span><?=number_format((float)$p['floor_area'],0)?> m² floor</span></div>
                        <div class="price-line">
                            <div><small><?=!empty($p['auction_id'])?'Current bid':'Asking price'?></small><b><?=buyer_money(!empty($p['auction_id'])?($p['current_bid']?:$p['starting_bid']):$p['price'])?></b></div>
                            <?php if(!empty($p['auction_id'])):?><div><small>Starting bid</small><span><?=buyer_money($p['starting_bid'])?></span></div><?php endif;?>
                        </div><a class="card-link" href="../property-view.php?id=<?= (int)$p['property_id'] ?>">View listing
                            <?=buyer_icon('arrow',14)?></a>
                    </div>
                </article><?php endforeach;?></div>
            <?php elseif($tab==='my-bids'):?>
            <section class="page-intro"><span class="eyebrow">YOUR ACTIVITY</span>
                <h1>My bidding activity</h1>
                <p>Keep track of the offers you've placed on HomeBid properties.</p>
            </section>
            <article class="activity-panel full">
                <div class="panel-heading">
                    <div>
                        <h2>Bid history</h2>
                        <p><?=count($myBids)?> records shown (latest 100)</p>
                    </div>
                </div><?php if(!$myBids):?><div class="empty-state">No bids yet. Explore available properties to get
                    started.</div><?php else:?><div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Your bid</th>
                                <th>Current bid</th>
                                <th>Auction</th>
                                <th>Bid date</th>
                                <th>Status</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach($myBids as $b):?><tr>
                                <td><b><?=bh($b['title'])?></b><small><?=bh($b['location'])?></small></td>
                                <td><?=buyer_money($b['bid_amount'])?></td>
                                <td><?=buyer_money($b['current_bid']??0)?></td>
                                <td><?=buyer_badge($b['auction_status']??'unknown')?></td>
                                <td><?=date('M j, Y g:i a',strtotime($b['created_at']))?></td>
                                <td><?=buyer_badge($b['bid_status'])?></td>
                                <td><?php if ((int)($b['highest_bidder_id'] ?? 0) === $userId && empty($b['auction_paid']) && !empty($b['auction_id']) && (strtolower((string)$b['auction_status']) === 'ended' || (!empty($b['end_time']) && strtotime($b['end_time']) <= time()))): ?>
                                    <form method="post" action="../checkout.php" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=bh(csrf_token())?>"><input type="hidden" name="payment_type" value="auction"><input type="hidden" name="auction_id" value="<?=(int)$b['auction_id']?>"><label>Method <select name="payment_method" required><option value="GCash">GCash</option><option value="Bank transfer">Bank transfer</option></select></label><label>Proof <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" required></label><button class="btn gold" type="submit">Submit payment proof</button></form>
                                    <?php elseif (!empty($b['auction_paid'])): ?><span class="status green">Paid</span> <a class="btn gold" target="_blank" rel="noopener" href="../receipt/auction-receipt.php?payment_id=<?=(int)$b['auction_payment_id']?>">View receipt</a><?php else: ?><span class="muted">—</span><?php endif; ?></td>
                            </tr><?php endforeach;?></tbody>
                    </table>
                </div><?php endif;?>
            </article>
            <?php elseif($tab==='notifications'):?>
            <section class="page-intro"><span class="eyebrow">YOUR HOMEBID UPDATES</span>
                <h1>Notifications</h1>
                <p>Get updates when another buyer outbids you or when your account activity changes.</p>
            </section>
            <article class="activity-panel full">
                <div class="panel-heading"><div><h2>Recent notifications</h2><p><?=count($notifications)?> latest updates</p></div></div>
                <?php if(!$notifications):?><div class="empty-state">You're all caught up. Bid updates will appear here.</div><?php else:?>
                    <div class="timeline"><?php foreach($notifications as $notification):?>
                        <div class="timeline-item">
                            <span class="timeline-dot"><?=buyer_icon('bell',16)?></span>
                            <div class="timeline-copy"><b><?=bh($notification['title'])?></b><small><?=bh($notification['message'])?></small></div>
                            <time><?=date('M j, Y · g:i a',strtotime($notification['created_at']))?><?=empty($notification['is_read'])?' · New':''?></time>
                        </div>
                    <?php endforeach;?></div>
                <?php endif;?>
            </article>
            <?php elseif($tab==='activity'):?>
            <?php $paidReservations=buyer_rows($conn,"SELECT cp.id payment_id,cp.amount,cp.created_at,p.title FROM checkout_payments cp JOIN properties p ON p.id=cp.property_id WHERE cp.user_id=? AND cp.payment_type='reservation' AND cp.status='paid' ORDER BY cp.created_at DESC LIMIT 50",[$userId]); ?>
            <article class="activity-panel full">
                <div class="panel-heading"><div><h2>Property reservation receipts</h2><p>Receipts for completed reservation payments</p></div></div>
                <?php if(!$paidReservations):?><div class="empty-state">No paid property reservations yet.</div><?php else:?>
                    <div class="timeline"><?php foreach($paidReservations as $paidReservation):?>
                        <div class="timeline-item"><span class="timeline-dot"><?=buyer_icon('clock',16)?></span>
                            <div class="timeline-copy"><b><?=bh($paidReservation['title'])?></b><small><?=buyer_money($paidReservation['amount'])?> · Paid reservation</small></div>
                            <time><?=date('M j, Y',strtotime($paidReservation['created_at']))?> · <a target="_blank" rel="noopener" href="../receipt/property-receipt.php?payment_id=<?=(int)$paidReservation['payment_id']?>">View receipt</a></time>
                        </div>
                    <?php endforeach;?></div>
                <?php endif;?>
            </article>

            <section class="page-intro"><span class="eyebrow">YOUR HOMEBID HISTORY</span>
                <h1>Activity log</h1>
                <p>A timeline of your bids, reservations, property visits, inquiries, payments, and account updates.</p>
            </section>
            <article class="activity-panel full">
                <div class="panel-heading"><div><h2>Recent activity</h2><p><?=count($activityLog)?> latest events</p></div>
                    <a href="?tab=my-bids">View bid history</a></div>
                <?php if(!$activityLog):?>
                    <div class="empty-state">No activity has been recorded yet. Your HomeBid actions will appear here.</div>
                <?php else:?>
                    <div class="timeline">
                    <?php foreach($activityLog as $event):?>
                        <div class="timeline-item">
                            <span class="timeline-dot"><?=buyer_icon(strtolower($event['entity_type'])==='bid'?'gavel':(strtolower($event['entity_type'])==='notification'?'bell':'clock'),16)?></span>
                            <div class="timeline-copy"><b><?=bh($event['activity'])?></b>
                                <small><?=bh(ucfirst($event['entity_type']))?><?=!empty($event['record_id'])?' #'.(int)$event['record_id']:''?></small>
                            </div>
                            <time><?=date('M j, Y · g:i a',strtotime($event['created_at']))?></time>
                        </div>
                    <?php endforeach;?>
                    </div>
                <?php endif;?>
            </article>
            <?php endif;?>
            <footer>© <?=date('Y')?> HomeBid. A smarter way to find your next home.</footer>
        </main>
    <script>
    const browseRegion = document.getElementById('browseRegion');
    const browseProvince = document.getElementById('browseProvince');
    if (browseRegion && browseProvince) browseRegion.addEventListener('change', () => {
        browseProvince.value = '';
        const citySelect = document.querySelector('select[name="city"]');
        if (citySelect) citySelect.value = '';
    });
    </script>
    </div>
</body>

</html>