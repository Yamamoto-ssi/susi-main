<?php
declare(strict_types=1);
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/db.php';
if(session_status()!==PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
function respond(int $status,array $body): never { http_response_code($status); echo json_encode($body); exit; }
if($_SERVER['REQUEST_METHOD']!=='POST') respond(405,['ok'=>false,'message'=>'Invalid request.']);
$action=$_POST['action']??'login';
$isStaffPortal = ($action === 'staff_login');
if ($isStaffPortal) verify_csrf();
$email=trim($_POST['email']??'');
$password=(string)($_POST['password']??'');
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||$password==='') respond(422,['ok'=>false,'message'=>'Enter a valid email and password.']);
if($action==='signup'){
 $name=trim($_POST['name']??'');
 if($name===''||strlen($password)<8) respond(422,['ok'=>false,'message'=>'Enter your name and a password with at least 8 characters.']);
 $username=mb_substr($name,0,50);
 $check=$pdo->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');$check->execute([$email,$username]);
 if($check->fetch()) respond(409,['ok'=>false,'message'=>'That email or username is already registered.']);
 $insert=$pdo->prepare("INSERT INTO users (username,email,password,role,is_verified) VALUES (?,?,?,'Buyer',0)");
 try{$insert->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);}catch(PDOException $e){respond(409,['ok'=>false,'message'=>'Unable to create account. Check whether the email or username is already used.']);}
 $id=(int)$pdo->lastInsertId();
 $_SESSION['user_id']=$id;$_SESSION['role']='Buyer';$_SESSION['username']=$username;
 respond(200,['ok'=>true,'message'=>'Account created. Your account is registered as a Buyer.','redirect'=>'client/dashboard.php']);
}
$q=$pdo->prepare('SELECT id,username,password,role,is_verified FROM users WHERE email=? LIMIT 1');$q->execute([$email]);$account=$q->fetch();
if(!$account) respond(401,['ok'=>false,'message'=>'Email or password is incorrect.']);
$stored=(string)$account['password'];
$valid=password_verify($password,$stored);
if(!$valid && $stored!=='' && !str_starts_with($stored,'$') && hash_equals($stored,$password)){
 $valid=true;$newHash=password_hash($password,PASSWORD_DEFAULT);$up=$pdo->prepare('UPDATE users SET password=? WHERE id=?');$up->execute([$newHash,$account['id']]);
}
if(!$valid) respond(401,['ok'=>false,'message'=>'Email or password is incorrect.']);
session_regenerate_id(true);
$_SESSION['user_id']=(int)$account['id'];$_SESSION['role']=$account['role'];$_SESSION['username']=$account['username'];
if ($isStaffPortal && !in_array($account['role'], ['Administrator','Staff'], true)) {
 respond(403,['ok'=>false,'message'=>'This login is only for Administrator and Staff accounts. Please use the buyer/client login.']);
}
if (!$isStaffPortal && in_array($account['role'], ['Administrator','Staff'], true)) {
 respond(403,['ok'=>false,'message'=>'Administrator and Staff accounts must use the Staff / Admin Login page.']);
}
if ($account['role'] === 'Client' || $account['role'] === 'Buyer') {
 $redirect = 'client/dashboard.php';
} elseif ($account['role'] === 'Staff') {
 $redirect = 'staff/dashboard.php';
} elseif ($account['role'] === 'Administrator') {
 $redirect = 'admin/dashboard.php';
} else {
 respond(403,['ok'=>false,'message'=>'Your account role is not configured for dashboard access. Please contact an administrator.']);
}
respond(200,['ok'=>true,'message'=>'Login successful.','redirect'=>$redirect]);