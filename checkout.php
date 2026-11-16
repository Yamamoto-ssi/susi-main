<?php
session_start();
require_once 'config/db.php';

function showCheckoutError(string $message, int $statusCode = 400): void
{
    http_response_code($statusCode);
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Checkout - Mendoza Real Estate</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-5"><div class="card border-0 shadow-sm mx-auto" style="max-width: 640px"><div class="card-body p-4"><h1 class="h4">Checkout could not be completed</h1><p class="mb-4">' . $safeMessage . '</p><a class="btn btn-dark" href="home.php">Return to home</a></div></div></main></body></html>';
    exit;
}

function getApplicationBaseUrl(): string
{
    $configuredUrl = getenv('APP_BASE_URL');
    if ($configuredUrl !== false && trim($configuredUrl) !== '') {
        return rtrim(trim($configuredUrl), '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/checkout.php'));
    $directory = $directory === '/' || $directory === '.' ? '' : '/' . trim($directory, '/');

    return $scheme . '://' . $serverName . $directory;
}

function markPaymentFailed(mysqli $conn, int $paymentId, ?int $reservationId): void
{
    $paymentStatement = $conn->prepare("UPDATE checkout_payments SET status = 'failed' WHERE id = ? AND status = 'creating'");
    $paymentStatement->bind_param('i', $paymentId);
    $paymentStatement->execute();
    $paymentStatement->close();

    if ($reservationId !== null) {
        $reservationStatement = $conn->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = ? AND status = 'Pending'");
        $reservationStatement->bind_param('i', $reservationId);
        $reservationStatement->execute();
        $reservationStatement->close();
    }
}

function sendWebhookResponse(int $statusCode, array $response): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') === 0) {
    $callbackToken = getenv('XENDIT_CALLBACK_TOKEN');
    $receivedToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';
    if ($callbackToken === false || trim($callbackToken) === '' || !is_string($receivedToken) || !hash_equals(trim($callbackToken), $receivedToken)) {
        error_log('Checkout webhook rejected: invalid callback token.');
        sendWebhookResponse(403, ['error' => 'Invalid callback token.']);
    }

    $callback = json_decode(file_get_contents('php://input'), true);
    $externalId = is_array($callback) ? ($callback['external_id'] ?? '') : '';
    $callbackStatus = is_array($callback) ? strtoupper((string) ($callback['status'] ?? '')) : '';
    if (!is_string($externalId) || $externalId === '' || !in_array($callbackStatus, ['PAID', 'EXPIRED'], true)) {
        sendWebhookResponse(400, ['error' => 'Invalid invoice callback payload.']);
    }

    try {
        $conn->begin_transaction();
        $paymentLookup = $conn->prepare('SELECT id, reservation_id, property_id, amount, status FROM checkout_payments WHERE external_id = ? LIMIT 1 FOR UPDATE');
        $paymentLookup->bind_param('s', $externalId);
        $paymentLookup->execute();
        $payment = $paymentLookup->get_result()->fetch_assoc();
        $paymentLookup->close();

        if (!$payment) {
            $conn->rollback();
            sendWebhookResponse(404, ['error' => 'Payment record not found.']);
        }

        if ($callbackStatus === 'PAID') {
            $paidAmount = $callback['paid_amount'] ?? null;
            if (!is_numeric($paidAmount) || abs((float) $paidAmount - (float) $payment['amount']) > 0.01) {
                $conn->rollback();
                error_log('Checkout webhook rejected: invoice amount mismatch for payment ' . (int) $payment['id'] . '.');
                sendWebhookResponse(400, ['error' => 'Invoice amount does not match.']);
            }
        }

        if ($payment['status'] !== 'paid' && $payment['status'] !== 'expired') {
            $newStatus = strtolower($callbackStatus);
            $paymentUpdate = $conn->prepare('UPDATE checkout_payments SET status = ? WHERE id = ?');
            $paymentUpdate->bind_param('si', $newStatus, $payment['id']);
            $paymentUpdate->execute();
            $paymentUpdate->close();

            if ($payment['reservation_id'] !== null) {
                if ($callbackStatus === 'PAID') {
                    $reservationUpdate = $conn->prepare("UPDATE reservations SET status = 'Approved' WHERE id = ? AND status IN ('Pending', 'Cancelled')");
                } else {
                    $reservationUpdate = $conn->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = ? AND status = 'Pending'");
                }
                $reservationUpdate->bind_param('i', $payment['reservation_id']);
                $reservationUpdate->execute();
                $reservationUpdate->close();

                if ($callbackStatus === 'PAID') {
                    $propertyUpdate = $conn->prepare("UPDATE properties SET status = 'Reserved' WHERE id = ? AND status = 'Available'");
                    $propertyUpdate->bind_param('i', $payment['property_id']);
                    $propertyUpdate->execute();
                    if ($propertyUpdate->affected_rows !== 1) {
                        error_log('Checkout payment ' . (int) $payment['id'] . ' was paid, but its property was no longer available to reserve.');
                    }
                    $propertyUpdate->close();
                }
            }
        }

        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        error_log('Checkout webhook database operation failed: ' . $exception->getMessage());
        sendWebhookResponse(500, ['error' => 'Could not update payment status.']);
    }

    sendWebhookResponse(200, ['received' => true]);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showCheckoutError('Start checkout from a property details page or a completed auction.', 405);
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !is_string($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    showCheckoutError('Your session expired. Go back and submit checkout again.', 403);
}

$secretKey = getenv('XENDIT_SECRET_KEY');
if ($secretKey === false || trim($secretKey) === '') {
    error_log('Checkout unavailable: XENDIT_SECRET_KEY is not configured.');
    showCheckoutError('Online payment is not configured yet. Please contact the site administrator.', 503);
}

$userId = (int) $_SESSION['user_id'];
$paymentType = $_POST['payment_type'] ?? '';
$propertyId = filter_var($_POST['property_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$auctionId = filter_var($_POST['auction_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!in_array($paymentType, ['reservation', 'auction'], true)) {
    showCheckoutError('The selected checkout type is invalid.');
}

if (($paymentType === 'reservation' && $propertyId === false) || ($paymentType === 'auction' && $auctionId === false)) {
    showCheckoutError('The selected property or auction is invalid.');
}

try {
    $userStatement = $conn->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
    $userStatement->bind_param('i', $userId);
    $userStatement->execute();
    $userResult = $userStatement->get_result();
    $user = $userResult->fetch_assoc();
    $userStatement->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Checkout user lookup failed: ' . $exception->getMessage());
    showCheckoutError('We could not verify your account. Please try again later.', 500);
}

if (!$user || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
    showCheckoutError('Your account needs a valid email address before payment can continue.');
}

$reservationId = null;
$paymentId = null;
$amount = 0.0;
$description = '';
$successUrl = '';
$failureUrl = '';

try {
    $conn->begin_transaction();

    if ($paymentType === 'reservation') {
        $propertyStatement = $conn->prepare("SELECT id, title FROM properties WHERE id = ? AND status = 'Available' FOR UPDATE");
        $propertyStatement->bind_param('i', $propertyId);
        $propertyStatement->execute();
        $property = $propertyStatement->get_result()->fetch_assoc();
        $propertyStatement->close();

        if (!$property) {
            $conn->rollback();
            showCheckoutError('This property is not currently available for reservation.');
        }

        $staleCreatingStatement = $conn->prepare("UPDATE checkout_payments SET status = 'failed' WHERE payment_type = 'reservation' AND property_id = ? AND status = 'creating' AND created_at <= DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        $staleCreatingStatement->bind_param('i', $propertyId);
        $staleCreatingStatement->execute();
        $staleCreatingStatement->close();
        $staleInvoiceStatement = $conn->prepare("UPDATE checkout_payments SET status = 'expired' WHERE payment_type = 'reservation' AND property_id = ? AND status = 'pending' AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $staleInvoiceStatement->bind_param('i', $propertyId);
        $staleInvoiceStatement->execute();
        $staleInvoiceStatement->close();

        $existingStatement = $conn->prepare("SELECT id, user_id, invoice_url, status FROM checkout_payments WHERE payment_type = 'reservation' AND property_id = ? AND status IN ('creating', 'pending') ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $existingStatement->bind_param('i', $propertyId);
        $existingStatement->execute();
        $existingPayment = $existingStatement->get_result()->fetch_assoc();
        $existingStatement->close();

        if ($existingPayment) {
            $conn->commit();
            if ((int) $existingPayment['user_id'] === $userId && $existingPayment['status'] === 'pending' && !empty($existingPayment['invoice_url'])) {
                header('Location: ' . $existingPayment['invoice_url']);
                exit;
            }
            showCheckoutError('A payment for this property is already being processed. Please try again later.', 409);
        }

        $auctionId = null;
        $amount = 5000.00;
        $reservationStatement = $conn->prepare("INSERT INTO reservations (property_id, user_id, reservation_fee, status) VALUES (?, ?, ?, 'Pending')");
        $reservationStatement->bind_param('iid', $propertyId, $userId, $amount);
        $reservationStatement->execute();
        $reservationId = (int) $conn->insert_id;
        $reservationStatement->close();

        $description = 'Reservation fee for ' . $property['title'];
        $successUrl = getApplicationBaseUrl() . '/property_details.php?id=' . $propertyId . '&payment=success';
        $failureUrl = getApplicationBaseUrl() . '/property_details.php?id=' . $propertyId . '&payment=failed';
    } else {
        $expireStatement = $conn->prepare("UPDATE auctions SET status = 'ended' WHERE id = ? AND status = 'active' AND end_time <= NOW()");
        $expireStatement->bind_param('i', $auctionId);
        $expireStatement->execute();
        $expireStatement->close();

        $auctionStatement = $conn->prepare("SELECT a.id, a.property_id, a.current_bid, a.highest_bidder_id, a.status, p.title FROM auctions a JOIN properties p ON p.id = a.property_id WHERE a.id = ? FOR UPDATE");
        $auctionStatement->bind_param('i', $auctionId);
        $auctionStatement->execute();
        $auction = $auctionStatement->get_result()->fetch_assoc();
        $auctionStatement->close();

        if (!$auction || $auction['status'] !== 'ended' || (int) $auction['highest_bidder_id'] !== $userId) {
            $conn->rollback();
            showCheckoutError('Only the winning bidder can pay, and checkout is available after the auction has ended.');
        }

        $reservationId = null;
        $staleCreatingStatement = $conn->prepare("UPDATE checkout_payments SET status = 'failed' WHERE payment_type = 'auction' AND auction_id = ? AND status = 'creating' AND created_at <= DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        $staleCreatingStatement->bind_param('i', $auctionId);
        $staleCreatingStatement->execute();
        $staleCreatingStatement->close();
        $staleInvoiceStatement = $conn->prepare("UPDATE checkout_payments SET status = 'expired' WHERE payment_type = 'auction' AND auction_id = ? AND status = 'pending' AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $staleInvoiceStatement->bind_param('i', $auctionId);
        $staleInvoiceStatement->execute();
        $staleInvoiceStatement->close();

        $existingStatement = $conn->prepare("SELECT id, invoice_url, status FROM checkout_payments WHERE payment_type = 'auction' AND auction_id = ? AND status IN ('creating', 'pending') ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $existingStatement->bind_param('i', $auctionId);
        $existingStatement->execute();
        $existingPayment = $existingStatement->get_result()->fetch_assoc();
        $existingStatement->close();

        if ($existingPayment) {
            $conn->commit();
            if ($existingPayment['status'] === 'pending' && !empty($existingPayment['invoice_url'])) {
                header('Location: ' . $existingPayment['invoice_url']);
                exit;
            }
            showCheckoutError('A payment invoice for this auction is already being prepared. Please wait a moment and try again.', 409);
        }

        $propertyId = (int) $auction['property_id'];
        $amount = (float) $auction['current_bid'];
        if ($amount <= 0) {
            $conn->rollback();
            showCheckoutError('The winning bid amount is invalid.');
        }
        $description = 'Winning bid payment for ' . $auction['title'];
        $successUrl = getApplicationBaseUrl() . '/bid_room.php?id=' . $auctionId . '&payment=success';
        $failureUrl = getApplicationBaseUrl() . '/bid_room.php?id=' . $auctionId . '&payment=failed';
    }

    $externalId = strtoupper($paymentType) . '_' . bin2hex(random_bytes(12));
    $paymentStatement = $conn->prepare('INSERT INTO checkout_payments (payment_type, reservation_id, auction_id, property_id, user_id, amount, external_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, \'creating\')');
    $paymentStatement->bind_param('siiiids', $paymentType, $reservationId, $auctionId, $propertyId, $userId, $amount, $externalId);
    $paymentStatement->execute();
    $paymentId = (int) $conn->insert_id;
    $paymentStatement->close();

    $conn->commit();
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    error_log('Checkout database operation failed: ' . $exception->getMessage());
    showCheckoutError('We could not start checkout. Please try again later.', 500);
}

$invoiceData = [
    'external_id' => $externalId,
    'amount' => $amount,
    'invoice_duration' => 86400,
    'payer_email' => $user['email'],
    'description' => $description,
    'success_redirect_url' => $successUrl,
    'failure_redirect_url' => $failureUrl,
];

$curl = curl_init('https://api.xendit.co/v2/invoices');
if ($curl === false) {
    markPaymentFailed($conn, $paymentId, $reservationId);
    error_log('Checkout invoice request could not be initialized.');
    showCheckoutError('The payment service is temporarily unavailable. Please try again later.', 502);
}

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($invoiceData),
    CURLOPT_USERPWD => trim($secretKey) . ':',
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($curl);
$httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

$invoice = is_string($response) ? json_decode($response, true) : null;
$invoiceUrl = is_array($invoice) ? ($invoice['invoice_url'] ?? '') : '';
$invoiceId = is_array($invoice) ? ($invoice['id'] ?? '') : '';
$invoiceHost = is_string($invoiceUrl) ? parse_url($invoiceUrl, PHP_URL_HOST) : false;
$invoiceScheme = is_string($invoiceUrl) ? parse_url($invoiceUrl, PHP_URL_SCHEME) : false;

if ($response === false || $curlError !== '' || !in_array($httpCode, [200, 201], true) || !is_string($invoiceId) || $invoiceId === '' || !is_string($invoiceUrl) || $invoiceScheme !== 'https' || $invoiceHost !== 'checkout.xendit.co') {
    markPaymentFailed($conn, $paymentId, $reservationId);
    error_log('Checkout invoice creation failed (HTTP ' . $httpCode . '): ' . ($curlError !== '' ? $curlError : 'invalid provider response'));
    showCheckoutError('We could not create your payment invoice. No payment was completed. Please try again later.', 502);
}

try {
    $updateStatement = $conn->prepare("UPDATE checkout_payments SET invoice_id = ?, invoice_url = ?, status = 'pending' WHERE id = ? AND status = 'creating'");
    $updateStatement->bind_param('ssi', $invoiceId, $invoiceUrl, $paymentId);
    $updateStatement->execute();
    $updateStatement->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Checkout invoice record update failed for payment ' . $paymentId . ': ' . $exception->getMessage());
    showCheckoutError('The invoice was created, but we could not save its payment record. Please contact support before trying again.', 500);
}

header('Location: ' . $invoiceUrl);
exit;
