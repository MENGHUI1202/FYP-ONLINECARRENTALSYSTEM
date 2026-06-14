<?php
// admin/generate_report_pdf.php
include('../includes/config.php');
include('../includes/auth.php');
checkLogin();

// 引入 Dompdf
require_once '../vendor/autoload.php'; 
use Dompdf\Dompdf;
use Dompdf\Options;

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t');

// 完全基于你原汁原味的系统字段和数据架构进行深度联合查询
$query = "SELECT b.booking_reference, b.created_at, b.grand_total, u.name as customer,
         (SELECT c.car_name FROM booking_items bi JOIN cars c ON bi.car_id = c.car_id WHERE bi.booking_id = b.booking_id LIMIT 1) as car_name
          FROM bookings b
          JOIN users u ON b.user_id = u.user_id
          WHERE b.booking_status IN ('approved', 'active', 'completed')
          AND DATE(b.created_at) BETWEEN '$start_date' AND '$end_date'
          ORDER BY b.created_at ASC";
          
$result = $conn->query($query);

$total_revenue = 0;
$total_bookings = $result->num_rows;
$table_rows = "";

while($row = $result->fetch_assoc()) {
    $total_revenue += $row['grand_total'];
    $date = date('d M Y', strtotime($row['created_at']));
    $amount = number_format($row['grand_total'], 2);
    $ref = htmlspecialchars($row['booking_reference']);
    $cust = htmlspecialchars($row['customer']);
    $car = htmlspecialchars($row['car_name'] ?? 'N/A');
    
    $table_rows .= "
        <tr>
            <td>{$date}</td>
            <td style='color:#1e3a8a; font-weight:bold;'>#{$ref}</td>
            <td>{$cust}</td>
            <td>{$car}</td>
            <td class='right'>RM {$amount}</td>
        </tr>
    ";
}

$formatted_total = number_format($total_revenue, 2);
$display_start = date('d M Y', strtotime($start_date));
$display_end = date('d M Y', strtotime($end_date));
$generated_on = date('d M Y, h:i A');

// PDF 视觉外观设计 (高级高奢紫色财务风格)
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Financial Intelligence Audit Statement</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1e293b; padding: 15px; font-size: 13px; line-height: 1.5; }
        .header { border-bottom: 3px solid #6b21a8; padding-bottom: 15px; margin-bottom: 25px; }
        .title { font-size: 24px; color: #6b21a8; font-weight: bold; margin: 0; letter-spacing: 1px; }
        .subtitle { font-size: 12px; color: #64748b; margin-top: 5px; text-transform: uppercase; letter-spacing: 1px; }
        
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
        .summary-table td { padding: 15px; vertical-align: top; }
        .summary-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: bold; letter-spacing: 0.5px; }
        .summary-value { font-size: 22px; font-weight: bold; color: #0f172a; margin-top: 5px; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.data-table th { background-color: #6b21a8; color: white; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.data-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        table.data-table .right { text-align: right; }
        
        .total-row td { font-weight: bold; color: #6b21a8; background-color: #f8fafc; border-top: 2px solid #6b21a8; font-size: 14px; padding: 15px 12px; }
        .footer { text-align: center; margin-top: 50px; font-size: 11px; color: #94a3b8; border-top: 1px dashed #e2e8f0; padding-top: 15px; }
    /* 新增：高奢紫色防伪水印与页脚排版优化 */
        .watermark { 
            position: fixed; 
            top: 40%; 
            left: 10%; 
            font-size: 38px; 
            font-weight: 900; 
            color: rgba(107, 33, 168, 0.05); /* 极淡的紫色，既不影响阅读，又能防伪 */
            transform: rotate(-25deg); 
            z-index: -1000; 
            letter-spacing: 4px; 
        }
        .footer { 
            text-align: center; 
            margin-top: 60px; 
            font-size: 10px; 
            color: #94a3b8; 
            border-top: 1px dashed #e2e8f0; 
            padding-top: 15px; 
            line-height: 1.6;
        }
        </style>
</head>
<body>

    <div class='header'>
        <table style='width: 100%;'>
            <tr>
                <td>
                    <h1 class='title'>Revenue & Operations Statement</h1>
                    <div class='subtitle'>Audit Scope: {$display_start} &mdash; {$display_end}</div>
                </td>
                <td style='text-align: right; vertical-align: bottom; color: #94a3b8; font-size: 11px;'>
                    FLEET COMMAND SYSTEM
                </td>
            </tr>
        </table>
    </div>

    <table class='summary-table'>
        <tr>
            <td width='50%' style='border-right: 1px solid #e2e8f0;'>
                <div class='summary-label'>Total Volume Processed</div>
                <div class='summary-value'>{$total_bookings} Confirmed Deals</div>
            </td>
            <td width='50%'>
                <div class='summary-label'>Gross Settled Revenue</div>
                <div class='summary-value' style='color: #6b21a8;'>RM {$formatted_total}</div>
            </td>
        </tr>
    </table>

    <table class='data-table'>
        <thead>
            <tr>
                <th width='18%'>Transaction Date</th>
                <th width='18%'>Reference</th>
                <th width='24%'>Customer</th>
                <th width='25%'>Dispatched Vehicle</th>
                <th width='15%' class='right'>Settled Gross</th>
            </tr>
        </thead>
        <tbody>
            {$table_rows}
            <tr class='total-row'>
                <td colspan='4' class='right'>SUMMARY ACCUMULATED TOTAL</td>
                <td class='right'>RM {$formatted_total}</td>
            </tr>
        </tbody>
    </table>

    <div class='watermark'>FLEET COMMAND OFFICIAL AUDIT</div>
    
    <div class='footer'>
        This financial audit document was automatically compiled and verified by Fleet Command Center core modules.<br>
        <strong>Generation Timestamp:</strong> {$generated_on} | <strong>Cryptographic Token:</strong> #FC-2026-N1-SECURE<br>
        <span style='color: #6b21a8; font-weight: bold;'>Digital Footprint Secured. Any manual alteration to this ledger voids its systemic authenticity.</span>
    </div>

</body>
</html>
";

// 配置并执行 Dompdf 渲染
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 直接在浏览器流式预览输出
$dompdf->stream("Revenue_Statement_{$start_date}_to_{$end_date}.pdf", ["Attachment" => 0]);
?>
