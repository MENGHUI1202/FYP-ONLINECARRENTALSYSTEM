<?php
// 1. 引入 Composer 的自动加载器
require '../vendor/autoload.php'; 
include('../includes/config.php');
include('../includes/auth.php');

use Dompdf\Dompdf;
use Dompdf\Options;

// 2. 获取订单 ID
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) { die("Invalid Booking ID"); }

// 3. 从数据库抓取完整数据（包括用户信息和车辆信息）
$sql = "SELECT b.*, u.name as customer_name, u.email as customer_email,
        (SELECT c.car_name FROM booking_items bi JOIN cars c ON bi.car_id = c.id WHERE bi.booking_id = b.id LIMIT 1) as car_model
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = $booking_id";

$res = $conn->query($sql);
$data = $res->fetch_assoc();

if (!$data) { die("Order not found"); }

// 4. 配置 Dompdf 选项（支持图片和远程资源）
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);

// 5. 准备高颜值的 HTML 模板
// 注意：这里我为你设计了一个简约大气、带有 Toyota 品牌感的排版
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
        .header-table { width: 100%; border-bottom: 2px solid #eb0a1e; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 28px; font-weight: bold; color: #eb0a1e; letter-spacing: -1px; }
        .receipt-title { text-align: right; font-size: 20px; color: #999; text-transform: uppercase; }
        .info-section { width: 100%; margin-bottom: 30px; }
        .info-box { width: 50%; vertical-align: top; }
        .label { font-size: 10px; color: #999; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; }
        .value { font-size: 14px; font-weight: bold; margin-bottom: 15px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 50px; }
        .items-table th { background: #f8f8f8; text-align: left; padding: 12px; font-size: 12px; border-bottom: 1px solid #eee; }
        .items-table td { padding: 15px 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        .total-section { text-align: right; }
        .total-row { display: inline-block; width: 250px; border-top: 2px solid #333; padding-top: 10px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #ccc; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo">TOYOTA <small style="font-size:12px; color:#666">DEALERSHIP</small></td>
            <td class="receipt-title">Official Receipt</td>
        </tr>
    </table>

    <table class="info-section">
        <tr>
            <td class="info-box">
                <div class="label">Billed To:</div>
                <div class="value">'.htmlspecialchars($data['customer_name']).'</div>
                <div class="label">Email:</div>
                <div class="value">'.htmlspecialchars($data['customer_email']).'</div>
            </td>
            <td class="info-box" style="text-align: right;">
                <div class="label">Receipt No:</div>
                <div class="value">#'.htmlspecialchars($data['booking_reference']).'</div>
                <div class="label">Date:</div>
                <div class="value">'.date('d F Y', strtotime($data['created_at'])).'</div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Order Type</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>'.htmlspecialchars($data['car_model']).'</strong><br>
                    <small style="color:#666">Chassis Allocation Pending</small>
                </td>
                <td>'.htmlspecialchars($data['order_type']).'</td>
                <td style="text-align: right;">RM '.number_format($data['grand_total'], 2).'</td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <table style="width: 100%">
                <tr>
                    <td style="text-align: left; font-weight: bold;">TOTAL PAID</td>
                    <td style="text-align: right; font-size: 24px; font-weight: 900; color: #eb0a1e;">RM '.number_format($data['grand_total'], 2).'</td>
                </tr>
            </table>
        </div>
    </div>

    <div style="margin-top: 40px; padding: 20px; background: #fff5f5; border-radius: 8px; font-size: 12px; color: #c00;">
        <strong>Notice:</strong> This receipt confirms the initial transaction for the vehicle booking. Please retain this for the loan application process with Toyota Financial Services.
    </div>

    <div class="footer">
        Computer Generated Receipt - No Signature Required<br>
        Toyota Malaysia Digital Sales System &copy; '.date('Y').'
    </div>
</body>
</html>
';

// 6. 渲染并输出 PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 直接在浏览器预览（如果想强制下载，把 "Attachment" 改为 1）
$dompdf->stream("Toyota_Receipt_".$data['booking_reference'].".pdf", array("Attachment" => 0));