<?php
session_start();
require_once __DIR__ . '/config/db.php';

$configPath = __DIR__ . '/config/xendit.local.php';
$xenditConfig = is_file($configPath) ? require $configPath : [];
if (!is_array($xenditConfig)) $xenditConfig = [];

function xenditReturnPage(string $title, string $message, string $tone = 'info'): void
{
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $color = $tone === 'success' ? '#18794e' : ($tone === 'error' ? '#b42318' : '#475467');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $title . ' | HomeBid</title><style>body{font-family:Arial,sans-serif;background:#f6f8f6;color:#26343d;margin:0;padding:28px}.card{max-width:620px;margin:8vh auto;background:white;border:1px solid #e5e9e6;border-radius:14px;padding:30px;box-shadow:0 8px 30px #172b1a0d}h1{font-size:24px;color:#263b32}p{line-height:1.7;color:' . $color . '}a{display:inline-block;margin-top:12px;padding:12px 18px;background:#263b32;color:white;text-decoration:none;border-radius:8px;font-weight:bold}</style></head><body><main class="card"><h1>' . $title . '</h1><p>' . $message . '</p><a href="buyer/dashboard.php">Return to HomeBid</a></main></body></html>';
    exit;
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Buyer') {
    xenditReturnPage('Sign in required', 'Please sign in to your HomeBid buyer account to check your payment status.');
}

$externalId = $_GET['external_id'] ?? '';
if (!is_string($externalId) || !preg_match('/^(RESERVATION|AUCTION)_[A-F0-9]{24}$/', $externalId)) {
    xenditReturnPage('Invalid payment reference', 'We could not identify this payment. Please open the payment again from your HomeBid account.', 'error');
}

try {
    $lookup = $conn->prepare('SELECT id, payment_type, reservation_id, auction_id, property_id, user_id, amount, external_id, invoice_id, status FROM checkout_payments WHERE external_id = ? AND user_id = ? LIMIT 1');
    $userId = (int) $_SESSION['user_id'];
    $lookup->bind_param('si', $externalId, $userId);
    $lookup->execute();
    $payment = $lookup->get_result()->fetch_assoc();
    $lookup->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Xendit return payment lookup failed: ' . $exception->getMessage());
    xenditReturnPage('Payment status unavailable', 'We could not check your payment right now. Please try again later.', 'error');
}

if (!$payment) {
    xenditReturnPage('Payment not found', 'This payment reference does not belong to your account.', 'error');
}

if ($payment['status'] !== 'paid') {
    $secretKey = getenv('XENDIT_SECRET_KEY');
    if (($secretKey === false || trim($secretKey) === '') && !empty($xenditConfig['secret_key'])) {
        $secretKey = $xenditConfig['secret_key'];
    }
    if ($secretKey === false || trim($secretKey) === '' || empty($payment['invoice_id'])) {
        xenditReturnPage('Payment confirmation pending', 'HomeBid could not verify the invoice yet. Do not pay again. Please contact support or check your buyer dashboard later.', 'error');
    }

    $curl = curl_init('https://api.xendit.co/v2/invoices/' . rawurlencode($payment['invoice_id']));
    if ($curl === false) {
        xenditReturnPage('Payment confirmation pending', 'The payment service is temporarily unavailable. Do not pay again; please check again later.', 'error');
    }
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_USERPWD => trim($secretKey) . ':',
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);
    $response = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    $invoice = is_string($response) ? json_decode($response, true) : null;
    if ($response === false || $curlError !== '' || $httpCode !== 200 || !is_array($invoice)
        || ($invoice['id'] ?? '') !== $payment['invoice_id']
        || ($invoice['external_id'] ?? '') !== $payment['external_id']) {
        error_log('Xendit return invoice verification failed (HTTP ' . $httpCode . '): ' . ($curlError !== '' ? $curlError : 'invalid provider response'));
        xenditReturnPage('Payment confirmation pending', 'Xendit has not returned a verifiable payment status yet. Do not pay again. Please check again shortly.', 'error');
    }

    $invoiceStatus = strtoupper((string) ($invoice['status'] ?? ''));
    if ($invoiceStatus === 'PAID') {
        $invoiceAmount = $invoice['paid_amount'] ?? null;
        $invoiceCurrency = strtoupper((string) ($invoice['currency'] ?? ''));
        if (!is_numeric($invoiceAmount) || abs((float) $invoiceAmount - (float) $payment['amount']) > 0.01 || $invoiceCurrency !== 'PHP') {
            error_log('Xendit return invoice amount or currency mismatch for payment ' . (int) $payment['id'] . '.');
            xenditReturnPage('Payment verification failed', 'The invoice details did not match this HomeBid payment. Please contact support before taking any further action.', 'error');
        }

        try {
            $conn->begin_transaction();
            $lock = $conn->prepare('SELECT status FROM checkout_payments WHERE id = ? FOR UPDATE');
            $paymentId = (int) $payment['id'];
            $lock->bind_param('i', $paymentId);
            $lock->execute();
            $lockedPayment = $lock->get_result()->fetch_assoc();
            $lock->close();

            if ($lockedPayment && $lockedPayment['status'] !== 'paid') {
                $paidUpdate = $conn->prepare("UPDATE checkout_payments SET status = 'paid' WHERE id = ?");
                $paidUpdate->bind_param('i', $paymentId);
                $paidUpdate->execute();
                $paidUpdate->close();

                if ($payment['payment_type'] === 'reservation' && $payment['reservation_id'] !== null) {
                    $propertyUpdate = $conn->prepare("UPDATE properties SET status = 'Reserved' WHERE id = ? AND status = 'Available'");
                    $propertyUpdate->bind_param('i', $payment['property_id']);
                    $propertyUpdate->execute();
                    $propertyReserved = $propertyUpdate->affected_rows === 1;
                    $propertyUpdate->close();

                    if ($propertyReserved) {
                        $reservationUpdate = $conn->prepare("UPDATE reservations SET status = 'Approved' WHERE id = ? AND status = 'Pending'");
                        $reservationUpdate->bind_param('i', $payment['reservation_id']);
                        $reservationUpdate->execute();
                        $reservationUpdate->close();
                    } else {
                        error_log('Xendit payment ' . $paymentId . ' was paid but the property was no longer available; reservation left pending for manual review.');
                    }
                } elseif ($payment['payment_type'] === 'auction' && $payment['auction_id'] !== null) {
                    $propertyUpdate = $conn->prepare("UPDATE properties SET status = 'Sold' WHERE id = ? AND status IN ('Auction', 'Available')");
                    $propertyUpdate->bind_param('i', $payment['property_id']);
                    $propertyUpdate->execute();
                    if ($propertyUpdate->affected_rows !== 1) {
                        error_log('Xendit auction payment ' . $paymentId . ' was paid but the property status could not be changed automatically.');
                    }
                    $propertyUpdate->close();
                }
            }
            $conn->commit();
            $payment['status'] = 'paid';
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log('Xendit return payment update failed: ' . $exception->getMessage());
            xenditReturnPage('Payment received; update pending', 'Xendit reports payment, but HomeBid could not finish updating the order. Please contact support and do not pay again.', 'error');
        }
    } elseif ($invoiceStatus === 'EXPIRED') {
        try {
            $conn->begin_transaction();
            $expiredUpdate = $conn->prepare("UPDATE checkout_payments SET status = 'expired' WHERE id = ? AND status IN ('creating', 'pending')");
            $paymentId = (int) $payment['id'];
            $expiredUpdate->bind_param('i', $paymentId);
            $expiredUpdate->execute();
            $expiredUpdate->close();
            if ($payment['reservation_id'] !== null) {
                $reservationUpdate = $conn->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = ? AND status = 'Pending'");
                $reservationUpdate->bind_param('i', $payment['reservation_id']);
                $reservationUpdate->execute();
                $reservationUpdate->close();
            }
            $conn->commit();
            xenditReturnPage('Invoice expired', 'The Xendit invoice has expired and was not marked as paid. You can start a new checkout if the property is still available.', 'error');
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log('Xendit expired invoice update failed: ' . $exception->getMessage());
            xenditReturnPage('Payment status unavailable', 'Please contact support to confirm the invoice status.', 'error');
        }
    } else {
        xenditReturnPage('Payment not yet completed', 'Xendit reports that this invoice is not paid yet. Do not create another payment while this invoice is still active.', 'error');
    }
}

if ($payment['payment_type'] === 'reservation') {
    $reservationStatus = false;
    if ($payment['reservation_id'] !== null) {
        $statusLookup = $conn->prepare('SELECT status FROM reservations WHERE id = ? LIMIT 1');
        $statusLookup->bind_param('i', $payment['reservation_id']);
        $statusLookup->execute();
        $reservationStatus = $statusLookup->get_result()->fetch_assoc();
        $statusLookup->close();
    }
    if ($reservationStatus && $reservationStatus['status'] === 'Approved') {
        xenditReturnPage('Payment successful', 'Xendit confirmed your ₱' . number_format((float) $payment['amount'], 2) . ' reservation payment. Your property reservation is confirmed.', 'success');
    }
    xenditReturnPage('Payment received; reservation review needed', 'Xendit confirmed your payment, but the property is no longer available to reserve automatically. Your payment is recorded; please contact HomeBid support to resolve the reservation.', 'error');
}

xenditReturnPage('Payment successful', 'Xendit confirmed your auction payment of ₱' . number_format((float) $payment['amount'], 2) . '. Your payment receipt is available in your buyer account.', 'success');
