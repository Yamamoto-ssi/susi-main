<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
    
$localGoogleConfig = is_file(__DIR__ . '/config/google.local.php') ? require __DIR__ . '/config/google.local.php' : [];
if (!is_array($localGoogleConfig)) $localGoogleConfig = [];
$clientId = getenv('GOOGLE_CLIENT_ID') ?: (string)($localGoogleConfig['client_id'] ?? '44077547718-4nesakkabijeqadjsse1fq82knq0rm1n.apps.googleusercontent.com');
$redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/susi/google-callback.php';

if ($clientId === '') {
    http_response_code(503);
    exit('Google Sign-In is not configured. Set GOOGLE_CLIENT_ID in your server environment.');
}

$intent = (string)($_GET['intent'] ?? 'login');
if (!in_array($intent, ['login','signup'], true)) $intent = 'login';
$_SESSION['google_auth_intent'] = $intent;
$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;
$_SESSION['google_oauth_started'] = time();

$params = [
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
exit;
