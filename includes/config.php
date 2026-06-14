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
?>
