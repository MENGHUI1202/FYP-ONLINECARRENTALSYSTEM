<?php
include('../includes/config.php');
include('../includes/auth.php');
if(!isset($_SESSION['admin_logged_in'])) { header("Location: index.php"); exit; }

// ★★★ 智能数据库升级：自动添加 Toyota 标签 ★★★
$check_cols = $conn->query("SHOW COLUMNS FROM cars LIKE 'is_tss'");
if($check_cols->num_rows == 0) {
    $conn->query("ALTER TABLE cars 
        ADD COLUMN is_tss TINYINT(1) DEFAULT 0, 
        ADD COLUMN is_tnga TINYINT(1) DEFAULT 0, 
        ADD COLUMN is_hybrid TINYINT(1) DEFAULT 0, 
        ADD COLUMN colors VARCHAR(255) DEFAULT ''");
}

// --- 1. 核心功能逻辑 ---

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (($_SESSION['role'] ?? '') !== 'super_admin') {
        die("Access Denied: You do not have permission to modify data.");
    }

    $car_name = $_POST['car_name'] ?? ''; 
    $brand = $_POST['brand'] ?? '';
    $type = $_POST['type'] ?? ''; 
    $price_day = $_POST['price_per_day'] ?? 0; 
    $price_hour = $_POST['price_per_hour'] ?? 0;
    $trans = $_POST['transmission'] ?? '';
    $seats = $_POST['seats'] ?? 5;
    $desc = $_POST['description'] ?? ''; 
    $specs = $_POST['specification'] ?? '';
    $avail = $_POST['availability'] ?? 1;
    $stock = isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : 1;
    
    $is_tss = isset($_POST['is_tss']) ? 1 : 0;
    $is_tnga = isset($_POST['is_tnga']) ? 1 : 0;
    $is_hybrid = isset($_POST['is_hybrid']) ? 1 : 0;
    $colors = $_POST['colors'] ?? '';
    
    $uploaded_images = [];
    if (!empty($_FILES['car_images']['name'][0])) {
        $target_dir = "../assets/uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        foreach($_FILES['car_images']['name'] as $key => $val){
            $file_name = basename($_FILES['car_images']['name'][$key]);
            $target_file = $target_dir . time() . "_" . $file_name;
            if(move_uploaded_file($_FILES['car_images']['tmp_name'][$key], $target_file)){
                $uploaded_images[] = $target_file; 
            }
        }
    }

    if (isset($_POST['add_car'])) {
        $stmt = $conn->prepare("INSERT INTO cars (car_name, brand, type, price_per_day, price_per_hour, transmission, seats, description, specification, availability, stock_quantity, is_tss, is_tnga, is_hybrid, colors) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssddsissiiiiis", $car_name, $brand, $type, $price_day, $price_hour, $trans, $seats, $desc, $specs, $avail, $stock, $is_tss, $is_tnga, $is_hybrid, $colors);
        
        if($stmt->execute()){
            $new_car_id = $conn->insert_id; 
            if(count($uploaded_images) > 0){
                $stmt_img = $conn->prepare("INSERT INTO car_images (car_id, image_url) VALUES (?, ?)");
                foreach($uploaded_images as $img_url){
                    $stmt_img->bind_param("is", $new_car_id, $img_url);
                    $stmt_img->execute();
                }
            }
        }

    } elseif (isset($_POST['edit_car'])) {
        $id = $_POST['car_id'];
        $stmt = $conn->prepare("UPDATE cars SET car_name=?, brand=?, type=?, price_per_day=?, price_per_hour=?, transmission=?, seats=?, description=?, specification=?, availability=?, stock_quantity=?, is_tss=?, is_tnga=?, is_hybrid=?, colors=? WHERE id=?");
        $stmt->bind_param("sssddsissiiiiisi", $car_name, $brand, $type, $price_day, $price_hour, $trans, $seats, $desc, $specs, $avail, $stock, $is_tss, $is_tnga, $is_hybrid, $colors, $id);
        $stmt->execute();
        
        if (count($uploaded_images) > 0) {
            $conn->query("DELETE FROM car_images WHERE car_id = $id");
            $stmt_img = $conn->prepare("INSERT INTO car_images (car_id, image_url) VALUES (?, ?)");
            foreach($uploaded_images as $img_url){
                $stmt_img->bind_param("is", $id, $img_url);
                $stmt_img->execute();
            }
        }
    }
    header("Location: manage_cars.php"); exit;
}

if (isset($_GET['delete'])) {
    if (($_SESSION['role'] ?? '') !== 'super_admin') { die("Access Denied."); }
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM car_images WHERE car_id=$id");
    $conn->query("DELETE FROM cars WHERE id=$id");
    header("Location: manage_cars.php"); exit;
}

$types_query = $conn->query("SELECT name as type FROM vehicle_categories ORDER BY name ASC");

// ★★★ 获取库存 Analytics 数据 ★★★
$total_models = $conn->query("SELECT COUNT(*) FROM cars")->fetch_row()[0];
$total_physical_stock = $conn->query("SELECT SUM(stock_quantity) FROM cars")->fetch_row()[0] ?? 0;
$low_stock_count = $conn->query("SELECT COUNT(*) FROM cars WHERE stock_quantity > 0 AND stock_quantity <= 2")->fetch_row()[0];
$fleet_value = $conn->query("SELECT SUM(stock_quantity * price_per_day) FROM cars")->fetch_row()[0] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enterprise Inventory | Toyota Dealership</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    
    <style>
        /* 深度覆盖 Bootstrap，实现 Toyota 企业级 UI */
        body { background-color: #0b1120; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #f8fafc; }
        a { text-decoration: none; }
        
        .main-content { padding: 24px 32px; margin-left: 260px; }
        
        /* ★★★ Analytics 顶部统计卡片 ★★★ */
        .stat-card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; padding: 20px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); border-color: rgba(255,255,255,0.1); }
        
        /* ★★★ 极简车卡重构 ★★★ */
        .inventory-card { 
            background: #162032; 
            border: 1px solid #1e293b; 
            border-radius: 16px; 
            overflow: hidden; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .inventory-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 0 0 1px #eb0a1e; 
        }

        /* 统一的 16:9 图片容器，带微妙 Zoom 效果 */
        .card-img-wrapper { height: 200px; overflow: hidden; position: relative; background: #0f172a; }
        .card-img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; opacity: 0.9; }
        .inventory-card:hover .card-img-wrapper img { transform: scale(1.05); opacity: 1; }
        
        /* 企业级库存徽章 (Business Logic Badges) */
        .status-badge { position: absolute; top: 12px; left: 12px; z-index: 10; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 1px; backdrop-filter: blur(4px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); }
        .status-instock { background: rgba(5, 150, 105, 0.9); color: #fff; border: 1px solid #047857; }
        .status-low { background: rgba(217, 119, 6, 0.9); color: #fff; border: 1px solid #b45309; }
        .status-out { background: rgba(220, 38, 38, 0.9); color: #fff; border: 1px solid #991b1b; }

        /* 库存进度条 (Inventory Health Bar) */
        .stock-progress { height: 6px; background: #334155; border-radius: 3px; overflow: hidden; margin-top: 8px; }
        .stock-bar { height: 100%; border-radius: 3px; transition: width 0.5s ease; }
        .bar-healthy { background: #10b981; }
        .bar-low { background: #f59e0b; }
        .bar-empty { background: #ef4444; width: 100%; }

        /* 操作面板 (Action Bar) */
        .action-bar { border-top: 1px solid #1e293b; background: rgba(15, 23, 42, 0.5); padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
        .btn-manage { background: #eb0a1e; color: white; border: none; padding: 6px 16px; border-radius: 6px; font-weight: bold; font-size: 12px; transition: background 0.2s; cursor: pointer; }
        .btn-manage:hover { background: #be123c; }
        .btn-details { color: #94a3b8; font-size: 12px; font-weight: bold; transition: color 0.2s; background: none; border: none; }
        .btn-details:hover { color: #fff; cursor: pointer; }

        /* 模态框修正以适应暗黑主题 */
        .modal-content { background: #1e293b; color: #f8fafc; border: 1px solid #334155; }
        .form-control, .form-select { background: #0f172a; border: 1px solid #334155; color: #fff; }
        .form-control:focus, .form-select:focus { background: #0f172a; border-color: #eb0a1e; color: #fff; box-shadow: none; }
        .form-label { color: #cbd5e1; font-weight: 600; font-size: 14px; }
        .tech-box { background: #0f172a; border-color: #334155; }
    </style>
</head>
<body>
    <?php include('include/sidebar.php'); ?>
    
    <div class="main-content">
        
        <div class="flex justify-between items-end mb-6">
            <div>
                <h1 class="text-2xl font-black text-white tracking-wide uppercase flex items-center gap-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Toyota.svg" class="h-5 filter brightness-0 invert opacity-80" alt="logo">
                    Inventory Control
                </h1>
                <p class="text-slate-400 mt-1 text-sm tracking-wide">Manage dealership physical fleet and stock levels.</p>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="stat-card">
                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Total Fleet Models</div>
                <div class="flex items-end justify-between">
                    <div class="text-3xl font-black text-white"><?php echo $total_models; ?></div>
                    <i class="fas fa-car-side text-2xl text-slate-600"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Total Physical Units</div>
                <div class="flex items-end justify-between">
                    <div class="text-3xl font-black text-white"><?php echo $total_physical_stock; ?></div>
                    <i class="fas fa-warehouse text-2xl text-blue-900/50"></i>
                </div>
            </div>
            <div class="stat-card border-l-4 border-l-amber-500">
                <div class="text-[10px] text-amber-500 font-bold uppercase tracking-widest mb-1">Low Stock Warning</div>
                <div class="flex items-end justify-between">
                    <div class="text-3xl font-black text-white"><?php echo $low_stock_count; ?> <span class="text-sm font-normal text-slate-500">models</span></div>
                    <i class="fas fa-exclamation-triangle text-2xl text-amber-500/50"></i>
                </div>
            </div>
            <div class="stat-card border-l-4 border-l-emerald-500">
                <div class="text-[10px] text-emerald-500 font-bold uppercase tracking-widest mb-1">Est. Fleet Value</div>
                <div class="flex items-end justify-between">
                    <div class="text-3xl font-black text-white"><span class="text-lg">RM</span> <?php echo number_format($fleet_value/1000000, 2); ?>M</div>
                    <i class="fas fa-chart-line text-2xl text-emerald-500/50"></i>
                </div>
            </div>
        </div>

        <div class="bg-[#162032] p-4 rounded-xl border border-slate-800 mb-6 flex justify-between items-center shadow-lg">
            <div class="flex gap-4 flex-1 max-w-3xl">
                <div class="bg-[#0f172a] flex items-center px-4 rounded-lg border border-slate-700 flex-1 focus-within:border-red-500 transition">
                    <i class="fas fa-search text-slate-500 mr-2"></i>
                    <input type="text" id="carSearch" class="bg-transparent text-sm text-white w-full py-2 outline-none" placeholder="Search by model, chassis, or type..." onkeyup="filterCars()">
                </div>
                
                <select class="bg-[#0f172a] text-slate-300 text-sm font-bold px-4 py-2 rounded-lg outline-none border border-slate-700 w-48" id="catFilter" onchange="filterCars()">
                    <option value="">All Body Types</option>
                    <?php 
                    if($types_query->num_rows > 0) {
                        $types_query->data_seek(0); 
                        while($t = $types_query->fetch_assoc()): 
                    ?>
                        <option value="<?php echo strtolower($t['type']); ?>"><?php echo $t['type']; ?></option>
                    <?php endwhile; } ?>
                </select>

                <button class="bg-[#0f172a] hover:bg-slate-800 text-slate-300 px-4 py-2 rounded-lg border border-slate-700 text-sm font-bold transition flex items-center gap-2">
                    <i class="fas fa-sliders-h"></i> Filters
                </button>
            </div>

            <?php if(($_SESSION['role'] ?? '') == 'super_admin'): ?>
                <button class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-sm font-bold transition shadow-lg shadow-red-900/50 flex items-center gap-2" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Receive Shipment
                </button>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
            <?php 
                $cars = $conn->query("SELECT * FROM cars ORDER BY id DESC");
                while($car = $cars->fetch_assoc()): 
                    $stock = isset($car['stock_quantity']) ? (int)$car['stock_quantity'] : 0;
                    
                    // 真实的商业库存状态逻辑
                    if ($stock == 0) {
                        $badge_cls = 'status-out'; $status_text = '<i class="fas fa-ban mr-1"></i> Sold Out';
                        $bar_cls = 'bar-empty'; $bar_width = '100%';
                    } elseif ($stock <= 2) {
                        $badge_cls = 'status-low'; $status_text = '<i class="fas fa-exclamation-circle mr-1"></i> Low Stock';
                        $bar_cls = 'bar-low'; $bar_width = ($stock * 10) . '%'; // 模拟低电量
                    } else {
                        $badge_cls = 'status-instock'; $status_text = '<i class="fas fa-check-circle mr-1"></i> In Stock';
                        $bar_cls = 'bar-healthy'; $bar_width = min($stock * 10, 100) . '%';
                    }
                    
                    $car_id = $car['id'];
                    $img_query = $conn->query("SELECT image_url FROM car_images WHERE car_id = $car_id LIMIT 1");
                    $img_src = 'https://via.placeholder.com/800x450?text=NO+IMAGE';
                    if($img_row = $img_query->fetch_assoc()){
                        $v = $img_row['image_url'];
                        if(strpos($v, 'http') !== 0 && strpos($v, 'car_image/') === false && strpos($v, '../') === false) $v = 'car_image/' . $v; 
                        $img_src = $v;
                    }
            ?>
            <div class="inventory-card car-item" data-title="<?php echo strtolower($car['car_name'] . ' ' . $car['brand']); ?>" data-category="<?php echo strtolower($car['type']); ?>">
                
                <div class="status-badge <?php echo $badge_cls; ?>">
                    <?php echo $status_text; ?>
                </div>

                <div class="card-img-wrapper">
                    <img src="<?php echo $img_src; ?>" alt="Car">
                </div>
                
                <div class="p-5 flex-1">
                    <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1"><?php echo $car['brand']; ?> | <?php echo $car['type']; ?></div>
                    <h3 class="text-xl font-black text-white mb-2 leading-tight"><?php echo $car['car_name']; ?></h3>
                    <div class="text-lg font-bold text-slate-300 mb-4">RM <?php echo number_format($car['price_per_day']); ?></div>
                    
                    <div class="flex gap-4 text-xs font-bold text-slate-400 mb-6">
                        <span><i class="fas fa-cogs mr-1"></i> <?php echo $car['transmission'] == 'Automatic' ? 'Auto' : 'Manual'; ?></span>
                        <span><i class="fas fa-chair mr-1"></i> <?php echo $car['seats']; ?> Seats</span>
                        <?php if($car['is_hybrid']): ?><span class="text-blue-400"><i class="fas fa-leaf"></i> Hybrid</span><?php endif; ?>
                    </div>

                    <div>
                        <div class="flex justify-between text-[11px] font-bold text-slate-400 mb-1 uppercase tracking-wider">
                            <span>Physical Stock</span>
                            <span class="<?php echo $stock <= 2 ? ($stock == 0 ? 'text-red-500' : 'text-amber-500') : 'text-emerald-500'; ?>"><?php echo $stock; ?> Units</span>
                        </div>
                        <div class="stock-progress">
                            <div class="stock-bar <?php echo $bar_cls; ?>" style="width: <?php echo $bar_width; ?>"></div>
                        </div>
                    </div>
                </div>

                <div class="action-bar">
                    <?php $car['display_image'] = $img_src; ?>
                    <button class="btn-details" onclick="openDetailsModal(<?php echo htmlspecialchars(json_encode($car), ENT_QUOTES, 'UTF-8'); ?>)">
                        <i class="fas fa-list-alt mr-1"></i> Details
                    </button>
                    
                    <?php if(($_SESSION['role'] ?? '') == 'super_admin'): ?>
                        <div class="flex gap-2">
                            <button class="btn-manage" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($car), ENT_QUOTES, 'UTF-8'); ?>)">
                                Manage
                            </button>
                            <a href="?delete=<?php echo $car['id']; ?>" class="w-8 h-8 rounded bg-slate-800 hover:bg-red-900/50 flex items-center justify-center text-slate-500 hover:text-red-500 transition border border-slate-700" onclick="return confirm('Destructive Action: Remove this fleet model completely?')" title="Remove from System">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <span class="text-xs text-slate-500 font-bold"><i class="fas fa-lock"></i> Read Only</span>
                    <?php endif; ?>
                </div>

            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="detailsModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.8); backdrop-filter: blur(5px);">
        <div class="modal-content" style="margin: 5% auto; padding: 0; width: 750px; max-width:95%; border-radius:16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); overflow: hidden; border: 1px solid #334155;">
            
            <div id="dtl-header-img" style="height: 220px; background-size: cover; background-position: center; position: relative;">
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, #0f172a 10%, transparent);"></div>
                
                <span onclick="closeDetailsModal()" style="position:absolute; top:20px; right:20px; cursor:pointer; font-size:1.2rem; color:#fff; background: rgba(0,0,0,0.5); width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; border: 1px solid rgba(255,255,255,0.2); transition: background 0.2s;">&times;</span>
                
                <div style="position: absolute; bottom: 20px; left: 30px;">
                    <span id="dtl-badge" class="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-widest bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 mb-2 inline-block">In Stock</span>
                    <h2 id="dtl-title" style="margin:0; font-size:2.2rem; color:#fff; font-weight:900; line-height: 1.1; letter-spacing: -0.5px;">Toyota Vios</h2>
                    <p id="dtl-subtitle" style="color:#94a3b8; font-weight:bold; font-size:12px; text-transform:uppercase; letter-spacing:2px; margin-top:8px;">Sedan | Auto</p>
                </div>
            </div>

            <div style="padding: 30px; background: #0f172a;">
                <div class="grid grid-cols-3 gap-6 mb-6 pb-6 border-b border-slate-800">
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Selling Price</div>
                        <div id="dtl-price" class="text-2xl font-black text-white">RM 0</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Booking Deposit</div>
                        <div id="dtl-deposit" class="text-2xl font-black text-slate-300">RM 0</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Physical Stock</div>
                        <div id="dtl-stock" class="text-2xl font-black text-white">0 Units</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-800 pb-2 flex items-center gap-2"><i class="fas fa-cogs text-red-500"></i> Technical Specs</h4>
                        <ul class="space-y-3 text-sm text-slate-300 font-medium">
                            <li class="flex justify-between"><span class="text-slate-500">Transmission:</span> <span id="dtl-trans" class="text-white">--</span></li>
                            <li class="flex justify-between"><span class="text-slate-500">Seating Capacity:</span> <span id="dtl-seats" class="text-white">--</span></li>
                            <li class="flex justify-between"><span class="text-slate-500">Color Options:</span> <span id="dtl-colors" class="text-right max-w-[180px] truncate text-white">--</span></li>
                        </ul>
                        <div id="dtl-tech-tags" class="flex gap-2 mt-5 flex-wrap"></div>
                    </div>

                    <div>
                         <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-800 pb-2 flex items-center gap-2"><i class="fas fa-file-alt text-red-500"></i> Vehicle Profile</h4>
                         <div class="mb-4">
                             <div class="text-[10px] text-slate-500 font-bold uppercase mb-1.5 tracking-wider">Internal Description</div>
                             <p id="dtl-desc" class="text-xs text-slate-400 leading-relaxed bg-[#162032] p-3.5 rounded-lg border border-slate-800 h-24 overflow-y-auto">--</p>
                         </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-[#0b1120] px-6 py-3 border-t border-slate-800 text-right">
                <button type="button" class="btn border border-slate-700 text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-bold px-4 py-1.5 rounded-lg transition" onclick="closeDetailsModal()">Close Profile</button>
            </div>
        </div>
    </div>

    <div id="carModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.8); backdrop-filter: blur(5px);">
        <div class="modal-content" style="margin: 5% auto; padding: 30px; width: 700px; max-width:95%; border-radius:16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
            <div style="display:flex; justify-content:space-between; margin-bottom:25px; border-bottom:1px solid #334155; padding-bottom:15px;">
                <h3 id="modalTitle" style="margin:0; font-size:1.5rem; color:#fff; font-weight:800;">Add Toyota Vehicle</h3>
                <span onclick="closeModal()" style="cursor:pointer; font-size:1.5rem; color:#94a3b8; line-height:1;">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="car_id" id="car_id">
                
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" id="brand" class="form-control fw-bold" style="color:#eb0a1e;" required value="Toyota" readonly>
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label class="form-label">Model Name <span class="text-danger">*</span></label>
                        <input type="text" name="car_name" id="car_name" class="form-control" placeholder="e.g. Vios / Corolla Cross" required>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Vehicle Images</label>
                    <input type="file" name="car_images[]" class="form-control" multiple accept="image/*">
                    <small class="text-slate-500 mt-1 d-block"><i class="fas fa-info-circle"></i> Uploading new images will replace all existing ones.</small>
                </div>
                
                <div class="row mt-3">
                     <div class="col-md-6 form-group mb-3">
                        <label class="form-label">Selling Price (RM) <span class="text-danger">*</span></label>
                        <input type="number" name="price_per_day" id="price_per_day" class="form-control" placeholder="0.00" step="0.01" required>
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label class="form-label">Booking Deposit (RM) <span class="text-danger">*</span></label>
                        <input type="number" name="price_per_hour" id="price_per_hour" class="form-control" placeholder="500.00" step="0.01" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 form-group mb-3">
                        <label class="form-label">Category</label>
                        <select name="type" id="type" class="form-select">
                            <?php 
                            if($types_query->num_rows > 0) {
                                $types_query->data_seek(0);
                                while($t = $types_query->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $t['type']; ?>"><?php echo $t['type']; ?></option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-3">
                        <label class="form-label">Transmission</label>
                        <select name="transmission" id="transmission" class="form-select">
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-3">
                        <label class="form-label">Availability</label>
                        <select name="availability" id="availability" class="form-select">
                            <option value="1">Available</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-3">
                        <label class="form-label">Stock Qty <span class="text-danger">*</span></label>
                        <input type="number" name="stock_quantity" id="stock_quantity" class="form-control" value="1" min="0" required>
                    </div>
                </div>

                <div class="tech-box mb-4 mt-3">
                    <label class="form-label text-danger fw-bold"><i class="fas fa-microchip me-1"></i> Technologies</label>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_hybrid" name="is_hybrid" value="1">
                                <label class="form-check-label ms-2 font-bold text-slate-300" for="is_hybrid">Hybrid</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_tss" name="is_tss" value="1">
                                <label class="form-check-label ms-2 font-bold text-slate-300" for="is_tss">TSS</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_tnga" name="is_tnga" value="1">
                                <label class="form-check-label ms-2 font-bold text-slate-300" for="is_tnga">TNGA</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 form-group">
                        <label class="form-label">Colors</label>
                        <input type="text" name="colors" id="colors" class="form-control" placeholder="e.g. Pearl White, Attitude Black">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label">Seats</label>
                        <input type="number" name="seats" id="seats" class="form-control" value="5">
                    </div>
                </div>

                <div style="text-align:right; margin-top:30px; padding-top:20px; border-top:1px solid #334155;">
                    <button type="button" class="btn border border-slate-600 text-slate-300 me-2" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="add_car" id="submitBtn" class="btn btn-danger px-4 fw-bold" style="background:#eb0a1e; border:none;">Save Fleet</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = document.getElementById('carModal');
        const form = document.querySelector('form');
        const modalTitle = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitBtn');

        function openAddModal() {
            form.reset(); 
            document.getElementById('car_id').value = '';
            document.getElementById('brand').value = 'Toyota'; 
            
            document.getElementById('is_tss').checked = false;
            document.getElementById('is_tnga').checked = false;
            document.getElementById('is_hybrid').checked = false;
            document.getElementById('colors').value = '';
            document.getElementById('stock_quantity').value = 1;

            modalTitle.innerText = 'Register Inbound Fleet';
            submitBtn.name = 'add_car'; 
            submitBtn.innerHTML = 'Save to Inventory';
            modal.style.display = 'block';
        }

        function openEditModal(car) {
            document.getElementById('car_id').value = car.id;
            document.getElementById('brand').value = car.brand;
            document.getElementById('car_name').value = car.car_name;
            document.getElementById('type').value = car.type; 
            document.getElementById('price_per_day').value = car.price_per_day;
            document.getElementById('price_per_hour').value = car.price_per_hour;
            document.getElementById('transmission').value = car.transmission;
            document.getElementById('seats').value = car.seats;
            document.getElementById('availability').value = car.availability;

            document.getElementById('is_tss').checked = car.is_tss == 1;
            document.getElementById('is_tnga').checked = car.is_tnga == 1;
            document.getElementById('is_hybrid').checked = car.is_hybrid == 1;
            document.getElementById('colors').value = car.colors || '';
            document.getElementById('stock_quantity').value = car.stock_quantity !== undefined ? car.stock_quantity : 0;

            modalTitle.innerText = 'Manage Fleet Unit';
            submitBtn.name = 'edit_car'; 
            submitBtn.innerHTML = 'Commit Changes';
            modal.style.display = 'block';
        }

        function closeModal() { modal.style.display = 'none'; }
        
        function filterCars() {
            const searchValue = document.getElementById('carSearch').value.toLowerCase();
            const catValue = document.getElementById('catFilter').value.toLowerCase();
            document.querySelectorAll('.car-item').forEach(el => {
                const title = el.getAttribute('data-title');
                const category = el.getAttribute('data-category');
                const matchesSearch = title.includes(searchValue);
                const matchesCategory = catValue === '' || category === catValue;
                el.style.display = (matchesSearch && matchesCategory) ? 'flex' : 'none';
            });
        }

        // === 全新：Vehicle Details Modal 控制逻辑 ===
        const detailsModal = document.getElementById('detailsModal');

        function openDetailsModal(car) {
            document.getElementById('dtl-header-img').style.backgroundImage = `url('${car.display_image}')`;
            document.getElementById('dtl-title').innerText = car.car_name;
            document.getElementById('dtl-subtitle').innerText = `${car.brand} | ${car.type} | ${car.transmission}`;
            
            document.getElementById('dtl-price').innerText = 'RM ' + parseFloat(car.price_per_day).toLocaleString();
            document.getElementById('dtl-deposit').innerText = 'RM ' + parseFloat(car.price_per_hour).toLocaleString();
            
            const stock = parseInt(car.stock_quantity) || 0;
            document.getElementById('dtl-stock').innerText = stock + ' Units';
            
            const badge = document.getElementById('dtl-badge');
            if (stock == 0) {
                badge.className = 'px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-widest bg-red-500/20 text-red-400 border border-red-500/30 mb-2 inline-block';
                badge.innerHTML = '<i class="fas fa-ban mr-1"></i> Sold Out';
            } else if (stock <= 2) {
                badge.className = 'px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-widest bg-amber-500/20 text-amber-400 border border-amber-500/30 mb-2 inline-block';
                badge.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Low Stock';
            } else {
                badge.className = 'px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-widest bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 mb-2 inline-block';
                badge.innerHTML = '<i class="fas fa-check-circle mr-1"></i> In Stock';
            }

            document.getElementById('dtl-trans').innerText = car.transmission;
            document.getElementById('dtl-seats').innerText = car.seats;
            document.getElementById('dtl-colors').innerText = car.colors || 'Standard Variant';
            document.getElementById('dtl-desc').innerText = car.description || 'No internal remarks provided for this model.';

            let tagsHtml = '';
            if(car.is_hybrid == 1) tagsHtml += `<span class="px-2 py-1 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[9px] font-bold uppercase tracking-wider"><i class="fas fa-leaf mr-1"></i> Hybrid Synergy</span>`;
            if(car.is_tss == 1) tagsHtml += `<span class="px-2 py-1 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[9px] font-bold uppercase tracking-wider"><i class="fas fa-shield-alt mr-1"></i> Safety Sense</span>`;
            if(car.is_tnga == 1) tagsHtml += `<span class="px-2 py-1 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-[9px] font-bold uppercase tracking-wider"><i class="fas fa-car-side mr-1"></i> TNGA Arch</span>`;
            document.getElementById('dtl-tech-tags').innerHTML = tagsHtml;

            detailsModal.style.display = 'block';
        }

        function closeDetailsModal() { detailsModal.style.display = 'none'; }

        // 让点击空白处可以关闭两个不同的 Modal
        window.onclick = function(event) { 
            if (event.target == modal) closeModal(); 
            if (event.target == detailsModal) closeDetailsModal();
        }
    </script>
</body>
</html>