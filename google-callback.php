<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/auth.php';

function google_callback_error(string $message, int $status = 400): never {
    http_response_code($status);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Google Sign-In — HomeBid</title><body style="font-family:Arial,sans-serif;background:#f7f6f2;color:#102b42;padding:48px 20px">';
    echo '<main style="max-width:520px;margin:8vh auto;background:white;padding:32px;border-radius:12px;box-shadow:0 10px 35px #102b4218">';
    echo '<h2>Unable to sign in with Google</h2><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><a href="index.php" style="color:#a87527">Return to HomeBid</a></p></main></body></html>';
    exit;
}

if (isset($_GET['error'])) {
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_started']);
    google_callback_error('Google sign-in was cancelled or could not be completed. Please try again.');
}

$receivedState = (string)($_GET['state'] ?? '');
$expectedState = (string)($_SESSION['google_oauth_state'] ?? '');
$startedAt = (int)($_SESSION['google_oauth_started'] ?? 0);
unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_started']);

if ($receivedState === '' || $expectedState === '' || !hash_equals($expectedState, $receivedState)
    || $startedAt < 1 || time() - $startedAt > 600) {
    google_callback_error('Your sign-in session expired or could not be verified. Please start again.');
}

$code = trim((string)($_GET['code'] ?? ''));
$clientId = getenv('GOOGLE_CLIENT_ID') ?: '44077547718-4nesakkabijeqadjsse1fq82knq0rm1n.apps.googleusercontent.com';
$clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
$redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/susi/google-callback.php';

if ($code === '') {
    google_callback_error('Google did not return an authorization code.');
}
if ($clientId === '' || $clientSecret === '') {
    google_callback_error('Google OAuth is not fully configured. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET.');
}
if (!function_exists('curl_init')) {
    google_callback_error('The PHP cURL extension is required for Google Sign-In.', 500);
}

$curl = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$tokenBody = curl_exec($curl);
$tokenStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
$curlErrno = curl_errno($curl);
curl_close($curl);

$tokenData = is_string($tokenBody) ? json_decode($tokenBody, true) : null;
$idToken = is_array($tokenData) ? (string)($tokenData['id_token'] ?? '') : '';
if ($tokenStatus !== 200 || $idToken === '') {
    error_log(
        'HomeBid Google OAuth token exchange failed. HTTP ' .
        $tokenStatus . ' | cURL errno: ' . $curlErrno .
        ' | cURL error: ' . ($curlError !== '' ? $curlError : 'No cURL error reported')
    );
    google_callback_error('Google could not complete the account verification. Please try again.', 401);
}

// Validate the ID token with Google and confirm that it was issued for this app.
$verifyCurl = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($idToken));
curl_setopt_array($verifyCurl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$claimsBody = curl_exec($verifyCurl);
$claimsStatus = (int)curl_getinfo($verifyCurl, CURLINFO_HTTP_CODE);
curl_close($verifyCurl);

$claims = is_string($claimsBody) ? json_decode($claimsBody, true) : null;
$issuer = is_array($claims) ? (string)($claims['iss'] ?? '') : '';
$email = is_array($claims) ? strtolower(trim((string)($claims['email'] ?? ''))) : '';
$googleId = is_array($claims) ? trim((string)($claims['sub'] ?? '')) : '';
$audience = is_array($claims) ? (string)($claims['aud'] ?? '') : '';
$emailVerified = is_array($claims) && (($claims['email_verified'] ?? false) === true || ($claims['email_verified'] ?? '') === 'true');
$expiresAt = is_array($claims) ? (int)($claims['exp'] ?? 0) : 0;

if ($claimsStatus !== 200 || !is_array($claims)
    || !in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
    || !hash_equals($clientId, $audience)
    || !$emailVerified || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || $googleId === '' || $expiresAt < time()) {
    google_callback_error('Google returned an account that could not be verified.', 401);
}

try {
    require_once __DIR__ . '/config/db.php';

    $account = hb_fetch_one(hb_query($conn,
        'SELECT id, username, email, role, google_id FROM users WHERE google_id=? LIMIT 1',
        [$googleId]
    ));

    if (!$account) {
        $account = hb_fetch_one(hb_query($conn,
            'SELECT id, username, email, role, google_id FROM users WHERE email=? LIMIT 1',
            [$email]
        ));
    }

    if ($account) {
        if (!empty($account['google_id']) && !hash_equals((string)$account['google_id'], $googleId)) {
            google_callback_error('This email is already connected to a different Google account. Sign in using your existing method.', 409);
        }

        $role = (string)$account['role'];
        if (!in_array($role, ['Buyer', 'Client'], true)) {
            google_callback_error('Google Sign-In is available for Buyer and Client accounts only.', 403);
        }

        if (empty($account['google_id'])) {
            hb_query($conn, 'UPDATE users SET google_id=?, is_verified=1 WHERE id=?', [$googleId, (int)$account['id']]);
        }
        $userId = (int)$account['id'];
        $username = (string)$account['username'];
    } else {
        // New Google users must complete their HomeBid profile before an account is created.
        $displayName = trim((string)($claims['name'] ?? ''));
        $suggestedUsername = $displayName !== '' ? $displayName : (string)strstr($email, '@', true);
        $suggestedUsername = trim(function_exists('mb_substr')
            ? mb_substr($suggestedUsername, 0, 50)
            : substr($suggestedUsername, 0, 50));

        $_SESSION['google_registration'] = [
            'google_id' => $googleId,
            'email' => $email,
            'suggested_username' => $suggestedUsername !== '' ? $suggestedUsername : 'Google User',
            'started_at' => time(),
        ];

        header('Location: google-register.php');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = $role;
    $_SESSION['username'] = $username;

    $destination = $role === 'Client' ? 'client/dashboard.php' : 'buyer/dashboard.php';
    header('Location: ' . $destination);
    exit;
} catch (Throwable $e) {
    error_log('HomeBid Google callback error: ' . $e->getMessage());
    google_callback_error('The server could not finish signing you in. Please check the PHP error log.', 500);
}
