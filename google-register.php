<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/auth.php';

$googleRegistration = $_SESSION['google_registration'] ?? null;
if (!is_array($googleRegistration)
    || empty($googleRegistration['google_id'])
    || empty($googleRegistration['email'])
    || !filter_var((string)$googleRegistration['email'], FILTER_VALIDATE_EMAIL)
    || (int)($googleRegistration['started_at'] ?? 0) < 1
    || time() - (int)$googleRegistration['started_at'] > 600) {
    unset($_SESSION['google_registration']);
    header('Location: index.php');
    exit;
}

$error = '';
$username = trim((string)($googleRegistration['suggested_username'] ?? ''));
$password = '';
$role = 'Buyer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role = 'Buyer';

    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters and use only letters, numbers, dots, underscores, or hyphens.';
    } elseif (strlen($password) < 8) {
        $error = 'Your password must be at least 8 characters long.';
    } else {
        try {
            require_once __DIR__ . '/config/db.php';

            $existing = hb_fetch_one(hb_query($conn,
                'SELECT id, google_id, username, role FROM users WHERE email=? OR google_id=? LIMIT 1',
                [(string)$googleRegistration['email'], (string)$googleRegistration['google_id']]
            ));

            if ($existing) {
                if (!empty($existing['google_id'])
                    && !hash_equals((string)$existing['google_id'], (string)$googleRegistration['google_id'])) {
                    $error = 'This email is already connected to another Google account. Please sign in using your existing method.';
                } else {
                    if (empty($existing['google_id'])) {
                        hb_query($conn, 'UPDATE users SET google_id=?, is_verified=1 WHERE id=?',
                            [(string)$googleRegistration['google_id'], (int)$existing['id']]);
                    }
                    unset($_SESSION['google_registration']);
                    session_regenerate_id(true);
                    $account = hb_fetch_one(hb_query($conn,
                        'SELECT id, username, role FROM users WHERE id=? LIMIT 1',
                        [(int)$existing['id']]
                    ));
                    if (!$account || !in_array((string)$account['role'], ['Buyer', 'Client'], true)) {
                        http_response_code(403);
                        exit('This account cannot sign in through Google.');
                    }
                    $_SESSION['user_id'] = (int)$account['id'];
                    $_SESSION['role'] = (string)$account['role'];
                    $_SESSION['username'] = (string)$account['username'];
                    hb_query($conn, 'INSERT INTO activity_logs (user_id,action,entity_type,record_id,ip_address) VALUES (?,?,?,?,?)', [(int)$account['id'],'Signed in with Google','user',(int)$account['id'],$_SERVER['REMOTE_ADDR']??null]);
                    header('Location: ' . ((string)$account['role'] === 'Client' ? 'client/dashboard.php' : 'buyer/dashboard.php'));
                    exit;
                }
            } else {
                $usernameExists = hb_fetch_one(hb_query($conn,
                    'SELECT id FROM users WHERE username=? LIMIT 1', [$username]
                ));
                if ($usernameExists) {
                    $error = 'That username is already taken. Please choose another one.';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    hb_query($conn,
                        'INSERT INTO users (username, email, google_id, password, role, is_verified) VALUES (?, ?, ?, ?, ?, 1)',
                        [$username, (string)$googleRegistration['email'], (string)$googleRegistration['google_id'], $hashedPassword, $role]
                    );
                    $userId = (int)mysqli_insert_id($conn);
                    hb_query($conn, 'INSERT INTO activity_logs (user_id,action,entity_type,record_id,ip_address) VALUES (?,?,?,?,?)', [$userId,'Created an account with Google','user',$userId,$_SERVER['REMOTE_ADDR']??null]);
                    unset($_SESSION['google_registration']);
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['role'] = $role;
                    $_SESSION['username'] = $username;
                    header('Location: ' . ($role === 'Client' ? 'client/dashboard.php' : 'buyer/dashboard.php'));
                    exit;
                }
            }
        } catch (Throwable $e) {
            error_log('HomeBid Google registration error: ' . $e->getMessage());
            $error = 'The server could not create your account. Please try again or check the PHP error log.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Registration — HomeBid</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=20261117-2">
    <style>
        body { min-height:100vh; background:var(--cream); }
        .google-register-wrap { min-height:100vh; display:grid; place-items:center; padding:35px 18px; }
        .google-register-card { width:min(480px,100%); background:#fff; padding:38px; border-radius:12px; box-shadow:0 18px 55px #102b4218; }
        .google-register-card h1 { font:800 27px Manrope,sans-serif; color:var(--navy); margin:0 0 9px; }
        .google-register-card .intro { color:var(--muted); font-size:13px; line-height:1.65; margin:0 0 25px; }
        .google-register-card form { display:grid; gap:15px; }
        .google-register-card label { display:grid; gap:7px; color:var(--ink); font-size:11px; font-weight:700; }
        .google-register-card input { width:100%; box-sizing:border-box; padding:13px 14px; border:1px solid var(--line); border-radius:5px; font:13px "DM Sans",sans-serif; color:var(--ink); }
        .google-register-card input[readonly] { background:#f5f5f2; color:#687582; }
        .google-register-card .btn { border:0; cursor:pointer; width:100%; margin-top:5px; }
        .google-register-error { padding:11px 13px; background:#fff1ef; color:#a33c31; border-radius:5px; font-size:12px; line-height:1.5; margin:0 0 15px; }
        .google-register-back { display:block; text-align:center; color:var(--muted); font-size:12px; margin-top:19px; text-decoration:none; }
        @media(max-width:480px) { .google-register-card { padding:27px 22px; } }
    </style>
</head>
<body>
    <main class="google-register-wrap">
        <section class="google-register-card">
            <p class="eyebrow">WELCOME TO HOMEBID</p>
            <h1>Complete Your Registration</h1>
            <p class="intro">Your Google account is verified. Choose a username and password to create your HomeBid buyer account. You can apply for seller access later.</p>
            <?php if ($error !== ''): ?>
                <p class="google-register-error" role="alert"><?= h($error) ?></p>
            <?php endif; ?>
            <form method="post" action="google-register.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
<label>
                    Username
                    <input type="text" name="username" maxlength="50" autocomplete="username" required value="<?= h($username) ?>" placeholder="Choose a username">
                </label>
                <label>
                    Password
                    <input type="password" name="password" minlength="8" autocomplete="new-password" required placeholder="Create a password (at least 8 characters)">
                </label>
                <label>
                    Email address
                    <input type="email" value="<?= h($googleRegistration['email']) ?>" readonly>
                </label>
                <button class="btn btn-gold" type="submit">Create HomeBid Account</button>
            </form>
            <a class="google-register-back" href="index.php">Cancel and return to HomeBid</a>
        </section>
    </main>
</body>
</html>
