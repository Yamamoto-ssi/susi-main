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
   hb_query($conn,"UPDATE auctions SET review_status=?,review_feedback=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND review_status<>'approved'",[$auctionStatus,$auctionDecision==='needs_revision'?$feedback:null,$staffId,$auctionId]);
   if(mysqli_affected_rows($conn)){hb_query($conn,'INSERT INTO activity_logs (user_id,action,entity_type,record_id,ip_address) VALUES (?,?,?,?,?)',[$staffId,$auctionDecision==='approve'?'Approved auction submission':($auctionDecision==='admin_approval'?'Forwarded auction for administrator approval':'Requested auction revision'),'auction',$auctionId,$_SERVER['REMOTE_ADDR']??null]);$message=$auctionDecision==='approve'?'Auction approved. It can proceed only when its property is approved.':($auctionDecision==='admin_approval'?'Auction forwarded to the administrator.':'Auction revision request sent to the seller.');}else $error='This auction was already approved or could not be found.';
  }
 }
 if($id && in_array($decision,['approve','admin_approval','needs_revision'],true)){
  $feedback=trim((string)($_POST['feedback']??''));
  if($decision==='needs_revision' && $feedback==='') $error='Please explain what the seller needs to revise.';
  else {$status=$decision==='approve'?'approved':$decision;$approved=$decision==='approve'?1:0;
   hb_query($conn,'UPDATE properties SET is_approved=?,review_status=?,review_feedback=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND is_approved=0',[$approved,$status,$decision==='needs_revision'?$feedback:null,$staffId,$id]);
   if(mysqli_affected_rows($conn)){if($decision==='approve')hb_query($conn,"UPDATE properties SET status=IF(EXISTS(SELECT 1 FROM auctions WHERE property_id=?),'Auction','Available') WHERE id=?",[$id,$id]);$action=$decision==='approve'?'Approved property submission':($decision==='admin_approval'?'Forwarded property for administrator approval':'Requested property revision');hb_query($conn,'INSERT INTO activity_logs (user_id,action,entity_type,record_id,ip_address) VALUES (?,?,?,?,?)',[$staffId,$action,'property',$id,$_SERVER['REMOTE_ADDR']??null]);$message=$decision==='approve'?'Property approved successfully.':($decision==='admin_approval'?'Submission forwarded to the administrator.':'Revision request sent to the seller.');}else $error='This submission was already reviewed or could not be found.';
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
    <title>HomeBid Staff Dashboard</title>
    <link rel="stylesheet" href="style_admin.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f5f6fa;
        color: #182235;
        margin: 0
    }

    .hb-wrap {
        max-width: 1250px;
        margin: auto;
        padding: 32px 22px
    }

    .hb-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px
    }

    .hb-sub {
        color: #68758a
    }

    .hb-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin: 24px 0
    }

    .hb-card {
        background: #fff;
        border: 1px solid #e2e7ef;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 18px
    }

    .hb-card small {
        color: #68758a
    }

    .hb-num {
        font-size: 28px;
        font-weight: bold;
        margin-top: 8px
    }

    .hb-table {
        width: 100%;
        border-collapse: collapse
    }

    .hb-table th,
    .hb-table td {
        text-align: left;
        padding: 12px;
        border-bottom: 1px solid #e7ebf1;
        font-size: 13px
    }

    .hb-table th {
        font-size: 11px;
        color: #68758a
    }

    .hb-scroll {
        overflow: auto
    }

    .hb-btn {
        border: 0;
        border-radius: 7px;
        padding: 8px 12px;
        font-weight: bold;
        cursor: pointer
    }

    .hb-yes {
        background: #dcfce7;
        color: #166534
    }

    .hb-no {
        background: #fee2e2;
        color: #991b1b
    }

    .hb-error { background:#fef2f2; color:#991b1b; padding:12px; border-radius:8px; margin:16px 0 }
    .hb-nav { display:flex; flex-wrap:wrap; gap:8px; margin:22px 0 }
    .hb-nav a { padding:9px 13px; border-radius:8px; background:#e8edf5; color:#24334d; font-weight:700; text-decoration:none; font-size:13px }
    .hb-nav a:hover { background:#dbe4f0 }
    .hb-msg {
        background: #ecfdf5;
        padding: 12px;
        border-radius: 8px;
        margin: 16px 0
    }

    @media(max-width:700px) {
        .hb-stats {
            grid-template-columns: 1fr
        }

        .hb-head {
            align-items: flex-start;
            flex-direction: column
        }
    }
    </style>
</head>

<body>
    <main class="hb-wrap">
        <header class="hb-head">
            <div>
                <h1>HomeBid Staff Dashboard</h1>
                <p class="hb-sub">Review seller submissions and monitor property listings.</p>
            </div>
            <div style="display:flex;gap:14px;align-items:center"><a href="../index.php">View HomeBid ↗</a>
                <form method="post" action="../logout.php"><input type="hidden" name="csrf_token"
                        value="<?=sh(csrf_token())?>"><button class="hb-btn hb-no" type="submit">Log out</button></form>
            </div>
        </header>
        <nav class="hb-nav"><a href="#approvals">Property approvals</a><a href="#auction-approvals">Auction approvals</a><a href="#listings">All listings</a><a href="#auctions">Auction monitoring</a><a href="#activity">Activity logs</a></nav>
        <?php if($message):?><p class="hb-msg"><?=sh($message)?></p><?php endif;?>
        <?php if($error):?><p class="hb-error"><?=sh($error)?></p><?php endif;?>
        <div class="hb-stats">
            <section class="hb-card"><small>Total properties</small>
                <div class="hb-num"><?=(int)$counts['total']?></div>
            </section>
            <section class="hb-card"><small>Awaiting review</small>
                <div class="hb-num"><?=(int)$counts['pending']?></div>
            </section>
            <section class="hb-card"><small>Approved listings</small>
                <div class="hb-num"><?=(int)$counts['approved']?></div>
            </section>
            <section class="hb-card"><small>Active auctions</small><div class="hb-num"><?=(int)$auctionCounts['active']?></div></section>
            <section class="hb-card"><small>Total bids placed</small><div class="hb-num"><?=$bidCount?></div></section>
            <section class="hb-card"><small>Sold properties</small><div class="hb-num"><?=(int)$counts['sold']?></div></section>
        </div>
        <section class="hb-card" id="approvals">
            <h2>Pending property approvals</h2>
            <div class="hb-scroll">
                <table class="hb-table">
                    <thead>
                        <tr>
                            <th>PROPERTY</th>
                            <th>SUBMITTED BY</th>
                            <th>LOCATION</th>
                            <th>PRICE</th>
                            <th>SUBMITTED</th>
                            <th>REVIEW DECISION</th>
                        </tr>
                    </thead>
                    <tbody><?php if(!$pending):?><tr>
                            <td colspan="6">There are no pending listings.</td>
                        </tr><?php endif;?><?php foreach($pending as $p):?><tr>
                            <td><b><?=sh($p['title'])?></b><br><small>ID #<?=(int)$p['id']?></small></td>
                            <td><?=sh($p['username']?:'Unknown')?><br><small><?=sh($p['email'])?></small></td>
                            <td><?=sh($p['location'])?></td>
                            <td>₱<?=number_format((float)$p['price'],2)?></td>
                            <td><?=sh($p['created_at'])?></td>
                            <td>
                                <form method="post" style="display:grid;gap:7px;min-width:190px"><input type="hidden" name="csrf_token" value="<?=sh(csrf_token())?>"><input type="hidden" name="property_id" value="<?=(int)$p['id']?>"><select name="decision" required><option value="">Choose action</option><option value="approve">Accept / Approve</option><option value="admin_approval">Admin approval</option><option value="needs_revision">Needs revision</option></select><textarea name="feedback" placeholder="Required when revision is needed" rows="2"></textarea><button class="hb-btn hb-yes" type="submit">Submit decision</button></form>
                            </td>
                        </tr><?php endforeach;?></tbody>
                </table>
            </div>
        </section>
        <section class="hb-card" id="listings">
            <h2>Recent listings</h2>
            <div class="hb-scroll">
                <table class="hb-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>PROPERTY</th>
                            <th>SUBMITTED BY</th>
                            <th>PRICE</th>
                            <th>LISTING STATUS</th>
                            <th>APPROVAL</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach($recent as $p):?><tr>
                            <td>#<?=(int)$p['id']?></td>
                            <td><?=sh($p['title'])?></td>
                            <td><?=sh($p['username']?:'Unknown')?></td>
                            <td>₱<?=number_format((float)$p['price'],2)?></td>
                            <td><?=sh($p['status'])?></td>
                            <td><?=sh(ucwords(str_replace('_',' ',$p['review_status']??($p['is_approved']?'approved':'pending'))))?><?php if(!empty($p['review_feedback'])):?><br><small><?=sh($p['review_feedback'])?></small><?php endif;?></td>
                        </tr><?php endforeach;?></tbody>
                </table>
            </div>
        </section>
        <section class="hb-card" id="auction-approvals">
            <h2>Pending auction approvals</h2><p class="hb-sub">Approve the auction schedule separately from the property listing. Bidding requires both approvals.</p>
            <div class="hb-scroll"><table class="hb-table"><thead><tr><th>PROPERTY / AUCTION</th><th>SELLER</th><th>PROPERTY APPROVAL</th><th>AUCTION REVIEW</th><th>START / END</th><th>DECISION</th></tr></thead><tbody>
            <?php if(!$pendingAuctions):?><tr><td colspan="6">There are no auctions awaiting review.</td></tr><?php endif;?>
            <?php foreach($pendingAuctions as $a):?><tr><td><b><?=sh($a['title'])?></b><br><small>Auction #<?=(int)$a['id']?></small></td><td><?=sh($a['username']?:'Unknown')?></td><td><?=!empty($a['is_approved'])?'Approved':'Waiting for property approval'?></td><td><?=sh(ucwords(str_replace('_',' ',$a['review_status'])))?><?php if(!empty($a['review_feedback'])):?><br><small><?=sh($a['review_feedback'])?></small><?php endif;?></td><td><?=sh($a['start_time'])?><br><?=sh($a['end_time'])?></td><td><form method="post" style="display:grid;gap:7px;min-width:190px"><input type="hidden" name="csrf_token" value="<?=sh(csrf_token())?>"><input type="hidden" name="action" value="review_auction"><input type="hidden" name="auction_id" value="<?=(int)$a['id']?>"><select name="auction_decision" required><option value="">Choose action</option><option value="approve">Accept / Approve auction</option><option value="admin_approval">Admin approval</option><option value="needs_revision">Needs revision</option></select><textarea name="auction_feedback" rows="2" placeholder="Required for revision"></textarea><button class="hb-btn hb-yes">Submit decision</button></form></td></tr><?php endforeach;?></tbody></table></div>
        </section>
        <section class="hb-card" id="auctions">
            <h2>Auction monitoring</h2>
            <p class="hb-sub">Latest auctions and their current bidding status.</p>
            <div class="hb-scroll"><table class="hb-table">
                <thead><tr><th>PROPERTY</th><th>STARTING BID</th><th>CURRENT BID</th><th>STARTS</th><th>ENDS</th><th>STATUS</th></tr></thead>
                <tbody><?php if(!$auctions):?><tr><td colspan="6">No auctions have been created yet.</td></tr><?php endif;?>
                <?php foreach($auctions as $a):?><tr><td><?=sh($a['title'])?></td><td>₱<?=number_format((float)$a['starting_bid'],2)?></td><td>₱<?=number_format((float)$a['current_bid'],2)?></td><td><?=sh($a['start_time'])?></td><td><?=sh($a['end_time'])?></td><td><?=sh(ucfirst((string)$a['status']))?></td></tr><?php endforeach;?></tbody>
            </table></div>
        </section>
        <section class="hb-card" id="activity">
            <h2>Recent staff activity</h2>
            <div class="hb-scroll"><table class="hb-table">
                <thead><tr><th>STAFF MEMBER</th><th>ACTION</th><th>RECORD</th><th>DATE</th></tr></thead>
                <tbody><?php if(!$logs):?><tr><td colspan="4">No activity has been recorded yet.</td></tr><?php endif;?>
                <?php foreach($logs as $log):?><tr><td><?=sh($log['username']?:'System')?></td><td><?=sh($log['action'])?></td><td><?=sh($log['entity_type'])?> #<?=(int)$log['record_id']?></td><td><?=sh($log['created_at'])?></td></tr><?php endforeach;?></tbody>
            </table></div>
        </section>
    </main>
</body>

</html>