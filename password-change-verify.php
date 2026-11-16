<?php
declare(strict_types=1);
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';

$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$validToken = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$request = false;
if ($validToken) {
    $request = hb_fetch_one(hb_query($conn, 'SELECT id,user_id,new_password_hash FROM password_change_requests WHERE token_hash=? AND expires_at>NOW() LIMIT 1', [hash('sha256', $token)]));
}
$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$request) {
        $error = 'This verification link is invalid or has expired. Please request a new password change.';
    } else {
        hb_query($conn, 'UPDATE users SET password=? WHERE id=?', [$request['new_password_hash'], (int)$request['user_id']]);
        hb_query($conn, 'DELETE FROM password_change_requests WHERE user_id=?', [(int)$request['user_id']]);
        $success = true;
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verify password change | HomeBid</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f4f6f3;font-family:'DM Sans',Arial,sans-serif;color:#1d2d27}.panel{width:min(100%,480px);background:#fff;padding:38px;border-radius:18px;box-shadow:0 18px 55px #173f3514;text-align:center}.mark{width:54px;height:54px;border-radius:16px;background:#e8f1eb;color:#24523d;display:grid;place-items:center;font-size:25px;margin:0 auto 18px}h1{font-size:25px;margin:0 0 10px}p{color:#68766e;line-height:1.65}.btn{display:inline-block;border:0;border-radius:9px;background:#173f35;color:#fff;padding:13px 22px;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none}.error{background:#fff0ef;color:#a8322a;padding:12px;border-radius:9px}.success{background:#eaf6ed;color:#23633a;padding:12px;border-radius:9px}.muted{font-size:13px;margin-top:22px}
</style></head><body><main class="panel"><div class="mark">✓</div>
<?php if($success): ?>
<h1>Password updated</h1><p>Your HomeBid password has been changed successfully. You can now sign in using your new password.</p><a class="btn" href="index.php">Return to HomeBid</a>
<?php elseif(!$request): ?>
<h1>Verification link unavailable</h1><p class="error"><?=h($error ?: 'This link is invalid or has expired. Please return to your account settings and request another verification email.')?></p><a class="btn" href="index.php">Return to HomeBid</a>
<?php else: ?>
<h1>Confirm password change</h1><p>Click below to confirm your password change. Your new password will take effect immediately after confirmation.</p>
<?php if($error): ?><p class="error"><?=h($error)?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><input type="hidden" name="token" value="<?=h($token)?>"><button class="btn" type="submit">Confirm password change</button></form>
<p class="muted">For your security, this link expires 15 minutes after it was requested.</p>
<?php endif; ?></main></body></html>
