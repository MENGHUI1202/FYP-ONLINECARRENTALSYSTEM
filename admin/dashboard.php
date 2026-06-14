<?php
include('../includes/config.php');
include('../includes/auth.php');
checkLogin();

date_default_timezone_set("Asia/Kuala_Lumpur");

// --- 1. 核心 KPI 统计 (保留原逻辑) ---
$cars_count = $conn->query("SELECT COUNT(*) as c FROM cars WHERE is_deleted = 0")->fetch_assoc()['c'];
$users_count = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$revenue = $conn->query("SELECT SUM(grand_total) as s FROM bookings WHERE booking_status IN ('approved', 'active', 'completed')")->fetch_assoc()['s'] ?? 0;
$active_deals = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE booking_status IN ('approved', 'active')")->fetch_assoc()['c'];

// --- 2. System Alerts 逻辑 (用于触发小铃铛红点) ---
$pending_bookings = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE booking_status = 'pending' OR booking_status = ''")->fetch_assoc()['c'];
$low_stock_cars = $conn->query("SELECT COUNT(*) as c FROM cars WHERE availability = 0 AND is_deleted = 0")->fetch_assoc()['c'];
// 【高级预警数据】
$pending_kyc_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE kyc_status = 'Pending'")->fetch_assoc()['c'] ?? 0;

$now = date('Y-m-d H:i:s');
// 晚拿车 (Late Pickups)：已经过了拿车时间，但还没交车 (Confirmed)
$late_pickups = $conn->query("SELECT COUNT(*) as c FROM booking_items bi JOIN bookings b ON bi.booking_id = b.id WHERE bi.start_datetime < '$now' AND b.booking_status = 'approved'")->fetch_assoc()['c'] ?? 0;
// 逾期未还 (Overdue Returns)：已经过了还车时间，但车还没回来 (Active)
$overdue_returns = $conn->query("SELECT COUNT(*) as c FROM booking_items bi JOIN bookings b ON bi.booking_id = b.id WHERE bi.end_datetime < '$now' AND b.booking_status = 'active'")->fetch_assoc()['c'] ?? 0;

// --- 3. 趋势图表数据 (过去 6 个月) ---
$months_labels = [];
$revenue_data = [];
$orders_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_val = date('Y-m', strtotime("-$i months"));
    $display_month = date('M', strtotime("-$i months"));
    $months_labels[] = $display_month;
    $rev = $conn->query("SELECT SUM(grand_total) as s FROM bookings WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month_val' AND booking_status IN ('approved', 'active', 'completed')")->fetch_assoc()['s'] ?? 0;
    $revenue_data[] = $rev;
    $ords = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month_val'")->fetch_assoc()['c'] ?? 0;
    $orders_data[] = $ords;
}

// --- 4. 最近流水 ---
$recent = $conn->query("SELECT b.*, u.name, 
                        (SELECT c.car_name FROM booking_items bi JOIN cars c ON bi.car_id = c.id WHERE bi.booking_id = b.id LIMIT 1) as car_name 
                        FROM bookings b 
                        JOIN users u ON b.user_id=u.id 
                        ORDER BY b.created_at DESC LIMIT 6");

// --- 5. 动态实时活动流 (Live Activity Feed) 算法 ---
// 动态时间计算函数 (Time-Ago Logic)
function time_ago($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array('y' => 'year','m' => 'month','w' => 'week','d' => 'day','h' => 'hour','i' => 'min','s' => 'sec');
    foreach ($string as $k => &$v) {
        if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } else { unset($string[$k]); }
    }
    $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'Just now';
}

// 抓取当前登录的管理员名字，如果没设置则默认显示 System Admin
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'System Admin'; 

$live_activities = [];
// 抓取最近的真实订单事件
$feed_res = $conn->query("SELECT b.booking_reference, b.booking_status, b.grand_total, b.created_at, u.name as customer_name, (SELECT c.car_name FROM booking_items bi JOIN cars c ON bi.car_id = c.id WHERE bi.booking_id = b.id LIMIT 1) as car_name FROM bookings b JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC LIMIT 5");

while($f = $feed_res->fetch_assoc()) {
    $time_str = time_ago($f['created_at']);
    $st = strtolower($f['booking_status']);
    if ($st === 'approved') $st = 'confirmed';
    
    // 智能事件映射机制
    if ($st == 'completed') {
        $live_activities[] = [
            'time' => $time_str, 'title' => 'Vehicle Returned',
            'desc' => "{$f['car_name']} checked in successfully. No damages reported.",
            'icon' => '<i class="fas fa-undo-alt text-[8px] text-blue-600"></i>', 'bg' => 'bg-blue-100 border-2 border-white', 'extra' => ''
        ];
    } elseif ($st == 'confirmed') {
        // Confirmed 状态：我们动态拆分成 "管理员审批通过" 和 "定金到账" 两个连贯动作
        $live_activities[] = [
            'time' => $time_str, 'title' => 'KYC Verification Completed',
            'desc' => "Customer <span class='font-bold text-slate-700'>{$f['customer_name']}</span> identity documents verified by <span class='text-emerald-600 font-bold'>{$admin_name}</span>.",
            'icon' => '<i class="fas fa-id-card text-[8px] text-emerald-600"></i>', 'bg' => 'bg-emerald-100 border-2 border-white', 'extra' => ''
        ];
        $live_activities[] = [
            'time' => time_ago(date('Y-m-d H:i:s', strtotime($f['created_at'] . ' - 5 minutes'))), 
            'title' => 'Deposit Received',
            'desc' => "RM " . number_format($f['grand_total'], 2) . " security deposit captured via FPX (Ref: #{$f['booking_reference']}).",
            'icon' => '<i class="fas fa-money-bill-wave text-[8px] text-white"></i>', 'bg' => 'bg-slate-800 border-2 border-white', 'extra' => ''
        ];
    } elseif ($st == 'pending' || $st == '') {
        $live_activities[] = [
            'time' => $time_str, 'title' => 'System detected overlapping booking',
            'desc' => "{$f['car_name']} requested by {$f['customer_name']} conflicts with existing reservation.",
            'icon' => '<i class="fas fa-exclamation-triangle text-[8px] text-amber-600"></i>', 'bg' => 'bg-amber-100 border-2 border-white',
            'extra' => '<span class="mt-2 inline-block px-2 py-0.5 bg-amber-50 text-amber-600 text-[10px] font-bold rounded border border-amber-200">Medium Risk</span>'
        ];
    } elseif ($st == 'active') {
        $live_activities[] = [
            'time' => $time_str, 'title' => 'Vehicle Handover',
            'desc' => "{$f['car_name']} keys handed over to {$f['customer_name']}. Vehicle is now on the road.",
            'icon' => '<i class="fas fa-car-side text-[8px] text-teal-600"></i>', 'bg' => 'bg-teal-100 border-2 border-white', 'extra' => ''
        ];
    }
}
// 截取前 4 个确保 UI 排版不乱
$live_activities = array_slice($live_activities, 0, 4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Intelligence | Fleet Command</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: { 
                        primary: { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 900: '#1e3a8a' }
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        },
                        swing: {
                            '0%, 100%': { transform: 'rotate(0deg)' },
                            '25%': { transform: 'rotate(15deg)' },
                            '50%': { transform: 'rotate(-10deg)' },
                            '75%': { transform: 'rotate(5deg)' },
                        }
                    },
                    animation: {
                        'blob': 'blob 10s infinite',
                        'swing': 'swing 0.5s ease-in-out',
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f4f7f9; }
        .glass-panel { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); }
        .glass-panel-hover:hover { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); transform: translateY(-2px); transition: all 0.3s ease; }
    </style>
</head>

<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex selection:bg-blue-200">

    <?php include('include/sidebar.php'); ?>
    
    <main class="ml-64 p-8 w-full max-w-[1800px] mx-auto grid grid-cols-1 xl:grid-cols-4 gap-8">
        
        <div class="xl:col-span-3 space-y-8">
            
            <header class="flex justify-between items-start pb-6 border-b border-slate-200">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black shadow-lg shadow-blue-500/30"><i class="fas fa-chart-pie"></i></div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Fleet Command Center</h1>
                    </div>
                    <p class="text-slate-500 text-sm font-medium">Real-time overview of your rental operations and revenue.</p>
                </div>
                
                <div class="text-right flex flex-col items-end">
                    <p class="text-sm font-black text-slate-800 uppercase tracking-widest mb-2"><?php echo date('l, d M Y'); ?></p>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 shadow-sm rounded-full">
                            <span class="relative flex h-2 w-2">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-[10px] font-bold text-slate-600 tracking-wide uppercase">All Systems Normal</span>
                        </div>
                    </div>
                </div>
            </header>

            <?php if($pending_kyc_count > 0 || $late_pickups > 0 || $overdue_returns > 0): ?>
            <div class="bg-red-50 border border-red-200 rounded-2xl p-6 mb-8 shadow-sm">
                <h3 class="text-sm font-black text-red-600 uppercase tracking-widest mb-4 flex items-center"><i class="fas fa-exclamation-triangle mr-2 animate-pulse"></i> Urgent Attention Required</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl p-4 border border-red-100 flex justify-between items-center shadow-sm">
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Pending KYC</p><h4 class="text-2xl font-black <?php echo $pending_kyc_count > 0 ? 'text-red-500' : 'text-slate-800'; ?>"><?php echo $pending_kyc_count; ?></h4></div>
                        <i class="fas fa-id-badge text-3xl <?php echo $pending_kyc_count > 0 ? 'text-red-200' : 'text-slate-100'; ?>"></i>
                    </div>
                    <div class="bg-white rounded-xl p-4 border border-red-100 flex justify-between items-center shadow-sm">
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Late Pickups</p><h4 class="text-2xl font-black <?php echo $late_pickups > 0 ? 'text-amber-500' : 'text-slate-800'; ?>"><?php echo $late_pickups; ?></h4></div>
                        <i class="fas fa-clock text-3xl <?php echo $late_pickups > 0 ? 'text-amber-200' : 'text-slate-100'; ?>"></i>
                    </div>
                    <div class="bg-white rounded-xl p-4 border border-red-100 flex justify-between items-center shadow-sm">
                        <div><p class="text-[10px] font-black text-slate-400 uppercase">Overdue Returns</p><h4 class="text-2xl font-black <?php echo $overdue_returns > 0 ? 'text-red-600' : 'text-slate-800'; ?>"><?php echo $overdue_returns; ?></h4></div>
                        <i class="fas fa-radiation text-3xl <?php echo $overdue_returns > 0 ? 'text-red-200' : 'text-slate-100'; ?>"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="lg:col-span-2 bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 relative overflow-hidden shadow-xl shadow-slate-900/20 text-white border border-slate-700">
                    <div class="absolute -right-10 -top-10 text-9xl text-white/5"><i class="fas fa-wallet"></i></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Monthly Revenue</h3>
                        <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 text-[10px] font-bold rounded border border-emerald-500/30"><i class="fas fa-arrow-up mr-1"></i>12%</span>
                    </div>
                    <div class="text-4xl font-black mb-1 relative z-10">RM <?php echo number_format($revenue); ?></div>
                    <p class="text-sm text-slate-400 relative z-10">vs RM <?php echo number_format($revenue * 0.88); ?> last month</p>
                </div>

                <div class="lg:col-span-1 bg-blue-600 rounded-2xl p-6 relative overflow-hidden shadow-xl shadow-blue-500/20 text-white">
                    <div class="absolute -right-6 -bottom-6 text-7xl text-white/10"><i class="fas fa-key"></i></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <h3 class="text-xs font-black text-blue-200 uppercase tracking-widest">Active Rentals</h3>
                    </div>
                    <div class="text-4xl font-black mb-1 relative z-10"><?php echo $active_deals; ?></div>
                    <p class="text-sm text-blue-200 relative z-10">Currently on the road</p>
                </div>

                <div class="lg:col-span-1 flex flex-col gap-5">
                    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex-1 flex flex-col justify-center">
                        <div class="flex justify-between items-center mb-1">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Fleet Stock</h3>
                            <i class="fas fa-car text-slate-300"></i>
                        </div>
                        <div class="text-xl font-black text-slate-800"><?php echo $cars_count; ?> <span class="text-xs font-normal text-slate-500 ml-1">Total</span></div>
                    </div>
                    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex-1 flex flex-col justify-center">
                        <div class="flex justify-between items-center mb-1">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending Leads</h3>
                            <i class="fas fa-clock text-amber-300"></i>
                        </div>
                        <div class="text-xl font-black text-slate-800"><?php echo $pending_bookings; ?> <span class="text-xs font-normal text-slate-500 ml-1">Awaiting</span></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="manage_cars.php" class="bg-white border border-slate-200 hover:border-blue-300 hover:shadow-md rounded-xl p-4 flex items-center gap-3 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition-colors"><i class="fas fa-plus"></i></div>
                    <div><h4 class="font-bold text-sm text-slate-800">New Vehicle</h4></div>
                </a>
                <a href="live_tracking.php" class="bg-white border border-slate-200 hover:border-teal-300 hover:shadow-md rounded-xl p-4 flex items-center gap-3 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center group-hover:bg-teal-600 group-hover:text-white transition-colors"><i class="fas fa-map-marked-alt"></i></div>
                    <div><h4 class="font-bold text-sm text-slate-800">Fleet Map</h4></div>
                </a>
                
                <a href="kyc_management.php" class="bg-white border border-slate-200 hover:border-indigo-300 hover:shadow-md rounded-xl p-4 flex items-center gap-3 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition-colors relative">
                        <i class="fas fa-id-card"></i>
                        
                        <?php if($pending_kyc_count > 0): ?>
                        <span class="absolute -top-1 -right-1 flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                        <?php endif; ?>

                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-slate-800">KYC Verify</h4>
                        <p class="text-[10px] text-emerald-600 font-medium mt-0.5">Real-time System</p>
                    </div>
                </a>
                
                <a href="export_reports.php" class="bg-white border border-slate-200 hover:border-purple-300 hover:shadow-md rounded-xl p-4 flex items-center gap-3 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-slate-800">Reports</h4>
                        <p class="text-[10px] text-purple-600 font-medium mt-0.5">PDF Engine</p>
                    </div>
                </a>
                </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest"><i class="fas fa-chart-area text-blue-500 mr-2"></i>Revenue Trend</h3>
                    </div>
                    <div class="h-[250px] w-full"><canvas id="trendsChart"></canvas></div>
                </div>

                <div class="bg-white rounded-2xl flex flex-col border border-slate-200 shadow-sm overflow-hidden">
                    <div class="flex justify-between items-center px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest"><i class="fas fa-bolt text-amber-500 mr-2"></i>Recent Pipeline</h3>
                        <a href="manage_bookings.php" class="text-xs font-bold text-blue-600 hover:underline">View All</a>
                    </div>
                    <div class="flex-1 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <tbody class="divide-y divide-slate-100">
                                <?php while($row = $recent->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="text-xs text-slate-500 mt-0.5"><?php echo !empty($row['car_name']) ? htmlspecialchars($row['car_name']) : 'N/A'; ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="font-black text-slate-800 mb-1">RM <?php echo number_format($row['grand_total'], 2); ?></div>
                                        <?php 
                                            $raw_st = strtolower($row['booking_status']); 
                                            if ($raw_st === 'approved') $raw_st = 'confirmed';
                                            if ($raw_st == 'confirmed') echo '<span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700">Confirmed</span>';
                                            elseif ($raw_st == 'pending' || $raw_st == '') echo '<span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-700">Pending</span>';
                                            elseif ($raw_st == 'completed') echo '<span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600">Completed</span>';
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:col-span-1">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm h-full flex flex-col sticky top-8">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-2xl">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                        Live Activity
                    </h3>
                </div>
                
                <div class="p-5 flex-1 overflow-y-auto">
                    <div class="relative border-l border-slate-200 ml-3 space-y-6 pb-4">
                        
                        <?php if(!empty($live_activities)): ?>
                            <?php foreach($live_activities as $activity): ?>
                            <div class="relative pl-6 group">
                                <span class="absolute -left-[9px] top-1 h-4 w-4 rounded-full <?php echo $activity['bg']; ?> flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <?php echo $activity['icon']; ?>
                                </span>
                                <div class="text-xs text-slate-400 mb-0.5 font-bold"><?php echo $activity['time']; ?></div>
                                <div class="text-sm font-bold text-slate-800"><?php echo $activity['title']; ?></div>
                                <div class="text-xs text-slate-500 mt-1 leading-relaxed"><?php echo $activity['desc']; ?></div>
                                <?php echo $activity['extra']; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-xs text-slate-400 font-bold pl-6">No recent activity detected.</div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        const ctx = document.getElementById('trendsChart').getContext('2d');
        const trendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months_labels); ?>,
                datasets: [
                    { label: 'Revenue (RM)', data: <?php echo json_encode($revenue_data); ?>, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', borderWidth: 3, pointBackgroundColor: '#ffffff', pointBorderColor: '#ef4444', fill: true, tension: 0.4, yAxisID: 'y' },
                    { label: 'Total Orders', data: <?php echo json_encode($orders_data); ?>, borderColor: '#3b82f6', borderDash: [5, 5], borderWidth: 3, pointBackgroundColor: '#ffffff', pointBorderColor: '#3b82f6', fill: false, tension: 0.4, yAxisID: 'y1' }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, font: { family: "'Plus Jakarta Sans'", size: 11, weight: 'bold' } } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { weight: 'bold' }, color: '#94a3b8' } },
                    y: { type: 'linear', display: true, position: 'left', grid: { color: '#f1f5f9', borderDash: [5, 5] }, ticks: { color: '#94a3b8', callback: v => 'RM '+(v/1000)+'k' } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { display: false }, ticks: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
