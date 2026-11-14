<?php
declare(strict_types=1);
require_once __DIR__.'/../config/auth.php';
$staffId=require_roles(['Staff','Administrator']);
require_once __DIR__.'/../config/db.php';
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $id=filter_input(INPUT_POST,'property_id',FILTER_VALIDATE_INT);
 $decision=$_POST['decision']??'';
 if($id&&in_array($decision,['approve','reject'],true)){
  $approved=$decision==='approve'?1:0;
  $q=$pdo->prepare('UPDATE properties SET is_approved=? WHERE id=?');$q->execute([$approved,$id]);
  if($q->rowCount()){
   $log=$pdo->prepare('INSERT INTO activity_logs (user_id,action,entity_type,record_id,ip_address) VALUES (?,?,?,?,?)');
   $log->execute([$staffId,$decision==='approve'?'Approved property listing':'Rejected property listing','property',$id,$_SERVER['REMOTE_ADDR']??null]);
  }
  $message=$q->rowCount()?'Review decision saved.':'No change was made to that listing.';
 }
}
$counts=$pdo->query('SELECT COUNT(*) total,COALESCE(SUM(is_approved=0),0) pending,COALESCE(SUM(is_approved=1),0) approved FROM properties')->fetch();
$pending=$pdo->query('SELECT p.*,u.username,u.email FROM properties p LEFT JOIN users u ON u.id=p.user_id WHERE p.is_approved=0 ORDER BY p.created_at ASC')->fetchAll();
$recent=$pdo->query('SELECT p.*,u.username FROM properties p LEFT JOIN users u ON u.id=p.user_id ORDER BY p.created_at DESC LIMIT 12')->fetchAll();
function sh($v){return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>HomeBid Staff Dashboard</title><link rel="stylesheet" href="style_admin.css"><style>
body{font-family:Arial,sans-serif;background:#f5f6fa;color:#182235;margin:0}.hb-wrap{max-width:1250px;margin:auto;padding:32px 22px}.hb-head{display:flex;justify-content:space-between;align-items:center;gap:16px}.hb-sub{color:#68758a}.hb-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:24px 0}.hb-card{background:#fff;border:1px solid #e2e7ef;border-radius:12px;padding:18px;margin-bottom:18px}.hb-card small{color:#68758a}.hb-num{font-size:28px;font-weight:bold;margin-top:8px}.hb-table{width:100%;border-collapse:collapse}.hb-table th,.hb-table td{text-align:left;padding:12px;border-bottom:1px solid #e7ebf1;font-size:13px}.hb-table th{font-size:11px;color:#68758a}.hb-scroll{overflow:auto}.hb-btn{border:0;border-radius:7px;padding:8px 12px;font-weight:bold;cursor:pointer}.hb-yes{background:#dcfce7;color:#166534}.hb-no{background:#fee2e2;color:#991b1b}.hb-msg{background:#ecfdf5;padding:12px;border-radius:8px;margin:16px 0}@media(max-width:700px){.hb-stats{grid-template-columns:1fr}.hb-head{align-items:flex-start;flex-direction:column}}
</style></head><body><main class="hb-wrap"><header class="hb-head"><div><h1>HomeBid Staff Dashboard</h1><p class="hb-sub">Review seller submissions and monitor property listings.</p></div><a href="../index.php">View HomeBid ↗</a></header>
<?php if($message):?><p class="hb-msg"><?=sh($message)?></p><?php endif;?>
<div class="hb-stats"><section class="hb-card"><small>Total properties</small><div class="hb-num"><?=(int)$counts['total']?></div></section><section class="hb-card"><small>Awaiting review</small><div class="hb-num"><?=(int)$counts['pending']?></div></section><section class="hb-card"><small>Approved listings</small><div class="hb-num"><?=(int)$counts['approved']?></div></section></div>
<section class="hb-card"><h2>Pending property approvals</h2><div class="hb-scroll"><table class="hb-table"><thead><tr><th>PROPERTY</th><th>SUBMITTED BY</th><th>LOCATION</th><th>PRICE</th><th>SUBMITTED</th><th>DECISION</th></tr></thead><tbody><?php if(!$pending):?><tr><td colspan="6">There are no pending listings.</td></tr><?php endif;?><?php foreach($pending as $p):?><tr><td><b><?=sh($p['title'])?></b><br><small>ID #<?=(int)$p['id']?></small></td><td><?=sh($p['username']?:'Unknown')?><br><small><?=sh($p['email'])?></small></td><td><?=sh($p['location'])?></td><td>₱<?=number_format((float)$p['price'],2)?></td><td><?=sh($p['created_at'])?></td><td><form method="post" style="display:flex;gap:6px"><input type="hidden" name="csrf_token" value="<?=sh(csrf_token())?>"><input type="hidden" name="property_id" value="<?=(int)$p['id']?>"><button class="hb-btn hb-yes" name="decision" value="approve">Approve</button><button class="hb-btn hb-no" name="decision" value="reject" onclick="return confirm('Reject this listing?')">Reject</button></form></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="hb-card"><h2>Recent listings</h2><div class="hb-scroll"><table class="hb-table"><thead><tr><th>ID</th><th>PROPERTY</th><th>SUBMITTED BY</th><th>PRICE</th><th>LISTING STATUS</th><th>APPROVAL</th></tr></thead><tbody><?php foreach($recent as $p):?><tr><td>#<?=(int)$p['id']?></td><td><?=sh($p['title'])?></td><td><?=sh($p['username']?:'Unknown')?></td><td>₱<?=number_format((float)$p['price'],2)?></td><td><?=sh($p['status'])?></td><td><?=$p['is_approved']?'Approved':'Pending / Rejected'?></td></tr><?php endforeach;?></tbody></table></div></section>
</main></body></html>