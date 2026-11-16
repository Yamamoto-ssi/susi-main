<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$profileRole = $profileRole ?? 'Buyer';
$userId = require_roles($profileRole === 'Seller' ? ['Client'] : ['Buyer']);
function ps_h($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function ps_initials(array $user): string {
    $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if ($name === '') $name = (string)($user['username'] ?? 'HB');
    $parts = preg_split('/\s+/', $name);
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) $letters .= strtoupper(substr($part, 0, 1));
    return $letters ?: 'HB';
}
$section = (string)($_GET['section'] ?? 'personal');
if (!in_array($section, ['personal','security','notifications'], true)) $section = 'personal';
$notice = '';
$error = '';
$user = hb_fetch_one(hb_query($conn, 'SELECT id,username,email,first_name,last_name,phone_number,mailing_address,password,email_notifications,auction_notifications,marketing_notifications,created_at FROM users WHERE id=? LIMIT 1', [$userId])) ?: [];
if (!$user) { http_response_code(404); exit('Account not found.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'save_profile') {
            $first = trim((string)($_POST['first_name'] ?? ''));
            $last = trim((string)($_POST['last_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone_number'] ?? ''));
            $address = trim((string)($_POST['mailing_address'] ?? ''));
            if ($first === '' || $last === '') throw new RuntimeException('Please enter your first and last name.');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');
            if (strlen($first)>80 || strlen($last)>80 || strlen($phone)>30 || strlen($address)>255) throw new RuntimeException('One or more fields are too long.');
            $duplicate = hb_fetch_one(hb_query($conn,'SELECT id FROM users WHERE email=? AND id<>? LIMIT 1',[$email,$userId]));
            if ($duplicate) throw new RuntimeException('That email address is already used by another account.');
            hb_query($conn,'UPDATE users SET first_name=?,last_name=?,email=?,phone_number=?,mailing_address=? WHERE id=?',[$first,$last,$email,$phone,$address,$userId]);
            $notice = 'Your profile details have been saved.';
            $section = 'personal';
        } elseif ($action === 'change_password') {
            $current = (string)($_POST['current_password'] ?? '');
            $new = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');
            if (!password_verify($current, (string)$user['password'])) throw new RuntimeException('Your current password is incorrect.');
            if (strlen($new) < 8) throw new RuntimeException('Your new password must contain at least 8 characters.');
            if ($new !== $confirm) throw new RuntimeException('The new passwords do not match.');
            if (!filter_var((string)$user['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Your account email address is invalid. Update it before changing your password.');

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $newPasswordHash = password_hash($new, PASSWORD_DEFAULT);
            hb_query($conn, 'DELETE FROM password_change_requests WHERE user_id=? OR expires_at < NOW()', [$userId]);
            hb_query($conn, 'INSERT INTO password_change_requests (user_id,token_hash,new_password_hash,expires_at) VALUES (?,?,?,DATE_ADD(NOW(), INTERVAL 15 MINUTE))', [$userId,$tokenHash,$newPasswordHash]);

            $baseUrl = rtrim((string)(getenv('APP_BASE_URL') ?: 'http://localhost/susi'), '/');
            $verifyUrl = $baseUrl . '/password-change-verify.php?token=' . urlencode($token);
            $smtpHost = (string)getenv('SMTP_HOST');
            $smtpUser = (string)getenv('SMTP_USERNAME');
            $smtpPass = (string)getenv('SMTP_PASSWORD');
            $smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
            $smtpSecure = strtolower((string)(getenv('SMTP_ENCRYPTION') ?: 'tls'));
            $fromEmail = (string)(getenv('SMTP_FROM_EMAIL') ?: $smtpUser);
            $fromName = (string)(getenv('SMTP_FROM_NAME') ?: 'HomeBid');

            if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '' || $fromEmail === '') {
                hb_query($conn, 'DELETE FROM password_change_requests WHERE user_id=?', [$userId]);
                throw new RuntimeException('Email verification is not configured yet. Please contact the administrator.');
            }

            try {
                require_once dirname(__DIR__) . '/vendor/autoload.php';
                $mailer = new PHPMailer\\PHPMailer\\PHPMailer(true);
                $mailer->isSMTP();
                $mailer->Host = $smtpHost;
                $mailer->SMTPAuth = true;
                $mailer->Username = $smtpUser;
                $mailer->Password = $smtpPass;
                $mailer->Port = $smtpPort;
                if ($smtpSecure === 'ssl') $mailer->SMTPSecure = PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_SMTPS;
                else $mailer->SMTPSecure = PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_STARTTLS;
                $mailer->CharSet = 'UTF-8';
                $mailer->setFrom($fromEmail, $fromName);
                $mailer->addAddress((string)$user['email']);
                $mailer->isHTML(true);
                $mailer->Subject = 'Verify your HomeBid password change';
                $safeUrl = htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8');
                $mailer->Body = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:28px;color:#243044"><h2>Confirm your password change</h2><p>We received a request to change your HomeBid password. Click the button below to confirm this change.</p><p style="margin:28px 0"><a href="' . $safeUrl . '" style="background:#173f35;color:#fff;text-decoration:none;padding:13px 22px;border-radius:8px;display:inline-block">Verify password change</a></p><p>This link expires in 15 minutes. If you did not request this change, ignore this email. Your password will remain unchanged.</p></div>';
                $mailer->AltBody = "Confirm your HomeBid password change using this link (expires in 15 minutes): " . $verifyUrl;
                $mailer->send();
            } catch (Throwable $mailError) {
                hb_query($conn, 'DELETE FROM password_change_requests WHERE user_id=?', [$userId]);
                error_log('HomeBid password verification email failed: ' . $mailError->getMessage());
                throw new RuntimeException('We could not send the verification email. Check the email settings and try again.');
            }

            $notice = 'A verification link has been sent to ' . (string)$user['email'] . '. Your password will change only after you verify the email.';
            $section = 'security';
        } elseif ($action === 'save_notifications') {
            hb_query($conn,'UPDATE users SET email_notifications=?,auction_notifications=?,marketing_notifications=? WHERE id=?',[
                isset($_POST['email_notifications'])?1:0,
                isset($_POST['auction_notifications'])?1:0,
                isset($_POST['marketing_notifications'])?1:0,$userId
            ]);
            $notice = 'Your notification preferences have been saved.';
            $section = 'notifications';
        }
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'We could not save your changes. Please check the database and try again.';
        if (!$e instanceof RuntimeException) error_log('HomeBid profile settings error: '.$e->getMessage());
    }
    $user = hb_fetch_one(hb_query($conn, 'SELECT id,username,email,first_name,last_name,phone_number,mailing_address,password,email_notifications,auction_notifications,marketing_notifications,created_at FROM users WHERE id=? LIMIT 1', [$userId])) ?: $user;
}
$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if ($displayName === '') $displayName = (string)$user['username'];
$initials = ps_initials($user);
$since = !empty($user['created_at']) ? date('Y',strtotime($user['created_at'])) : date('Y');
$roleName = $profileRole === 'Seller' ? 'Seller' : 'Buyer';
$backUrl = $profileRole === 'Seller' ? 'dashboard.php' : 'dashboard.php';
$stats = ['main'=>0,'secondary'=>0];
if ($profileRole === 'Seller') {
    $stats['main'] = (int)hb_fetch_value(hb_query($conn,'SELECT COUNT(*) FROM properties WHERE user_id=?',[$userId]));
    $stats['secondary'] = (int)hb_fetch_value(hb_query($conn,'SELECT COUNT(*) FROM auctions a JOIN properties p ON p.id=a.property_id WHERE p.user_id=? AND a.status IN (\'ended\',\'sold\')',[$userId]));
} else {
    $stats['main'] = (int)hb_fetch_value(hb_query($conn,'SELECT COUNT(*) FROM bids WHERE user_id=?',[$userId]));
    $stats['secondary'] = (int)hb_fetch_value(hb_query($conn,'SELECT COUNT(*) FROM bids WHERE user_id=? AND status IN (\'winning\',\'won\')',[$userId]));
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profile &amp; settings | HomeBid</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/profile-settings.css?v=1"></head>
<body>
<header class="topbar"><a class="crumb" href="../index.php">HomeBid</a><span>›</span><b>Profile &amp; settings</b><div class="top-right"><span class="role-pill"><?=$roleName?> account</span><span class="avatar small"><?=ps_h($initials)?></span></div></header>
<main class="wrap">
<div class="heading"><h1>Profile &amp; settings</h1><p>Manage your personal details and account preferences.</p></div>
<?php if($notice):?><div class="flash success"><?=ps_h($notice)?></div><?php endif;?><?php if($error):?><div class="flash error"><?=ps_h($error)?></div><?php endif;?>
<div class="layout">
<aside class="profile-side">
<div class="identity"><span class="avatar large"><?=ps_h($initials)?></span><div><h2><?=ps_h($displayName)?></h2><p><?=ps_h($user['email'])?></p><span class="verified">● HomeBid <?=$roleName?></span></div></div>
<div class="stats"><div><b><?=number_format($stats['main'])?></b><small><?=$profileRole==='Seller'?'Properties':'Bids placed'?></small></div><div><b><?=number_format($stats['secondary'])?></b><small><?=$profileRole==='Seller'?'Ended auctions':'Winning bids'?></small></div><div><b><?=ps_h($since)?></b><small>Member since</small></div></div>
<nav class="settings-nav">
<a class="<?=$section==='personal'?'active':''?>" href="?section=personal"><span>♙</span>Personal details <b>›</b></a>
<a class="<?=$section==='security'?'active':''?>" href="?section=security"><span>◇</span>Security <b>›</b></a>
<a class="<?=$section==='notifications'?'active':''?>" href="?section=notifications"><span>♧</span>Notifications <b>›</b></a>
<a href="../logout.php" onclick="event.preventDefault();document.getElementById('logoutForm').submit()"><span>⇥</span>Sign out</a>
</nav>
<form id="logoutForm" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?=ps_h(csrf_token())?>"></form>
</aside>
<section class="settings-card">
<?php if($section==='personal'):?>
<div class="card-head"><div><span class="eyebrow">PERSONAL INFORMATION</span><h2>Your details</h2><p>Keep your contact information up to date.</p></div><span class="edit-mark">EDIT PROFILE</span></div>
<form method="post" class="settings-form"><input type="hidden" name="csrf_token" value="<?=ps_h(csrf_token())?>"><input type="hidden" name="action" value="save_profile">
<div class="field-grid"><label>First name<input name="first_name" maxlength="80" required value="<?=ps_h($user['first_name'])?>" placeholder="First name"></label>
<label>Last name<input name="last_name" maxlength="80" required value="<?=ps_h($user['last_name'])?>" placeholder="Last name"></label>
<label>Email address<input type="email" name="email" required value="<?=ps_h($user['email'])?>"></label>
<label>Phone number<input type="tel" name="phone_number" maxlength="30" value="<?=ps_h($user['phone_number'])?>" placeholder="+63 9XX XXX XXXX"></label>
<label class="wide">Mailing address<input name="mailing_address" maxlength="255" value="<?=ps_h($user['mailing_address'])?>" placeholder="House no., street, barangay, city, province"></label></div>
<div class="form-actions"><button class="primary" type="submit">Save changes</button></div></form>
<div class="security-inline"><div><h3>Account security</h3><p>Keep your password private and update it regularly.</p></div><a class="secondary" href="?section=security">Change password</a></div>
<?php elseif($section==='security'):?>
<div class="card-head"><div><span class="eyebrow">ACCOUNT SECURITY</span><h2>Change password</h2><p>Use a strong password you don't use on other websites.</p></div></div>
<form method="post" class="settings-form narrow"><input type="hidden" name="csrf_token" value="<?=ps_h(csrf_token())?>"><input type="hidden" name="action" value="change_password"><p class="security-note">For your protection, we will email you a verification link before your new password takes effect.</p>
<label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
<label>New password<input type="password" name="new_password" minlength="8" autocomplete="new-password" required><small>At least 8 characters.</small></label>
<label>Confirm new password<input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></label>
<div class="form-actions"><button class="primary" type="submit">Update password</button></div></form>
<?php else:?>
<div class="card-head"><div><span class="eyebrow">PREFERENCES</span><h2>Notifications</h2><p>Choose which HomeBid updates you'd like to receive.</p></div></div>
<form method="post" class="settings-form"><input type="hidden" name="csrf_token" value="<?=ps_h(csrf_token())?>"><input type="hidden" name="action" value="save_notifications">
<label class="toggle-row"><span><b>Email notifications</b><small>Receive important account updates by email.</small></span><input type="checkbox" name="email_notifications" value="1" <?=$user['email_notifications']?'checked':''?>></label>
<label class="toggle-row"><span><b>Auction updates</b><small>Get updates about bids, auction schedules, and results.</small></span><input type="checkbox" name="auction_notifications" value="1" <?=$user['auction_notifications']?'checked':''?>></label>
<label class="toggle-row"><span><b>HomeBid announcements</b><small>Receive occasional product and service announcements.</small></span><input type="checkbox" name="marketing_notifications" value="1" <?=$user['marketing_notifications']?'checked':''?>></label>
<div class="form-actions"><button class="primary" type="submit">Save preferences</button></div></form>
<?php endif;?>
</section></div></main></body></html>