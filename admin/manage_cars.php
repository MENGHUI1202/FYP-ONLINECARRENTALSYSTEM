<?php
include('../includes/config.php');
include('../includes/auth.php');
if(!isset($_SESSION['admin_logged_in'])) { header("Location: index.php"); exit; }

// 【完美抓取管理员名字】如果你还有其他的 session key，加在前面即可
$current_admin = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin ID: ' . ($_SESSION['admin_id'] ?? 'Unknown');

// --- 1. 核心功能逻辑与安全加固 ---

if (($_SERVER['REQUEST_METHOD'] ?? '') == 'POST') {
    if (($_SESSION['role'] ?? '') !== 'super_admin') {
        die("Access Denied: You do not have permission to modify data.");
    }

    // 【安全升级】POST 模式的删除操作
    if (isset($_POST['delete_car'])) {
        $id = intval($_POST['delete_id']);
        
        // 审计日志前置：先查出车名以便记录日志
        $stmt_find = $conn->prepare("SELECT car_name, brand FROM cars WHERE id = ?");
        $stmt_find->bind_param("i", $id);
        $stmt_find->execute();
        $car_info = $stmt_find->get_result()->fetch_assoc();
        $full_car_name = ($car_info['brand'] ?? '') . ' ' . ($car_info['car_name'] ?? 'Unknown');

        // 软删除：把车辆状态改为隐藏，不删除图片，防止历史报表崩盘
        $stmt_del_car = $conn->prepare("UPDATE cars SET is_deleted = 1, availability = 0, stock_quantity = 0 WHERE id = ?");
        $stmt_del_car->bind_param("i", $id);
        $stmt_del_car->execute();
        $stmt_del_units = $conn->prepare("UPDATE car_units SET current_status = 'inactive', reserved_booking_id = NULL WHERE car_id = ?");
        if ($stmt_del_units) {
            $stmt_del_units->bind_param("i", $id);
            $stmt_del_units->execute();
            $stmt_del_units->close();
        }
        
        // 【大招 1】写入审计日志
        $log_stmt = $conn->prepare("INSERT INTO audit_logs (admin_name, action_type, car_model, details) VALUES (?, 'DELETE', ?, 'Permanently removed vehicle asset from inventory')");
        $log_stmt->bind_param("ss", $current_admin, $full_car_name);
        $log_stmt->execute();

        header("Location: manage_cars.php?msg=deleted"); exit;
    }

    // 处理新增与编辑的变量
    $car_name = trim($_POST['car_name'] ?? ''); 
    $brand = $_POST['brand'] ?? '';
    $type = $_POST['type'] ?? ''; 
    $price_day = floatval($_POST['price_per_day'] ?? 0); 
    $price_hour = 0.00;
    $trans = $_POST['transmission'] ?? '';
    $seats = intval($_POST['seats'] ?? 5);
    $desc = trim($_POST['description'] ?? ''); 
    $specs = trim($_POST['specification'] ?? '');
    $stock = intval($_POST['stock_quantity'] ?? 1);
    
    // 图片上传
    $uploaded_images = [];
    if (!empty($_FILES['car_images']['name'][0])) {
        $target_dir = "../assets/uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        foreach($_FILES['car_images']['name'] as $key => $val){
            if ($_FILES['car_images']['error'][$key] == 0) {
                $ext = pathinfo($_FILES['car_images']['name'][$key], PATHINFO_EXTENSION);
                $target_file = $target_dir . uniqid('car_', true) . "." . $ext;
                if(move_uploaded_file($_FILES['car_images']['tmp_name'][$key], $target_file)){
                    $uploaded_images[] = $target_file; 
                }
            }
        }
    }

    if (isset($_POST['add_car'])) {
        $stmt = $conn->prepare("INSERT INTO cars (car_name, brand, type, price_per_day, price_per_hour, transmission, seats, description, specification, stock_quantity) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssddsissi", $car_name, $brand, $type, $price_day, $price_hour, $trans, $seats, $desc, $specs, $stock);
        
        if($stmt->execute()){
            $new_car_id = $conn->insert_id; 
            $safe_brand = $conn->real_escape_string($brand);
            $conn->query("UPDATE cars SET id = car_id, brand = '$safe_brand', image_url = COALESCE(image_url, main_image) WHERE car_id = $new_car_id");
            if(count($uploaded_images) > 0){
                $stmt_img = $conn->prepare("INSERT INTO car_images (car_id, image_url) VALUES (?, ?)");
                foreach($uploaded_images as $img_url){
                    $stmt_img->bind_param("is", $new_car_id, $img_url);
                    $stmt_img->execute();
                    $conn->query("UPDATE car_images SET id = image_id WHERE image_id = " . intval($conn->insert_id));
                }
            }
            // 【大招 1】写入审计日志
            $full_name = $brand . ' ' . $car_name;
            $details = "Created new vehicle fleet. Init stock: $stock, Price/Day: RM$price_day";
            $log_stmt = $conn->prepare("INSERT INTO audit_logs (admin_name, action_type, car_model, details) VALUES (?, 'CREATE', ?, ?)");
            $log_stmt->bind_param("sss", $current_admin, $full_name, $details);
            $log_stmt->execute();
        }
        header("Location: manage_cars.php?msg=added"); exit;

    } elseif (isset($_POST['edit_car'])) {
        $id = intval($_POST['car_id']);
        
        // 【高级大招：Data Diffing】1. 先拉取修改前的旧数据
        $stmt_old = $conn->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt_old->bind_param("i", $id);
        $stmt_old->execute();
        $old_car = $stmt_old->get_result()->fetch_assoc();

        // 2. 执行真实的 UPDATE 覆盖数据
        $stmt = $conn->prepare("UPDATE cars SET car_name=?, brand=?, type=?, price_per_day=?, price_per_hour=?, transmission=?, seats=?, description=?, specification=?, stock_quantity=? WHERE id=?");
        $stmt->bind_param("sssddsissii", $car_name, $brand, $type, $price_day, $price_hour, $trans, $seats, $desc, $specs, $stock, $id);
        
        if($stmt->execute()) {
            if (count($uploaded_images) > 0) {
                $stmt_del = $conn->prepare("DELETE FROM car_images WHERE car_id = ?");
                $stmt_del->bind_param("i", $id);
                $stmt_del->execute();

                $stmt_img = $conn->prepare("INSERT INTO car_images (car_id, image_url) VALUES (?, ?)");
                foreach($uploaded_images as $img_url){
                    $stmt_img->bind_param("is", $id, $img_url);
                    $stmt_img->execute();
                }
            }
            
            // 【高级大招：Data Diffing】3. 智能对比旧数据和新数据，生成变动日志
            $changes = [];
            if ($old_car['car_name'] != $car_name) $changes[] = "Model [{$old_car['car_name']} ➔ {$car_name}]";
            if ($old_car['brand'] != $brand) $changes[] = "Brand [{$old_car['brand']} ➔ {$brand}]";
            if ($old_car['price_per_day'] != $price_day) $changes[] = "Price/Day [RM{$old_car['price_per_day']} ➔ RM{$price_day}]";
            if ($old_car['stock_quantity'] != $stock) $changes[] = "Stock [{$old_car['stock_quantity']} ➔ {$stock}]";
            if (count($uploaded_images) > 0) $changes[] = "Uploaded new images";
            
            // 如果只有一些没那么重要的数据改了（比如 description），或者根本没改
            $details = empty($changes) ? "Updated record details without major spec changes." : "Modified: " . implode(", ", $changes);

            // 4. 写入审计日志
            $full_name = $brand . ' ' . $car_name;
            $log_stmt = $conn->prepare("INSERT INTO audit_logs (admin_name, action_type, car_model, details) VALUES (?, 'UPDATE', ?, ?)");
            $log_stmt->bind_param("sss", $current_admin, $full_name, $details);
            $log_stmt->execute();
        }
        header("Location: manage_cars.php?msg=updated"); exit;
    }
}

// 基础数据查询
$types_query = $conn->query("SELECT name as type FROM vehicle_categories ORDER BY name ASC");
$brands_query = $conn->query("SELECT brand_name FROM brands ORDER BY brand_name ASC");
$all_brands = [];
if ($brands_query) { while($b = $brands_query->fetch_assoc()) { $all_brands[] = $b['brand_name']; } }

// 统计 Summary Bar
$stat_total = $conn->query("SELECT COUNT(*) as c FROM cars WHERE is_deleted = 0")->fetch_assoc()['c'] ?? 0;
$stat_avail = $conn->query("SELECT COUNT(*) as c FROM cars WHERE is_deleted = 0 AND stock_quantity > 0")->fetch_assoc()['c'] ?? 0;
$stat_out = $conn->query("SELECT COUNT(*) as c FROM cars WHERE is_deleted = 0 AND stock_quantity = 0")->fetch_assoc()['c'] ?? 0;
$stat_cats = $conn->query("SELECT COUNT(DISTINCT type) as c FROM cars WHERE is_deleted = 0")->fetch_assoc()['c'] ?? 0;

// 拉取最新的 15 条审计日志备用
$logs_res = $conn->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 15");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fleet | Fleet Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }, colors: { primary: '#3b82f6' } } } }
    </script>
    <style>
        body { background: radial-gradient(circle at top right, #e0e7ff 0%, #f8fafc 40%, #f1f5f9 100%); }
        .glass-card { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 1); box-shadow: 0 10px 40px -10px rgba(226, 232, 240, 0.8); }
        .car-image-container:hover img { transform: scale(1.05); }
        /* 日历弹窗内部样式微调，强制变成高级浅色调 */
        .fc { font-family: 'Plus Jakarta Sans', sans-serif; }
        .fc .fc-toolbar-title { font-size: 1.25rem; font-weight: 900; color: #1e293b; text-transform: uppercase; letter-spacing: 0.05em; }
        .fc .fc-button-primary { background-color: #0f172a; border: none; font-weight: bold; text-transform: uppercase; font-size: 11px; tracking: 0.1em; border-radius: 8px; }
        .fc .fc-button-primary:hover { background-color: #1e293b; }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include('include/sidebar.php'); ?>
    
    <main class="ml-64 p-10 w-full max-w-[1600px] mx-auto">
        <header class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Fleet Inventory</h1>
                <p class="text-slate-500 mt-1 font-medium">Manage and monitor vehicle stock levels.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="openLogModal()" class="px-5 py-3 bg-white border border-slate-200 text-slate-700 rounded-xl font-bold shadow-sm hover:bg-slate-50 active:scale-[0.98] transition-all flex items-center gap-2 text-xs uppercase tracking-wider">
                    <i class="fas fa-history text-slate-400"></i> Audit Logs
                </button>
                <?php if(($_SESSION['role'] ?? '') == 'super_admin'): ?>
                <button onclick="openAddModal()" class="px-6 py-3 bg-slate-900 text-white rounded-xl font-bold shadow-xl shadow-slate-900/20 hover:bg-slate-800 active:scale-[0.98] transition-all flex items-center gap-2 uppercase tracking-widest text-xs">
                    <i class="fas fa-plus"></i> Add Vehicle
                </button>
                <?php endif; ?>
            </div>
        </header>

        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-4 border border-slate-100 flex items-center justify-between shadow-sm">
                <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Fleet</p><h4 class="text-2xl font-black text-slate-800"><?php echo $stat_total; ?></h4></div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center"><i class="fas fa-car-side"></i></div>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-slate-100 flex items-center justify-between shadow-sm">
                <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Available Units</p><h4 class="text-2xl font-black text-emerald-500"><?php echo $stat_avail; ?></h4></div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-slate-100 flex items-center justify-between shadow-sm">
                <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Out of Stock</p><h4 class="text-2xl font-black text-red-500"><?php echo $stat_out; ?></h4></div>
                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center"><i class="fas fa-times-circle"></i></div>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-slate-100 flex items-center justify-between shadow-sm">
                <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Active Categories</p><h4 class="text-2xl font-black text-indigo-500"><?php echo $stat_cats; ?></h4></div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center"><i class="fas fa-tags"></i></div>
            </div>
        </div>

        <div class="glass-card p-3 rounded-2xl mb-8 flex flex-wrap gap-4 items-center border border-slate-200">
            <div class="relative flex-1 min-w-[300px]">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="carSearch" placeholder="Search models or brands..." class="w-full pl-11 pr-5 py-2.5 bg-slate-50 border border-slate-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-primary transition-all font-bold text-sm">
            </div>
            <select id="catFilter" class="px-5 py-2.5 bg-slate-50 border border-slate-100 rounded-xl font-bold outline-none cursor-pointer text-sm">
                <option value="">All Body Types</option>
                <?php 
                if($types_query->num_rows > 0) {
                    $types_query->data_seek(0); 
                    while($t = $types_query->fetch_assoc()): 
                ?>
                    <option value="<?php echo strtolower($t['type']); ?>"><?php echo $t['type']; ?></option>
                <?php endwhile; } ?>
            </select>
            <select id="brandFilter" class="px-5 py-2.5 bg-slate-50 border border-slate-100 rounded-xl font-bold outline-none cursor-pointer text-sm">
                <option value="">All Brands</option>
                <?php foreach($all_brands as $brand_name): ?>
                    <option value="<?php echo htmlspecialchars(strtolower($brand_name), ENT_QUOTES); ?>"><?php echo htmlspecialchars($brand_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="carGrid">
            <?php 
            $cars = $conn->query("SELECT * FROM cars WHERE is_deleted = 0 ORDER BY id DESC");
            while($car = $cars->fetch_assoc()):
                $stock = isset($car['stock_quantity']) ? intval($car['stock_quantity']) : 1; 
                $car_id = $car['id'];
                
                $img_query = $conn->query("SELECT image_url FROM car_images WHERE car_id = $car_id LIMIT 1");
                $img = '';
                if($img_row = $img_query->fetch_assoc()){
                    $img = $img_row['image_url'];
                    if(strpos($img, 'http') !== 0 && strpos($img, 'car_image/') === false && strpos($img, '../') === false) $img = 'car_image/' . $img; 
                }
            ?>
            <div class="car-item bg-white border border-slate-100 group rounded-3xl overflow-hidden flex flex-col hover:shadow-xl transition-all" data-title="<?php echo htmlspecialchars(strtolower($car['car_name'] . ' ' . $car['brand']), ENT_QUOTES); ?>" data-category="<?php echo htmlspecialchars(strtolower($car['type']), ENT_QUOTES); ?>" data-brand="<?php echo htmlspecialchars(strtolower($car['brand']), ENT_QUOTES); ?>">
                
                <div class="relative car-image-container h-40 overflow-hidden bg-slate-50 flex items-center justify-center border-b border-slate-100">
                    <?php if($img): ?>
                        <img src="<?php echo $img; ?>" class="w-full h-full object-cover transition-transform duration-700">
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center text-slate-300">
                            <i class="fas fa-car-side text-4xl mb-2 opacity-50"></i>
                            <span class="text-[9px] font-black uppercase tracking-widest">No Cover Image</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="absolute top-3 right-3">
                        <?php if($stock > 2): ?>
                            <span class="px-3 py-1.5 backdrop-blur-xl bg-emerald-500/90 text-white text-[9px] font-black uppercase tracking-widest rounded-lg shadow-sm border border-emerald-400">IN STOCK (<?php echo $stock; ?>)</span>
                        <?php elseif($stock > 0 && $stock <= 2): ?>
                            <span class="px-3 py-1.5 backdrop-blur-xl bg-amber-500/90 text-white text-[9px] font-black uppercase tracking-widest rounded-lg shadow-sm border border-amber-400 animate-pulse">LOW STOCK (<?php echo $stock; ?>)</span>
                        <?php else: ?>
                            <span class="px-3 py-1.5 backdrop-blur-xl bg-red-500/90 text-white text-[9px] font-black uppercase tracking-widest rounded-lg shadow-sm border border-red-400">OUT OF STOCK</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="p-5 flex-1 flex flex-col">
                    <div class="mb-3">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.1em] mb-0.5"><?php echo htmlspecialchars($car['brand']); ?></p>
                        <h3 class="text-lg font-black text-slate-800 leading-tight"><?php echo htmlspecialchars($car['car_name']); ?></h3>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-1.5 mb-4">
                        <span class="px-2 py-1 bg-slate-50 text-slate-500 rounded text-[9px] font-bold uppercase tracking-tight border border-slate-100"><i class="fas fa-tag mr-1 opacity-60"></i><?php echo htmlspecialchars($car['type']); ?></span>
                        <span class="px-2 py-1 bg-slate-50 text-slate-500 rounded text-[9px] font-bold uppercase tracking-tight border border-slate-100"><i class="fas fa-cogs mr-1 opacity-60"></i><?php echo htmlspecialchars($car['transmission']); ?></span>
                        <span class="px-2 py-1 bg-slate-50 text-slate-500 rounded text-[9px] font-bold uppercase tracking-tight border border-slate-100"><i class="fas fa-chair mr-1 opacity-60"></i><?php echo htmlspecialchars($car['seats']); ?> Pax</span>
                    </div>
                    
                    <div class="mt-auto pt-4 border-t border-slate-100 border-dashed flex justify-between items-end">
                        <div>
                            <h4 class="text-xl font-black text-slate-900 leading-none">RM<?php echo number_format($car['price_per_day'], 0); ?><span class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">/ Day</span></h4>
                            <p class="text-[10px] font-bold text-slate-400 mt-1">Daily rental only</p>
                        </div>
                        <div class="flex gap-1.5">
                            <button onclick="openCalendarModal(<?php echo $car['id']; ?>, '<?php echo addslashes($car['brand'] . ' ' . $car['car_name']); ?>')" class="w-8 h-8 bg-slate-50 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 border border-slate-100 rounded-lg transition-all shadow-sm" title="View Availability Schedule"><i class="fas fa-calendar-alt text-xs"></i></button>

                            <?php if(($_SESSION['role'] ?? '') == 'super_admin'): ?>
                                <button onclick='openEditModal(<?php echo json_encode($car); ?>)' class="w-8 h-8 bg-slate-50 text-slate-500 hover:bg-primary hover:text-white border border-slate-100 rounded-lg transition-all shadow-sm" title="Edit Data"><i class="fas fa-pen text-xs"></i></button>
                                <form action="" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this vehicle entirely?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $car['id']; ?>">
                                    <button type="submit" name="delete_car" class="w-8 h-8 flex items-center justify-center bg-red-50 text-red-500 hover:bg-red-500 hover:text-white border border-red-100 rounded-lg transition-all shadow-sm" title="Delete Model"><i class="fas fa-trash-alt text-xs"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <div id="carModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-2xl rounded-3xl p-8 shadow-2xl my-8 relative border border-slate-100">
            <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                <h3 id="modalTitle" class="text-2xl font-black text-slate-800">Add Vehicle</h3>
                <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-red-500 text-2xl transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="mb-5 p-4 bg-blue-50 border border-blue-100 rounded-2xl text-xs font-bold text-blue-700 leading-relaxed">
                <i class="fas fa-circle-info mr-2"></i> Update vehicle identity, daily rental price, stock, images, category, transmission, seats, description and specifications here. Hourly rental pricing is no longer used.
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="car_id" id="car_id">
                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Brand</label>
                        <select name="brand" id="brand" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 rounded-xl outline-none font-bold cursor-pointer text-sm" required>
                            <option value="" disabled selected>Select Brand...</option>
                            <?php foreach($all_brands as $brand_name): ?>
                                <option value="<?php echo htmlspecialchars($brand_name); ?>"><?php echo htmlspecialchars($brand_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Model Name</label>
                        <input type="text" name="car_name" id="car_name" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 rounded-xl outline-none font-bold text-sm" required>
                    </div>
                </div>

                <div class="bg-slate-50 border border-slate-200 border-dashed rounded-xl p-4">
                    <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-2">Upload Vehicle Images</label>
                    <input type="file" name="car_images[]" class="w-full text-sm font-bold file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer" multiple accept=".jpg,.jpeg,.png,.webp">
                    <p id="imageHint" class="text-[10px] font-bold text-slate-400 mt-2"><i class="fas fa-info-circle mr-1"></i> For best display, use 4:3 ratio images. JPG/PNG only.</p>
                </div>

                <div class="grid grid-cols-2 gap-5">
                     <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Price / Day (RM)</label>
                        <input type="number" name="price_per_day" id="price_per_day" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 rounded-xl outline-none font-bold text-sm" step="0.01" required>
                        <p class="text-[10px] font-bold text-slate-400 mt-2 ml-1">Shown to customers and used for booking calculation.</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" min="0" value="1" required class="w-full px-5 py-3 bg-indigo-50/50 border border-indigo-100 focus:bg-white focus:border-indigo-400 rounded-xl font-black outline-none text-indigo-700">
                        <p class="text-[10px] font-bold text-slate-400 mt-2 ml-1">Set 0 only when this model should be unavailable.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Body Type</label>
                        <select name="type" id="type" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 rounded-xl font-bold outline-none cursor-pointer text-sm">
                            <?php 
                            if($types_query->num_rows > 0) {
                                $types_query->data_seek(0);
                                while($t = $types_query->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $t['type']; ?>"><?php echo $t['type']; ?></option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Trans</label>
                            <select name="transmission" id="transmission" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 rounded-xl font-bold outline-none cursor-pointer text-sm">
                                <option value="Auto">Auto</option>
                                <option value="Manual">Manual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Pax</label>
                            <input type="number" name="seats" id="seats" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 rounded-xl font-bold outline-none text-sm" value="5">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Description</label>
                    <textarea name="description" id="description" rows="2" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 rounded-xl font-medium outline-none text-sm leading-relaxed"></textarea>
                    <p class="text-[10px] font-bold text-slate-400 mt-2 ml-1">Short customer-facing summary of the vehicle.</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Specifications</label>
                    <textarea name="specification" id="specification" rows="2" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-500 rounded-xl font-medium outline-none text-sm leading-relaxed"></textarea>
                    <p class="text-[10px] font-bold text-slate-400 mt-2 ml-1">Example: engine, luggage capacity, fuel type, safety features.</p>
                </div>
                
                <div class="pt-6 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-slate-200 transition-all">Cancel</button>
                    <button type="submit" name="add_car" id="submitBtn" class="px-8 py-3 bg-slate-900 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-slate-900/20 hover:bg-slate-800 transition-all hover:-translate-y-0.5">Save Data</button>
                </div>
            </form>
        </div>
    </div>

    <div id="logModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-4xl rounded-3xl p-8 shadow-2xl relative max-h-[85vh] flex flex-col">
            <button type="button" onclick="closeLogModal()" class="absolute top-6 right-6 text-slate-400 hover:text-red-500 text-2xl"><i class="fas fa-times"></i></button>
            <h3 class="text-2xl font-black text-slate-800 mb-2 flex items-center gap-2"><i class="fas fa-shield-alt text-blue-500"></i> Operation Audit Logs</h3>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 pb-3">Traceability & System Accountability</p>
            
            <div class="overflow-y-auto flex-1 pr-2 custom-scrollbar">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-400 font-black uppercase tracking-wider rounded-xl">
                        <tr><th class="p-3">Timestamp</th><th class="p-3">Operator</th><th class="p-3">Action</th><th class="p-3">Target Asset</th><th class="p-3">Log Details</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-600">
                        <?php if($logs_res && $logs_res->num_rows > 0): while($l = $logs_res->fetch_assoc()): 
                            $badge = 'bg-slate-100 text-slate-600';
                            if($l['action_type']=='CREATE') $badge='bg-emerald-50 text-emerald-600 border border-emerald-100';
                            if($l['action_type']=='UPDATE') $badge='bg-amber-50 text-amber-600 border border-amber-100';
                            if($l['action_type']=='DELETE') $badge='bg-red-50 text-red-600 border border-red-100';
                        ?>
                        <tr class="hover:bg-slate-50/50">
                            <td class="p-3 font-bold text-slate-400"><?php echo date('d M, H:i', strtotime($l['created_at'])); ?></td>
                            <td class="p-3 font-black text-slate-700"><?php echo htmlspecialchars($l['admin_name']); ?></td>
                            <td class="p-3"><span class="px-2 py-0.5 rounded text-[10px] font-black tracking-wide <?php echo $badge; ?>"><?php echo $l['action_type']; ?></span></td>
                            <td class="p-3 font-bold text-primary"><?php echo htmlspecialchars($l['car_model']); ?></td>
                            <td class="p-3 text-slate-500"><?php echo htmlspecialchars($l['details']); ?></td>
                        </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center py-8 text-slate-400 font-bold">No logs recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="calendarModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-4xl rounded-3xl p-8 shadow-2xl relative flex flex-col h-[80vh]">
            <button type="button" onclick="closeCalendarModal()" class="absolute top-6 right-6 text-slate-400 hover:text-red-500 text-2xl z-10"><i class="fas fa-times"></i></button>
            <h3 class="text-2xl font-black text-slate-800 mb-1 flex items-center gap-2"><i class="fas fa-calendar-alt text-emerald-500"></i> Availability Schedule</h3>
            <p id="calendarCarTitle" class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 pb-3">Loading unit timeline...</p>
            
            <div id="calendar" class="flex-1 bg-slate-50/50 p-4 rounded-2xl border border-slate-100 overflow-hidden shadow-inner"></div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('carModal');
        const form = document.querySelector('form');
        const submitBtn = document.getElementById('submitBtn');
        const imgHint = document.getElementById('imageHint');
        
        let fullCalendarInstance = null; // 全局日历实例

        // --- 审计日志弹窗控制 ---
        function openLogModal() { document.getElementById('logModal').classList.remove('hidden'); }
        function closeLogModal() { document.getElementById('logModal').classList.add('hidden'); }

        // --- 【核心JS大招】智能车辆日历渲染器 ---
        function openCalendarModal(carId, carTitle) {
            document.getElementById('calendarCarTitle').innerHTML = `<i class="fas fa-car mr-1 text-slate-400"></i> Unit: <span class="text-primary">${carTitle}</span>`;
            document.getElementById('calendarModal').classList.remove('hidden');

            const calendarEl = document.getElementById('calendar');
            
            // 如果之前初始化过，先销毁，防止内存泄漏和渲染错位
            if (fullCalendarInstance) { fullCalendarInstance.destroy(); }

            // 重新初始化 FullCalendar
            fullCalendarInstance = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                events: `get_car_calendar.php?car_id=${carId}`, // 异步向我们刚做的接口拿 JSON 数据
                eventDidMount: function(info) {
                    // 当鼠标悬停在被租掉的日期时，弹出精美小悬浮框提示司机名字
                    if(info.event.extendedProps.customer) {
                        info.el.setAttribute('title', `Driver: ${info.event.extendedProps.customer}`);
                    }
                }
            });

            // 重点防踩坑：由于日历在隐藏的 Modal 里，必须在 Modal 显示后延迟 50ms 重新计算宽高
            setTimeout(() => { fullCalendarInstance.render(); }, 50);
        }

        function closeCalendarModal() { document.getElementById('calendarModal').classList.add('hidden'); }

        // --- 基础表单弹窗控制 ---
        function openAddModal() {
            form.reset(); 
            document.getElementById('car_id').value = '';
            document.getElementById('specification').value = ''; 
            document.getElementById('stock_quantity').value = 1;
            document.getElementById('modalTitle').innerText = 'Add New Vehicle';
            submitBtn.name = 'add_car'; 
            submitBtn.innerText = 'Publish Vehicle';
            imgHint.innerHTML = '<i class="fas fa-info-circle mr-1"></i> For best display, use 4:3 ratio images. JPG/PNG only.';
            modal.classList.remove('hidden');
        }

        function openEditModal(car) {
            document.getElementById('car_id').value = car.id;
            document.getElementById('brand').value = car.brand;
            document.getElementById('car_name').value = car.car_name;
            document.getElementById('type').value = car.type; 
            document.getElementById('price_per_day').value = car.price_per_day;
            
            let trans = car.transmission;
            if(trans.toLowerCase().includes('auto')) trans = 'Auto';
            if(trans.toLowerCase().includes('man')) trans = 'Manual';
            document.getElementById('transmission').value = trans;
            
            document.getElementById('seats').value = car.seats;
            document.getElementById('stock_quantity').value = car.stock_quantity !== undefined ? car.stock_quantity : 1;
            document.getElementById('description').value = car.description;
            document.getElementById('specification').value = car.specification;

            document.getElementById('modalTitle').innerText = 'Edit Vehicle Data';
            submitBtn.name = 'edit_car'; 
            submitBtn.innerText = 'Update Changes';
            imgHint.innerHTML = '<i class="fas fa-exclamation-circle text-amber-500 mr-1"></i> <b>Leave empty</b> to keep existing images. Uploading new images will replace old ones.';
            modal.classList.remove('hidden');
        }

        function closeModal() { modal.classList.add('hidden'); }
        
        function filterCars() {
            const searchValue = document.getElementById('carSearch').value.toLowerCase();
            const catValue = document.getElementById('catFilter').value.toLowerCase();
            const brandValue = document.getElementById('brandFilter').value.toLowerCase();
            document.querySelectorAll('.car-item').forEach(el => {
                const title = el.getAttribute('data-title');
                const category = el.getAttribute('data-category');
                const brand = el.getAttribute('data-brand');
                el.style.display = (
                    title.includes(searchValue) &&
                    (catValue === '' || category === catValue) &&
                    (brandValue === '' || brand === brandValue)
                ) ? 'flex' : 'none';
            });
        }
        document.getElementById('carSearch').addEventListener('input', filterCars);
        document.getElementById('catFilter').addEventListener('change', filterCars);
        document.getElementById('brandFilter').addEventListener('change', filterCars);

        // 点击背景关闭所有 Modal
        window.onclick = function(e) {
            if (e.target == modal) closeModal();
            if (e.target == document.getElementById('logModal')) closeLogModal();
            if (e.target == document.getElementById('calendarModal')) closeCalendarModal();
        }
    </script>
</body>
</html>
