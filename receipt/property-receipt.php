<?php
declare(strict_types=1);
require_once __DIR__.'/../config/auth.php';
$userId = require_roles(['Buyer']);
require_once __DIR__.'/../config/db.php';

function bh($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function buyer_money($v): string { return '₱' . number_format((float)$v, 2); }
function buyer_one(mysqli $conn, string $sql, array $args = []): array { $s = hb_query($conn, $sql, $args); return hb_fetch_one($s) ?: []; }

// Fetch logged-in buyer account details
$account = buyer_one($conn, 'SELECT id, username, email FROM users WHERE id=?', [$userId]);
$buyerName = $account['username'] ?? 'Juan Dela Cruz';
$buyerEmail = $account['email'] ?? 'buyer@homebid.ph';
$receiptNo = 'PRV-' . date('Y') . '-' . str_pad((string)$userId, 5, '0', STR_PAD_LEFT);

// Temporary property purchase / reservation receipt data
$receipt = [
    'buyer_name'     => $buyerName,
    'buyer_address'  => 'Angeles City, Pampanga 2009',
    'buyer_phone'    => '+63 912 345 6789',
    'buyer_email'    => $buyerEmail,
    'constituent_id' => $receiptNo,
    'purchased_on'   => date('m/d/Y'),
    'printed_on'     => date('m/d/Y'),
    'payment_type'   => 'Reservation Fee / Bank Deposit',
    'reference_no'   => 'REF-9941820',
    'item' => [
        'name'           => 'Minimalist Townhouse — Angeles City, Pampanga',
        'category'       => 'House & Lot',
        'transaction'    => 'Direct Purchase / Reservation',
        'property_price' => 3200000.00,
        'amount_paid'    => 50000.00, // Reservation or initial downpayment
        'balance_due'    => 3150000.00
    ]
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Property Receipt — <?= bh($receipt['constituent_id']) ?> | HomeBid</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Georgia, serif;
            color: #111111;
            background: #f4f5f6;
            margin: 0;
            padding: 28px 16px;
            line-height: 1.45;
        }
        .no-print-bar {
            max-width: 820px;
            margin: 0 auto 16px;
            padding: 12px 18px;
            background: #173e36;
            color: #ffffff;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .no-print-bar a {
            color: #ffd982;
            text-decoration: none;
            font-weight: 700;
        }
        .receipt-page {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 46px 50px;
            border: 1px solid #dcdfe4;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .header-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 36px;
        }
        .col-left, .col-right {
            width: 36%;
            font-size: 14px;
        }
        .col-left p, .col-right p {
            margin: 0 0 8px;
        }
        .col-center {
            width: 28%;
            text-align: center;
        }
        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: Georgia, serif;
            font-size: 22px;
            font-weight: 700;
            color: #173e36;
        }
        .brand-logo span.symbol {
            display: inline-grid;
            place-items: center;
            width: 34px;
            height: 34px;
            background: #173e36;
            color: #d6a94c;
            border-radius: 8px;
            font-size: 18px;
        }
        .brand-sub {
            display: block;
            font-size: 10px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #666666;
            margin-top: 3px;
            font-family: Arial, sans-serif;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .meta-row p {
            margin: 0 0 8px;
        }
        .meta-right {
            text-align: right;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 22px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 13.5px;
        }
        thead th {
            border-bottom: 1.5px solid #222222;
            padding: 10px 8px;
            text-align: center;
            font-weight: 700;
            vertical-align: bottom;
        }
        thead th:first-child {
            text-align: left;
        }
        tbody td {
            border-bottom: 1.5px solid #222222;
            padding: 12px 8px;
            text-align: center;
            vertical-align: top;
        }
        tbody td:first-child {
            text-align: left;
        }
        tfoot td {
            padding: 12px 8px;
            text-align: center;
            font-weight: 700;
        }
        tfoot td.total-label {
            text-align: right;
        }
        .temp-note {
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px dashed #cccccc;
            font-size: 11px;
            color: #555555;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .receipt-page {
                border: none;
                box-shadow: none;
                padding: 10px 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <span><strong>Printable Property Receipt:</strong> Press <b>Ctrl + P</b> (or <b>Cmd + P</b>) to print or save as PDF.</span>
        <a href="dashboard.php">&larr; Back to Dashboard</a>
    </div>

    <div class="receipt-page">
        <!-- Top 3-Column Header -->
        <div class="header-grid">
            <div class="col-left">
                <p><strong>Property Receipt</strong></p>
                <p><?= bh($receipt['buyer_name']) ?></p>
                <p><?= bh($receipt['buyer_address']) ?></p>
                <p>Email: <?= bh($receipt['buyer_email']) ?></p>
                <p>Phone: <?= bh($receipt['buyer_phone']) ?></p>
            </div>

            <div class="col-center">
                <div class="brand-logo">
                    <span class="symbol">⌂</span>
                    <span>HomeBid</span>
                </div>
                <span class="brand-sub">Real Estate System</span>
            </div>

            <div class="col-right">
                <p><strong>HomeBid Philippines</strong></p>
                <p>McArthur Highway, Dau</p>
                <p>Mabalacat City, Pampanga 2010</p>
                <p>Phone: (045) 555-0194</p>
                <p>TIN: 009-842-110-000</p>
            </div>
        </div>

        <!-- Receipt ID & Dates -->
        <div class="meta-row">
            <div>
                <p><strong>Receipt Reference:</strong> <?= bh($receipt['constituent_id']) ?></p>
                <p><strong>Transaction Type:</strong> <?= bh($receipt['item']['transaction']) ?></p>
            </div>
            <div class="meta-right">
                <p><strong>Transaction Date:</strong> <?= bh($receipt['purchased_on']) ?></p>
                <p><strong>Printed on:</strong> <?= bh($receipt['printed_on']) ?></p>
            </div>
        </div>

        <!-- Payment Mode -->
        <div class="payment-row">
            <div><strong>Payment Mode:</strong> <?= bh($receipt['payment_type']) ?></div>
            <div><strong>Bank Ref #:</strong> <?= bh($receipt['reference_no']) ?></div>
        </div>

        <!-- Itemized Property Table -->
        <table>
            <thead>
                <tr>
                    <th>Property Description</th>
                    <th>Category</th>
                    <th>Total Price</th>
                    <th>Amount Paid</th>
                    <th>Remaining Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= bh($receipt['item']['name']) ?></td>
                    <td><?= bh($receipt['item']['category']) ?></td>
                    <td><?= bh(buyer_money($receipt['item']['property_price'])) ?></td>
                    <td><?= bh(buyer_money($receipt['item']['amount_paid'])) ?></td>
                    <td><?= bh(buyer_money($receipt['item']['balance_due'])) ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="total-label">Total Paid:</td>
                    <td></td>
                    <td><?= bh(buyer_money($receipt['item']['amount_paid'])) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="temp-note">
            <span>Official Property Transaction Receipt — Temporary Preview Record</span>
            <span>Verified Buyer: <?= bh($receipt['buyer_name']) ?></span>
        </div>
    </div>

</body>
</html>