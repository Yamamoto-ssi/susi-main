<?php
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('DB_HOST') ?: '127.0.0.1';
$name = getenv('DB_NAME') ?: 'house_and_lot_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
try {
    $conn = mysqli_connect($host, $user, $pass, $name);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('HomeBid database connection failed.');
    http_response_code(500);
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'login-agent.php') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Database connection failed. Make sure MySQL is running and house_and_lot_db is imported in phpMyAdmin.']);
        exit;
    }
    exit('Database connection failed. Check config/db.php and confirm that MySQL is running and house_and_lot_db exists.');
}
function hb_query(mysqli $conn, string $sql, array $params = []): mysqli_result|bool {
    if ($params) {
        $parts = explode('?', $sql);
        if (count($parts) !== count($params) + 1) throw new InvalidArgumentException('SQL placeholder count does not match supplied values.');
        $built = array_shift($parts);
        foreach ($params as $i => $value) {
            if ($value === null) $replacement = 'NULL';
            elseif (is_bool($value) || is_int($value)) $replacement = (string)(int)$value;
            elseif (is_float($value)) $replacement = (string)$value;
            else $replacement = "'" . mysqli_real_escape_string($conn, (string)$value) . "'";
            $built .= $replacement . $parts[$i];
        }
        $sql = $built;
    }
    return mysqli_query($conn, $sql);
}
function hb_fetch_all(mysqli_result|bool $result): array {
    return $result instanceof mysqli_result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}
function hb_fetch_one(mysqli_result|bool $result): array|false {
    if (!$result instanceof mysqli_result) {
        return false;
    }

    $row = mysqli_fetch_assoc($result);

    return is_array($row) ? $row : false;
}
function hb_fetch_value(mysqli_result|bool $result, int $column = 0): mixed {
    if (!$result instanceof mysqli_result) return false;
    $row = mysqli_fetch_row($result);
    return $row[$column] ?? false;
}
