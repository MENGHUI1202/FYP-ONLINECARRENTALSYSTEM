<?php
include('../includes/config.php');
include('../includes/auth.php');

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}

// 1. 核心统计数据
$total_cars = $conn->query("SELECT COUNT(*) FROM cars")->fetch_row()[0];
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_bookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$total_revenue = $conn->query("SELECT SUM(grand_total) FROM bookings WHERE booking_status IN ('Completed', 'Downpayment Paid')")->fetch_row()[0] ?? 0;

// 2. 智能警报数据
$low_stock_cars = $conn->query("SELECT COUNT(*) FROM cars WHERE stock_quantity <= 2")->fetch_row()[0];
$pending_loans = $conn->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'Loan Processing'")->fetch_row()[0];
$new_leads = $conn->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'Deposit Paid'")->fetch_row()[0];

// 3. ★★★ 升级版：获取近期交易 (图片抓取已安全屏蔽) ★★★
$recent_bookings = $conn->query("
    SELECT b.id, b.booking_reference, b.booking_status, b.grand_total, b.created_at, b.order_type,
           u.name as customer_name, 
           (SELECT c.car_name FROM booking_items bi JOIN cars c ON bi.car_id = c.id WHERE bi.booking_id = b.id LIMIT 1) as car_name
           /* 如果你的数据库有图片字段，请把下面这行的注释 // 删掉，并把 car_image_column 换成你真实的字段名 */
           /* , (SELECT c.car_image_column FROM booking_items bi JOIN cars c ON bi.car_id = c.id WHERE bi.booking_id = b.id LIMIT 1) as car_image */
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Toyota Dealership Control Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .red-glow-border { border-left: 4px solid #eb0a1e; }
        
        .metric-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; }
        .metric-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5); border-color: rgba(235, 10, 30, 0.3); }
        
        .badge-pending { background: #fef08a; color: #9a3412; border: 1px solid #fde047; }
        .badge-processing { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
        .badge-completed { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-danger { background: #ffe4e6; color: #9f1239; border: 1px solid #fecdd3; }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }

        /* Notification Dropdown Animation */
        #notificationDropdown { transform-origin: top right; transition: all 0.2s ease-out; }
        .dropdown-hidden { opacity: 0; transform: scale(0.95); pointer-events: none; }
        .dropdown-visible { opacity: 1; transform: scale(1); pointer-events: auto; }
    </style>
</head>
<body class="flex">

    <?php include('include/sidebar.php'); ?>

    <div class="flex-1 ml-64 p-8 overflow-y-auto h-screen">
        
        <header class="flex justify-between items-end mb-8 border-b border-slate-800 pb-6 relative">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Toyota.svg" alt="Toyota Logo" class="h-6 filter brightness-0 invert opacity-80">
                    <h1 class="text-2xl font-black tracking-wider uppercase text-white">Toyota Business Intelligence Center</h1>
                </div>
                <p class="text-slate-400 text-sm tracking-wide">Monitor dealership operations, inventory and customer activities in real time.</p>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <div class="text-xs text-slate-400 font-bold tracking-widest uppercase"><?php echo date('l, d F Y'); ?></div>
                    <div class="text-sm font-bold text-white"><i class="fas fa-circle text-emerald-500 text-[8px] mr-1 blink"></i> System Online</div>
                </div>
                
                <div class="flex gap-3 relative">
                    <button onclick="toggleNotifications()" class="w-10 h-10 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center transition relative">
                        <i class="fas fa-bell"></i>
                        <?php $total_alerts = $new_leads + $pending_loans + ($low_stock_cars > 0 ? 1 : 0); ?>
                        <?php if($total_alerts > 0): ?><span class="absolute top-0 right-0 w-4 h-4 bg-red-600 text-white text-[9px] font-bold rounded-full border-2 border-slate-900 flex items-center justify-center"><?php echo $total_alerts; ?></span><?php endif; ?>
                    </button>
                    
                    <div id="notificationDropdown" class="dropdown-hidden absolute top-12 right-12 w-80 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-700 bg-slate-800/50 flex justify-between items-center">
                            <span class="font-bold text-white text-sm">System Alerts</span>
                            <span class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-full"><?php echo $total_alerts; ?> New</span>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <?php if($new_leads > 0): ?>
                            <a href="manage_bookings.php" class="flex items-start gap-3 p-4 border-b border-slate-700 hover:bg-slate-700/50 transition">
                                <div class="w-8 h-8 rounded-full bg-yellow-500/20 text-yellow-500 flex items-center justify-center flex-shrink-0"><i class="fas fa-file-signature"></i></div>
                                <div><p class="text-sm font-bold text-white mb-0.5">New Leads Received</p><p class="text-xs text-slate-400"><?php echo $new_leads; ?> customers paid deposit.</p></div>
                            </a>
                            <?php endif; ?>
                            <?php if($pending_loans > 0): ?>
                            <a href="manage_bookings.php" class="flex items-start gap-3 p-4 border-b border-slate-700 hover:bg-slate-700/50 transition">
                                <div class="w-8 h-8 rounded-full bg-blue-500/20 text-blue-500 flex items-center justify-center flex-shrink-0"><i class="fas fa-university"></i></div>
                                <div><p class="text-sm font-bold text-white mb-0.5">Loan Approvals Pending</p><p class="text-xs text-slate-400"><?php echo $pending_loans; ?> deals waiting for bank.</p></div>
                            </a>
                            <?php endif; ?>
                            <?php if($low_stock_cars > 0): ?>
                            <a href="manage_cars.php" class="flex items-start gap-3 p-4 border-b border-slate-700 hover:bg-slate-700/50 transition">
                                <div class="w-8 h-8 rounded-full bg-red-500/20 text-red-500 flex items-center justify-center flex-shrink-0"><i class="fas fa-exclamation-triangle"></i></div>
                                <div><p class="text-sm font-bold text-white mb-0.5">Low Stock Warning</p><p class="text-xs text-slate-400"><?php echo $low_stock_cars; ?> models need restocking.</p></div>
                            </a>
                            <?php endif; ?>
                            <?php if($total_alerts == 0): ?>
                            <div class="p-6 text-center text-slate-500 text-sm"><i class="fas fa-check-circle text-2xl mb-2 block opacity-50"></i> All clear. No new alerts.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="w-10 h-10 rounded-full bg-slate-700 border-2 border-slate-600 overflow-hidden cursor-pointer hover:border-red-500 transition">
                        <div class="w-full h-full flex items-center justify-center bg-slate-800 font-bold text-slate-300">AD</div>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex gap-4 mb-8">
            <a href="manage_cars.php" class="flex-1 glass-panel rounded-xl p-4 flex items-center gap-4 hover:bg-slate-800/80 transition group">
                <div class="w-12 h-12 rounded-lg bg-red-500/10 text-red-500 flex items-center justify-center text-xl group-hover:bg-red-500 group-hover:text-white transition"><i class="fas fa-car"></i></div>
                <div><div class="font-bold text-white">Add Vehicle</div><div class="text-xs text-slate-400">Update showroom stock</div></div>
            </a>
            <a href="manage_bookings.php" class="flex-1 glass-panel rounded-xl p-4 flex items-center gap-4 hover:bg-slate-800/80 transition group">
                <div class="w-12 h-12 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center text-xl group-hover:bg-blue-500 group-hover:text-white transition"><i class="fas fa-tasks"></i></div>
                <div><div class="font-bold text-white">Process Leads</div><div class="text-xs text-slate-400">View Kanban pipeline</div></div>
            </a>
            <button onclick="alert('Module in development')" class="flex-1 glass-panel rounded-xl p-4 flex items-center gap-4 hover:bg-slate-800/80 transition group">
                <div class="w-12 h-12 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl group-hover:bg-emerald-500 group-hover:text-white transition"><i class="fas fa-calendar-check"></i></div>
                <div><div class="font-bold text-white">Test Drives</div><div class="text-xs text-slate-400">Manage appointments</div></div>
            </button>
            <button onclick="alert('PDF Report Generation will be connected here.')" class="flex-1 glass-panel rounded-xl p-4 flex items-center gap-4 hover:bg-slate-800/80 transition group">
                <div class="w-12 h-12 rounded-lg bg-purple-500/10 text-purple-500 flex items-center justify-center text-xl group-hover:bg-purple-500 group-hover:text-white transition"><i class="fas fa-file-export"></i></div>
                <div><div class="font-bold text-white">Export Report</div><div class="text-xs text-slate-400">Download PDF/Excel</div></div>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="glass-panel metric-card rounded-2xl p-6 red-glow-border">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Revenue</p>
                        <h3 class="text-3xl font-black text-white">RM <?php echo number_format($total_revenue); ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-300"><i class="fas fa-wallet"></i></div>
                </div>
                <div class="mt-4 text-xs font-bold text-emerald-400 flex items-center gap-1"><i class="fas fa-arrow-up"></i> 12% vs last month</div>
            </div>

            <div class="glass-panel metric-card rounded-2xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Active Deals</p>
                        <h3 class="text-3xl font-black text-white"><?php echo $total_bookings; ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-300"><i class="fas fa-file-contract"></i></div>
                </div>
                <div class="mt-4 text-xs font-bold text-orange-400 flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> <?php echo $new_leads; ?> new leads waiting</div>
            </div>

            <div class="glass-panel metric-card rounded-2xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Fleet Inventory</p>
                        <h3 class="text-3xl font-black text-white"><?php echo $total_cars; ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-300"><i class="fas fa-car-side"></i></div>
                </div>
                <div class="mt-4 text-xs font-bold <?php echo $low_stock_cars > 0 ? 'text-red-400' : 'text-slate-400'; ?> flex items-center gap-1">
                    <i class="fas fa-info-circle"></i> <?php echo $low_stock_cars; ?> models in low stock
                </div>
            </div>

            <div class="glass-panel metric-card rounded-2xl p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Customer Base</p>
                        <h3 class="text-3xl font-black text-white"><?php echo $total_users; ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-300"><i class="fas fa-users"></i></div>
                </div>
                <div class="mt-4 text-xs font-bold text-emerald-400 flex items-center gap-1"><i class="fas fa-arrow-up"></i> 5% growth rate</div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-8">
            
            <div class="col-span-2 glass-panel rounded-2xl p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-white text-lg"><i class="fas fa-chart-line mr-2 text-red-500"></i> Sales & Revenue Trends</h3>
                    <select class="bg-slate-800 text-slate-300 text-xs font-bold px-3 py-1 rounded outline-none border border-slate-700">
                        <option>Last 6 Months</option>
                        <option>This Year</option>
                    </select>
                </div>
                <div class="h-64 w-full relative">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <div class="glass-panel rounded-2xl p-6 flex flex-col">
                <h3 class="font-bold text-white text-lg mb-6"><i class="fas fa-satellite-dish mr-2 text-red-500"></i> System Alerts</h3>
                
                <div class="space-y-4 flex-1">
                    <?php if($low_stock_cars > 0): ?>
                    <div class="bg-slate-800 border-l-4 border-red-500 p-4 rounded-r-lg shadow flex gap-3">
                        <i class="fas fa-exclamation-triangle text-red-500 mt-1"></i>
                        <div>
                            <h4 class="text-white font-bold text-sm">Inventory Alert</h4>
                            <p class="text-slate-400 text-xs mt-1"><?php echo $low_stock_cars; ?> vehicle models are running low on stock. Consider ordering.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($pending_loans > 0): ?>
                    <div class="bg-slate-800 border-l-4 border-blue-500 p-4 rounded-r-lg shadow flex gap-3">
                        <i class="fas fa-university text-blue-500 mt-1"></i>
                        <div>
                            <h4 class="text-white font-bold text-sm">Bank Approvals Pending</h4>
                            <p class="text-slate-400 text-xs mt-1"><?php echo $pending_loans; ?> orders are stuck in Loan Processing stage. Check Bank Portal.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($new_leads > 0): ?>
                    <div class="bg-slate-800 border-l-4 border-yellow-500 p-4 rounded-r-lg shadow flex gap-3">
                        <i class="fas fa-file-signature text-yellow-500 mt-1"></i>
                        <div>
                            <h4 class="text-white font-bold text-sm">New Leads Require Attention</h4>
                            <p class="text-slate-400 text-xs mt-1"><?php echo $new_leads; ?> customers have paid deposits. Await document submission.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mt-8 glass-panel rounded-2xl p-6 mb-10">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-white text-lg"><i class="fas fa-list-alt mr-2 text-red-500"></i> Deal Pipeline Flow (Latest)</h3>
                <a href="manage_bookings.php" class="text-xs font-bold text-slate-400 hover:text-white transition bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700">Open Kanban Board <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 text-xs uppercase tracking-wider border-b border-slate-700">
                            <th class="pb-3 px-4 w-20">Vehicle</th>
                            <th class="pb-3 px-4">Ref ID / Customer</th>
                            <th class="pb-3 px-4">Amount</th>
                            <th class="pb-3 px-4">Stage & Status</th>
                            <th class="pb-3 px-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php 
                        if($recent_bookings->num_rows > 0): 
                            while($deal = $recent_bookings->fetch_assoc()):
                                $st = $deal['booking_status'];
                                $badge_class = 'badge-pending'; 
                                if(in_array($st, ['Loan Approved', 'Completed'])) $badge_class = 'badge-completed'; 
                                if(in_array($st, ['Loan Processing', 'Downpayment Paid'])) $badge_class = 'badge-processing'; 
                                if(in_array($st, ['Loan Rejected', 'Cancelled'])) $badge_class = 'badge-danger'; 
                        ?>
                        <tr class="border-b border-slate-800/50 hover:bg-slate-800/50 transition">
                            <td class="py-3 px-4">
                                <?php if(!empty($deal['car_image'])): ?>
                                    <div class="w-16 h-10 rounded bg-slate-800 border border-slate-700 overflow-hidden relative">
                                        <img src="../uploads/<?php echo $deal['car_image']; ?>" class="w-full h-full object-cover opacity-80" alt="car">
                                    </div>
                                <?php else: ?>
                                    <div class="w-16 h-10 rounded bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-500"><i class="fas fa-car"></i></div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-mono text-xs font-bold text-slate-400 mb-0.5">#<?php echo $deal['booking_reference']; ?></div>
                                <div class="font-bold text-white"><i class="fas fa-user-circle text-slate-500 mr-1 text-xs"></i> <?php echo htmlspecialchars($deal['customer_name']); ?></div>
                                <div class="text-[10px] text-slate-500 truncate w-40"><?php echo htmlspecialchars($deal['car_name'] ?? 'Multiple Vehicles'); ?></div>
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-200">RM <?php echo number_format($deal['grand_total']); ?></td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider <?php echo $badge_class; ?>">
                                    <?php echo $st; ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="manage_bookings.php" class="inline-block bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-lg text-xs font-bold transition border border-slate-700 shadow-sm">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-800 mb-4 text-slate-500 text-2xl">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h3 class="text-white font-bold text-lg mb-1">No Recent Deals Found</h3>
                                <p class="text-slate-400 text-sm">When customers place orders, they will appear in this pipeline.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        // === Dropdown 逻辑 ===
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown.classList.contains('dropdown-hidden')) {
                dropdown.classList.remove('dropdown-hidden');
                dropdown.classList.add('dropdown-visible');
            } else {
                dropdown.classList.add('dropdown-hidden');
                dropdown.classList.remove('dropdown-visible');
            }
        }
        
        // 点击其他地方关闭下拉菜单
        window.addEventListener('click', function(e) {
            if (!document.getElementById('notificationDropdown').contains(e.target) && !e.target.closest('button[onclick="toggleNotifications()"]')) {
                document.getElementById('notificationDropdown').classList.add('dropdown-hidden');
                document.getElementById('notificationDropdown').classList.remove('dropdown-visible');
            }
        });

        // === 核心图表逻辑 Chart.js ===
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        // 创建渐变效果 (更高级)
        const gradientRevenue = ctx.createLinearGradient(0, 0, 0, 400);
        gradientRevenue.addColorStop(0, 'rgba(235, 10, 30, 0.5)'); // Toyota Red
        gradientRevenue.addColorStop(1, 'rgba(235, 10, 30, 0.0)');

        const gradientOrders = ctx.createLinearGradient(0, 0, 0, 400);
        gradientOrders.addColorStop(0, 'rgba(56, 189, 248, 0.5)'); // Light Blue
        gradientOrders.addColorStop(1, 'rgba(56, 189, 248, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Revenue (RM)',
                        data: [45000, 60000, 35000, 80000, 95000, 150000],
                        borderColor: '#eb0a1e',
                        backgroundColor: gradientRevenue,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#eb0a1e',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.4, // 曲线平滑度
                        yAxisID: 'y'
                    },
                    {
                        label: 'Total Orders',
                        data: [3, 4, 2, 6, 7, 10],
                        borderColor: '#38bdf8',
                        backgroundColor: gradientOrders,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#38bdf8',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        borderDash: [5, 5], // 虚线区分
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: '#94a3b8', font: { family: 'Helvetica', weight: 'bold' } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: true,
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(51, 65, 85, 0.3)', drawBorder: false },
                        ticks: { color: '#64748b' }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: { color: 'rgba(51, 65, 85, 0.3)', drawBorder: false },
                        ticks: { color: '#64748b', callback: function(value) { return 'RM ' + (value/1000) + 'k'; } }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: { drawOnChartArea: false }, // 避免网格线重叠
                        ticks: { color: '#38bdf8', stepSize: 2 }
                    }
                }
            }
        });
    </script>
</body>
</html>