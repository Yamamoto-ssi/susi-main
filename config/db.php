<?php
declare(strict_types=1);

/**
 * HomeBid MySQLi database connection.
 * XAMPP defaults: database=house_and_lot_db, user=root, blank password.
 * Optional environment overrides: DB_HOST, DB_NAME, DB_USER, DB_PASS.
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

final class MySQLiStatement
{
    private ?mysqli_stmt $stmt;
    private ?mysqli_result $result;

    public function __construct(?mysqli_stmt $stmt = null, ?mysqli_result $result = null)
    {
        $this->stmt = $stmt;
        $this->result = $result;
    }

    public function execute(array $params = []): bool
    {
        if (!$this->stmt) return false;
        if ($params) {
            $types = '';
            $values = [];
            foreach ($params as $value) {
                if (is_int($value)) $types .= 'i';
                elseif (is_float($value)) $types .= 'd';
                else $types .= 's';
                $values[] = $value;
            }
            $refs = [];
            foreach ($values as $key => &$value) $refs[$key] = &$value;
            unset($value);
            $this->stmt->bind_param($types, ...$refs);
        }
        $ok = $this->stmt->execute();
        $this->result = $this->stmt->get_result() ?: null;
        return $ok;
    }

    public function fetch(int $mode = MYSQLI_ASSOC): array|false
    {
        return $this->result ? $this->result->fetch_assoc() : false;
    }

    public function fetchAll(int $mode = MYSQLI_ASSOC): array
    {
        return $this->result ? $this->result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->fetch();
        return $row === false ? false : (array_values($row)[$column] ?? false);
    }

    public function rowCount(): int
    {
        return $this->stmt ? $this->stmt->affected_rows : 0;
    }
}

final class MySQLiConnection
{
    private mysqli $link;

    public function __construct(mysqli $link) { $this->link = $link; }

    public function prepare(string $sql): MySQLiStatement
    {
        return new MySQLiStatement($this->link->prepare($sql));
    }

    public function query(string $sql): MySQLiStatement
    {
        $result = $this->link->query($sql);
        return new MySQLiStatement(null, $result instanceof mysqli_result ? $result : null);
    }

    public function lastInsertId(): string { return (string)$this->link->insert_id; }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$name = getenv('DB_NAME') ?: 'house_and_lot_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $mysqli = new mysqli($host, $user, $pass, $name);
    $mysqli->set_charset('utf8mb4');
    $conn = new MySQLiConnection($mysqli);
} catch (mysqli_sql_exception $e) {
    error_log('HomeBid MySQLi connection failed: ' . $e->getMessage());
    http_response_code(500);
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'login-agent.php') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Database connection failed. Make sure MySQL is running and house_and_lot_db is imported in phpMyAdmin.']);
        exit;
    }
    exit('Database connection failed. Check config/db.php and confirm that MySQL is running and house_and_lot_db exists.');
}
