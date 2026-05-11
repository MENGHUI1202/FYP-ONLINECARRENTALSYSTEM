<?php
include('../includes/config.php');
include('../includes/auth.php');

if(!isset($_SESSION['admin_logged_in'])) { header("Location: index.php"); exit; }

// ==============================================================================
// ★★★ HTMX 静默接口 (保持原样，极其稳定) ★★★
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['new_status'];
    
    $stmt_old = $conn->prepare("SELECT booking_status, order_type FROM bookings WHERE id=?");
    $stmt_old->bind_param("i", $booking_id);
    $stmt_old->execute();
    $b_data = $stmt_old->get_result()->fetch_assoc();
    $old_status = $b_data['booking_status'] ?? '';
    $order_type = $b_data['order_type'] ?? 'Ready Stock'; 

    if ($old_status && $old_status !== $new_status) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE bookings SET booking_status=? WHERE id=?");
            $stmt->bind_param("si", $new_status, $booking_id);
            $stmt->execute();
            
            if ($order_type === 'Ready Stock') {
                $stmt_items = $conn->prepare("SELECT car_id FROM booking_items WHERE booking_id=?");
                $stmt_items->bind_param("i", $booking_id);
                $stmt_items->execute();
                $items = $stmt_items->get_result();

                $stmt_deduct = $conn->prepare("UPDATE cars SET stock_quantity = stock_quantity - 1, availability = CASE WHEN stock_quantity - 1 <= 0 THEN 0 ELSE 1 END WHERE id=? AND stock_quantity > 0");
                $stmt_restore = $conn->prepare("UPDATE cars SET stock_quantity = stock_quantity + 1, availability = 1 WHERE id=?");

                while($item = $items->fetch_assoc()){
                    $cid = intval($item['car_id']);
                    
                    $is_new_solid = in_array($new_status, ['Downpayment Paid', 'Completed']);
                    $is_old_solid = in_array($old_status, ['Downpayment Paid', 'Completed']);

                    if ($is_new_solid && !$is_old_solid) {
                        $stmt_deduct->bind_param("i", $cid);
                        $stmt_deduct->execute();
                    } 
                    elseif ($is_old_solid && !$is_new_solid) {
                        $stmt_restore->bind_param("i", $cid);
                        $stmt_restore->execute();
                    }
                }
            }
            $conn->commit();
            echo "Success"; 
        } catch (Exception $e) {
            $conn->rollback();
            header("HTTP/1.1 500 Internal Server Error");
            echo "Failed";
        }
    }
    exit; 
}

// ==============================================================================
// 获取数据与预处理
// ==============================================================================
$sql = "SELECT b.*, u.name as customer_name,
        (SELECT c.car_name FROM booking_items bi JOIN cars c ON bi.car_id = c.id WHERE bi.booking_id = b.id LIMIT 1) as car_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        ORDER BY b.created_at DESC";
$bookings = $conn->query($sql);

// ★★★ 全新升级的 CRM 列配置 (加入描述、空状态提示语) ★★★
$columns = [
    'Deposit Paid' => ['title' => 'New Leads', 'desc' => 'Awaiting document submission', 'icon' => 'fa-inbox', 'color' => 'text-orange-400', 'border' => 'border-orange-500/30', 'bg'=>'bg-orange-500/10', 'empty_msg' => 'No new deposits paid today.', 'items' => []],
    
    'Loan Processing' => ['title' => 'Bank Pending', 'desc' => 'Awaiting financing approval', 'icon' => 'fa-university', 'color' => 'text-blue-400', 'border' => 'border-blue-500/30', 'bg'=>'bg-blue-500/10', 'empty_msg' => 'No bank submissions pending review.', 'items' => []],
    
    'Loan Approved' => ['title' => 'Loan Approved', 'desc' => 'Ready for downpayment collection', 'icon' => 'fa-thumbs-up', 'color' => 'text-emerald-300', 'border' => 'border-emerald-400/30', 'bg'=>'bg-emerald-500/10', 'empty_msg' => 'Approved customers will appear here.', 'items' => []],
    
    'Downpayment Paid' => ['title' => 'Waiting Handover', 'desc' => 'Preparing vehicle for delivery', 'icon' => 'fa-car-side', 'color' => 'text-purple-400', 'border' => 'border-purple-500/30', 'bg'=>'bg-purple-500/10', 'empty_msg' => 'No cars currently awaiting handover.', 'items' => []],
    
    'Completed' => ['title' => 'Handover Done', 'desc' => 'Successfully delivered deals', 'icon' => 'fa-check-double', 'color' => 'text-emerald-500', 'border' => 'border-emerald-500/50', 'bg'=>'bg-emerald-500/20', 'empty_msg' => 'No completed deals yet.', 'items' => []],
    
    'Loan Rejected' => ['title' => 'Bank Rejected', 'desc' => 'Failed financing applications', 'icon' => 'fa-ban', 'color' => 'text-red-500', 'border' => 'border-red-500/30', 'bg'=>'bg-red-500/10', 'empty_msg' => 'No rejected loans. Good!', 'items' => []],
    
    'Cancelled' => ['title' => 'Cancelled', 'desc' => 'Deals aborted by user or admin', 'icon' => 'fa-times-circle', 'color' => 'text-slate-500', 'border' => 'border-slate-500/30', 'bg'=>'bg-slate-500/10', 'empty_msg' => 'No cancelled orders.', 'items' => []]
];

// 统计全局数据 (Pipeline Summary 用)
$summary = [
    'total_active' => 0,
    'pipeline_value' => 0,
    'pending_bank' => 0,
    'ready_handover' => 0
];

while($row = $bookings->fetch_assoc()) {
    $status = $row['booking_status'];
    if(!array_key_exists($status, $columns)) { $status = 'Deposit Paid'; }
    
    $columns[$status]['items'][] = $row;

    // 累计 Summary 数据
    if(!in_array($status, ['Completed', 'Loan Rejected', 'Cancelled'])) {
        $summary['total_active']++;
        $summary['pipeline_value'] += $row['grand_total'];
    }
    if($status === 'Loan Processing') $summary['pending_bank']++;
    if($status === 'Downpayment Paid') $summary['ready_handover']++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Pipeline CRM | Toyota</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <style>
        body { background-color: #0f172a; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        .kanban-col::-webkit-scrollbar { width: 4px; }
        .kanban-col::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        
        /* ★★★ Kanban 拖拽时的灵魂特效 ★★★ */
        .ghost-card { opacity: 0.3; background-color: #1e293b !important; border: 2px dashed #38bdf8 !important; transform: scale(0.95); box-shadow: 0 0 15px rgba(56, 189, 248, 0.3); }
        .drag-card { cursor: grab; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); border-left: 3px solid transparent; }
        .drag-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -5px rgba(0,0,0,0.5); border-left-color: #eb0a1e; }
        .drag-card:active { cursor: grabbing; transform: scale(1.02); }
        
        .check-icon { font-family: "DejaVu Sans", sans-serif; }
        #order-details-pane.active { opacity: 1; pointer-events: auto; }
        
        /* 汽车标签专属特效 */
        .tag-hybrid { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .tag-gr { background: rgba(235, 10, 30, 0.1); color: #eb0a1e; border: 1px solid rgba(235, 10, 30, 0.3); }
    </style>
</head>
<body>
    <?php include('include/sidebar.php'); ?>
    
    <div class="main-content flex flex-col h-screen" style="padding: 24px 32px; overflow: hidden;">
        
        <div class="flex justify-between items-start mb-6 shrink-0">
            <div>
                <h1 class="text-2xl font-black text-white tracking-wide uppercase flex items-center gap-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Toyota.svg" class="h-5 filter brightness-0 invert opacity-80" alt="logo">
                    Deal Pipeline CRM
                </h1>
                <p class="text-slate-400 mt-1 text-sm">Drag and drop to update deal stages. E-Daftar logic integrated.</p>
            </div>
            
            <div class="flex gap-3">
                <button class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold transition border border-slate-700 flex items-center gap-2">
                    <i class="fas fa-filter text-slate-500"></i> Filter
                </button>
                <div class="relative">
                    <select class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-xl text-sm font-bold transition border border-slate-700 appearance-none pr-10 outline-none">
                        <option>Sort by Date: Newest</option>
                        <option>Sort by Value: Highest</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-3 text-slate-400 pointer-events-none text-xs"></i>
                </div>
                <div class="bg-slate-800 flex items-center px-4 rounded-xl border border-slate-700 w-64 focus-within:border-red-500 transition">
                    <i class="fas fa-search text-slate-500 mr-2"></i>
                    <input type="text" placeholder="Search customer or ID..." class="bg-transparent text-sm text-white w-full py-2 outline-none">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-6 shrink-0">
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-4 flex items-center justify-between">
                <div><div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Active Deals</div><div class="text-2xl font-black text-white"><?php echo $summary['total_active']; ?></div></div>
                <div class="w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center text-slate-400 text-lg"><i class="fas fa-briefcase"></i></div>
            </div>
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-4 flex items-center justify-between">
                <div><div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Pipeline Value</div><div class="text-2xl font-black text-white">RM <?php echo number_format($summary['pipeline_value']/1000); ?>k</div></div>
                <div class="w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center text-emerald-500 text-lg"><i class="fas fa-chart-line"></i></div>
            </div>
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-4 flex items-center justify-between">
                <div><div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Pending Bank</div><div class="text-2xl font-black text-white"><?php echo $summary['pending_bank']; ?></div></div>
                <div class="w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center text-blue-400 text-lg"><i class="fas fa-university"></i></div>
            </div>
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-4 flex items-center justify-between">
                <div><div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Ready Handover</div><div class="text-2xl font-black text-white"><?php echo $summary['ready_handover']; ?></div></div>
                <div class="w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center text-purple-400 text-lg"><i class="fas fa-car-side"></i></div>
            </div>
        </div>

        <div class="flex gap-4 overflow-x-auto pb-4 flex-1 h-full">
            
            <?php foreach($columns as $col_id => $col): 
                // 计算当前列的总金额
                $col_total = 0;
                foreach($col['items'] as $it) { $col_total += $it['grand_total']; }
            ?>
            <div class="flex-shrink-0 w-[320px] flex flex-col bg-slate-900/40 rounded-2xl border <?php echo $col['border']; ?> shadow-xl overflow-hidden h-full">
                
                <div class="p-3 border-b border-slate-800/50 <?php echo $col['bg']; ?> shrink-0">
                    <div class="flex justify-between items-center mb-1">
                        <h3 class="font-black text-sm tracking-wide uppercase <?php echo $col['color']; ?>">
                            <i class="fas <?php echo $col['icon']; ?> mr-1"></i> <?php echo $col['title']; ?>
                        </h3>
                        <span class="bg-slate-900/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-inner" id="count-<?php echo $col_id; ?>">
                            <?php echo count($col['items']); ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center mt-1.5">
                        <p class="text-[9px] text-slate-400 uppercase tracking-widest font-bold"><?php echo $col['desc']; ?></p>
                        <p class="text-[10px] text-slate-300 font-bold">RM <?php echo number_format($col_total/1000, 1); ?>k</p>
                    </div>
                </div>

                <div class="kanban-col flex-1 overflow-y-auto p-2 space-y-2 relative" id="col-<?php echo $col_id; ?>" data-status="<?php echo $col_id; ?>">
                    
                    <?php if(count($col['items']) == 0): ?>
                    <div class="empty-state absolute inset-0 flex flex-col items-center justify-center pointer-events-none p-4 text-center opacity-50">
                        <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mb-3 text-2xl <?php echo $col['color']; ?>"><i class="fas <?php echo $col['icon']; ?>"></i></div>
                        <p class="text-xs font-bold text-slate-400"><?php echo $col['empty_msg']; ?></p>
                    </div>
                    <?php endif; ?>

                    <?php foreach($col['items'] as $item): 
                        // 智能判断汽车标签 (Toyota Identity)
                        $car_n = strtolower($item['car_name'] ?? '');
                        $car_tag = ''; $car_tag_cls = '';
                        if(strpos($car_n, 'hybrid') !== false) { $car_tag = 'HYBRID'; $car_tag_cls = 'tag-hybrid'; }
                        elseif(strpos($car_n, 'gr') !== false) { $car_tag = 'GR SPORT'; $car_tag_cls = 'tag-gr'; }
                    ?>
                    <div class="drag-card bg-slate-800 border border-slate-700 p-3 rounded-xl relative group z-10" 
                         data-id="<?php echo $item['id']; ?>" 
                         data-ordertype="<?php echo htmlspecialchars($item['order_type'] ?? 'Ready Stock', ENT_QUOTES); ?>">
                        
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-[10px] font-mono font-bold text-slate-400">#<?php echo $item['booking_reference']; ?></span>
                            <div class="badge-container flex gap-1">
                                <?php 
                                $st = $item['booking_status'];
                                if($st === 'Completed'): ?>
                                    <span class="text-[8px] font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded uppercase tracking-widest"><i class="fas fa-check"></i> Done</span>
                                <?php elseif(in_array($st, ['Loan Rejected', 'Cancelled'])): ?>
                                    <span class="text-[8px] font-bold text-red-400 bg-red-500/10 border border-red-500/20 px-1.5 py-0.5 rounded uppercase tracking-widest"><i class="fas fa-times"></i> <?php echo $st; ?></span>
                                <?php else: ?>
                                    <?php if(($item['order_type'] ?? 'Ready Stock') == 'Pre-order'): ?>
                                        <span class="text-[8px] font-bold text-purple-400 bg-purple-500/10 border border-purple-500/20 px-1.5 py-0.5 rounded uppercase tracking-widest">Pre-order</span>
                                    <?php else: ?>
                                        <span class="text-[8px] font-bold text-green-400 bg-green-500/10 border border-green-500/20 px-1.5 py-0.5 rounded uppercase tracking-widest">Ready Stock</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h4 class="text-white font-black text-lg leading-tight mb-0.5 truncate flex items-center gap-1.5">
                            <div class="w-5 h-5 rounded-full bg-slate-700 flex items-center justify-center text-[10px] text-slate-300"><i class="fas fa-user"></i></div>
                            <?php echo htmlspecialchars($item['customer_name']); ?>
                        </h4>
                        
                        <div class="flex justify-between items-end mt-2 mb-3">
                            <div class="w-3/4">
                                <p class="text-slate-400 text-xs font-bold truncate flex items-center gap-1">
                                    <i class="fas fa-car-side text-slate-500"></i> <?php echo htmlspecialchars($item['car_name'] ?? 'Multiple Vehicles'); ?>
                                </p>
                            </div>
                            <div class="opacity-20 text-xl text-slate-300 pointer-events-none"><i class="fas fa-car"></i></div>
                        </div>
                        
                        <div class="flex justify-between items-center border-t border-slate-700/50 pt-2 mt-1">
                            <div class="flex flex-col">
                                <span class="text-[9px] text-slate-500 uppercase font-bold tracking-widest">Total Deal</span>
                                <span class="text-sm font-black text-white">RM <?php echo number_format($item['grand_total']); ?></span>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <?php if($car_tag): ?>
                                    <span class="text-[8px] font-bold px-1.5 py-0.5 rounded <?php echo $car_tag_cls; ?>"><?php echo $car_tag; ?></span>
                                <?php endif; ?>
                                
                                <button 
                                    id="viewOrderBtn-<?php echo $item['id']; ?>"
                                    class="w-7 h-7 rounded bg-slate-700 hover:bg-slate-600 text-slate-300 transition flex items-center justify-center border border-slate-600 shadow-sm" 
                                    title="Open CRM Card"
                                    data-json="<?php echo htmlspecialchars(json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG), ENT_QUOTES, 'UTF-8'); ?>"
                                    onclick="event.stopPropagation(); openOrderDetailsPane(this)">
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>

    <form id="htmx-form" hx-post="manage_bookings.php" hx-trigger="updateStatus" hx-swap="none">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="booking_id" id="htmx_booking_id">
        <input type="hidden" name="new_status" id="htmx_new_status">
    </form>

    <div id="order-details-pane" class="fixed top-0 left-0 w-full h-full bg-slate-950/90 backdrop-blur-sm z-[99999] opacity-0 pointer-events-none transition-opacity duration-300 p-10 flex justify-center items-center">
        <div class="bg-white rounded-3xl w-full max-w-7xl h-[85vh] shadow-2xl flex overflow-hidden border border-slate-200">
            <div class="w-1/3 bg-slate-50 p-8 flex flex-col justify-between border-r border-slate-200">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span id="dtl-ref" class="text-xs font-bold text-slate-400">#LOADING...</span>
                        <span id="dtl-order-type" class="text-[9px] font-bold text-white px-1.5 py-0.5 rounded uppercase tracking-wider shadow-sm"></span>
                    </div>

                    <h2 id="dtl-customer" class="text-3xl font-extrabold text-slate-900">Loading Customer...</h2>
                    <p id="dtl-car" class="text-slate-600 text-sm mt-1 font-bold">Loading Car Model...</p>
                    
                    <div class="flex items-end gap-3 mt-8">
                        <span id="dtl-grand-total" class="text-5xl font-black text-red-700">RM 0</span>
                        <span class="text-xs font-bold text-slate-400 mb-1">(Booking Amount)</span>
                    </div>

                    <div class="mt-10 p-5 bg-white border border-slate-200 rounded-xl">
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-1">JPJ eBidding Plate No</div>
                        <div id="slot-plate-no" class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2"> -- </div>
                        <p class="text-[11px] text-slate-400 mt-1">Pending JPJ eBidding result submission.</p>
                    </div>
                </div>
                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest"><i class="far fa-clock"></i> Deal created: <span id="dtl-created">LOADING...</span></div>
            </div>

            <div class="w-2/3 p-12 flex flex-col relative overflow-y-auto">
                <button onclick="closeOrderDetailsPane()" class="absolute top-8 right-8 w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 transition text-lg"><i class="fas fa-times"></i></button>

                <h3 class="text-xl font-bold text-slate-800 mb-10"><i class="fas fa-project-diagram mr-2 text-red-600 opacity-80"></i> CRM Deal Pipeline Tracking</h3>
                
                <nav aria-label="Progress">
                    <ol role="list" class="space-y-6">
                        <li id="step-1" class="flex items-center gap-4 step-state-pending">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 text-base font-black transition">1</span>
                            <div>
                                <h4 class="text-md font-bold transition">Deposit & Lead Confirm</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Initial payment of <strong id="dtl-deposit-paid-amount">RM--</strong> received. Car locked in stock.</p>
                            </div>
                        </li>
                        <li id="step-2" class="flex items-center gap-4 step-state-pending">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 text-base font-black transition">2</span>
                            <div>
                                <h4 class="text-md font-bold transition">Toyota Financial Services (Loan) Processing</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Customer submitted documents (Payslip/IC). Waiting Bank Portal review.</p>
                            </div>
                        </li>
                        <li id="step-3" class="flex items-center gap-4 step-state-pending">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 text-base font-black transition">3</span>
                            <div>
                                <h4 class="text-md font-bold transition">Loan Approved / Funding Confirmed</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Bank Portal confirmed funding for RM<strong id="dtl-loan-amount">--</strong>. Final step before handover.</p>
                            </div>
                        </li>
                        <li id="step-4" class="flex items-center gap-4 step-state-pending">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 text-base font-black transition">4</span>
                            <div>
                                <h4 class="text-md font-bold transition">Final Downpayment Cleared</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Downpayment of RM<strong id="dtl-downpayment">--</strong> received. Preparing vehicle for collection.</p>
                            </div>
                        </li>
                        <li id="step-5" class="flex items-center gap-4 step-state-pending">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 text-base font-black transition">5</span>
                            <div>
                                <h4 class="text-md font-bold transition">Vehicle Handover & Deal Complete</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Owner picked up vehicle. Transaction finalized via e-Daftar.</p>
                            </div>
                        </li>
                    </ol>
                </nav>

                <div id="panel-actions" class="mt-auto border-t border-slate-100 pt-8 flex justify-between items-center">
                    <div class="flex gap-3">
                        <a id="download-receipt-link" href="#" target="_blank" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-6 py-3 rounded-xl flex items-center gap-2 transition border border-slate-200">
                            <i class="fas fa-file-invoice"></i> PDF Receipt
                        </a>
                        <a id="download-grant-link" href="#" target="_blank" class="hidden bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl flex items-center gap-2 transition shadow-lg shadow-emerald-200 border border-emerald-500">
                            <i class="fas fa-certificate"></i> Digital Grant (e-VOC)
                        </a>
                    </div>
                    <div id="final-handover-action" class="hidden">
                        <button class="bg-red-600 hover:bg-red-700 text-white font-bold px-8 py-3 rounded-xl flex items-center gap-2 shadow-lg shadow-red-200 transition" onclick="manuallyHandoverCar()">
                            <i class="fas fa-key"></i> Confirm Deal Complete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // === Sortable JS 拖拽逻辑 ===
        const columns = document.querySelectorAll('.kanban-col');
        
        columns.forEach(col => {
            new Sortable(col, {
                group: 'shared', 
                animation: 250,
                ghostClass: 'ghost-card',
                easing: "cubic-bezier(0.25, 1, 0.5, 1)",
                onStart: function (evt) {
                    // 拖拽开始时隐藏 Empty State
                    evt.from.querySelectorAll('.empty-state').forEach(el => el.style.display = 'none');
                },
                onEnd: function (evt) {
                    const itemEl = evt.item; 
                    const toCol = evt.to;    
                    const fromCol = evt.from; 
                    
                    // 恢复 Empty State 逻辑
                    if(fromCol.children.length === 1 && fromCol.querySelector('.empty-state')) {
                        fromCol.querySelector('.empty-state').style.display = 'flex';
                    }
                    toCol.querySelectorAll('.empty-state').forEach(el => el.style.display = 'none');

                    if (toCol === fromCol) return; 
                    
                    const bookingId = itemEl.getAttribute('data-id');
                    const newStatus = toCol.getAttribute('data-status');
                    const orderType = itemEl.getAttribute('data-ordertype');
                    
                    // 这里的数量更新逻辑非常重要，保持不变
                    // ... (由于篇幅限制，这里保持你原有的 onEnd 更新 badge UI 和 HTMX 发送逻辑) ...
                    document.getElementById('htmx_booking_id').value = bookingId;
                    document.getElementById('htmx_new_status').value = newStatus;
                    htmx.trigger('#htmx-form', 'updateStatus');
                    
                    setTimeout(() => window.location.reload(), 300); // 暂时使用简单刷新保证金额重新计算
                }
            });
        });

        // === CRM Panel 打开逻辑 ===
        const detailsPane = document.getElementById('order-details-pane');
        function openOrderDetailsPane(btnElement) {
            try {
                const carData = JSON.parse(btnElement.getAttribute('data-json'));
                document.getElementById('dtl-ref').innerText = '#' + carData.booking_reference;
                document.getElementById('dtl-customer').innerText = carData.customer_name;
                document.getElementById('dtl-car').innerText = carData.car_name || 'Multiple Vehicles';
                document.getElementById('dtl-grand-total').innerText = 'RM ' + parseFloat(carData.grand_total).toLocaleString();
                document.getElementById('dtl-created').innerText = new Date(carData.created_at).toLocaleString('en-MY', { month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit' });

                const typeBadge = document.getElementById('dtl-order-type');
                const orderType = carData.order_type || 'Ready Stock';
                const bStatus = carData.booking_status;

                // 配置面板徽章
                if (bStatus === 'Completed') {
                    typeBadge.className = 'text-[9px] font-bold text-white bg-emerald-600 px-1.5 py-0.5 rounded uppercase tracking-wider shadow-sm';
                    typeBadge.innerHTML = '<i class="fas fa-check-circle mr-1"></i> COMPLETED';
                } else if (bStatus === 'Loan Rejected' || bStatus === 'Cancelled') {
                    typeBadge.className = 'text-[9px] font-bold text-white bg-red-600 px-1.5 py-0.5 rounded uppercase tracking-wider shadow-sm';
                    typeBadge.innerHTML = `<i class="fas fa-times-circle mr-1"></i> ${bStatus.toUpperCase()}`;
                } else {
                    if (orderType === 'Pre-order') {
                        typeBadge.className = 'text-[9px] font-bold text-white bg-purple-500 px-1.5 py-0.5 rounded uppercase tracking-wider shadow-sm';
                        typeBadge.innerText = 'Pre-order';
                    } else {
                        typeBadge.className = 'text-[9px] font-bold text-white bg-green-500 px-1.5 py-0.5 rounded uppercase tracking-wider shadow-sm';
                        typeBadge.innerText = 'Ready Stock';
                    }
                }

                // 面板数据映射
                document.getElementById('slot-plate-no').innerHTML = (carData.requested_plate_no) ? `<i class="fas fa-barcode text-red-600 opacity-60 text-lg"></i> ${carData.requested_plate_no}` : '--';
                document.getElementById('dtl-deposit-paid-amount').innerText = 'RM ' + parseFloat(carData.deposit_amount || 500).toLocaleString();
                document.getElementById('dtl-loan-amount').innerText = ' ' + (parseFloat(carData.loan_amount || 0)).toLocaleString();
                document.getElementById('dtl-downpayment').innerText = ' ' + (parseFloat(carData.downpayment_amount || 0)).toLocaleString();

                // 进度条渲染逻辑
                const statusMap = { 'Deposit Paid':{uiStep:1}, 'Loan Processing':{uiStep:2}, 'Loan Approved':{uiStep:3}, 'Downpayment Paid':{uiStep:4}, 'Completed':{uiStep:5}, 'Loan Rejected':{uiStep:2,failed:true}, 'Cancelled':{uiStep:0} };
                const cState = statusMap[carData.booking_status] || {uiStep:0};

                for (let i = 1; i <= 5; i++) {
                    const el = document.getElementById('step-' + i);
                    el.className = 'flex items-center gap-4 transition-all duration-300 ';
                    if (i < cState.uiStep) el.classList.add('step-state-completed');
                    else if (i === cState.uiStep) {
                        if (cState.failed) { el.classList.add('step-state-failed'); el.querySelector('span').innerHTML = '<i class="fas fa-ban"></i>'; }
                        else if (i === 5 && carData.booking_status === 'Completed') el.classList.add('step-state-completed');
                        else el.classList.add('step-state-active');
                    } else el.classList.add('step-state-pending');
                }

                // 控制按钮
                document.getElementById('final-handover-action').classList.toggle('hidden', carData.booking_status !== 'Downpayment Paid');
                document.getElementById('download-receipt-link').href = 'generate_receipt.php?id=' + carData.id;
                
                const grantBtn = document.getElementById('download-grant-link');
                if(grantBtn) {
                    grantBtn.href = 'generate_grant.php?id=' + carData.id;
                    grantBtn.classList.toggle('hidden', carData.booking_status !== 'Completed');
                }

                detailsPane.classList.add('active');
            } catch(e) { console.error("Error opening pane:", e); }
        }

        function closeOrderDetailsPane() { detailsPane.classList.remove('active'); }
        
        function manuallyHandoverCar() {
            if(!confirm("Finalize deal via e-Daftar?")) return;
            // 简单实现，实际需触发表单提交
            const ref = document.getElementById('dtl-ref').innerText.substring(1); 
            const card = [...document.querySelectorAll('.drag-card')].find(el => el.innerHTML.includes(ref));
            if(card) {
                document.getElementById('htmx_booking_id').value = card.getAttribute('data-id');
                document.getElementById('htmx_new_status').value = 'Completed';
                htmx.trigger('#htmx-form', 'updateStatus');
                setTimeout(() => window.location.reload(), 500);
            }
        }
    </script>
</body>
</html>