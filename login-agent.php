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
    $password = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        respond(422, ['ok'=>false,'message'=>'Enter a valid email and password.']);
    }

    if ($action === 'signup') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '' || strlen($password) < 8) respond(422, ['ok'=>false,'message'=>'Enter your name and a password with at least 8 characters.']);
        $username = function_exists('mb_substr') ? mb_substr($name, 0, 50) : substr($name, 0, 50);
        $check = $pdo->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
        $check->execute([$email, $username]);
        if ($check->fetch()) respond(409, ['ok'=>false,'message'=>'That email or username is already registered.']);
        $insert = $pdo->prepare("INSERT INTO users (username,email,password,role,is_verified) VALUES (?,?,?,'Buyer',0)");
        $insert->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
        $_SESSION['user_id']=(int)$pdo->lastInsertId(); $_SESSION['role']='Buyer'; $_SESSION['username']=$username;
        respond(200,['ok'=>true,'message'=>'Account created.','redirect'=>'client/dashboard.php']);
    }

    $q = $pdo->prepare('SELECT id,username,password,role FROM users WHERE email=? LIMIT 1');
    $q->execute([$email]);
    $account = $q->fetch();
    if (!$account) respond(401,['ok'=>false,'message'=>'Email or password is incorrect.']);
    $stored = (string)$account['password'];
    $valid = password_verify($password, $stored);
    if (!$valid && $stored !== '' && !str_starts_with($stored, '$') && hash_equals($stored, $password)) {
        $valid = true;
        $up = $pdo->prepare('UPDATE users SET password=? WHERE id=?');
        $up->execute([password_hash($password,PASSWORD_DEFAULT),$account['id']]);
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
    elseif ($role==='Staff') $redirect='staff/dashboard.php';
    elseif ($role==='Client'||$role==='Buyer') $redirect='client/dashboard.php';
    else respond(403,['ok'=>false,'message'=>'Your account role is not configured for dashboard access. Contact an administrator.']);

    session_regenerate_id(true);
    $_SESSION['user_id']=(int)$account['id'];
    $_SESSION['role']=$role;
    $_SESSION['username']=$account['username'];
    respond(200,['ok'=>true,'message'=>'Login successful.','redirect'=>$redirect]);
} catch (Throwable $e) {
    error_log('HomeBid login-agent error: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
    respond(500,['ok'=>false,'message'=>'The server could not complete the login. Check the PHP error log; a database table or column may need to be corrected.']);
}
