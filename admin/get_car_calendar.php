<?php
include('../includes/config.php');
header('Content-Type: application/json');

$car_id = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;
if ($car_id === 0) { echo json_encode([]); exit; }

// 【核心算法】抓取这辆车所有“未取消”的真实订单租期
$sql = "SELECT bi.start_datetime, bi.end_datetime, b.booking_reference, u.name AS customer_name
        FROM booking_items bi
        JOIN bookings b ON bi.booking_id = b.booking_id
        LEFT JOIN users u ON u.user_id = b.user_id
        WHERE bi.car_id = ? AND b.booking_status NOT IN ('cancelled', 'rejected')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $car_id);
$stmt->execute();
$res = $stmt->get_result();

$events = [];
while($row = $res->fetch_assoc()) {
    // 转化为 FullCalendar.js 开源库能识别的标准 JSON 格式
    $events[] = [
        'title' => '📅 Booked (#' . $row['booking_reference'] . ')',
        'start' => date('Y-m-d\TH:i:s', strtotime($row['start_datetime'])),
        'end' => date('Y-m-d\TH:i:s', strtotime($row['end_datetime'])),
        'color' => '#ef4444', // 预订成功的日期一律变红
        'extendedProps' => [
            'customer' => $row['customer_name']
        ]
    ];
}

echo json_encode($events);
