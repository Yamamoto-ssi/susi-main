<?php
declare(strict_types=1);
require_once __DIR__.'/../config/auth.php';
$viewerId = require_roles(['Administrator','Staff']);
require_once __DIR__.'/../config/db.php';

function rh($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function rmoney($value): string { return '₱' . number_format((float)$value, 2); }

$q = trim((string)($_GET['q'] ?? ''));
$sql = "SELECT cp.id, cp.payment_type, cp.amount, cp.external_id, cp.invoice_id, cp.created_at,
               p.title AS property_title, u.username AS buyer_name, u.email AS buyer_email
        FROM checkout_payments cp
        INNER JOIN properties p ON p.id = cp.property_id
        INNER JOIN users u ON u.id = cp.user_id
        WHERE cp.status = 'paid'";
$args = [];
if ($q !== '') {
    $sql .= " AND (p.title LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR cp.external_id LIKE ? OR cp.invoice_id LIKE ? OR CAST(cp.id AS CHAR) LIKE ?)";
    $term = '%' . $q . '%';
    $args = [$term, $term, $term, $term, $term, $term];
}
$sql .= " ORDER BY cp.created_at DESC, cp.id DESC LIMIT 300";
$payments = hb_fetch_all(hb_query($conn, $sql, $args));
$totalPaid = (float)hb_fetch_value(hb_query($conn, "SELECT COALESCE(SUM(amount),0) FROM checkout_payments WHERE status='paid'"));
$totalCount = (int)hb_fetch_value(hb_query($conn, "SELECT COUNT(*) FROM checkout_payments WHERE status='paid'"));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Paid Receipts | HomeBid</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f5f7f8;color:#17212b;font:14px Arial,sans-serif}.wrap{max-width:1200px;margin:0 auto;padding:28px 18px}.top{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:22px}.top h1{margin:0;font-size:27px}.muted{color:#687783}.back,.print-link{display:inline-flex;align-items:center;padding:9px 12px;border-radius:8px;text-decoration:none;font-weight:700}.back{background:#e8edf0;color:#17212b}.print-link{background:#173e36;color:#fff}.stats{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:18px}.stat,.panel{background:#fff;border:1px solid #e1e7ea;border-radius:12px;padding:17px}.stat strong{display:block;font-size:22px;margin-top:7px}.panel{overflow:hidden}.tools{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.tools form{display:flex;gap:8px;flex:1;max-width:520px}.tools input{min-width:0;flex:1;padding:10px 12px;border:1px solid #ccd5da;border-radius:8px;font:inherit}.tools button{border:0;border-radius:8px;background:#172b3b;color:white;padding:10px 15px;font-weight:700;cursor:pointer}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:850px}th,td{text-align:left;padding:12px 10px;border-bottom:1px solid #edf0f2;vertical-align:top}th{font-size:11px;color:#687783;text-transform:uppercase;letter-spacing:.04em}.status{display:inline-block;padding:5px 9px;border-radius:30px;background:#eaf8ef;color:#17643a;font-size:11px;font-weight:700}.empty{text-align:center;padding:30px;color:#687783}@media(max-width:600px){.wrap{padding:18px 12px}.top{align-items:flex-start;flex-direction:column}.tools{align-items:stretch;flex-direction:column}.tools form{max-width:none}}
</style>
</head>
<body><main class="wrap">
<header class="top"><div><p class="muted">HOMEBID MANAGEMENT</p><h1>Paid receipts</h1><p class="muted">View and print receipts for payments confirmed as paid.</p></div>
<a class="back" href="<?=($_SESSION['role']??'')==='Administrator'?'../admin/dashboard.php':'../staff/staff_dashboard.php'?>">← Back to dashboard</a></header>
<section class="stats"><div class="stat"><span class="muted">Paid transactions</span><strong><?=number_format($totalCount)?></strong></div><div class="stat"><span class="muted">Total paid amount</span><strong><?=rh(rmoney($totalPaid))?></strong></div></section>
<section class="panel"><div class="tools"><div><strong>Payment records</strong><div class="muted">Showing up to 300 most recent paid transactions.</div></div>
<form method="get"><input type="search" name="q" value="<?=rh($q)?>" placeholder="Search buyer, property, receipt or reference"><button type="submit">Search</button></form></div>
<div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Type</th><th>Buyer</th><th>Property</th><th>Amount paid</th><th>Payment date</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php if(!$payments):?><tr><td class="empty" colspan="8">No paid receipts found.</td></tr><?php endif;?>
<?php foreach($payments as $payment): $receiptPage=$payment['payment_type']==='auction'?'auction-receipt.php':'property-receipt.php'; $receiptNo=($payment['payment_type']==='auction'?'HB-AUC-':'HB-RES-').str_pad((string)$payment['id'],6,'0',STR_PAD_LEFT); ?>
<tr><td><strong><?=rh($receiptNo)?></strong><br><span class="muted">#<?= (int)$payment['id']?></span></td><td><?=rh(ucfirst($payment['payment_type']))?></td><td><?=rh($payment['buyer_name'])?><br><span class="muted"><?=rh($payment['buyer_email'])?></span></td><td><?=rh($payment['property_title'])?></td><td><strong><?=rh(rmoney($payment['amount']))?></strong></td><td><?=rh(date('M j, Y g:i a',strtotime($payment['created_at'])))?></td><td><span class="status">Paid</span></td><td><a class="print-link" href="<?=$receiptPage?>?payment_id=<?=(int)$payment['id']?>" target="_blank" rel="noopener">View / Print</a></td></tr>
<?php endforeach;?>
</tbody></table></div></section>
</main></body></html>
