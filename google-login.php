<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

function google_respond(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        google_respond(405, ['ok' => false, 'message' => 'Invalid request.']);
    }

    $clientId = getenv('GOOGLE_CLIENT_ID') ?: '';
    $credential = trim((string)($_POST['credential'] ?? ''));
    if ($clientId === '') {
        google_respond(503, ['ok' => false, 'message' => 'Google Sign-In is not configured yet.']);
    }
    if ($credential === '') {
        google_respond(422, ['ok' => false, 'message' => 'Google did not return an account credential.']);
    }
    if (!function_exists('curl_init')) {
        google_respond(500, ['ok' => false, 'message' => 'The PHP cURL extension is required for Google Sign-In.']);
    }

    // Ask Google's token information endpoint to validate the ID token and its audience.
    $curl = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($credential));
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $responseBody = curl_exec($curl);
    $httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if (!is_string($responseBody) || $httpStatus !== 200) {
        google_respond(401, ['ok' => false, 'message' => 'Google could not verify this sign-in. Please try again.']);
    }

    $claims = json_decode($responseBody, true);
    $issuer = (string)($claims['iss'] ?? '');
    $email = strtolower(trim((string)($claims['email'] ?? '')));
    $googleId = trim((string)($claims['sub'] ?? ''));
    $audience = (string)($claims['aud'] ?? '');
    $emailVerified = ($claims['email_verified'] ?? false) === true
        || ($claims['email_verified'] ?? '') === 'true';

    if (!is_array($claims)
        || !in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
        || !hash_equals($clientId, $audience)
        || !$emailVerified
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $googleId === '') {
        google_respond(401, ['ok' => false, 'message' => 'The Google account could not be verified.']);
    }

    require_once __DIR__ . '/config/auth.php';
    require_once __DIR__ . '/config/db.php';

    $accountResult = hb_query(
        $conn,
        'SELECT id, username, role, google_id FROM users WHERE google_id=? OR email=? LIMIT 1',
        [$googleId, $email]
    );
    $account = hb_fetch_one($accountResult);

    if ($account) {
        if (!empty($account['google_id']) && !hash_equals((string)$account['google_id'], $googleId)) {
            google_respond(409, ['ok' => false, 'message' => 'This email is already connected to a different Google account.']);
        }
        $role = (string)$account['role'];
        if (!in_array($role, ['Buyer', 'Client'], true)) {
            google_respond(403, ['ok' => false, 'message' => 'Google Sign-In is available for Buyer and Client accounts only.']);
        }
        if (empty($account['google_id'])) {
            hb_query($conn, 'UPDATE users SET google_id=?, is_verified=1 WHERE id=?', [$googleId, (int)$account['id']]);
        }
        $userId = (int)$account['id'];
        $username = (string)$account['username'];
    } else {
        $displayName = trim((string)($claims['name'] ?? ''));
        $username = $displayName !== '' ? $displayName : strstr($email, '@', true);
        $username = trim(function_exists('mb_substr') ? mb_substr($username, 0, 50) : substr($username, 0, 50));
        if ($username === '') $username = 'Google User';

        $baseUsername = $username;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $nameCheck = hb_fetch_one(hb_query($conn, 'SELECT id FROM users WHERE username=? LIMIT 1', [$username]));
            if (!$nameCheck) break;
            $suffix = (string)random_int(1000, 9999);
            $username = substr($baseUsername, 0, 45) . $suffix;
        }
        $existingEmail = hb_fetch_one(hb_query($conn, 'SELECT id FROM users WHERE email=? LIMIT 1', [$email]));
        if ($existingEmail) {
            google_respond(409, ['ok' => false, 'message' => 'An account already uses this email. Please sign in with your password first.']);
        }

        $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        hb_query(
            $conn,
            "INSERT INTO users (username, email, google_id, password, role, is_verified) VALUES (?, ?, ?, ?, 'Buyer', 1)",
            [$username, $email, $googleId, $randomPassword]
        );
        $userId = (int)mysqli_insert_id($conn);
        $role = 'Buyer';
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = $role;
    $_SESSION['username'] = $username;

    $redirect = $role === 'Client' ? 'client/dashboard.php' : 'buyer/dashboard.php';
    google_respond(200, ['ok' => true, 'message' => 'Google sign-in successful.', 'redirect' => $redirect]);
} catch (Throwable $e) {
    error_log('HomeBid Google login error: ' . $e->getMessage());
    google_respond(500, ['ok' => false, 'message' => 'The server could not complete Google sign-in. Check the PHP error log.']);
}
