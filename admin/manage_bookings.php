<?php
include('../includes/config.php');
include('../includes/auth.php');
checkLogin();


// --- 1. 逻辑处理部分 (加入 Reject 备注处理) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $requested_action = strtolower(trim($_GET['action']));
    $status_map = [
        'confirmed' => 'approved',
        'approve' => 'approved',
        'approved' => 'approved',
        'active' => 'active',
        'cancelled' => 'cancelled',
        'completed' => 'completed',
        'reject' => 'rejected',
        'rejected' => 'rejected',
    ];
    $action = $status_map[$requested_action] ?? '';
    $allowed_status = ['approved', 'active', 'cancelled', 'completed', 'rejected'];
    
  if (in_array($action, $allowed_status, true)) {
        $notes = isset($_POST['admin_notes']) ? $conn->real_escape_string($_POST['admin_notes']) : '';
        
        // 开启事务 (Transaction) 保护
        $conn->begin_transaction();
        
        try {
            if ($notes !== '') {
            $stmt = $conn->prepare("UPDATE bookings SET booking_status=?, admin_note=?, admin_notes=?, id = booking_id WHERE booking_id=?");
                $stmt->bind_param("sssi", $action, $notes, $notes, $id);
            } else {
                $stmt = $conn->prepare("UPDATE bookings SET booking_status=?, id = booking_id WHERE booking_id=?");
                $stmt->bind_param("si", $action, $id);
            }
            $stmt->execute();

            $booking_user_res = $conn->query("SELECT user_id FROM bookings WHERE booking_id=$id LIMIT 1");
            $booking_user_id = intval($booking_user_res->fetch_assoc()['user_id'] ?? 0);
            if ($booking_user_id > 0 && ($action == 'approved' || $action == 'rejected')) {
                $review_admin_id = current_admin_id();
                $review_admin_sql = $review_admin_id > 0 ? (string)$review_admin_id : 'NULL';
                if ($action == 'approved') {
                    $conn->query("UPDATE user_documents SET verification_status = 'Verified', admin_note = NULL, reviewed_by_admin_id = $review_admin_sql, reviewed_at = NOW() WHERE user_id = $booking_user_id AND verification_status = 'Pending Verification'");
                } else {
                    $safe_notes = $conn->real_escape_string($notes ?: 'Document rejected. Please re-upload clearer identity documents.');
                    $conn->query("UPDATE user_documents SET verification_status = 'Rejected', admin_note = '$safe_notes', reviewed_by_admin_id = $review_admin_sql, reviewed_at = NOW() WHERE user_id = $booking_user_id AND verification_status = 'Pending Verification'");
                }
                if ($conn->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name = 'sp_refresh_user_kyc_status'")->num_rows > 0) {
                    $conn->query("CALL sp_refresh_user_kyc_status($booking_user_id)");
                }
            }
             
            $items = $conn->query("SELECT car_id, unit_id FROM booking_items WHERE booking_id=$id");
            while($item = $items->fetch_assoc()){
                $cid = $item['car_id'];
                $unit_id = intval($item['unit_id'] ?? 0);
                if ($action == 'approved' || $action == 'active') {
                    // 加锁减库存
                    if ($unit_id > 0) $conn->query("UPDATE car_units SET current_status = 'booked', reserved_booking_id = $id WHERE unit_id = $unit_id");
                } elseif ($action == 'cancelled' || $action == 'completed' || $action == 'rejected') {
                    // 解锁加库存
                    if ($unit_id > 0) $conn->query("UPDATE car_units SET current_status = 'available', reserved_booking_id = NULL WHERE unit_id = $unit_id AND reserved_booking_id = $id");
                }
                $conn->query("UPDATE cars c LEFT JOIN (SELECT car_id, SUM(current_status = 'available') AS available_units FROM car_units GROUP BY car_id) s ON s.car_id = c.car_id SET c.stock_quantity = COALESCE(s.available_units, 0), c.availability = CASE WHEN COALESCE(s.available_units, 0) > 0 THEN 1 ELSE 0 END WHERE c.id=$cid AND c.is_deleted = 0");
            }
            
            // 提交事务
            $meta_res = $conn->query("
                SELECT b.booking_reference, u.name AS customer_name,
                       (SELECT CONCAT(c.brand, ' ', c.car_name)
                        FROM booking_items bi
                        JOIN cars c ON c.id = bi.car_id
                        WHERE bi.booking_id = b.booking_id
                        LIMIT 1) AS car_label
                FROM bookings b
                LEFT JOIN users u ON u.user_id = b.user_id
                WHERE b.booking_id = $id
                LIMIT 1
            ");
            $meta = $meta_res ? $meta_res->fetch_assoc() : [];
            $ref = $meta['booking_reference'] ?? ('#' . $id);
            $customer = $meta['customer_name'] ?? 'Customer';
            $car_label = trim((string)($meta['car_label'] ?? ''));
            $audit_actions = [
                'approved' => ['BOOKING_APPROVED', 'approved booking'],
                'active' => ['BOOKING_HANDOVER', 'executed handover for booking'],
                'completed' => ['BOOKING_RETURNED', 'processed return for booking'],
                'cancelled' => ['BOOKING_CANCELLED', 'cancelled booking'],
                'rejected' => ['BOOKING_REJECTED', 'rejected booking'],
            ];
            [$audit_type, $audit_phrase] = $audit_actions[$action] ?? ['BOOKING_UPDATED', 'updated booking'];
            $audit_details = ucfirst($audit_phrase) . " {$ref} for {$customer}.";
            if ($notes !== '') {
                $audit_details .= " Note: {$notes}";
            }
            admin_audit_log($conn, $audit_type, $audit_details, 'booking', $id, $car_label !== '' ? $car_label : null);

            $conn->commit();
            $msg = ($action == 'completed') ? 'returned' : 'updated';
            header("Location: manage_bookings.php?msg=$msg"); 
            exit;
            
        } catch (Exception $e) {
            // 一旦出错，立刻回滚，防止账目和库存不符
            $conn->rollback();
            echo "<script>alert('System Error: Concurrent action blocked. Rolled back.'); window.history.back();</script>";
            exit;
        }
    }
}

// --- 2. 高级运营 HUD 数据拉取 ---
$today = date('Y-m-d');
$stat_pending = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE booking_status IN ('pending', '')")->fetch_assoc()['c'] ?? 0;
$stat_active = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE booking_status = 'active'")->fetch_assoc()['c'] ?? 0;
$stat_handover = $conn->query("SELECT COUNT(DISTINCT b.booking_id) as c FROM bookings b JOIN booking_items bi ON b.booking_id = bi.booking_id WHERE b.booking_status = 'approved' AND DATE(bi.start_datetime) = '$today'")->fetch_assoc()['c'] ?? 0;
$stat_returns = $conn->query("SELECT COUNT(DISTINCT b.booking_id) as c FROM bookings b JOIN booking_items bi ON b.booking_id = bi.booking_id WHERE b.booking_status = 'active' AND DATE(bi.end_datetime) = '$today'")->fetch_assoc()['c'] ?? 0;

// --- 3. 列表数据查询 ---
$sql_active = "SELECT b.*, b.booking_id AS id, u.license_number, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
               (SELECT CASE WHEN ud.file_path LIKE 'assets/uploads/documents/%' THEN CONCAT('NEW_CAR_RENTAL_SYSTEM/', ud.file_path) ELSE ud.file_path END FROM user_documents ud WHERE ud.user_id = b.user_id AND ud.document_type IN ('IC Photo','IC','Identity Card') ORDER BY ud.uploaded_at DESC LIMIT 1) AS ic_document_path,
               (SELECT CASE WHEN ud.file_path LIKE 'assets/uploads/documents/%' THEN CONCAT('NEW_CAR_RENTAL_SYSTEM/', ud.file_path) ELSE ud.file_path END FROM user_documents ud WHERE ud.user_id = b.user_id AND ud.document_type IN ('Driving License Photo','Driving License','License') ORDER BY ud.uploaded_at DESC LIMIT 1) AS license_document_path
               FROM bookings b LEFT JOIN users u ON b.user_id = u.user_id WHERE b.booking_status IN ('pending', 'approved', 'active', '') ORDER BY b.created_at DESC";
$res_active = $conn->query($sql_active);

$sql_history = "SELECT b.*, b.booking_id AS id, u.license_number, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                (SELECT CASE WHEN ud.file_path LIKE 'assets/uploads/documents/%' THEN CONCAT('NEW_CAR_RENTAL_SYSTEM/', ud.file_path) ELSE ud.file_path END FROM user_documents ud WHERE ud.user_id = b.user_id AND ud.document_type IN ('IC Photo','IC','Identity Card') ORDER BY ud.uploaded_at DESC LIMIT 1) AS ic_document_path,
                (SELECT CASE WHEN ud.file_path LIKE 'assets/uploads/documents/%' THEN CONCAT('NEW_CAR_RENTAL_SYSTEM/', ud.file_path) ELSE ud.file_path END FROM user_documents ud WHERE ud.user_id = b.user_id AND ud.document_type IN ('Driving License Photo','Driving License','License') ORDER BY ud.uploaded_at DESC LIMIT 1) AS license_document_path
                FROM bookings b LEFT JOIN users u ON b.user_id = u.user_id WHERE b.booking_status IN ('completed', 'cancelled', 'rejected') ORDER BY b.created_at DESC";
$res_history = $conn->query($sql_history);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Operations | Fleet Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }, colors: { primary: '#3b82f6', danger: '#ef4444', warning: '#f59e0b' } } } }
    </script>
    <style>
        body { background: #f8fafc; }
        .glass-card { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .nav-active { border-left: 4px solid #3b82f6; background: linear-gradient(90deg, #eff6ff 0%, #ffffff 100%); box-shadow: inset 2px 0 10px rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include('include/sidebar.php'); ?>
    
    <main class="ml-64 p-10 w-full max-w-[1600px] mx-auto">
        <header class="mb-8 flex justify-between items-end border-b border-slate-200 pb-6">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Operations Control</h1>
                <p class="text-slate-500 mt-1 font-medium">Real-time booking pipeline and risk assessment.</p>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-200 rounded-full">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">System Live</span>
            </div>
        </header>

        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-warning"></div>
                <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Action Required</p><h4 class="text-2xl font-black text-warning"><?php echo $stat_pending; ?> <span class="text-xs text-slate-400 font-bold">Pending KYC</span></h4></div>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-400"></div>
                <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Today's Handover</p><h4 class="text-2xl font-black text-emerald-500"><?php echo $stat_handover; ?> <span class="text-xs text-slate-400 font-bold">Vehicles</span></h4></div>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-400"></div>
                <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Fleet On Road</p><h4 class="text-2xl font-black text-blue-500"><?php echo $stat_active; ?> <span class="text-xs text-slate-400 font-bold">Active</span></h4></div>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-400"></div>
                <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Expected Returns</p><h4 class="text-2xl font-black text-indigo-500"><?php echo $stat_returns; ?> <span class="text-xs text-slate-400 font-bold">Today</span></h4></div>
            </div>
        </div>

        <?php if(isset($_GET['msg']) && $_GET['msg']=='returned'): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-2xl mb-8 flex items-center font-bold shadow-sm">
            <i class="fas fa-check-circle mr-3 text-lg"></i> Vehicle securely returned and archived to History.
        </div>
        <?php endif; ?>

        <div class="glass-card rounded-[2rem] overflow-hidden mb-12">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div class="relative w-72">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="bookingSearch" placeholder="Search reference..." class="w-full pl-11 pr-5 py-2.5 bg-white border border-slate-200 focus:border-primary rounded-xl outline-none transition-all font-bold text-sm">
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm">
                    <i class="fas fa-clock text-blue-500"></i><span class="font-black text-slate-700 text-xs uppercase tracking-widest">Active Pipeline</span>
                </div>
            </div>
            
            <div class="overflow-x-auto min-h-[300px] pb-24">
                <table class="w-full text-left border-collapse data-table">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Booking Details</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Vehicle & Time Risk</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 text-center w-72">Pipeline Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if($res_active->num_rows > 0): ?>
                            <?php while($row = $res_active->fetch_assoc()): 
                                $bid = intval($row['booking_id'] ?? $row['id'] ?? 0);
                                $items_res = $conn->query("SELECT bi.*, c.car_name, COALESCE(c.brand, br.brand_name) AS brand, c.image_url as main_image, (SELECT image_url FROM car_images WHERE car_id = c.car_id ORDER BY id ASC LIMIT 1) as gallery_image FROM booking_items bi LEFT JOIN cars c ON bi.car_id = c.car_id LEFT JOIN brands br ON br.brand_id = c.brand_id WHERE bi.booking_id = $bid");
                                $st_check = strtolower(trim($row['booking_status']));
                                if ($st_check === 'approved') $st_check = 'confirmed';
                                
                                $w = 0; $t1 = $t2 = $t3 = $t4 = 'bg-slate-200 text-slate-400'; $status_msg = '';
                                if ($st_check == 'pending' || $st_check == '') {
                                    $w = 0; $t1 = 'bg-warning text-white shadow-[0_0_12px_rgba(245,158,11,0.6)]'; $status_msg = '<span class="text-warning">AWAITING VERIFY</span>';
                                } elseif ($st_check == 'confirmed') {
                                    $w = 33; $t1 = 'bg-emerald-500 text-white'; $t2 = 'bg-blue-500 text-white shadow-[0_0_12px_rgba(59,130,246,0.6)] animate-pulse'; $status_msg = '<span class="text-blue-500">READY FOR HANDOVER</span>';
                                } elseif ($st_check == 'active') {
                                    $w = 66; $t1 = 'bg-emerald-500 text-white'; $t2 = 'bg-emerald-500 text-white'; $t3 = 'bg-indigo-500 text-white shadow-[0_0_12px_rgba(99,102,241,0.6)] animate-pulse'; $status_msg = '<span class="text-indigo-500">ON THE ROAD</span>';
                                }

                                // ========== 新增：智能风险评级 (Risk Assessment) ==========
                                $risk_level = 'LOW';
                                $risk_badge = '<span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded text-[9px] font-black tracking-widest uppercase shadow-sm">Low Risk</span>';
                                
                                if ($row['grand_total'] > 3000) {
                                    $risk_level = 'MEDIUM';
                                }
                                $wait_hours_total = floor((time() - strtotime($row['created_at'])) / 3600);
                                if (($st_check == 'pending' || $st_check == '') && $wait_hours_total > 24) {
                                    $risk_level = 'HIGH';
                                }

                                if ($risk_level == 'HIGH') {
                                    $risk_badge = '<span class="px-2 py-0.5 bg-red-50 text-red-600 border border-red-200 rounded text-[9px] font-black tracking-widest uppercase shadow-sm"><i class="fas fa-exclamation-triangle mr-1 animate-pulse"></i>High Risk</span>';
                                } elseif ($risk_level == 'MEDIUM') {
                                    $risk_badge = '<span class="px-2 py-0.5 bg-amber-50 text-amber-600 border border-amber-200 rounded text-[9px] font-black tracking-widest uppercase shadow-sm">Med Risk</span>';
                                }
                                // ========================================================
                                
                                $booking_has_fatal_conflict = false; // 用于锁定 Approve 按钮
                            ?>
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-6 py-6 align-top">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 font-bold border border-slate-200 shrink-0">
                                            <?php echo substr($row['customer_name'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <div class="font-black text-slate-900 text-base">#<?php echo $row['booking_reference']; ?></div>
                                            <div class="text-[10px] font-bold text-slate-500 mt-0.5"><?php echo htmlspecialchars($row['customer_name']); ?></div>
                                            <div class="text-[10px] font-bold text-slate-400 mb-1.5"><?php echo htmlspecialchars($row['customer_phone']); ?></div>
                                            <?php echo $risk_badge; ?> </div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-6">
                                    <div class="space-y-4">
                                    <?php while($item = $items_res->fetch_assoc()): 
                                        $img_src = !empty($item['gallery_image']) ? $item['gallery_image'] : $item['main_image'];
                                        if (!empty($img_src) && strpos($img_src, 'http') !== 0 && strpos($img_src, '../') === false) $img_src = '../' . $img_src;
                                        
                                        $clean_car_name = trim(str_ireplace($item['brand'], '', $item['car_name']));
                                        $display_name = $item['brand'] . ' ' . $clean_car_name;

                                        $now = time();
                                        $start_time = strtotime($item['start_datetime']);
                                        $end_time = strtotime($item['end_datetime']);
                                        $time_badge = '';
                                        
                                        if ($st_check == 'pending' || $st_check == '') {
                                            $wait_hours = floor(($now - strtotime($row['created_at'])) / 3600);
                                            $time_badge = "<span class='text-amber-700 bg-amber-100 px-2 py-0.5 rounded text-[9px] font-black border border-amber-300 shadow-sm'><i class='fas fa-clock mr-1'></i>Waiting {$wait_hours}h</span>";
                                        } elseif ($st_check == 'confirmed') {
                                            if ($start_time > $now) {
                                                $hours = floor(($start_time - $now) / 3600);
                                                $time_badge = "<span class='text-blue-700 bg-blue-100 px-2 py-0.5 rounded text-[9px] font-black border border-blue-300 shadow-sm'><i class='fas fa-stopwatch mr-1'></i>Pickup in {$hours}h</span>";
                                            } else {
                                                $time_badge = "<span class='text-white bg-danger px-2 py-0.5 rounded text-[9px] font-black animate-pulse shadow-md shadow-red-500/50'><i class='fas fa-exclamation-triangle mr-1'></i>LATE PICKUP</span>";
                                            }
                                        } elseif ($st_check == 'active') {
                                            if ($end_time < $now) {
                                                $days = floor(($now - $end_time) / 86400);
                                                $time_badge = "<span class='text-white bg-danger px-2 py-0.5 rounded text-[9px] font-black animate-pulse shadow-md shadow-red-500/50'><i class='fas fa-radiation mr-1'></i>OVERDUE {$days}D</span>";
                                            } else {
                                                $hours = floor(($end_time - $now) / 3600);
                                                $time_badge = "<span class='text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded text-[9px] font-black border border-emerald-300 shadow-sm'><i class='fas fa-hourglass-half mr-1'></i>Return in {$hours}h</span>";
                                            }
                                        }

                                        // 冲突检测
                                        $conflict_target = intval($item['unit_id'] ?? 0) > 0
                                            ? "bi.unit_id = " . intval($item['unit_id'])
                                            : "bi.car_id = " . intval($item['car_id']);
                                        $safe_start = $conn->real_escape_string($item['start_datetime']);
                                        $safe_end = $conn->real_escape_string($item['end_datetime']);
                                        $conflict_query = "SELECT COUNT(*) as c FROM booking_items bi JOIN bookings b ON bi.booking_id = b.booking_id WHERE $conflict_target AND b.booking_status IN ('approved', 'active') AND b.booking_id != {$bid} AND bi.start_datetime < '{$safe_end}' AND bi.end_datetime > '{$safe_start}'";
                                        $has_conflict = $conn->query($conflict_query)->fetch_assoc()['c'] > 0;
                                        
                                        $conflict_badge = "";
                                        if ($has_conflict && ($st_check == 'pending' || $st_check == '')) {
                                            $booking_has_fatal_conflict = true; // 触发锁死条件
                                            $conflict_badge = "<div class='text-danger text-[9px] font-black mt-1 px-2 py-0.5 bg-red-50 rounded border border-red-200 inline-block shadow-sm'><i class='fas fa-biohazard animate-pulse'></i> CONFLICT: ALREADY RESERVED</div>";
                                        }
                                    ?>
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 shrink-0 shadow-sm">
                                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <div class="font-black text-slate-800 text-sm leading-tight flex items-center flex-wrap gap-2 mb-1">
                                                <?php echo htmlspecialchars($display_name); ?>
                                                <?php echo $time_badge; ?>
                                            </div>
                                            <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest bg-slate-100 inline-block px-2 py-0.5 rounded border border-slate-200">
                                                <?php echo date('d M, H:i', strtotime($item['start_datetime'])); ?> <i class="fas fa-arrow-right mx-1 opacity-50"></i> <?php echo date('d M, H:i', strtotime($item['end_datetime'])); ?>
                                            </div>
                                            <br><?php echo $conflict_badge; ?>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-6 w-72 align-middle">
                                    <div class="flex flex-col items-center justify-center w-full mt-1">
                                        <div class="relative w-full max-w-[220px] h-1.5 bg-slate-200 rounded-full flex items-center justify-between z-0">
                                            <div class="absolute left-0 top-0 bottom-0 bg-emerald-400 rounded-full transition-all duration-1000 z-0" style="width: <?php echo $w; ?>%;"></div>
                                            <div class="w-7 h-7 rounded-full z-10 flex items-center justify-center text-[10px] ring-4 ring-white <?php echo $t1; ?>"><i class="fas fa-user-check"></i></div>
                                            <div class="w-7 h-7 rounded-full z-10 flex items-center justify-center text-[10px] ring-4 ring-white <?php echo $t2; ?>"><i class="fas fa-key"></i></div>
                                            <div class="w-7 h-7 rounded-full z-10 flex items-center justify-center text-[10px] ring-4 ring-white <?php echo $t3; ?>"><i class="fas fa-car-side"></i></div>
                                            <div class="w-7 h-7 rounded-full z-10 flex items-center justify-center text-[10px] ring-4 ring-white <?php echo $t4; ?>"><i class="fas fa-flag-checkered"></i></div>
                                        </div>
                                        <div class="text-[10px] font-black mt-4 uppercase tracking-widest text-center"><?php echo $status_msg; ?></div>
                                    </div>
                                </td>

                                <td class="px-6 py-6 text-right align-middle">
                                    <div class="relative inline-block text-left group">
                                        <button type="button" class="w-9 h-9 flex items-center justify-center bg-white border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm focus:outline-none">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="origin-top-right absolute right-12 top-0 w-52 rounded-xl shadow-2xl bg-white border border-slate-100 divide-y divide-slate-100 hidden group-hover:block z-[100]">
                                            <div class="py-1.5">
                                                <a href="#" onclick="viewBooking(<?php echo $row['id']; ?>)" class="group flex items-center px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-blue-600"><i class="fas fa-eye w-5 text-slate-400 group-hover:text-blue-500"></i> View Full Details</a>
                                                <?php if($st_check == 'confirmed' || $st_check == 'active'): ?>
                                                    <a href="generate_receipt.php?id=<?php echo $row['id']; ?>" target="_blank" class="group flex items-center px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-600"><i class="fas fa-file-invoice w-5 text-purple-400 group-hover:text-purple-500"></i> Print PDF Receipt</a>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="py-1.5 bg-slate-50/50">
                                                <?php if($st_check == 'pending' || $st_check == ''): ?>
                                                    <a href="#" onclick="viewKYC(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['ic_document_path'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['license_document_path'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['customer_name'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['license_number'] ?? 'N/A', ENT_QUOTES); ?>', 'verify', '<?php echo $st_check; ?>', '', <?php echo $booking_has_fatal_conflict ? 'true' : 'false'; ?>)" class="group flex items-center px-4 py-2.5 text-xs font-black text-amber-600 hover:bg-amber-100/50"><i class="fas fa-shield-alt w-5"></i> Verify Identity Docs</a>
                                                <?php elseif($st_check == 'confirmed'): ?>
                                                    <a href="?action=Active&id=<?php echo $row['id']; ?>" onclick="return confirm('Confirm key handover?')" class="group flex items-center px-4 py-2.5 text-xs font-black text-blue-600 hover:bg-blue-100/50"><i class="fas fa-key w-5"></i> Execute Handover</a>
                                                <?php elseif($st_check == 'active'): ?>
                                                    <a href="?action=Completed&id=<?php echo $row['id']; ?>" onclick="return confirm('Confirm vehicle returned?')" class="group flex items-center px-4 py-2.5 text-xs font-black text-emerald-600 hover:bg-emerald-100/50"><i class="fas fa-undo w-5"></i> Process Return</a>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="py-1.5">
                                                <a href="?action=Cancelled&id=<?php echo $row['id']; ?>" onclick="return confirm('Terminate this booking completely?')" class="group flex items-center px-4 py-2.5 text-xs font-bold text-red-600 hover:bg-red-50"><i class="fas fa-times-circle w-5 text-red-400 group-hover:text-red-600"></i> Cancel Reservation</a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-slate-400 font-bold py-12">No active pipeline items.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="flex items-center gap-3 mb-6 mt-10">
            <i class="fas fa-archive text-slate-500 text-xl"></i>
            <h3 class="text-xl font-black">History Archive</h3>
        </div>
        <div class="glass-card rounded-[2.5rem] overflow-hidden opacity-90 hover:opacity-100 transition-opacity">
            <div class="overflow-x-auto p-4">
                <table class="w-full text-left border-collapse">
                    <tbody class="divide-y divide-slate-100">
                        <?php if($res_history->num_rows > 0): ?>
                            <?php while($row = $res_history->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-5 font-black text-slate-400">#<?php echo $row['booking_reference']; ?></td>
                                <td class="px-6 py-5 font-bold text-slate-700"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td class="px-6 py-5 font-black text-slate-800">RM <?php echo number_format($row['grand_total'], 2); ?></td>
                                <td class="px-6 py-5">
                                    <?php if(strtolower($row['booking_status']) == 'completed'): ?>
                                        <span class="px-3 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-lg text-[9px] font-black uppercase tracking-widest shadow-sm"><i class="fas fa-flag-checkered mr-1"></i> Completed</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1.5 bg-red-50 text-red-500 border border-red-100 rounded-lg text-[9px] font-black uppercase tracking-widest shadow-sm"><i class="fas fa-ban mr-1"></i> Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="viewBooking(<?php echo $row['id']; ?>)" class="w-9 h-9 bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl transition-all shadow-sm" title="View Details"><i class="fas fa-eye"></i></button>
                                        <?php if(strtolower($row['booking_status']) == 'completed'): ?>
                                            <a href="generate_receipt.php?id=<?php echo $row['id']; ?>" target="_blank" class="w-9 h-9 flex items-center justify-center bg-white border border-slate-200 text-purple-600 hover:bg-purple-50 hover:border-purple-200 rounded-xl transition-all shadow-sm" title="Download Receipt"><i class="fas fa-file-pdf"></i></a>
                                        <?php endif; ?>
                                        
                                        <button onclick="viewKYC(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['ic_document_path'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['license_document_path'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['customer_name'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['license_number'] ?? 'N/A', ENT_QUOTES); ?>', 'view', '<?php echo strtolower($row['booking_status']); ?>', '<?php echo htmlspecialchars(str_replace(["\r", "\n"], ' ', $row['admin_notes'] ?? ''), ENT_QUOTES); ?>')" 
                                                class="w-9 h-9 bg-white border border-slate-200 <?php echo strtolower($row['booking_status']) == 'completed' ? 'text-emerald-500' : 'text-slate-400'; ?> hover:bg-slate-50 rounded-xl transition-all shadow-sm" title="View Identity Record">
                                            <i class="fas <?php echo strtolower($row['booking_status']) == 'completed' ? 'fa-check-circle' : 'fa-id-badge'; ?>"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-slate-400 font-bold py-10">No history records.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="bookingModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="glass-card w-full max-w-4xl rounded-[2rem] p-8 shadow-2xl relative flex flex-col max-h-[90vh]">
            <button onclick="closeModal()" class="absolute top-6 right-6 text-slate-400 hover:text-slate-800 text-2xl transition-colors"><i class="fas fa-times"></i></button>
            <div id="modalBody" class="overflow-y-auto custom-scrollbar pr-4"></div>
        </div>
    </div>

    <script>
        document.getElementById('bookingSearch').addEventListener('keyup', function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll('.data-table tbody tr').forEach(row => { row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none'; });
        });

        const modal = document.getElementById('bookingModal');
        const body = document.getElementById('modalBody');

        function closeModal() { modal.classList.add('hidden'); }
        window.onclick = function(e) { if(e.target == modal) closeModal(); }

        // --- 新增 hasConflict 参数的 viewKYC ---
        function viewKYC(bookingId, icPath, licensePath, customerName, licenseNo, mode, status = '', adminNotes = '', hasConflict = false) {
            modal.classList.remove('hidden');
            let icHtml = (icPath && icPath !== 'null' && icPath !== '') ? `<img src="../${icPath}" class="w-full h-full object-cover rounded-xl shadow-sm hover:scale-[1.02] transition-transform">` : '<div class="text-slate-300 font-black uppercase text-xs">No File</div>';
            let licenseHtml = (licensePath && licensePath !== 'null' && licensePath !== '') ? `<img src="../${licensePath}" class="w-full h-full object-cover rounded-xl shadow-sm hover:scale-[1.02] transition-transform">` : '<div class="text-slate-300 font-black uppercase text-xs">No File</div>';

            let isPassed = (status === 'confirmed' || status === 'active' || status === 'completed');
            let chkAttr = '';
            
            if (mode === 'view') {
                if (isPassed) {
                    chkAttr = 'checked disabled class="w-4 h-4 text-emerald-500 rounded border-emerald-300 bg-emerald-100 cursor-not-allowed opacity-100"';
                } else {
                    chkAttr = 'disabled class="w-4 h-4 text-slate-300 rounded border-slate-200 bg-slate-50 cursor-not-allowed opacity-50"';
                }
            } else {
                chkAttr = 'class="w-4 h-4 text-emerald-500 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer group-hover:ring-2 group-hover:ring-emerald-200 transition-all"';
            }

            // ========== 核心防呆机制：冲突硬拦截 ==========
            let approveBtn = '';
            let conflictWarning = '';
            if (hasConflict) {
                // 如果有冲突，按钮变灰且被禁用 (Disabled)
                approveBtn = `<button type="button" disabled class="px-8 py-3 bg-slate-200 text-slate-400 rounded-xl font-black text-xs uppercase tracking-widest cursor-not-allowed shadow-inner"><i class="fas fa-lock mr-2"></i>Locked: Date Conflict</button>`;
                // 显示红色严重警告横幅
                conflictWarning = `
                    <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl shadow-inner relative z-20 flex items-start gap-3">
                        <i class="fas fa-radiation text-red-500 text-xl animate-pulse"></i>
                        <div>
                            <p class="text-[10px] font-black text-red-500 uppercase tracking-widest mb-0.5">CRITICAL CONFLICT DETECTED</p>
                            <p class="text-xs font-bold text-red-700">The selected dates for this vehicle overlap with an existing confirmed reservation. Approval is physically locked. Please reject or contact customer.</p>
                        </div>
                    </div>`;
            } else {
                approveBtn = `<a href="?action=Confirmed&id=${bookingId}" onclick="return confirm('Ensure the uploaded documents MATCH the registered details. Approve?')" class="px-8 py-3 bg-emerald-500 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-emerald-500/30 hover:bg-emerald-600 transition-all">Approve Booking</a>`;
            }
            // ==============================================

            let actionButtons = `
                <form action="manage_bookings.php?action=Rejected&id=${bookingId}" method="POST" class="w-full flex gap-3">
                    <div class="flex-1">
                        <select onchange="this.form.admin_notes.value=this.value" class="w-full mb-2 px-4 py-3 bg-white border border-red-200 rounded-xl text-xs font-bold text-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 shadow-sm">
                            <option value="">Choose rejection reason...</option>
                            <option value="IC photo is unclear. Please re-upload a clearer IC photo.">IC photo unclear</option>
                            <option value="Driving license photo is unclear. Please re-upload a clearer license photo.">License photo unclear</option>
                            <option value="Name or document details do not match the booking profile.">Details do not match</option>
                            <option value="Document is incomplete or cropped. Please upload the full document.">Incomplete document</option>
                        </select>
                        <input type="text" name="admin_notes" placeholder="Rejection reason (e.g., Blurred IC)..." class="w-full px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-xs font-bold text-red-700 placeholder-red-300 focus:outline-none focus:ring-2 focus:ring-red-400 shadow-inner">
                    </div>
                    <button type="submit" onclick="return confirm('Reject booking with this reason?')" class="px-6 py-3 bg-white border border-red-200 text-red-600 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-red-50 transition-all shadow-sm">Reject / Ask Re-upload</button>
                    ${approveBtn}
                </form>
            `;

            if (mode === 'view') {
                actionButtons = `<div class="flex flex-col items-center justify-center w-full"><div class="px-6 py-2 bg-emerald-50 text-emerald-600 rounded-full font-black text-sm uppercase tracking-widest mb-4 border border-emerald-200 shadow-sm"><i class="fas fa-archive mr-2"></i> Document Archive (View Only)</div><button onclick="closeModal()" class="px-10 py-4 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-slate-900/20 hover:bg-slate-800 transition-all hover:-translate-y-1">Close View</button></div>`;
            }

            let rejectHtml = '';
            if (mode === 'view' && status === 'cancelled' && adminNotes.trim() !== '') {
                rejectHtml = `
                    <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl shadow-inner relative z-20">
                        <p class="text-[9px] font-black text-red-400 uppercase tracking-widest mb-1"><i class="fas fa-exclamation-circle"></i> Rejection Reason</p>
                        <p class="text-sm font-bold text-red-700">${adminNotes}</p>
                    </div>
                `;
            }

            let checklistOverlay = (mode === 'view' && status === 'cancelled') ? '<div class="absolute inset-0 bg-white/40 backdrop-blur-[1px] rounded-xl z-10 pointer-events-none"></div>' : '';

            body.innerHTML = `
                <div class="p-2 text-center">
                    <h3 class="text-2xl font-black text-slate-800 mb-6 flex items-center justify-center gap-3"><i class="fas fa-shield-alt text-amber-500"></i> Identity Verification</h3>
                    
                    <div class="grid grid-cols-3 gap-6 mb-6 text-left">
                        <div class="col-span-2 grid grid-cols-2 gap-4">
                            <div class="space-y-2"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Customer NRIC</p><div class="aspect-[4/3] bg-slate-50 rounded-xl border border-slate-200 p-1 flex items-center justify-center">${icHtml}</div></div>
                            <div class="space-y-2"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Driving License</p><div class="aspect-[4/3] bg-slate-50 rounded-xl border border-slate-200 p-1 flex items-center justify-center">${licenseHtml}</div></div>
                        </div>
                        
                        <div class="col-span-1 bg-slate-50 border border-slate-200 rounded-xl p-5 flex flex-col shadow-sm relative">
                            ${checklistOverlay}
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 relative z-20">Verification Checklist</p>
                            <div class="space-y-4 flex-1 relative z-20">
                                <label class="flex items-center gap-3 ${mode === 'view' ? '' : 'group'}"><input type="checkbox" ${chkAttr}><span class="text-xs font-bold text-slate-600 ${mode === 'view' ? '' : 'group-hover:text-slate-900'} transition-colors">Name Matches DB (${customerName})</span></label>
                                <label class="flex items-center gap-3 ${mode === 'view' ? '' : 'group'}"><input type="checkbox" ${chkAttr}><span class="text-xs font-bold text-slate-600 ${mode === 'view' ? '' : 'group-hover:text-slate-900'} transition-colors">License #: ${licenseNo}</span></label>
                                <label class="flex items-center gap-3 ${mode === 'view' ? '' : 'group'}"><input type="checkbox" ${chkAttr}><span class="text-xs font-bold text-slate-600 ${mode === 'view' ? '' : 'group-hover:text-slate-900'} transition-colors">Images are Clear & Legible</span></label>
                                <label class="flex items-center gap-3 ${mode === 'view' ? '' : 'group'}"><input type="checkbox" ${chkAttr}><span class="text-xs font-bold text-slate-600 ${mode === 'view' ? '' : 'group-hover:text-slate-900'} transition-colors">No Fraud Risk Detected</span></label>
                            </div>
                            ${rejectHtml}
                            ${conflictWarning} </div>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-slate-100 flex justify-center gap-4">
                        ${actionButtons}
                    </div>
                </div>`;
        }

        async function viewBooking(id) {
            modal.classList.remove('hidden');
            body.innerHTML = '<div class="text-center py-10"><i class="fas fa-circle-notch fa-spin text-4xl text-primary"></i></div>';
            try {
                const res = await fetch(`get_booking_details.php?id=${id}`);
                const data = await res.json();
                if (data.error) { body.innerHTML = `<div class="p-4 bg-red-50 text-red-600 rounded-2xl font-bold">${data.error}</div>`; return; }
                const b = data.booking;
                let itemsHtml = '';
                data.items.forEach(item => {
                    const start = new Date(item.start_datetime).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute:'2-digit' });
                    const end = new Date(item.end_datetime).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute:'2-digit' });
                    itemsHtml += `<div class="bg-white border border-slate-100 rounded-2xl p-5 mb-3 shadow-sm"><div class="flex justify-between items-start"><div><div class="font-black text-primary text-lg"><i class="fas fa-car mr-2"></i>${item.brand} ${item.car_name}</div><div class="text-xs font-bold text-slate-500 mt-1">${start} <i class="fas fa-arrow-right mx-2 text-slate-300"></i> ${end}</div></div><span class="px-3 py-1.5 bg-slate-800 text-white rounded-xl font-black text-sm">RM ${parseFloat(item.subtotal).toFixed(2)}</span></div></div>`;
                });
                body.innerHTML = `
                    <div class="p-2">
                        <h2 class="text-2xl font-black text-slate-800 mb-6 flex items-center"><i class="fas fa-file-invoice text-primary mr-3"></i> Booking Details</h2>
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100 shadow-sm"><h6 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Customer Info</h6><div class="font-bold text-slate-700 mb-1"><i class="fas fa-user-circle w-5 text-slate-400"></i> ${b.customer_name}</div><div class="font-bold text-slate-700 mb-1"><i class="fas fa-phone w-5 text-slate-400"></i> ${b.customer_phone}</div><div class="font-bold text-slate-700"><i class="fas fa-envelope w-5 text-slate-400"></i> ${b.customer_email}</div></div>
                            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100 shadow-sm"><h6 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Reference</h6><div class="text-xl font-black text-primary mb-2">#${b.booking_reference}</div><div class="font-bold text-slate-700 text-sm mb-1"><i class="fas fa-credit-card w-5 text-slate-400"></i> ${b.payment_method.toUpperCase()}</div><div class="font-bold text-slate-700 text-sm"><i class="fas fa-clock w-5 text-slate-400"></i> ${new Date(b.created_at).toLocaleDateString()}</div></div>
                        </div>
                        <h6 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Rental Items</h6>${itemsHtml}
                        <div class="bg-slate-50 border border-slate-100 p-6 rounded-2xl mt-6 shadow-sm"><div class="flex justify-between font-bold text-slate-500 mb-2"><span>Subtotal</span><span>RM ${parseFloat(b.total_amount).toFixed(2)}</span></div><div class="flex justify-between font-bold text-slate-500 mb-4"><span>Tax & Fees</span><span>RM ${(parseFloat(b.tax_amount) + parseFloat(b.service_fee)).toFixed(2)}</span></div><div class="flex justify-between items-center pt-4 border-t border-slate-200"><span class="font-black text-slate-800 uppercase tracking-widest">Grand Total</span><span class="text-2xl font-black text-primary">RM ${parseFloat(b.grand_total).toFixed(2)}</span></div></div>
                        <div class="mt-6 text-center"><button type="button" onclick="closeModal()" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/20">Close Details</button></div>
                    </div>`;
            } catch (err) { console.error(err); body.innerHTML = '<div class="p-4 bg-red-50 text-red-600 rounded-2xl font-bold">Failed to load details.</div>'; }
        }
    </script>
</body>
</html>
