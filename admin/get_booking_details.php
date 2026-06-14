<?php
include('../includes/config.php');
include('../includes/auth.php');

header('Content-Type: application/json');

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($booking_id <= 0) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT
        b.*,
        b.booking_id AS id,
        b.admin_note AS admin_notes,
        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,
        u.license_number
     FROM bookings b
     LEFT JOIN users u ON u.user_id = b.user_id
     WHERE b.booking_id = ?
     LIMIT 1"
);
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode(['error' => 'Booking not found']);
    exit;
}

$items = [];
$stmt_items = $conn->prepare(
    "SELECT
        bi.*,
        bi.booking_item_id AS id,
        c.car_name,
        c.model,
        c.main_image AS image_url,
        br.brand_name AS brand,
        cu.plate_number,
        cu.current_status AS unit_status
     FROM booking_items bi
     LEFT JOIN cars c ON c.car_id = bi.car_id
     LEFT JOIN brands br ON br.brand_id = c.brand_id
     LEFT JOIN car_units cu ON cu.unit_id = bi.unit_id
     WHERE bi.booking_id = ?"
);
$stmt_items->bind_param('i', $booking_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();

while ($item = $res_items->fetch_assoc()) {
    $item['addons'] = [];
    $items[] = $item;
}

echo json_encode([
    'booking' => $booking,
    'items' => $items,
]);
?>
