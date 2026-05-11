<?php
// 文件名：ajax_search.php (放在 admin 目录下)
session_start();
include('../includes/config.php');

// 安全拦截：没登录的直接踢掉
if(!isset($_SESSION['admin_logged_in'])) { 
    echo json_encode([]); 
    exit; 
}

// 获取搜索词
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// 搜索词太短时不查数据库，保护服务器性能
if(strlen($query) < 1) { 
    echo json_encode([]); 
    exit; 
}

$results = [];
$search_param = "%{$query}%";

// ==========================================
// 1. 搜寻 Customers (用户)
// ==========================================
$stmt1 = $conn->prepare("SELECT id, name, email, phone FROM users WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? LIMIT 3");
$stmt1->bind_param("sss", $search_param, $search_param, $search_param);
$stmt1->execute();
$res1 = $stmt1->get_result();
while($row = $res1->fetch_assoc()) {
    $results[] = [
        'group' => 'Customers',
        'icon' => 'fa-user text-green-400',
        'title' => $row['name'],
        'subtitle' => $row['email'] . ($row['phone'] ? ' - ' . $row['phone'] : ''),
        // ★ 核心改动：把客户名字传进 URL
        'url' => 'manage_users.php?cmd_search=' . urlencode($row['name']) 
    ];
}

// ==========================================
// 2. 搜寻 Cars (车辆)
// ==========================================
$stmt2 = $conn->prepare("SELECT id, brand, car_name, type FROM cars WHERE brand LIKE ? OR car_name LIKE ? LIMIT 3");
$stmt2->bind_param("ss", $search_param, $search_param);
$stmt2->execute();
$res2 = $stmt2->get_result();
while($row = $res2->fetch_assoc()) {
    $results[] = [
        'group' => 'Vehicles',
        'icon' => 'fa-car text-blue-400',
        'title' => $row['brand'] . ' ' . $row['car_name'],
        'subtitle' => 'Category: ' . $row['type'],
        // ★ 核心改动：把车名传进 URL
        'url' => 'manage_cars.php?cmd_search=' . urlencode($row['car_name'])
    ];
}

// ==========================================
// 3. 搜寻 Bookings (订单)
// ==========================================
$stmt3 = $conn->prepare("SELECT id, booking_reference, booking_status FROM bookings WHERE booking_reference LIKE ? LIMIT 3");
$stmt3->bind_param("s", $search_param);
$stmt3->execute();
$res3 = $stmt3->get_result();
while($row = $res3->fetch_assoc()) {
    $results[] = [
        'group' => 'Orders',
        'icon' => 'fa-file-invoice-dollar text-orange-400',
        'title' => 'Order #' . $row['booking_reference'],
        'subtitle' => 'Status: ' . $row['booking_status'],
        // ★ 核心改动：把订单号传进 URL
        'url' => 'manage_bookings.php?cmd_search=' . urlencode($row['booking_reference'])
    ];
}

// 如果数据库里啥都没搜到，给几个默认的导航建议
if(empty($results)) {
    $results[] = ['group' => 'Navigation', 'icon' => 'fa-home', 'title' => 'Go to Dashboard', 'subtitle' => 'System overview', 'url' => 'dashboard.php'];
    $results[] = ['group' => 'Navigation', 'icon' => 'fa-plus', 'title' => 'Add New Vehicle', 'subtitle' => 'Inventory management', 'url' => 'manage_cars.php'];
}

// 将结果转成 JSON 格式输出给 JavaScript
header('Content-Type: application/json');
echo json_encode($results);
?>