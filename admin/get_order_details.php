<?php
include('../includes/config.php');
include('../includes/auth.php');

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$booking_id = intval($_GET['id']);

// 1. 获取主订单信息
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode(['error' => 'Booking not found']);
    exit;
}

// 2. 获取车辆信息 (Items)
$items = [];
$stmt_items = $conn->prepare("
    SELECT bi.*, c.car_name, c.brand, c.model, c.image_url 
    FROM booking_items bi 
    LEFT JOIN cars c ON bi.car_id = c.id 
    WHERE bi.booking_id = ?
");
$stmt_items->bind_param("i", $booking_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();

while ($item = $res_items->fetch_assoc()) {
    // 3. 获取每个车的 Add-ons (Services) ★★★ 重点在这里
    $services = [];
    $stmt_services = $conn->prepare("SELECT * FROM booking_services WHERE booking_item_id = ?");
    $stmt_services->bind_param("i", $item['id']);
    $stmt_services->execute();
    $res_services = $stmt_services->get_result();
    
    while ($svc = $res_services->fetch_assoc()) {
        $services[] = $svc;
    }
    
    $item['addons'] = $services; // 把 add-ons 放进车辆数据里
    $items[] = $item;
}

// 返回完整数据给前端
echo json_encode([
    'booking' => $booking,
    'items' => $items
]);
?>