<?php
declare(strict_types=1);
require_once __DIR__ . '/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
verify_csrf();

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId > 0) {
    try {
        require_once __DIR__ . '/config/db.php';
        hb_query($conn, 'INSERT INTO activity_logs (user_id,action,entity_type,record_id,ip_address) VALUES (?,?,?,?,?)',
            [$userId, 'Logged out', 'user', $userId, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {
        error_log('HomeBid logout activity log could not be saved.');
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();
header('Location: index.php?logged_out=1');
exit;
