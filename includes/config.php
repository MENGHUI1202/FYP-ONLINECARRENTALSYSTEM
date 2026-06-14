<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

$conn = new mysqli('localhost', 'root', '', 'car_rental_system_unified_copy_20260609_001225');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

define('SITE_NAME', 'KH Car Rental');
define('CONTACT_PHONE', '+60 12-345 6789');

function db_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS c
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $stmt->bind_param('s', $table);
    $stmt->execute();
    return ((int)($stmt->get_result()->fetch_assoc()['c'] ?? 0)) > 0;
}

function db_column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS c
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    return ((int)($stmt->get_result()->fetch_assoc()['c'] ?? 0)) > 0;
}

function current_admin_id(): int
{
    return (int)($_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0);
}

function current_admin_name(): string
{
    $name = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? '';
    $name = trim((string)$name);

    if ($name !== '') {
        return $name;
    }

    $admin_id = current_admin_id();
    return $admin_id > 0 ? 'Admin ID: ' . $admin_id : 'System Admin';
}

function admin_audit_log(mysqli $conn, string $action_type, string $details, ?string $target_type = null, ?int $target_id = null, ?string $car_model = null): void
{
    try {
        if (!db_table_exists($conn, 'audit_logs')) {
            $conn->query("
                CREATE TABLE audit_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    admin_id INT NULL,
                    admin_name VARCHAR(100) NOT NULL,
                    action_type VARCHAR(50) NOT NULL,
                    target_type VARCHAR(50) NULL,
                    target_id INT NULL,
                    car_model VARCHAR(100) NULL,
                    details TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_admin_id (admin_id),
                    KEY idx_target (target_type, target_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        $admin_id = current_admin_id();
        $admin_id_param = $admin_id > 0 ? $admin_id : null;
        $admin_name = current_admin_name();

        $stmt = $conn->prepare("
            INSERT INTO audit_logs (admin_id, admin_name, action_type, target_type, target_id, car_model, details)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('isssiss', $admin_id_param, $admin_name, $action_type, $target_type, $target_id, $car_model, $details);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $ex) {
        // Audit logging must never block an admin operation.
    }
}
?>
