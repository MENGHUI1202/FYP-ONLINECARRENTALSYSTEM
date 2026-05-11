<?php
// 1. 引入 Dompdf
require '../vendor/autoload.php'; 
include('../includes/config.php');
include('../includes/auth.php');

use Dompdf\Dompdf;
use Dompdf\Options;

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($booking_id <= 0) { die("Invalid Booking ID"); }

// 2. 抓取车辆与顾客数据
$sql = "SELECT b.*, u.name as customer_name, u.email as customer_email,
        (SELECT c.car_name FROM booking_items bi JOIN cars c ON bi.car_id = c.id WHERE bi.booking_id = b.id LIMIT 1) as car_model,
        (SELECT c.colors FROM booking_items bi JOIN cars c ON bi.car_id = c.id WHERE bi.booking_id = b.id LIMIT 1) as colors,
        (SELECT c.brand FROM booking_items bi JOIN cars c ON bi.car_id = c.id WHERE bi.booking_id = b.id LIMIT 1) as brand
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = $booking_id";

$res = $conn->query($sql);
$data = $res->fetch_assoc();

if (!$data) { die("Order not found"); }

if ($data['booking_status'] !== 'Completed') {
    die("<h2 style='color:red; font-family:sans-serif;'>Access Denied: Vehicle handover is not completed. Digital Grant cannot be issued yet.</h2>");
}

// 3. 算法生成逼真的硬件序列号和 JPJ 追踪码
$year = date('Y', strtotime($data['created_at']));
$chassis_no = 'JTMB' . $year . str_pad($booking_id * 1024 + 5566, 7, '0', STR_PAD_LEFT);
$engine_no  = '2NR-' . str_pad($booking_id * 881 + 9988, 7, '0', STR_PAD_LEFT);
$plate_no   = !empty($data['requested_plate_no']) ? strtoupper($data['requested_plate_no']) : 'PENDING REGISTRATION';
$jpj_tracking = 'EDAF-' . $year . '-' . str_pad($booking_id * 77 + 331, 6, '0', STR_PAD_LEFT);

// 纯日期格式
date_default_timezone_set('Asia/Kuala_Lumpur');
$edaftar_date = strtoupper(date('d F Y'));

$color_arr = explode(',', $data['colors'] ?? 'Standard Finish');
$main_color = trim($color_arr[0]);

// 智能 Engine CC 生成逻辑
$car_name_lower = strtolower($data['car_model']);
if (strpos($car_name_lower, 'vios') !== false || strpos($car_name_lower, 'yaris') !== false) {
    $engine_cc = '1496 SP';
} elseif (strpos($car_name_lower, 'camry') !== false) {
    $engine_cc = '2487 SP';
} elseif (strpos($car_name_lower, 'hilux') !== false) {
    $engine_cc = '2393 SP';
} elseif (strpos($car_name_lower, 'corolla cross') !== false) {
    $engine_cc = '1798 SP';
} elseif (strpos($car_name_lower, 'alphard') !== false) {
    $engine_cc = '2393 SP';
} else {
    $engine_cc = '1496 SP'; // 默认 1.5L
}

// 4. 配置 Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);

// 5. 炫酷的证书级 HTML 模板
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #1e293b; margin: 0; padding: 20px; }
        
        .certificate-container { border: 12px solid #be123c; padding: 40px; position: relative; height: 900px; background-color: #fff5f7; }
        .inner-border { border: 2px solid #fecdd3; padding: 40px; height: 815px; position: relative; }
        
        .header { text-align: center; margin-bottom: 40px; }
        .logo-text { font-size: 36px; font-weight: 900; color: #be123c; letter-spacing: 2px; margin-bottom: 5px; }
        .sub-title { font-size: 14px; color: #881337; letter-spacing: 5px; text-transform: uppercase; }
        .doc-title { text-align: center; font-size: 28px; font-weight: bold; margin-top: 30px; margin-bottom: 40px; border-bottom: 2px solid #fecdd3; padding-bottom: 15px; color: #1e293b; }
        
        .section-title { background: #ffe4e6; padding: 8px 15px; font-size: 12px; font-weight: bold; color: #be123c; text-transform: uppercase; letter-spacing: 1px; border-left: 4px solid #e11d48; margin-bottom: 15px; margin-top: 30px; }
        
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 12px 5px; border-bottom: 1px dashed #fecdd3; font-size: 14px; }
        .info-label { font-weight: bold; color: #4c1d95; width: 30%; text-transform: uppercase; font-size: 11px; }
        .info-value { font-weight: 900; color: #0f172a; font-size: 16px; }
        
        .edaftar-box { border: 2px solid #fbcfe8; margin-top: 40px; border-radius: 4px; overflow: hidden; page-break-before: always; background-color: #fce7f3; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .edaftar-header { background: #1e3a8a; color: #ffffff; padding: 8px 15px; font-size: 11px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
        .edaftar-table { width: 100%; border-collapse: collapse; }
        .edaftar-table td { padding: 20px; vertical-align: top; }
        .edaftar-left { width: 55%; border-right: 1px solid #f9a8d4; background-color: #fce7f3;}
        .edaftar-right { width: 45%; background-color: #fdf2f8; text-align: center; vertical-align: middle !important; }
        
        .status-label { font-size: 9px; color: #64748b; font-weight: bold; letter-spacing: 1px; margin-bottom: 4px; text-transform: uppercase; }
        .status-value-green { color: #059669; font-weight: bold; font-size: 14px; margin-bottom: 15px; }
        .tracking-mono { font-family: "Courier New", Courier, monospace; font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 15px; background: #ffffff; padding: 4px 8px; display: inline-block; border: 1px solid #f9a8d4;}
        .timestamp-mono { font-family: "Courier New", Courier, monospace; font-size: 13px; font-weight: bold; color: #1e40af; margin-bottom: 15px; }
        
        .syarat-section { border-top: 1px dashed #f9a8d4; padding: 15px 20px; background: rgba(255, 255, 255, 0.4); }
        .syarat-title { font-size: 10px; font-weight: bold; color: #be123c; margin-bottom: 8px; letter-spacing: 1px; }
        .syarat-list { margin: 0; padding-left: 15px; font-size: 11px; color: #475569; line-height: 1.6; }
        .syarat-list li { margin-bottom: 4px; }

        .check-icon { font-family: "DejaVu Sans", sans-serif; }
        .plate-container { background: #000000; color: #ffffff; font-size: 28px; font-weight: 900; letter-spacing: 4px; padding: 12px 10px; border-radius: 4px; border: 3px double #cbd5e1; display: inline-block; margin-top: 5px; }
        
        /* ★★★ 核心修复：单行极度密集的条形码，文字分行居中 ★★★ */
        .barcode-section { 
            background: rgba(255, 255, 255, 0.6); 
            border-top: 1px solid #fbcfe8; 
            padding: 15px 15px; 
            text-align: center; /* 整体居中 */
        }
        .barcode-text { 
            font-family: monospace; 
            font-size: 28px; /* 调大字号，让线条更长 */
            letter-spacing: -3.5px; /* ★ 极度压缩字距，模拟真实条码 */
            color: #1e293b; 
            font-weight: bold;
            white-space: nowrap; 
            display: block; /* 强行独占一行 */
            margin-bottom: 8px; /* 和下面的验证文字拉开距离 */
        }
        .barcode-verify { 
            display: block; /* 强行独占一行 */
            font-size: 9px; 
            color: #94a3b8; 
            font-weight: bold; 
            letter-spacing: 2px; 
        }

        .footer-signatures { position: absolute; bottom: 40px; width: 100%; text-align: center; }
        .signature-line { border-top: 1px solid #000; width: 250px; margin: 0 auto; padding-top: 5px; font-weight: bold; font-size: 12px; }
        .watermark { position: absolute; top: 350px; left: 100px; font-size: 80px; color: rgba(190, 18, 60, 0.04); transform: rotate(-45deg); font-weight: 900; letter-spacing: 10px; z-index: -1; }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="inner-border">
            <div class="watermark">OFFICIAL TOYOTA</div>
            
            <div class="header">
                <div class="logo-text">TOYOTA</div>
                <div class="sub-title">Motor Corporation Malaysia</div>
            </div>

            <div class="doc-title">VEHICLE OWNERSHIP CERTIFICATE<br><span style="font-size:14px; font-weight:normal; color:#881337;">(INTERNAL e-VOC SYSTEM)</span></div>

            <div class="section-title">Owner Details</div>
            <table class="info-table">
                <tr>
                    <td class="info-label">Registered Owner</td>
                    <td class="info-value">'.strtoupper($data['customer_name']).'</td>
                </tr>
                <tr>
                    <td class="info-label">Reference ID</td>
                    <td class="info-value">#'.strtoupper($data['booking_reference']).'</td>
                </tr>
            </table>

            <div class="section-title">Vehicle Technical Specifications</div>
            <table class="info-table">
                <tr>
                    <td class="info-label">Make & Model</td>
                    <td class="info-value">TOYOTA '.strtoupper($data['car_model']).'</td>
                </tr>
                <tr>
                    <td class="info-label">Chassis Number (VIN)</td>
                    <td class="info-value" style="font-family: monospace; font-size: 18px;">'.$chassis_no.'</td>
                </tr>
                <tr>
                    <td class="info-label">Engine Number</td>
                    <td class="info-value" style="font-family: monospace; font-size: 18px;">'.$engine_no.'</td>
                </tr>
                <tr>
                    <td class="info-label">Engine Capacity (CC)</td>
                    <td class="info-value" style="font-family: monospace; font-size: 18px;">'.$engine_cc.'</td>
                </tr>
                <tr>
                    <td class="info-label">Color</td>
                    <td class="info-value">'.strtoupper($main_color).'</td>
                </tr>
            </table>

            <div class="edaftar-box">
                <div class="edaftar-header">
                    SYSTEM INTEGRATION | JPJ e-DAFTAR CLEARANCE
                </div>
                <table class="edaftar-table">
                    <tr>
                        <td class="edaftar-left">
                            <div class="status-label">Registration Status</div>
                            <div class="status-value-green"><span class="check-icon">&#10004;</span> APPROVED & LINKED</div>
                            
                            <div class="status-label">JPJ System Tracking No.</div>
                            <div class="tracking-mono">'.$jpj_tracking.'</div>

                            <div class="status-label">Registration Date</div>
                            <div class="timestamp-mono">'.$edaftar_date.'</div>
                            
                            <div class="status-label">Road Tax (LKM) Validity</div>
                            <div style="font-family: monospace; font-size: 13px; font-weight: bold; color: #0f172a;">12 MONTHS</div>
                        </td>
                        <td class="edaftar-right">
                            <div style="font-size: 10px; color: #64748b; font-weight: bold; letter-spacing: 1px; margin-bottom: 8px;">AUTHORIZED LICENSE PLATE</div>
                            <div class="plate-container">'.$plate_no.'</div>
                        </td>
                    </tr>
                </table>
                
                <div class="syarat-section">
                    <div class="syarat-title">JPJ REGISTRATION CONDITIONS</div>
                    <ol class="syarat-list">
                        <li>Ownership cannot be transferred within 4 years from the registration date.</li>
                        <li>Registration is allowed under the importer\'s name only.</li>
                        <li>Customs duty exempted. Duty must be paid before change of use or ownership.</li>
                    </ol>
                </div>

                <div class="barcode-section">
                    <div class="barcode-text">||||||&nbsp;||||&nbsp;||&nbsp;||||||&nbsp;&nbsp;||||&nbsp;||&nbsp;||||||||&nbsp;&nbsp;||||&nbsp;||&nbsp;|||||&nbsp;|||&nbsp;||&nbsp;|||||</div>
                    <div class="barcode-verify">VERIFIED VIA TOYOTA SECURE API</div>
                </div>
            </div>

            <div class="footer-signatures">
                <div style="margin-bottom: 20px;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e4/Approved_icon.svg/500px-Approved_icon.svg.png" style="width: 80px; opacity: 0.3; filter: grayscale(100%);">
                </div>
                <div class="signature-line">
                    AUTHORIZED SYSTEM GENERATED<br>
                    <span style="font-weight: normal; font-size: 10px; color: #64748b;">This is a computer-generated document. No signature is required.</span>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>
';

// 6. 渲染输出
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("Toyota_Digital_Grant_".$data['booking_reference'].".pdf", array("Attachment" => 0));
?>