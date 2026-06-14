<?php
include('../includes/config.php');
include('../includes/auth.php');

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$promo_id = isset($_GET['promo_id']) ? (int)$_GET['promo_id'] : 0;
if ($promo_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid promo code selected.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT
        pcu.usage_id,
        pcu.user_id,
        pcu.booking_id,
        pcu.used_at,
        pcu.created_at,
        u.name,
        u.email,
        u.phone,
        b.booking_reference,
        b.booking_status,
        b.grand_total
     FROM promo_code_usage pcu
     LEFT JOIN users u ON u.user_id = pcu.user_id
     LEFT JOIN bookings b ON b.booking_id = pcu.booking_id
     WHERE pcu.promo_id = ?
     ORDER BY COALESCE(pcu.used_at, pcu.created_at) DESC, pcu.usage_id DESC"
);
$stmt->bind_param('i', $promo_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['ok' => true, 'rows' => $rows]);
?>
