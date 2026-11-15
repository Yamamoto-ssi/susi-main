<?php
declare(strict_types=1);

/**
 * HomeBid database connection.
 * XAMPP defaults: database=house_and_lot_db, username=root, password=""
 * Override with DB_HOST, DB_NAME, DB_USER and DB_PASS environment variables.
 */
$host = getenv('DB_HOST') ?: '127.0.0.1';
$name = getenv('DB_NAME') ?: 'house_and_lot_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('HomeBid database connection failed: ' . $e->getMessage());
    if (defined('HOMEBID_ALLOW_OFFLINE_DB') && HOMEBID_ALLOW_OFFLINE_DB) {
        $pdo = null;
    } else {
        http_response_code(500);
        if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'login-agent.php') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Database connection failed. Make sure MySQL is running and house_and_lot_db is imported in phpMyAdmin.']);
            exit;
        }
        exit('Database connection failed. Check config/db.php and confirm that MySQL is running and the house_and_lot_db database has been imported.');
    }
}
