<?php
include('../includes/config.php');
include('../includes/auth.php');
checkLogin();

// --- 1. 删除操作 ---
if (isset($_GET['del_cat'])) {
    $id = intval($_GET['del_cat']);
    $conn->query("DELETE FROM vehicle_categories WHERE id = $id");
    header("Location: manage_categories.php?msg=deleted");
    exit;
}
if (isset($_GET['del_brand'])) {
    $id = intval($_GET['del_brand']);
    $conn->query("DELETE FROM brands WHERE id = $id");
    header("Location: manage_categories.php?msg=deleted");
    exit;
}

// --- 2. 新增与编辑操作 (支持 Logo 上传) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- 新增 Category ---
    if (isset($_POST['add_category'])) {
        $name = $conn->real_escape_string($_POST['category_name']);
        $conn->query("INSERT INTO vehicle_categories (name) VALUES ('$name')");
        header("Location: manage_categories.php?msg=added");
        exit;
    }
    
    // --- 编辑 Category ---
    if (isset($_POST['edit_category'])) {
        $id = intval($_POST['cat_id']);
        $name = $conn->real_escape_string($_POST['category_name']);
        $conn->query("UPDATE vehicle_categories SET name='$name' WHERE id=$id");
        header("Location: manage_categories.php?msg=updated");
        exit;
    }

    // --- 新增 Brand ---
    if (isset($_POST['add_brand'])) {
        $name = $conn->real_escape_string($_POST['brand_name']);
        $logo_path = '';
        if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] == 0) {
            $dir = '../assets/uploads/brands/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $ext = pathinfo($_FILES['brand_logo']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . rand(100,999) . '.' . $ext;
            if (move_uploaded_file($_FILES['brand_logo']['tmp_name'], $dir . $filename)) {
                $logo_path = 'assets/uploads/brands/' . $filename;
            }
        }
        $conn->query("INSERT INTO brands (brand_name, brand_logo) VALUES ('$name', '$logo_path')");
        $new_brand_id = intval($conn->insert_id);
        if ($new_brand_id > 0) {
            $conn->query("UPDATE brands SET id = brand_id WHERE brand_id = $new_brand_id");
        }
        header("Location: manage_categories.php?msg=added");
        exit;
    }
    
    // --- 编辑 Brand (更新 Logo) ---
    if (isset($_POST['edit_brand'])) {
        $id = intval($_POST['brand_id']);
        $name = $conn->real_escape_string($_POST['brand_name']);
        
        // 检查是否有上传新照片
        if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] == 0) {
            $dir = '../assets/uploads/brands/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $ext = pathinfo($_FILES['brand_logo']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . rand(100,999) . '.' . $ext;
            if (move_uploaded_file($_FILES['brand_logo']['tmp_name'], $dir . $filename)) {
                $logo_path = 'assets/uploads/brands/' . $filename;
                // 有新照片，更新名称和照片路径
                $conn->query("UPDATE brands SET brand_name='$name', brand_logo='$logo_path' WHERE id=$id");
            }
        } else {
            // 没有上传新照片，只更新名称，保留原有照片
            $conn->query("UPDATE brands SET brand_name='$name' WHERE id=$id");
        }
        header("Location: manage_categories.php?msg=updated");
        exit;
    }
}

// --- 3. 智能数据拉取 ---
$categories = $conn->query("SELECT vc.*, (SELECT COUNT(*) FROM cars c WHERE c.type = vc.name) as linked_cars FROM vehicle_categories vc ORDER BY id DESC");
$brands = $conn->query("SELECT b.*, (SELECT COUNT(*) FROM cars c WHERE c.brand = b.brand_name) as linked_cars FROM brands b ORDER BY id DESC");

$total_cats = $categories->num_rows;
$total_brands = $brands->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vehicle Attributes | Fleet Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }, colors: { primary: '#3b82f6' } } } }
    </script>
    <style>
        body { background: radial-gradient(circle at top right, #e0e7ff 0%, #f8fafc 40%, #f1f5f9 100%); }
        .glass-card { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 1); box-shadow: 0 10px 40px -10px rgba(226, 232, 240, 0.8); }
        .tab-content { display: none; opacity: 0; transition: opacity 0.4s ease-in-out; }
        .tab-content.active { display: block; opacity: 1; }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include('include/sidebar.php'); ?>
    
    <main class="ml-64 p-10 w-full max-w-[1400px] mx-auto">
        <header class="mb-10 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Vehicle Attributes</h1>
                <p class="text-slate-500 mt-1 font-medium">Manage fleet classifications and brand identities.</p>
            </div>
            <button onclick="openAddModal()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black text-sm uppercase tracking-widest transition-all shadow-lg shadow-blue-500/30 flex items-center gap-2 hover:-translate-y-1">
                <i class="fas fa-plus-circle"></i> <span id="addBtnText">Create New</span>
            </button>
        </header>

        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-2xl mb-8 flex items-center font-bold shadow-sm">
                <i class="fas fa-check-circle mr-3 text-lg"></i> Record processed successfully.
            </div>
        <?php endif; ?>

        <div class="flex gap-4 mb-8">
            <button onclick="switchTab('types')" id="tab-types" class="px-8 py-3 rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-md bg-blue-600 text-white flex items-center gap-2">
                <i class="fas fa-car-side"></i> Body Types
            </button>
            <button onclick="switchTab('brands')" id="tab-brands" class="px-8 py-3 rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-sm bg-white text-slate-500 hover:bg-slate-50 flex items-center gap-2">
                <i class="fas fa-copyright"></i> Brands
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="glass-card rounded-[2rem] p-6 flex justify-between items-center border-l-4 border-blue-500">
                <div>
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Total Body Types</h4>
                    <div class="text-3xl font-black text-slate-800"><?php echo $total_cats; ?></div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-xl shadow-sm"><i class="fas fa-layer-group"></i></div>
            </div>
            <div class="glass-card rounded-[2rem] p-6 flex justify-between items-center border-l-4 border-indigo-500">
                <div>
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Registered Brands</h4>
                    <div class="text-3xl font-black text-slate-800"><?php echo $total_brands; ?></div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-xl shadow-sm"><i class="fas fa-tags"></i></div>
            </div>
        </div>

        <div id="content-types" class="glass-card rounded-[2.5rem] overflow-hidden tab-content active">
            <div class="p-6 border-b border-slate-100 bg-white/30 flex justify-between items-center">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Classification Rules</h3>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-left border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">ID #</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Body Type</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Vehicles Linked</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($categories->num_rows > 0): while($row = $categories->fetch_assoc()): ?>
                        <tr class="bg-slate-50/50 hover:bg-white transition-all rounded-2xl group">
                            <td class="px-6 py-4 rounded-l-2xl font-black text-slate-400">#<?php echo sprintf('%02d', $row['id']); ?></td>
                            <td class="px-6 py-4 font-black text-slate-700 text-base"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-lg text-xs font-black"><?php echo $row['linked_cars']; ?> Fleet</span>
                            </td>
                            <td class="px-6 py-4 text-right rounded-r-2xl">
                                <button onclick="openEditCatModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>')" class="w-9 h-9 inline-flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 rounded-xl transition-all shadow-sm mr-1" title="Edit"><i class="fas fa-edit"></i></button>
                                <a href="?del_cat=<?php echo $row['id']; ?>" onclick="return confirm('Delete this body type?')" class="w-9 h-9 inline-flex items-center justify-center bg-white border border-slate-200 text-red-500 hover:bg-red-50 hover:border-red-200 rounded-xl transition-all shadow-sm" title="Delete"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center py-10 text-slate-400 font-bold">No body types found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="content-brands" class="glass-card rounded-[2.5rem] overflow-hidden tab-content">
            <div class="p-6 border-b border-slate-100 bg-white/30 flex justify-between items-center">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Brand Directory</h3>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-left border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Logo</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Brand Name</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Fleet Size</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($brands->num_rows > 0): while($row = $brands->fetch_assoc()): 
                            $logo = !empty($row['brand_logo']) ? '../' . $row['brand_logo'] : '';
                        ?>
                        <tr class="bg-slate-50/50 hover:bg-white transition-all rounded-2xl group">
                            <td class="px-6 py-4 rounded-l-2xl">
                                <div class="w-14 h-14 bg-white border border-slate-100 rounded-xl flex items-center justify-center overflow-hidden shadow-sm p-2">
                                    <?php if($logo): ?>
                                        <img src="<?php echo htmlspecialchars($logo); ?>" class="max-w-full max-h-full object-contain">
                                    <?php else: ?>
                                        <i class="fas fa-car text-slate-300 text-xl"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-black text-slate-700 text-base uppercase tracking-wider"><?php echo htmlspecialchars($row['brand_name']); ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1.5 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-lg text-xs font-black"><?php echo $row['linked_cars']; ?> Models</span>
                            </td>
                            <td class="px-6 py-4 text-right rounded-r-2xl">
                                <button onclick="openEditBrandModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['brand_name']); ?>')" class="w-9 h-9 inline-flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 rounded-xl transition-all shadow-sm mr-1" title="Edit Brand"><i class="fas fa-edit"></i></button>
                                <a href="?del_brand=<?php echo $row['id']; ?>" onclick="return confirm('Delete this brand?')" class="w-9 h-9 inline-flex items-center justify-center bg-white border border-slate-200 text-red-500 hover:bg-red-50 hover:border-red-200 rounded-xl transition-all shadow-sm" title="Delete Brand"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center py-10 text-slate-400 font-bold">No brands found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="actionModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="glass-card w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl relative">
            <button onclick="closeModal()" class="absolute top-6 right-6 text-slate-400 hover:text-slate-800 text-xl transition-colors"><i class="fas fa-times"></i></button>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        let currentTab = 'types'; // 默认状态

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.getElementById('content-' + tab).classList.add('active');

            const btnTypes = document.getElementById('tab-types');
            const btnBrands = document.getElementById('tab-brands');

            if (tab === 'types') {
                btnTypes.className = "px-8 py-3 rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-md bg-blue-600 text-white flex items-center gap-2";
                btnBrands.className = "px-8 py-3 rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-sm bg-white text-slate-500 hover:bg-slate-50 flex items-center gap-2 border border-slate-100";
                document.getElementById('addBtnText').innerText = "Add Body Type";
            } else {
                btnBrands.className = "px-8 py-3 rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-md bg-blue-600 text-white flex items-center gap-2";
                btnTypes.className = "px-8 py-3 rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-sm bg-white text-slate-500 hover:bg-slate-50 flex items-center gap-2 border border-slate-100";
                document.getElementById('addBtnText').innerText = "Add Brand";
            }
        }

        // --- 打开新增弹窗 ---
        function openAddModal() {
            const modal = document.getElementById('actionModal');
            const body = document.getElementById('modalBody');
            
            if (currentTab === 'types') {
                body.innerHTML = `
                    <h3 class="text-2xl font-black text-slate-800 mb-2">New Body Type</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Classify your fleet</p>
                    <form action="" method="POST">
                        <div class="mb-6">
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-widest mb-2">Type Name</label>
                            <input type="text" name="category_name" required placeholder="e.g. SUV, Luxury..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-xl outline-none transition-all font-bold">
                        </div>
                        <button type="submit" name="add_category" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg">Save Record</button>
                    </form>
                `;
            } else {
                body.innerHTML = `
                    <h3 class="text-2xl font-black text-slate-800 mb-2">New Brand</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Add manufacturer details</p>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-widest mb-2">Brand Name</label>
                            <input type="text" name="brand_name" required placeholder="e.g. Toyota, BMW..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-xl outline-none transition-all font-bold">
                        </div>
                        <div class="mb-6">
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-widest mb-2">Upload Logo</label>
                            <div class="relative border-2 border-dashed border-slate-200 rounded-xl p-4 bg-slate-50 hover:bg-slate-100 transition-colors text-center cursor-pointer">
                                <i class="fas fa-cloud-upload-alt text-2xl text-blue-400 mb-2"></i>
                                <div class="text-xs font-bold text-slate-500">Click to browse image</div>
                                <input type="file" name="brand_logo" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            </div>
                        </div>
                        <button type="submit" name="add_brand" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg">Save Brand</button>
                    </form>
                `;
            }
            modal.classList.remove('hidden');
        }

        // --- 打开编辑 Body Type 弹窗 ---
        function openEditCatModal(id, name) {
            const modal = document.getElementById('actionModal');
            const body = document.getElementById('modalBody');
            body.innerHTML = `
                <h3 class="text-2xl font-black text-slate-800 mb-2">Edit Body Type</h3>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Update classification</p>
                <form action="" method="POST">
                    <input type="hidden" name="cat_id" value="${id}">
                    <div class="mb-6">
                        <label class="block text-xs font-black text-slate-800 uppercase tracking-widest mb-2">Type Name</label>
                        <input type="text" name="category_name" value="${name}" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-xl outline-none transition-all font-bold">
                    </div>
                    <button type="submit" name="edit_category" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/30">Update Record</button>
                </form>
            `;
            modal.classList.remove('hidden');
        }

        // --- 打开编辑 Brand 弹窗 (可以更新 Logo) ---
        function openEditBrandModal(id, name) {
            const modal = document.getElementById('actionModal');
            const body = document.getElementById('modalBody');
            body.innerHTML = `
                <h3 class="text-2xl font-black text-slate-800 mb-2">Edit Brand</h3>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Update brand identity</p>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="brand_id" value="${id}">
                    <div class="mb-4">
                        <label class="block text-xs font-black text-slate-800 uppercase tracking-widest mb-2">Brand Name</label>
                        <input type="text" name="brand_name" value="${name}" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-xl outline-none transition-all font-bold">
                    </div>
                    <div class="mb-6">
                        <label class="block text-xs font-black text-slate-800 uppercase tracking-widest mb-2">Update Logo <span class="text-slate-400 font-normal">(Optional)</span></label>
                        <div class="relative border-2 border-dashed border-slate-200 rounded-xl p-4 bg-slate-50 hover:bg-slate-100 transition-colors text-center cursor-pointer">
                            <i class="fas fa-image text-2xl text-indigo-400 mb-2"></i>
                            <div class="text-xs font-bold text-slate-500">Upload new logo to replace old</div>
                            <input type="file" name="brand_logo" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        </div>
                        <p class="text-[10px] text-slate-400 font-bold mt-2">* Leave empty if you want to keep the current logo.</p>
                    </div>
                    <button type="submit" name="edit_brand" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/30">Update Brand</button>
                </form>
            `;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('actionModal').classList.add('hidden');
        }
        
        window.onclick = function(e) {
            if (e.target == document.getElementById('actionModal')) closeModal();
        }
    </script>
</body>
</html>
