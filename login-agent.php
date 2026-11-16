<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
function respond(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
try {
    require_once __DIR__ . '/config/auth.php';
    require_once __DIR__ . '/config/db.php';
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok'=>false,'message'=>'Invalid request.']);
    $action = $_POST['action'] ?? 'login';
    $isStaffPortal = ($action === 'staff_login');
    if ($isStaffPortal) {
        $sent = $_POST['csrf_token'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            respond(419, ['ok'=>false,'message'=>'Your session expired. Refresh the login page and try again.']);
        }
    }

    $email = trim((string)($_POST['email'] ?? ''));
    $loginIdentifier = $email;
    $password = (string)($_POST['password'] ?? '');

    if ($password === '' || $loginIdentifier === '' || ($action === 'signup' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        respond(422, ['ok'=>false,'message'=>$action === 'signup' ? 'Enter a valid email and password.' : 'Enter your email address or username and password.']);
    }

    if ($action === 'signup') {
        $name = trim((string)($_POST['name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $role = 'Buyer';
        if ($name === '') respond(422,['ok'=>false,'message'=>'Please enter your full name.']);
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            respond(422,['ok'=>false,'message'=>'Username must be 3–50 characters and use only letters, numbers, dots, underscores, or hyphens.']);
        }
        // Every new account starts as a Buyer. Seller access requires a reviewed application.
        if (strlen($password) < 8) respond(422,['ok'=>false,'message'=>'Your password must be at least 8 characters.']);

        $check = hb_fetch_one(hb_query($conn, 'SELECT id FROM users WHERE email=? OR username=? LIMIT 1', [$email,$username]));
        if ($check) respond(409,['ok'=>false,'message'=>'That email address or username is already registered.']);

        hb_query($conn, 'INSERT INTO users (username,email,password,role,is_verified) VALUES (?,?,?,?,1)',
            [$username,$email,password_hash($password,PASSWORD_DEFAULT),$role]);
        $userId = (int)mysqli_insert_id($conn);
        session_regenerate_id(true);
        $_SESSION['user_id']=$userId;
        $_SESSION['role']=$role;
        $_SESSION['username']=$username;
        $redirect = $role === 'Client' ? 'client/dashboard.php' : 'buyer/dashboard.php';
        respond(200,['ok'=>true,'message'=>'Your HomeBid account has been created.','redirect'=>$redirect]);
    }

    $q = hb_query($conn, 'SELECT id,username,password,role FROM users WHERE email=? OR username=? LIMIT 1', [$loginIdentifier,$loginIdentifier]);
    $account = hb_fetch_one($q);
    if (!$account) respond(401,['ok'=>false,'message'=>'Email or password is incorrect.']);
    $stored = (string)$account['password'];
    $valid = password_verify($password, $stored);
    if (!$valid && $stored !== '' && !str_starts_with($stored, '$') && hash_equals($stored, $password)) {
        $valid = true;
        $up = hb_query($conn, 'UPDATE users SET password=? WHERE id=?', [password_hash($password,PASSWORD_DEFAULT),$account['id']]);
    }
    if (!$valid) respond(401,['ok'=>false,'message'=>'Email or password is incorrect.']);

    $role = (string)$account['role'];
    if ($isStaffPortal && !in_array($role,['Administrator','Staff'],true)) {
        respond(403,['ok'=>false,'message'=>'This login is only for Administrator and Staff accounts.']);
    }
    if (!$isStaffPortal && in_array($role,['Administrator','Staff'],true)) {
        respond(403,['ok'=>false,'message'=>'Administrator and Staff accounts must use the Staff / Admin Login page.']);
    }
    if ($role==='Administrator') $redirect='admin/dashboard.php';
    elseif ($role==='Staff') $redirect='staff/module.php';
    elseif ($role==='Buyer') $redirect='buyer/dashboard.php';
    elseif ($role==='Client') $redirect='client/dashboard.php';
    else respond(403,['ok'=>false,'message'=>'Your account role is not configured for dashboard access. Contact an administrator.']);

    session_regenerate_id(true);
    $_SESSION['user_id']=(int)$account['id'];
    $_SESSION['role']=$role;
    $_SESSION['username']=$account['username'];
    respond(200,['ok'=>true,'message'=>'Login successful.','redirect'=>$redirect]);
} catch (Throwable $e) {
    error_log('HomeBid login-agent error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    respond(500, [
        'ok' => false, 
        'message' => 'The server could not complete the login. Check the PHP error log for details.'
    ]);
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
if ($account['role'] === 'Client') {
 $redirect = 'client/dashboard.php';
} elseif ($account['role'] === 'Buyer') {
 $redirect = 'client/dashboard.php';

} elseif ($account['role'] === 'Staff') {
 $redirect = 'staff/dashboard.php';
} elseif ($account['role'] === 'Administrator') {
 $redirect = 'admin/dashboard.php';

} else {
 respond(403,['ok'=>false,'message'=>'Your account role is not configured for dashboard access. Please contact an administrator.']);
}
respond(200,['ok'=>true,'message'=>'Login successful.','redirect'=>$redirect]);

