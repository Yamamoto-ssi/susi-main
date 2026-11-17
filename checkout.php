<?php
declare(strict_types=1);
require_once __DIR__ . '/config/auth.php';
$userId = require_roles(['Buyer']);
require_once __DIR__ . '/config/db.php';
function payment_h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function payment_page(string $title, string $message, string $tone = 'info'): void {
 $color=$tone==='success'?'#18794e':($tone==='error'?'#b42318':'#475467');
 echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.payment_h($title).' | HomeBid</title><style>body{font-family:Arial,sans-serif;background:#f5f7f8;color:#17212b;margin:0;padding:24px}.card{max-width:680px;margin:7vh auto;background:#fff;border:1px solid #e1e7ea;border-radius:14px;padding:28px}h1{font-size:24px}p{line-height:1.65;color:'.$color.'}a{display:inline-block;margin:8px 8px 0 0;padding:11px 15px;border-radius:8px;background:#173e36;color:white;text-decoration:none;font-weight:700}</style></head><body><main class="card"><h1>'.payment_h($title).'</h1><p>'.payment_h($message).'</p><a href="buyer/dashboard.php?tab=my-bids">My bidding activity</a><a href="buyer/dashboard.php?tab=activity">Payment history</a></main></body></html>';exit;
}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);payment_page('Payment request required','Start payment from a property page or your bidding activity.','error');}
verify_csrf();
$paymentType=(string)($_POST['payment_type']??'');
if(!in_array($paymentType,['reservation','auction'],true))payment_page('Invalid payment','The selected payment type is not valid.','error');
if(!isset($_FILES['payment_proof'])||!is_uploaded_file($_FILES['payment_proof']['tmp_name']))payment_page('Proof of payment required','Upload a screenshot or PDF of your GCash or bank transfer before submitting.','error');
$file=$_FILES['payment_proof'];
if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||(int)$file['size']>5*1024*1024)payment_page('Upload failed','The proof must be no larger than 5 MB.','error');
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
if(!isset($extensions[$mime]))payment_page('Unsupported file','Upload a JPG, PNG, WEBP image, or PDF file.','error');
$propertyId=filter_var($_POST['property_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
$auctionId=filter_var($_POST['auction_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:null;
$amount=0.0;$reservationId=null;$relativePath=null;
try{
 $conn->begin_transaction();
 if($paymentType==='reservation'){
  if(!$propertyId)throw new RuntimeException('The property reference is invalid.');
  $stmt=$conn->prepare("SELECT id FROM properties WHERE id=? AND status='Available' AND is_approved=1 AND review_status='approved' FOR UPDATE");$stmt->bind_param('i',$propertyId);$stmt->execute();$property=$stmt->get_result()->fetch_assoc();$stmt->close();
  if(!$property)throw new RuntimeException('This property is no longer available for reservation.');
  $stmt=$conn->prepare("SELECT id FROM checkout_payments WHERE payment_type='reservation' AND property_id=? AND user_id=? AND status='pending' LIMIT 1 FOR UPDATE");$stmt->bind_param('ii',$propertyId,$userId);$stmt->execute();$existing=$stmt->get_result()->fetch_assoc();$stmt->close();
  if($existing)throw new RuntimeException('You already have a reservation payment awaiting review for this property.');
  $amount=5000.00;$stmt=$conn->prepare("INSERT INTO reservations(property_id,user_id,reservation_fee,status) VALUES(?,?,?,'Pending')");$stmt->bind_param('iid',$propertyId,$userId,$amount);$stmt->execute();$reservationId=(int)$conn->insert_id;$stmt->close();
 }else{
  if(!$auctionId)throw new RuntimeException('The auction reference is invalid.');
  $stmt=$conn->prepare("SELECT a.id,a.property_id,a.current_bid,a.highest_bidder_id,a.status,a.end_time FROM auctions a WHERE a.id=? FOR UPDATE");$stmt->bind_param('i',$auctionId);$stmt->execute();$auction=$stmt->get_result()->fetch_assoc();$stmt->close();
  if(!$auction||(int)$auction['highest_bidder_id']!==$userId||($auction['status']!=='ended'&&strtotime($auction['end_time'])>time()))throw new RuntimeException('Only the winning bidder can submit payment after the auction ends.');
  $propertyId=(int)$auction['property_id'];$amount=(float)$auction['current_bid'];if($amount<=0)throw new RuntimeException('The winning bid amount is invalid.');
  $stmt=$conn->prepare("SELECT id FROM checkout_payments WHERE auction_id=? AND status IN ('pending','paid') LIMIT 1 FOR UPDATE");$stmt->bind_param('i',$auctionId);$stmt->execute();$existing=$stmt->get_result()->fetch_assoc();$stmt->close();if($existing)throw new RuntimeException('A payment for this auction is already submitted or completed.');
 }
 $proofDir=__DIR__.'/uploads/private/payment-proofs';if(!is_dir($proofDir)&&!mkdir($proofDir,0700,true)&&!is_dir($proofDir))throw new RuntimeException('The private proof folder could not be created.');
 $proofName=bin2hex(random_bytes(20)).'.'.$extensions[$mime];$relativePath='uploads/private/payment-proofs/'.$proofName;if(!move_uploaded_file($file['tmp_name'],$proofDir.'/'.$proofName))throw new RuntimeException('The proof file could not be saved.');
 $externalId='MANUAL_'.strtoupper(bin2hex(random_bytes(12)));
 $stmt=$conn->prepare("INSERT INTO checkout_payments(payment_type,reservation_id,auction_id,property_id,user_id,amount,external_id,status,payment_method,proof_path) VALUES(?,?,?,?,?,?,?,'pending','Manual transfer',?)");
 $stmt->bind_param('siiiidss',$paymentType,$reservationId,$auctionId,$propertyId,$userId,$amount,$externalId,$relativePath);$stmt->execute();$paymentId=(int)$conn->insert_id;$stmt->close();$conn->commit();
}catch(Throwable $e){try{$conn->rollback();}catch(Throwable $ignored){}if($relativePath&&is_file(__DIR__.'/'.$relativePath))@unlink(__DIR__.'/'.$relativePath);error_log('HomeBid manual payment submission: '.$e->getMessage());payment_page('Payment was not submitted',$e instanceof RuntimeException?$e->getMessage():'A database error occurred. Please contact HomeBid support.','error');}
try{$title=$paymentType==='auction'?'Auction payment proof submitted':'Reservation payment proof submitted';$message='Your proof of payment is awaiting Staff/Admin verification. Do not send another payment while this submission is pending.';$entity='payment';$stmt=$conn->prepare("INSERT INTO notifications(user_id,title,message,related_entity,related_id) VALUES(?,?,?,?,?)");$stmt->bind_param('ssssi',$userId,$title,$message,$entity,$paymentId);$stmt->execute();$stmt->close();}catch(Throwable $ignored){}
payment_page('Proof submitted successfully','Your payment of ₱'.number_format($amount,2).' has been submitted for verification. Your receipt will become available only after Staff/Admin approves the payment.','success');
