<?php
declare(strict_types=1);
require_once __DIR__.'/auth.php';
$userId=require_login();
require_once __DIR__.'/db.php';

if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();

$mode=(string)($_POST['mode']??'');
$current=(string)($_SESSION['role']??'');
if(!in_array($current,['Buyer','Client'],true)||!in_array($mode,['Buyer','Client'],true)){
    http_response_code(403);
    exit('This account cannot switch between Buyer and Seller mode.');
}

$approved=hb_fetch_one(hb_query($conn,
    "SELECT id FROM seller_applications WHERE user_id=? AND status='approved' ORDER BY reviewed_at DESC LIMIT 1",
    [$userId]
));
if(!$approved){
    http_response_code(403);
    exit('Seller mode is available only after your seller application has been approved.');
}

if($mode!==$current){
    hb_query($conn,'UPDATE users SET role=? WHERE id=?',[$mode,$userId]);
    session_regenerate_id(true);
    $_SESSION['role']=$mode;
}

$destination=$mode==='Client'?'../client/dashboard.php':'../buyer/dashboard.php';
header('Location: '.$destination);
exit;
