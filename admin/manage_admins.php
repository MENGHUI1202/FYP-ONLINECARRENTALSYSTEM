<?php
include('../includes/config.php');
include('../includes/auth.php');

// 权限检查
$is_super = (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin');

// --- 后端逻辑 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['del'])) {
    if (!$is_super) {
        die("Access Denied");
    }

    // 1. 添加管理员 (Add Admin)
    if (isset($_POST['add_admin'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        if (!empty($username) && !empty($password)) {
            
            // ★★★ 核心新增：密码强度检查 ★★★
            if (strlen($password) < 8) {
                echo "<script>alert('Error: Password too short! Must be at least 8 characters.'); window.history.back();</script>";
                exit;
            } elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
                echo "<script>alert('Error: Password must contain at least 1 Uppercase letter and 1 Number.'); window.history.back();</script>";
                exit;
            }

            // 检查用户名是否重复
            $check = $conn->query("SELECT id FROM admin WHERE username = '$username'");
            if ($check->num_rows > 0) {
                echo "<script>alert('Error: Username already exists!'); window.history.back();</script>";
                exit;
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO admin (username, password, role) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    header("Location: manage_admins.php?msg=added"); exit;
                } else {
                    echo "<script>alert('Error adding admin.');</script>";
                }
            }
        }
    }

    // 2. 编辑管理员 (Edit Admin)
    if (isset($_POST['edit_admin'])) {
        $id = $_POST['admin_id'];
        $username = trim($_POST['username']);
        $role = $_POST['role'];
        $password = $_POST['password']; 

        if (!empty($username)) {
            if (!empty($password)) {
                
                // ★★★ 核心新增：编辑时如果填了新密码，也要检查强度 ★★★
                if (strlen($password) < 8) {
                    echo "<script>alert('Error: Password too short! Must be at least 8 characters.'); window.history.back();</script>";
                    exit;
                } elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
                    echo "<script>alert('Error: Password must contain at least 1 Uppercase letter and 1 Number.'); window.history.back();</script>";
                    exit;
                }

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admin SET username=?, password=?, role=? WHERE id=?");
                $stmt->bind_param("sssi", $username, $hashed_password, $role, $id);
            } else {
                $stmt = $conn->prepare("UPDATE admin SET username=?, role=? WHERE id=?");
                $stmt->bind_param("ssi", $username, $role, $id);
            }
            if ($stmt->execute()) {
                header("Location: manage_admins.php?msg=updated"); exit;
            } else {
                echo "<script>alert('Update failed.');</script>";
            }
        }
    }

    // 3. 删除管理员
    if (isset($_GET['del'])) {
        $id = intval($_GET['del']);
        if ($id == $_SESSION['admin_id']) {
            echo "<script>alert('You cannot delete your own account!'); window.location.href='manage_admins.php';</script>";
            exit;
        }
        $conn->query("DELETE FROM admin WHERE id=$id");
        header("Location: manage_admins.php?msg=deleted"); exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Administrators</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .admin-card {
            background: white; border-radius: 12px; padding: 20px;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid #f3f4f6; transition: transform 0.2s;
        }
        .admin-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        
        /* 头像样式 */
        .avatar-circle {
            width: 50px; height: 50px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: bold; margin-right: 15px; flex-shrink: 0;
            text-transform: uppercase;
        }
        
        .avatar-img {
            width: 50px; height: 50px; border-radius: 50%; object-fit: cover;
            margin-right: 15px; flex-shrink: 0; border: 2px solid #e0e7ff;
        }

        .badge-super { background: #e0e7ff; color: #4338ca; padding: 5px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .badge-manager { background: #ecfdf5; color: #047857; padding: 5px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .btn-icon { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: none; cursor: pointer; margin-left: 5px; transition: 0.2s; }
        .btn-edit { background: #f3f4f6; color: #4b5563; }
        .btn-edit:hover { background: #dbeafe; color: #2563eb; }
        .btn-del { background: #fee2e2; color: #ef4444; }
        .btn-del:hover { background: #dc2626; color: white; }
    </style>
</head>
<body>
    <?php include('include/sidebar.php'); ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">System Administrators</h1>
                <p class="page-subtitle">Manage access control and permissions.</p>
            </div>
            
            <?php if ($is_super): ?>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-user-plus me-2"></i> New Admin
            </button>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-md-12">
                <?php 
                $admins = $conn->query("SELECT * FROM admin ORDER BY id ASC");
                if($admins && $admins->num_rows > 0):
                    while($row = $admins->fetch_assoc()):
                        $row_is_super = ($row['role'] == 'super_admin');
                        $badge_class = $row_is_super ? 'badge-super' : 'badge-manager';
                        $role_text = $row_is_super ? 'Super Admin' : 'Manager';
                        
                        // 智能获取头像
                        $db_img = !empty($row['avatar']) ? $row['avatar'] : ($row['profile_picture'] ?? '');
                        $img_path = '';
                        if (!empty($db_img)) {
                            if (strpos($db_img, 'assets/') === 0) {
                                $img_path = '../' . $db_img; 
                            } elseif (strpos($db_img, '../') === 0 || strpos($db_img, 'http') === 0) {
                                $img_path = $db_img; 
                            } else {
                                $img_path = '../assets/uploads/' . $db_img; 
                            }
                        }
                        $initial = strtoupper(substr($row['username'], 0, 1));
                ?>
                <div class="admin-card">
                    <div style="display:flex; align-items:center;">
                        
                        <?php if (!empty($img_path)): ?>
                            <img src="<?php echo htmlspecialchars($img_path); ?>" 
                                 class="avatar-img" 
                                 alt="Avatar" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"> 
                                 <div class="avatar-circle" style="display:none;"><?php echo $initial; ?></div>
                        <?php else: ?>
                            <div class="avatar-circle"><?php echo $initial; ?></div>
                        <?php endif; ?>

                        <div>
                            <h5 style="margin:0; font-weight:700; color:#1f2937;"><?php echo htmlspecialchars($row['username']); ?></h5>
                            <span class="<?php echo $badge_class; ?>"><?php echo $role_text; ?></span>
                        </div>
                    </div>
                    
                    <div>
                        <?php if ($is_super): ?>
                            <button class="btn-icon btn-edit" onclick='openEditModal(<?php echo json_encode($row); ?>)'>
                                <i class="fas fa-pen"></i>
                            </button>
                            <?php if($row['id'] != $_SESSION['admin_id']): ?>
                                <a href="?del=<?php echo $row['id']; ?>" class="btn-icon btn-del" onclick="return confirm('Delete this admin?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fas fa-lock text-muted" style="margin-right:10px; opacity:0.5;"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; else: ?>
                    <p class="text-muted">No admins found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($is_super): ?>
    <div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
                <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 30px; border-radius: 16px 16px 0 0;">
                    <div style="width:100%; text-align:center;">
                        <div style="width:60px; height:60px; background:rgba(255,255,255,0.2); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:15px;">
                            <i class="fas fa-user-plus" style="font-size:30px;"></i>
                        </div>
                        <h4 class="modal-title fw-bold" id="modalTitle">New Admin</h4>
                        <p style="opacity:0.9; font-size:0.9rem; margin:0;">Grant system access</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="position:absolute; top:20px; right:20px;"></button>
                </div>
                
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="admin_id" id="admin_id">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size:0.85rem;">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" name="username" id="username" class="form-control border-start-0 bg-light" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size:0.85rem;">Access Level</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-shield-alt text-muted"></i></span>
                                <select name="role" id="role" class="form-select border-start-0 bg-light">
                                    <option value="super_admin">Super Admin (Full Access)</option>
                                    <option value="manager">Manager (Read Only)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size:0.85rem;">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" name="password" id="password" class="form-control border-start-0 bg-light" placeholder="Min 8 chars, 1 Uppercase, 1 Number">
                            </div>
                            <div class="form-text small mt-1 text-muted">
                                <i class="fas fa-info-circle me-1"></i> Strong password required (8+ chars, Uppercase & Number)
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" name="add_admin" id="submitBtn" class="btn btn-primary w-100 py-2 fw-bold" style="border-radius:10px; background: #6366f1; border-color: #6366f1;">
                            Create Access <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var myModal = new bootstrap.Modal(document.getElementById('adminModal'));
        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'New Admin';
            document.getElementById('submitBtn').name = 'add_admin';
            document.getElementById('submitBtn').innerHTML = 'Create Access <i class="fas fa-arrow-right ms-2"></i>';
            document.getElementById('admin_id').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            myModal.show();
        }
        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = 'Edit Admin';
            document.getElementById('submitBtn').name = 'edit_admin';
            document.getElementById('submitBtn').innerHTML = 'Update Access <i class="fas fa-save ms-2"></i>';
            document.getElementById('admin_id').value = data.id;
            document.getElementById('username').value = data.username;
            document.getElementById('role').value = data.role;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            myModal.show();
        }
    </script>
    <?php endif; ?>
</body>
</html>