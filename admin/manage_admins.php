<?php
include('../includes/config.php');
include('../includes/auth.php');
checkLogin();

// 权限检查
$is_super = (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin');

// --- 后端逻辑 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['del']) || isset($_GET['toggle_status'])) {
    if (!$is_super) die("Access Denied");

    // 1. 添加管理员 (Add Admin)
    if (isset($_POST['add_admin'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        // 获取复选框权限
        $perm_fleet = isset($_POST['perm_fleet']) ? 1 : 0;
        $perm_bookings = isset($_POST['perm_bookings']) ? 1 : 0;
        $perm_users = isset($_POST['perm_users']) ? 1 : 0;

        if (!empty($username) && !empty($password)) {
            if (strlen($password) < 8) {
                echo "<script>alert('Error: Password too short! Must be at least 8 characters.'); window.history.back();</script>"; exit;
            } elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
                echo "<script>alert('Error: Password must contain at least 1 Uppercase letter and 1 Number.'); window.history.back();</script>"; exit;
            }

            $check = $conn->query("SELECT id FROM admin WHERE username = '$username'");
            if ($check->num_rows > 0) {
                echo "<script>alert('Error: Username already exists!'); window.history.back();</script>"; exit;
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO admin (username, password, role, perm_fleet, perm_bookings, perm_users) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiii", $username, $hashed_password, $role, $perm_fleet, $perm_bookings, $perm_users);
                if ($stmt->execute()) {
                    admin_audit_log($conn, 'ADMIN_CREATED', "Created admin account {$username} with {$role} role.", 'admin', (int)$conn->insert_id);
                    header("Location: manage_admins.php?msg=added"); exit;
                }
            }
        }
    }

    // 2. 编辑管理员 (Edit Admin)
    if (isset($_POST['edit_admin'])) {
        $id = $_POST['admin_id']; $username = trim($_POST['username']); $role = $_POST['role']; $password = $_POST['password']; 
        
        // 获取复选框权限
        $perm_fleet = isset($_POST['perm_fleet']) ? 1 : 0;
        $perm_bookings = isset($_POST['perm_bookings']) ? 1 : 0;
        $perm_users = isset($_POST['perm_users']) ? 1 : 0;

        if (!empty($username)) {
            if (!empty($password)) {
                if (strlen($password) < 8) { echo "<script>alert('Error: Password too short! Must be at least 8 characters.'); window.history.back();</script>"; exit; } 
                elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) { echo "<script>alert('Error: Password must contain at least 1 Uppercase letter and 1 Number.'); window.history.back();</script>"; exit; }

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admin SET username=?, password=?, role=?, perm_fleet=?, perm_bookings=?, perm_users=? WHERE id=?");
                $stmt->bind_param("sssiiii", $username, $hashed_password, $role, $perm_fleet, $perm_bookings, $perm_users, $id);
            } else {
                $stmt = $conn->prepare("UPDATE admin SET username=?, role=?, perm_fleet=?, perm_bookings=?, perm_users=? WHERE id=?");
                $stmt->bind_param("ssiiii", $username, $role, $perm_fleet, $perm_bookings, $perm_users, $id);
            }
            if ($stmt->execute()) {
                admin_audit_log($conn, 'ADMIN_UPDATED', "Updated admin account {$username} with {$role} role.", 'admin', (int)$id);
                header("Location: manage_admins.php?msg=updated"); exit;
            }
        }
    }

    // 3. 删除管理员
    if (isset($_GET['del'])) {
        $id = intval($_GET['del']);
        if ($id == $_SESSION['admin_id']) { echo "<script>alert('You cannot delete your own account!'); window.location.href='manage_admins.php';</script>"; exit; }
        $old = $conn->query("SELECT username FROM admin WHERE id=$id LIMIT 1")->fetch_assoc();
        $conn->query("DELETE FROM admin WHERE id=$id");
        admin_audit_log($conn, 'ADMIN_DELETED', "Deleted admin account " . ($old['username'] ?? '#' . $id) . ".", 'admin', $id);
        header("Location: manage_admins.php?msg=deleted"); exit;
    }

    // 4. 切换管理员状态 (Suspend / Activate)
    if (isset($_GET['toggle_status'])) {
        $id = intval($_GET['toggle_status']);
        if ($id == $_SESSION['admin_id']) { 
            echo "<script>alert('Error: You cannot suspend your own account!'); window.location.href='manage_admins.php';</script>"; exit; 
        }
        // 反转状态: 1 变 0, 0 变 1
        $old = $conn->query("SELECT username, is_active FROM admin WHERE id=$id LIMIT 1")->fetch_assoc();
        $conn->query("UPDATE admin SET is_active = NOT is_active WHERE id=$id");
        $new_status = !empty($old['is_active']) ? 'suspended' : 'activated';
        admin_audit_log($conn, 'ADMIN_STATUS_CHANGED', "Admin account " . ($old['username'] ?? '#' . $id) . " was {$new_status}.", 'admin', $id);
        header("Location: manage_admins.php?msg=status_updated"); exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Administrators | Fleet Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script> tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }, colors: { primary: '#3b82f6' } } } } </script>
    <style> body { background: radial-gradient(circle at top right, #e0e7ff 0%, #f8fafc 40%, #f1f5f9 100%); } .glass-card { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 1); box-shadow: 0 10px 40px -10px rgba(226, 232, 240, 0.8); } </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include('include/sidebar.php'); ?>
    
    <main class="ml-64 p-10 w-full">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">System Administrators</h1>
                <p class="text-slate-500 mt-1 font-medium">Manage access control and team permissions.</p>
            </div>
            <?php if ($is_super): ?>
            <button onclick="openAddModal()" class="px-6 py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl font-bold shadow-xl shadow-slate-900/20 transition-all flex items-center gap-2">
                <i class="fas fa-user-plus text-blue-400"></i> New Admin
            </button>
            <?php endif; ?>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php 
            $admins = $conn->query("SELECT * FROM admin ORDER BY id ASC");
            if($admins && $admins->num_rows > 0):
                while($row = $admins->fetch_assoc()):
                    $row_is_super = ($row['role'] == 'super_admin');
                    
                    $db_img = !empty($row['avatar']) ? $row['avatar'] : ($row['profile_picture'] ?? '');
                    $img_path = '';
                    if (!empty($db_img)) {
                        if (strpos($db_img, 'assets/') === 0) $img_path = '../' . $db_img; 
                        elseif (strpos($db_img, '../') === 0 || strpos($db_img, 'http') === 0) $img_path = $db_img; 
                        else $img_path = '../assets/uploads/' . $db_img; 
                    }
                    $initial = strtoupper(substr($row['username'], 0, 1));
            ?>
            <div class="glass-card p-6 rounded-[2rem] flex flex-col justify-between hover:-translate-y-1 transition-transform">
                <div class="flex items-center gap-4 mb-6">
                    <?php if (!empty($img_path)): ?>
                        <img src="<?php echo htmlspecialchars($img_path); ?>" class="w-16 h-16 rounded-[1.2rem] object-cover shadow-sm border-2 border-white" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"> 
                        <div class="hidden w-16 h-16 rounded-[1.2rem] bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-black text-2xl items-center justify-center shadow-lg shadow-blue-500/20 border-2 border-white"><?php echo $initial; ?></div>
                    <?php else: ?>
                        <div class="w-16 h-16 rounded-[1.2rem] bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-blue-500/20 border-2 border-white"><?php echo $initial; ?></div>
                    <?php endif; ?>
                    <div>
                        <div class="flex items-center gap-2">
                            <h5 class="text-xl font-black text-slate-800 leading-tight"><?php echo htmlspecialchars($row['username']); ?></h5>
                            <?php if(isset($row['is_active']) && $row['is_active'] == 0): ?>
                                <span class="px-2 py-0.5 bg-red-50 text-red-500 rounded text-[9px] font-black uppercase tracking-widest border border-red-100 shadow-sm animate-pulse">Suspended</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-500 rounded text-[9px] font-black uppercase tracking-widest border border-emerald-100 shadow-sm">Active</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-widest mt-1 <?php echo $row_is_super ? 'text-emerald-500' : 'text-slate-400'; ?>">
                            <?php echo $row_is_super ? '<i class="fas fa-shield-check mr-1"></i> Super Admin' : '<i class="fas fa-user-tie mr-1"></i> Manager'; ?>
                        </p>
                        <?php if(!$row_is_super): ?>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <?php if(isset($row['perm_fleet']) && $row['perm_fleet']) echo '<span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[8px] font-bold uppercase"><i class="fas fa-car mr-1"></i>Fleet</span>'; ?>
                            <?php if(isset($row['perm_bookings']) && $row['perm_bookings']) echo '<span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[8px] font-bold uppercase"><i class="fas fa-calendar mr-1"></i>Bookings</span>'; ?>
                            <?php if(isset($row['perm_users']) && $row['perm_users']) echo '<span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[8px] font-bold uppercase"><i class="fas fa-users mr-1"></i>Users</span>'; ?>
                            <?php if(isset($row['perm_fleet']) && !$row['perm_fleet'] && !$row['perm_bookings'] && !$row['perm_users']) echo '<span class="px-2 py-0.5 bg-red-50 text-red-400 border border-red-100 rounded text-[8px] font-bold uppercase">No Access</span>'; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                    <?php if ($is_super): ?>
                        <button onclick='openEditModal(<?php echo json_encode($row); ?>)' class="w-10 h-10 bg-slate-100 text-slate-600 hover:bg-primary hover:text-white rounded-xl transition-all shadow-sm" title="Edit Admin"><i class="fas fa-pen"></i></button>
                        
                        <?php if($row['id'] != $_SESSION['admin_id']): ?>
                            <?php if(isset($row['is_active']) && $row['is_active'] == 0): ?>
                                <a href="#" onclick="confirmDangerAction('?toggle_status=<?php echo $row['id']; ?>', 'ACTIVATE')" class="w-10 h-10 flex items-center justify-center bg-emerald-50 text-emerald-500 hover:bg-emerald-500 hover:text-white rounded-xl transition-all shadow-sm" title="Activate Account"><i class="fas fa-user-check"></i></a>
                            <?php else: ?>
                                <a href="#" onclick="confirmDangerAction('?toggle_status=<?php echo $row['id']; ?>', 'SUSPEND')" class="w-10 h-10 flex items-center justify-center bg-amber-50 text-amber-500 hover:bg-amber-500 hover:text-white rounded-xl transition-all shadow-sm" title="Suspend Account"><i class="fas fa-ban"></i></a>
                            <?php endif; ?>
                            
                            <a href="#" onclick="confirmDangerAction('?del=<?php echo $row['id']; ?>', 'DELETE')" class="w-10 h-10 flex items-center justify-center bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-xl transition-all shadow-sm" title="Delete Permanent"><i class="fas fa-trash-alt"></i></a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button disabled class="w-10 h-10 bg-slate-50 text-slate-300 rounded-xl cursor-not-allowed"><i class="fas fa-lock"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; else: ?>
                <p class="text-slate-400 col-span-3">No admins found.</p>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($is_super): ?>
    <div id="adminModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="glass-card w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl relative">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-2xl font-black text-slate-800">New Admin</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-800 text-2xl"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" class="space-y-5">
                <input type="hidden" name="admin_id" id="admin_id">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Username</label>
                    <input type="text" name="username" id="username" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-primary outline-none font-bold" required>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Access Level</label>
                    <select name="role" id="role" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-primary outline-none font-bold cursor-pointer">
                        <option value="super_admin">Super Admin (Full Access)</option>
                        <option value="manager">Manager (Read Only)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Password</label>
                    <input type="password" name="password" id="password" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-primary outline-none font-bold" placeholder="Min 8 chars, 1 Uppercase, 1 Number">
                    <p class="text-xs text-slate-400 font-bold mt-2 ml-1"><i class="fas fa-shield-alt mr-1"></i> Strong password required.</p>
                </div>

                <div class="border-t border-slate-100 pt-4 mt-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Module Permissions (For Managers)</label>
                    <div class="grid grid-cols-1 gap-2">
                        <label class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-100 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors">
                            <input type="checkbox" name="perm_fleet" id="perm_fleet" value="1" class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                            <span class="text-xs font-bold text-slate-700"><i class="fas fa-car w-5 text-slate-400"></i>Fleet & Categories Management</span>
                        </label>
                        <label class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-100 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors">
                            <input type="checkbox" name="perm_bookings" id="perm_bookings" value="1" class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                            <span class="text-xs font-bold text-slate-700"><i class="fas fa-calendar-check w-5 text-slate-400"></i>Bookings & Live GPS Control</span>
                        </label>
                        <label class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-100 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors">
                            <input type="checkbox" name="perm_users" id="perm_users" value="1" class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                            <span class="text-xs font-bold text-slate-700"><i class="fas fa-users w-5 text-slate-400"></i>Customers & KYC Approval</span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 mt-6 border-t border-slate-100">
                    <button type="submit" name="add_admin" id="submitBtn" class="w-full py-4 bg-primary text-white rounded-2xl font-black shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-all">Create Access</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const modal = document.getElementById('adminModal');
        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'New Admin';
            document.getElementById('submitBtn').name = 'add_admin';
            document.getElementById('submitBtn').innerText = 'Create Access';
            document.getElementById('admin_id').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            
            // 重置复选框
            document.getElementById('perm_fleet').checked = false;
            document.getElementById('perm_bookings').checked = false;
            document.getElementById('perm_users').checked = false;

            modal.classList.remove('hidden');
        }
        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = 'Edit Admin';
            document.getElementById('submitBtn').name = 'edit_admin';
            document.getElementById('submitBtn').innerText = 'Update Access';
            document.getElementById('admin_id').value = data.id;
            document.getElementById('username').value = data.username;
            document.getElementById('role').value = data.role;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            
            // 勾选已有的权限
            document.getElementById('perm_fleet').checked = (data.perm_fleet == 1);
            document.getElementById('perm_bookings').checked = (data.perm_bookings == 1);
            document.getElementById('perm_users').checked = (data.perm_users == 1);

            modal.classList.remove('hidden');
        }
        function closeModal() { modal.classList.add('hidden'); }
    </script>
    <?php endif; ?>
    <script>
        // 高危操作拦截器
        function confirmDangerAction(url, keyword) {
            let input = prompt(`[SECURITY WARNING]\nThis is a critical action. Please type '${keyword}' to proceed:`);
            if (input === keyword) {
                window.location.href = url;
            } else if (input !== null) {
                alert("Operation blocked: Keyword mismatch.");
            }
        }
    </script>
</body>
</html>
