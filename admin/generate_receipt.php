<?php
// admin/generate_receipt.php
include('../includes/config.php');
include('../includes/auth.php');
checkLogin();

// 1. 引入 Composer 的自动加载文件
require_once '../vendor/autoload.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

// 2. 获取订单 ID
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($booking_id === 0) { 
    die("<h2 style='color:red; text-align:center; font-family:sans-serif; margin-top:50px;'>Invalid Booking ID</h2>"); 
}

// 3. 分步严谨查询：先查订单主体及用户信息
$query_booking = "SELECT b.*, u.name AS customer_name, u.phone AS customer_phone, u.email AS customer_email 
                  FROM bookings b
                  LEFT JOIN users u ON u.user_id = b.user_id
                  WHERE b.booking_id = $booking_id LIMIT 1";
                  
$res_booking = $conn->query($query_booking);
if($res_booking->num_rows === 0) { 
    die("<h2 style='color:red; text-align:center; font-family:sans-serif; margin-top:50px;'>Booking Record Not Found</h2>"); 
}
$booking_data = $res_booking->fetch_assoc();

// 4. 严谨查询订单包含的车辆及所有附加项目
$query_items = "SELECT bi.*, c.car_name, br.brand_name AS brand
                FROM booking_items bi
                LEFT JOIN cars c ON bi.car_id = c.car_id
                LEFT JOIN brands br ON br.brand_id = c.brand_id
                WHERE bi.booking_id = $booking_id 
                ORDER BY bi.booking_item_id ASC";
$res_items = $conn->query($query_items);

// 提取基础变量
$order_ref = htmlspecialchars($booking_data['booking_reference']);
$customer = htmlspecialchars($booking_data['customer_name']);
$phone = htmlspecialchars($booking_data['customer_phone']);
$email = htmlspecialchars($booking_data['customer_email']);
$created = date('d M Y', strtotime($booking_data['created_at']));
$generation_time = date('d M Y, h:i A');

// --- 核心逻辑：循环遍历订单项，动态拼装成与前台一模一样的多行表格 ---
$table_rows_html = "";
$carTotal = 0;
$servicesTotal = 0;

while($item = $res_items->fetch_assoc()) {
    $days = intval($item['rental_days'] ?? 1);
    $price_per_day = floatval($item['price_per_day'] ?? 0);
    $subtotal = floatval($item['subtotal'] ?? 0);
    $carTotal += $subtotal;

    $vehicle_title = htmlspecialchars(($item['brand'] ?? '') . ' ' . $item['car_name']);
    $start_dt = date('d M Y, h:i A', strtotime($item['start_datetime']));
    $end_dt = date('d M Y, h:i A', strtotime($item['end_datetime']));
    
    // 行 1：汽车租赁基础行
    $table_rows_html .= "
    <tr>
        <td>
            <div class='item-title'>{$vehicle_title} (Rental Car)</div>
            <div class='item-desc'>
                Pick-up: {$start_dt} &bull; Return: {$end_dt}
            </div>
        </td>
        <td class='right'>{$days} day(s)</td>
        <td class='right'>RM " . number_format($price_per_day, 2) . "</td>
        <td class='right' style='font-weight:bold;'>RM " . number_format($subtotal, 2) . "</td>
    </tr>";

    // 行 2：保险行（如果存在费用）
    if (floatval($item['insurance_charge']) > 0) {
        $ins_charge = floatval($item['insurance_charge']);
        $servicesTotal += $ins_charge;
        $ins_pkg = htmlspecialchars($item['insurance_package'] ?? 'Basic Coverage');
        $table_rows_html .= "
        <tr>
            <td>
                <div class='item-title'>Insurance Package</div>
                <div class='item-desc'>{$ins_pkg}</div>
            </td>
            <td class='right'>-</td>
            <td class='right'>-</td>
            <td class='right' style='font-weight:bold;'>RM " . number_format($ins_charge, 2) . "</td>
        </tr>";
    }

    // 行 3：驾驶员年龄附加费（如果存在费用）
    if (floatval($item['driver_age_charge']) > 0) {
        $age_charge = floatval($item['driver_age_charge']);
        $servicesTotal += $age_charge;
        $age_grp = htmlspecialchars($item['driver_age_group'] ?? '25–69 years');
        $table_rows_html .= "
        <tr>
            <td>
                <div class='item-title'>Driver Age Surcharge</div>
                <div class='item-desc'>{$age_grp}</div>
            </td>
            <td class='right'>-</td>
            <td class='right'>-</td>
            <td class='right' style='font-weight:bold;'>RM " . number_format($age_charge, 2) . "</td>
        </tr>";
    }

    // 行 4：附加增值服务行（如 Child Seat, Dashcam 等，解析 JSON）
    if (floatval($item['addon_services_charge']) > 0) {
        $addon_charge = floatval($item['addon_services_charge']);
        $servicesTotal += $addon_charge;
        $raw_addons = $item['addon_services'];
        $addon_text = "Optional Add-ons";
        if (!empty($raw_addons)) {
            $decoded = json_decode($raw_addons, true);
            if (is_array($decoded)) {
                $addon_text = implode(", ", $decoded);
            } else {
                $addon_text = str_replace(['|', ','], ', ', $raw_addons);
            }
        }
        $addon_text = htmlspecialchars($addon_text);
        $table_rows_html .= "
        <tr>
            <td>
                <div class='item-title'>Add-on Services</div>
                <div class='item-desc'>{$addon_text}</div>
            </td>
            <td class='right'>-</td>
            <td class='right'>-</td>
            <td class='right' style='font-weight:bold;'>RM " . number_format($addon_charge, 2) . "</td>
        </tr>";
    }

    // 行 5：预付油费行（如果存在费用）
    if (floatval($item['fuel_charge']) > 0) {
        $fuel_charge = floatval($item['fuel_charge']);
        $servicesTotal += $fuel_charge;
        $table_rows_html .= "
        <tr>
            <td>
                <div class='item-title'>Fuel Option</div>
                <div class='item-desc'>Full Tank Option</div>
            </td>
            <td class='right'>-</td>
            <td class='right'>-</td>
            <td class='right' style='font-weight:bold;'>RM " . number_format($fuel_charge, 2) . "</td>
        </tr>";
    }
}

// 获取其他系统费用
$tax_fee = floatval($booking_data['tax_amount'] ?? 0);
$service_fee = floatval($booking_data['service_fee'] ?? 0);
// 如果把系统税费和平台手续费也计入保护与服务费，则累加
$servicesTotal += $tax_fee + $service_fee;

$discount = floatval($booking_data['voucher_discount'] ?? 0);
$grand_total = floatval($booking_data['grand_total'] ?? 0);

// 5. PDF HTML 模版输出
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Receipt #{$order_ref}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1e293b; margin: 0; padding: 20px; font-size: 14px; line-height: 1.5; }
        .primary-color { color: #1284c6; }
        .header { border-bottom: 3px solid #1284c6; padding-bottom: 15px; margin-bottom: 30px; }
        .logo { font-size: 26px; font-weight: bold; margin: 0; color: #10233d; letter-spacing: 0.5px; }
        .logo span { font-weight: normal; color: #1284c6; }
        .receipt-title { font-size: 20px; color: #6e8297; text-transform: uppercase; font-weight: bold; text-align: right; margin-top: -30px; letter-spacing: 1.5px; }
        .info-grid { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        .info-grid td { vertical-align: top; width: 48%; }
        .spacer-td { width: 4%; }
        .info-box { background: #f5fbff; padding: 16px; border-radius: 12px; border: 1px solid #d8ecfb; }
        .info-title { font-size: 10px; text-transform: uppercase; color: #6e8297; letter-spacing: 0.8px; margin-bottom: 8px; font-weight: bold; border-bottom: 1px solid #d8ecfb; padding-bottom: 4px; }
        .info-text { font-size: 14px; font-weight: bold; color: #10233d; margin: 0 0 4px 0; }
        .info-subtext { font-size: 12px; font-weight: normal; color: #6e8297; margin: 0 0 3px 0; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 25px; }
        .table th { background-color: #1284c6; color: white; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .table td { padding: 12px; border-bottom: 1px solid #d8ecfb; vertical-align: top; font-size: 13px; }
        .table .right { text-align: right; }
        .item-title { font-weight: bold; font-size: 14px; color: #10233d; margin-bottom: 4px; }
        .item-desc { color: #6e8297; font-size: 12px; line-height: 1.5; }
        .totals-table { width: 45%; float: right; border-collapse: collapse; margin-top: 5px; }
        .totals-table td { padding: 7px 10px; font-size: 13px; color: #6e8297; }
        .totals-table .totals-label { text-align: left; font-weight: 600; }
        .totals-table .totals-value { text-align: right; font-weight: bold; color: #10233d; }
        .grand-total-row td { border-top: 2px solid #1284c6; padding-top: 10px; }
        .grand-total-label { font-size: 15px; font-weight: bold; color: #10233d !important; }
        .grand-total-value { font-size: 20px; font-weight: bold; color: #1284c6 !important; text-align: right; }
        .watermark { position: fixed; top: 42%; left: 12%; font-size: 44px; font-weight: 900; color: rgba(18, 132, 198, 0.03); transform: rotate(-22deg); z-index: -1000; letter-spacing: 3px; }
        .footer { clear: both; margin-top: 70px; padding-top: 15px; border-top: 1px dashed #6e8297; text-align: center; color: #6e8297; font-size: 11px; line-height: 1.5; }
    </style>
</head>
<body>

    <div class='header'>
        <h1 class='logo'>KH <span>CAR RENTAL</span></h1>
        <div class='receipt-title'>OFFICIAL RECEIPT</div>
    </div>

    <table class='info-grid'>
        <tr>
            <td>
                <div class='info-box'>
                    <div class='info-title'>Bill To</div>
                    <p class='info-text'>{$customer}</p>
                    <p class='info-subtext'>{$phone}</p>
                    <p class='info-subtext'>{$email}</p>
                </div>
            </td>
            <td class='spacer-td'></td>
            <td>
                <div class='info-box'>
                    <div class='info-title'>Booking Details</div>
                    <p class='info-subtext'><strong>Ref Number:</strong> <span class='primary-color' style='font-weight:bold;'>#{$order_ref}</span></p>
                    <p class='info-subtext'><strong>Date Issued:</strong> {$created}</p>
                    <p class='info-subtext'><strong>Payment Method:</strong> " . htmlspecialchars($booking_data['payment_method'] ?? '-') . "</p>
                </div>
            </td>
        </tr>
    </table>

    <table class='table'>
        <thead>
            <tr>
                <th width='55%'>Description</th>
                <th width='15%' class='right'>Qty / Days</th>
                <th width='15%' class='right'>Unit Price</th>
                <th width='15%' class='right'>Amount</th>
            </tr>
        </thead>
        <tbody>
            {$table_rows_html}
        </tbody>
    </table>

    <table class='totals-table'>
        <tr>
            <td class='totals-label'>Car Rental Total:</td>
            <td class='totals-value'>RM " . number_format($carTotal, 2) . "</td>
        </tr>
        <tr>
            <td class='totals-label'>Extra Protection & Services:</td>
            <td class='totals-value'>RM " . number_format($servicesTotal, 2) . "</td>
        </tr>
        <tr>
            <td class='totals-label' style='padding-bottom: 12px;'>Voucher Discount:</td>
            <td class='totals-value' style='padding-bottom: 12px; color: #e2453b;'>-RM " . number_format($discount, 2) . "</td>
        </tr>
        <tr class='grand-total-row'>
            <td class='totals-label grand-total-label'>GRAND TOTAL:</td>
            <td class='grand-total-value'>RM " . number_format($grand_total, 2) . "</td>
        </tr>
    </table>

    <div class='watermark'>OFFICIAL RECEIPT - SECURED</div>

    <div class='footer'>
        Thank you for choosing KH CAR RENTAL. Have a safe and pleasant journey!<br>
        <strong>Generation Time:</strong> {$generation_time} | <strong>Receipt ID:</strong> #REC-{$order_ref}<br>
        <span style='color: #1284c6; font-weight: bold;'>Digital Footprint Secured. This is a computer-generated document. No signature is required.</span>
    </div>

</body>
</html>
";

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("Receipt_{$order_ref}.pdf", ["Attachment" => 0]);
?>