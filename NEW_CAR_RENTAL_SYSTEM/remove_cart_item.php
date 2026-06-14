<?php
require_once "config.php";

function tableExists($conn, $table) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row["total"] ?? 0) > 0;
}

if (empty($_SESSION["user_id"])) {
    header("Location: login.php?redirect=cart.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cart.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$cartItemId = (int)($_POST["cart_item_id"] ?? 0);

if ($cartItemId > 0 && tableExists($conn, "cart_items")) {
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $cartItemId, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: cart.php");
exit;
