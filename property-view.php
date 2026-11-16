<?php
declare(strict_types=1);
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

function property_h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
function property_money($value): string {
    return '₱' . number_format((float)$value, 2);
}
function property_image_url($path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    return ltrim($path, '/');
}

$propertyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$propertyId || $propertyId < 1) {
    http_response_code(404);
    exit('Property not found. <a href="index.php">Return to HomeBid</a>');
}

try {
    $result = hb_query($conn, "
        SELECT p.id,p.title,p.description,p.category,p.location,p.region,p.province,p.city,p.barangay,
               p.lot_number,p.lot_area,p.floor_area,p.price,p.status,p.image_path,p.created_at,
               a.id AS auction_id,a.starting_bid,a.current_bid,a.start_time,a.end_time,a.status AS auction_status
        FROM properties p
        LEFT JOIN auctions a ON a.id=(
            SELECT a2.id FROM auctions a2 WHERE a2.property_id=p.id ORDER BY a2.id DESC LIMIT 1
        )
        WHERE p.id=? AND p.is_approved=1 AND p.review_status='approved'
          AND (p.status='Available' OR (p.status='Auction' AND a.review_status='approved'
               AND a.status IN ('active','upcoming','ended'))
               OR (p.status='Reserved' AND EXISTS (
                   SELECT 1 FROM reservations r WHERE r.property_id=p.id AND r.user_id=? AND r.status='Approved'
               )))
        LIMIT 1
    ", [$propertyId, (int)($_SESSION['user_id'] ?? 0)]);
    $property = hb_fetch_one($result);
} catch (Throwable $error) {
    error_log('HomeBid property detail lookup failed.');
    http_response_code(500);
    exit('We could not load this property right now. Please try again later.');
}
if (!$property) {
    http_response_code(404);
    exit('This property is unavailable or has not been approved. <a href="index.php">Browse HomeBid</a>');
}

$bidMessage = '';
$bidMessageType = 'error';
if (isset($_GET['payment']) && $_GET['payment'] === 'success') {
    $paidReservation = false;
    if (!empty($_SESSION['user_id'])) {
        try {
            $paidReservation = (bool) hb_fetch_one(hb_query($conn,
                "SELECT id FROM checkout_payments WHERE payment_type='reservation' AND property_id=? AND user_id=? AND status='paid' ORDER BY id DESC LIMIT 1",
                [$propertyId, (int) $_SESSION['user_id']]));
        } catch (Throwable $error) {
            error_log('HomeBid reservation confirmation lookup failed.');
        }
    }
    $bidMessage = $paidReservation
        ? 'Your reservation payment was received and confirmed. The property is now reserved for your account.'
        : 'Xendit returned you to HomeBid. We are waiting for payment confirmation. Refresh this page in a moment.';
    $bidMessageType = $paidReservation ? 'success' : 'error';
} elseif (isset($_GET['payment']) && $_GET['payment'] === 'failed') {
    $bidMessage = 'Your payment was not completed. You can try again while the property remains available.';
    $bidMessageType = 'error';
}
if (isset($_GET['bid']) && $_GET['bid'] === 'success') {
    $bidMessage = 'Your bid was submitted successfully.';
    $bidMessageType = 'success';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place_bid') {
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Buyer') {
        http_response_code(403);
        $bidMessage = 'Please sign in with a buyer account to place a bid.';
    } else {
        verify_csrf();
        $bidAmount = filter_var($_POST['bid_amount'] ?? null, FILTER_VALIDATE_FLOAT);
        $buyerId = (int)$_SESSION['user_id'];
        try {
            if (empty($property['auction_id'])) {
                throw new RuntimeException('This property is not currently listed for auction.');
            }
            mysqli_begin_transaction($conn);
            $auctionId = (int)$property['auction_id'];

            // Keep auction status synchronized with database time before accepting any bid.
            hb_query($conn, "UPDATE auctions
                SET status='active'
                WHERE id=? AND status='upcoming'
                  AND review_status='approved'
                  AND start_time<=NOW() AND end_time>NOW()", [$auctionId]);
            hb_query($conn, "UPDATE auctions
                SET status='ended'
                WHERE id=? AND status IN ('upcoming','active')
                  AND end_time<=NOW()", [$auctionId]);

            $auctionResult = hb_query($conn, "SELECT a.*,p.user_id AS seller_id,p.title,
                    (a.start_time<=NOW()) AS has_started,
                    (a.end_time>NOW()) AS has_not_ended
                FROM auctions a JOIN properties p ON p.id=a.property_id
                WHERE a.id=? AND a.property_id=? AND p.is_approved=1 AND p.review_status='approved'
                  AND a.review_status='approved' FOR UPDATE", [$auctionId, $propertyId]);
            $auction = hb_fetch_one($auctionResult);
            if (!$auction) throw new RuntimeException('This auction is no longer available.');
            if ($auction['status'] !== 'active' || !(int)$auction['has_started'] || !(int)$auction['has_not_ended']) {
                throw new RuntimeException('Bidding has closed or is not open yet. You can no longer submit a bid for this auction.');
            }
            $minimumBid = max((float)$auction['starting_bid'], (float)$auction['current_bid'] + 1);
            if ($bidAmount === false || $bidAmount <= (float)$auction['current_bid'] || $bidAmount < (float)$auction['starting_bid']) {
                throw new RuntimeException('Your bid must be at least ₱' . number_format($minimumBid, 2) . '.');
            }
            if ((int)$auction['seller_id'] === $buyerId) throw new RuntimeException('You cannot bid on your own property.');
            hb_query($conn, "INSERT INTO bids (property_id,user_id,bid_amount,status) VALUES (?,?,?,'Pending')",
                [$propertyId, $buyerId, number_format((float)$bidAmount, 2, '.', '')]);
            hb_query($conn, "UPDATE auctions SET current_bid=?,highest_bidder_id=? WHERE id=?",
                [number_format((float)$bidAmount, 2, '.', ''), $buyerId, (int)$auction['id']]);
            hb_query($conn, "INSERT INTO activity_logs (user_id,action,entity_type,record_id,ip_address)
                VALUES (?,?,?,?,?)", [$buyerId, 'Placed a bid of ₱' . number_format((float)$bidAmount, 2) . ' on ' . $auction['title'],
                'bid', (int)mysqli_insert_id($conn), $_SERVER['REMOTE_ADDR'] ?? null]);
            mysqli_commit($conn);
            header('Location: property-view.php?id=' . $propertyId . '&bid=success');
            exit;
        } catch (RuntimeException $error) {
            mysqli_rollback($conn);
            $bidMessage = $error->getMessage();
        } catch (Throwable $error) {
            mysqli_rollback($conn);
            error_log('HomeBid bid submission failed: ' . $error->getMessage());
            $bidMessage = 'We could not submit your bid. Please try again.';
        }
    }
}

$gallery = [];
if (!empty($property['image_path'])) $gallery[] = $property['image_path'];
try {
    $imageRows = hb_fetch_all(hb_query($conn,
        'SELECT image_path FROM property_images WHERE property_id=? ORDER BY id ASC', [$propertyId]));
    foreach ($imageRows as $imageRow) {
        if (!empty($imageRow['image_path']) && !in_array($imageRow['image_path'], $gallery, true)) {
            $gallery[] = $imageRow['image_path'];
        }
    }
} catch (Throwable $error) {
    error_log('HomeBid property gallery lookup failed.');
}
$tags = [];
try {
    $tagRows = hb_fetch_all(hb_query($conn,
        'SELECT tag_name FROM property_tags WHERE property_id=? ORDER BY tag_name ASC', [$propertyId]));
    $tags = array_column($tagRows, 'tag_name');
} catch (Throwable $error) {
    error_log('HomeBid property tags lookup failed.');
}

$isLoggedIn = !empty($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
$buyerLink = $isLoggedIn && $role === 'Buyer' ? 'buyer/dashboard.php' : 'index.php?login=required';
$displayPrice = !empty($property['auction_id'])
    ? ((float)$property['current_bid'] > 0 ? $property['current_bid'] : $property['starting_bid'])
    : $property['price'];
$priceLabel = !empty($property['auction_id']) ? 'Current bid' : 'Asking price';
$addressParts = array_filter([
    $property['lot_number'] ? 'Lot/House No. ' . $property['lot_number'] : '',
    $property['barangay'] ?? '', $property['city'] ?? '', $property['province'] ?? '',
    $property['region'] ?? ''
]);
$fullAddress = $addressParts ? implode(', ', $addressParts) : ($property['location'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=property_h($property['title'])?> | HomeBid</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--navy:#101d3a;--gold:#d99020;--ink:#172033;--muted:#718096;--line:#e8edf3;--paper:#f7f8fa}
*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:'DM Sans',sans-serif}.top{background:#fff;border-bottom:1px solid var(--line)}.nav{max-width:1160px;margin:auto;padding:17px 24px;display:flex;align-items:center;justify-content:space-between}.brand{font:800 22px Manrope,sans-serif;color:var(--navy);text-decoration:none}.brand span{display:inline-grid;place-items:center;width:34px;height:34px;border-radius:10px;background:var(--gold);color:white;margin-right:8px}.nav a.back{color:var(--navy);text-decoration:none;font-weight:700;font-size:13px}.wrap{max-width:1160px;margin:34px auto;padding:0 24px 60px}.crumb{font-size:12px;color:var(--muted);margin-bottom:12px}.crumb a{color:var(--muted)}h1,h2,h3{font-family:Manrope,sans-serif}h1{font-size:clamp(27px,4vw,40px);margin:8px 0}.place{color:var(--muted);margin:0 0 24px}.layout{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(280px,.75fr);gap:26px;align-items:start}.main-card,.side-card{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden}.hero-image{width:100%;height:410px;object-fit:cover;display:block;background:#e9edf2}.gallery{display:grid;grid-template-columns:repeat(4,1fr);gap:9px;padding:12px}.gallery img{width:100%;height:100px;object-fit:cover;border-radius:9px;background:#edf0f4;cursor:pointer}.content{padding:26px}.pills{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:15px}.pill{font-size:11px;font-weight:700;padding:7px 11px;border-radius:30px;background:#edf2f7;color:#475569}.pill.gold{background:#fff3dc;color:#9a5b08}.specs{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:22px 0}.spec{background:#f7f8fa;padding:15px;border-radius:10px}.spec small{display:block;color:var(--muted);font-size:11px}.spec b{display:block;margin-top:5px;font-size:17px}.content h2{font-size:19px;margin:25px 0 10px}.description{white-space:pre-line;line-height:1.8;color:#536174;font-size:14px}.tags{display:flex;gap:8px;flex-wrap:wrap}.tag{padding:7px 10px;border:1px solid var(--line);border-radius:20px;font-size:11px;color:#536174}.side-card{padding:24px;position:sticky;top:20px}.eyebrow{font-size:10px;font-weight:800;letter-spacing:.13em;color:#a86b13}.price{font:800 30px Manrope,sans-serif;margin:7px 0}.muted{color:var(--muted);font-size:12px;line-height:1.6}.rule{height:1px;background:var(--line);margin:20px 0}.info{display:flex;justify-content:space-between;gap:15px;margin:13px 0;font-size:13px}.info span{color:var(--muted)}.info b{text-align:right}.btn{display:block;text-align:center;text-decoration:none;padding:14px;border-radius:9px;background:var(--gold);color:white;font-weight:800;font-size:13px;margin-top:18px}.btn.secondary{background:var(--navy)}.notice{font-size:11px;line-height:1.6;color:var(--muted);text-align:center;margin-top:13px}.empty{height:300px;display:grid;place-items:center;background:#e9edf2;color:#8b98a9;font-size:54px}@media(max-width:800px){.layout{grid-template-columns:1fr}.side-card{position:static}.hero-image{height:320px}}@media(max-width:520px){.wrap{padding:0 14px 40px;margin:22px auto}.nav{padding:14px}.hero-image{height:240px}.gallery{grid-template-columns:repeat(3,1fr)}.gallery img{height:78px}.content{padding:20px}.specs{grid-template-columns:1fr 1fr}.price{font-size:26px}}

/* HomeBid minimal property detail refresh */
:root{--navy:#263b32;--gold:#8da394;--ink:#26343d;--muted:#73808a;--line:#e7ebe8;--paper:#f7f8f7}
body{background:#f7f8f7;color:#26343d;font-family:'DM Sans',sans-serif;-webkit-font-smoothing:antialiased}
.top{background:#ffffffed;backdrop-filter:blur(12px);border-bottom:1px solid #e7ebe8}
.nav{max-width:1200px;padding:14px clamp(16px,3vw,28px)}
.brand{color:#263b32;font-size:21px}.brand span{background:#eaf0eb;color:#354d3e;border-radius:10px}
.wrap{max-width:1200px;margin:28px auto;padding:0 clamp(15px,3vw,28px) 55px}
.crumb{color:#73808a}
.property-layout,.detail-grid{gap:24px}
.gallery,.detail-card,.info-card,.booking-card,.content-card{border:1px solid #e7ebe8;border-radius:15px;box-shadow:0 5px 22px #1d352508}
button,.btn,a.button{border-radius:9px}
input,select,textarea{border:1px solid #dfe5e1;border-radius:9px;min-height:42px}
@media(max-width:760px){.nav{padding:12px 16px}.wrap{margin:18px auto;padding:0 14px 35px}.property-layout,.detail-grid{grid-template-columns:1fr!important}.gallery img{max-height:360px;object-fit:cover}}
@media(max-width:480px){.wrap{padding:0 12px 28px}.gallery,.detail-card,.info-card,.booking-card,.content-card{border-radius:12px}.nav{gap:12px}}
</style>
</head>
<body>
<header class="top"><div class="nav"><a class="brand" href="index.php"><span>⌂</span>HomeBid</a><a class="back" href="<?= $isLoggedIn && $role==='Buyer' ? 'buyer/dashboard.php?tab=browse' : 'index.php#properties' ?>">← Back to properties</a></div></header>
<main class="wrap">
<div class="crumb"><a href="index.php">Home</a> / Property details</div>
<h1><?=property_h($property['title'])?></h1>
<p class="place">⌖ <?=property_h($property['location'])?></p>
<div class="layout">
<section class="main-card">
<?php if ($gallery): ?><img class="hero-image" id="mainPhoto" src="<?=property_h(property_image_url($gallery[0]))?>" alt="<?=property_h($property['title'])?>"><?php if(count($gallery)>1):?><div class="gallery"><?php foreach(array_slice($gallery,1) as $photo):?><img src="<?=property_h(property_image_url($photo))?>" alt="Additional property photo" onclick="document.getElementById('mainPhoto').src=this.src"><?php endforeach;?></div><?php endif;?>
<?php else: ?><div class="empty">⌂</div><?php endif;?>
<div class="content">
<div class="pills"><span class="pill gold"><?=!empty($property['auction_id'])?'Auction property':'Available property'?></span><?php if(!empty($property['category'])):?><span class="pill"><?=property_h($property['category'])?></span><?php endif;?></div>
<div class="specs"><div class="spec"><small>Lot area</small><b><?=number_format((float)$property['lot_area'],2)?> m²</b></div><div class="spec"><small>Floor area</small><b><?=number_format((float)$property['floor_area'],2)?> m²</b></div><div class="spec"><small>Property type</small><b><?=property_h($property['category']?:'House & Lot')?></b></div></div>
<h2>About this property</h2><p class="description"><?=property_h($property['description'])?></p>
<h2>Location details</h2><p class="description"><?=property_h($fullAddress)?></p>
<?php if($tags):?><h2>Property features</h2><div class="tags"><?php foreach($tags as $tag):?><span class="tag"><?=property_h($tag)?></span><?php endforeach;?></div><?php endif;?>
</div>
</section>
<aside class="side-card">
<span class="eyebrow"><?=!empty($property['auction_id'])?'AUCTION DETAILS':'PROPERTY PRICE'?></span>
<div class="price"><?=property_money($displayPrice)?></div><p class="muted"><?=$priceLabel?> · Reviewed and approved by HomeBid</p>
<div class="rule"></div>
<div class="info"><span>Property status</span><b><?=property_h(!empty($property['auction_id'])?$property['auction_status']:$property['status'])?></b></div>
<?php if(!empty($property['auction_id'])):?><div class="info"><span>Starting bid</span><b><?=property_money($property['starting_bid'])?></b></div><div class="info"><span>Auction starts</span><b><?=date('M j, Y g:i A',strtotime($property['start_time']))?></b></div><div class="info"><span>Auction ends</span><b><?=date('M j, Y g:i A',strtotime($property['end_time']))?></b></div><?php endif;?>
<div class="info"><span>Listed on</span><b><?=date('M j, Y',strtotime($property['created_at']))?></b></div>
<?php if ($bidMessage !== ''): ?><p class="notice" style="color:<?=$bidMessageType==='success'?'#167044':'#b42318'?>"><?=property_h($bidMessage)?></p><?php endif; ?>
<?php if (!$isLoggedIn || $role !== 'Buyer'): ?>
<a class="btn" href="<?=property_h($buyerLink)?>">Log in to continue</a>
<p class="notice">Sign in as a buyer to access bidding and other buyer features. Auction participation is subject to account verification and auction rules.</p>
<?php elseif (!empty($property['auction_id'])): ?>
<?php if (($property['auction_status'] ?? '') === 'active' && strtotime($property['start_time']) <= time() && strtotime($property['end_time']) > time()): ?>
<form method="post" style="margin-top:18px">
<label for="bid_amount" style="display:block;font-size:12px;font-weight:700;margin-bottom:7px">Your bid amount (₱)</label>
<input id="bid_amount" name="bid_amount" type="number" min="<?=number_format(max((float)$property['starting_bid'],(float)$property['current_bid']+1),2,'.','')?>" step="0.01" required style="width:100%;padding:13px;border:1px solid var(--line);border-radius:9px;font:inherit">
<input type="hidden" name="action" value="place_bid"><input type="hidden" name="csrf_token" value="<?=property_h(csrf_token())?>">
<button class="btn" type="submit" style="width:100%;border:0;cursor:pointer">Place bid</button>
<p class="notice">Minimum next bid: <?=property_money(max((float)$property['starting_bid'],(float)$property['current_bid']+1))?></p>
</form>
<?php else: ?><p class="notice">Bidding is not open right now. Check the auction schedule above.</p><?php endif; ?>
<?php else: ?>
<form method="post" action="checkout.php" style="margin-top:18px">
<input type="hidden" name="csrf_token" value="<?=property_h(csrf_token())?>">
<input type="hidden" name="payment_type" value="reservation">
<input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>">
<div class="info"><span>Reservation fee</span><b>₱5,000.00</b></div>
<button class="btn" type="submit" style="width:100%;border:0;cursor:pointer">Reserve with Xendit</button>
<p class="notice">You will be redirected to Xendit to complete your reservation payment securely.</p>
</form>
<?php endif; ?>
<a class="btn secondary" href="index.php#properties">Browse more properties</a>
</aside>
</div>
</main>
</body>
</html>
