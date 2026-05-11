<?php
include('../includes/config.php');
include('../includes/auth.php');
if(!isset($_SESSION['admin_logged_in'])) { header("Location: index.php"); exit; }

// --- 1. 逻辑处理 ---

// A. 删除用户
if (isset($_GET['delete'])) {
    if (($_SESSION['role'] ?? '') !== 'super_admin') {
        die("Access Denied: You do not have permission to delete users.");
    }

    $id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: manage_users.php?msg=deleted"); exit;
}

// B. 编辑用户
if (isset($_POST['edit_user'])) {
    if (($_SESSION['role'] ?? '') !== 'super_admin') {
        die("Access Denied: You do not have permission to edit users.");
    }

    $id = $_POST['user_id'];
    $name = $_POST['fullname']; // 获取表单里的全名
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    // ★★★ 修复1：数据库字段名为 name，不是 fullname ★★★
    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $phone, $id);
    
    if ($stmt->execute()) {
        header("Location: manage_users.php?msg=updated"); exit;
    } else {
        die("Error updating user: " . $conn->error);
    }
}

// --- 2. 顶部统计数据 ---
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$new_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetch_assoc()['c'];

// ★★★ 修复2：状态字段改为 booking_status ★★★
$active_rentals = $conn->query("SELECT COUNT(DISTINCT user_id) as c FROM bookings WHERE booking_status = 'Confirmed'")->fetch_assoc()['c'];

// --- 3. 核心查询 ---
// 使用 grand_total 统计消费额更准确
$sql = "SELECT u.*, 
        COUNT(b.id) as booking_count, 
        COALESCE(SUM(b.grand_total), 0) as total_spent 
        FROM users u 
        LEFT JOIN bookings b ON u.id = b.user_id 
        GROUP BY u.id 
        ORDER BY u.id DESC";
$users = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include('include/sidebar.php'); ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Customer Insights</h1>
            <p class="page-subtitle">Manage registered users and track their lifetime value.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="card" style="padding: 20px; display:flex; align-items:center; gap: 15px; margin-bottom:0;">
                <div style="width:50px; height:50px; background:#eff6ff; color:#2563eb; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.5rem; color:#1e293b;"><?php echo $total_users; ?></h3>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">Total Customers</p>
                </div>
            </div>
            <div class="card" style="padding: 20px; display:flex; align-items:center; gap: 15px; margin-bottom:0;">
                <div style="width:50px; height:50px; background:#dcfce7; color:#166534; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.5rem; color:#1e293b;"><?php echo $new_users; ?></h3>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">New This Month</p>
                </div>
            </div>
            <div class="card" style="padding: 20px; display:flex; align-items:center; gap: 15px; margin-bottom:0;">
                <div style="width:50px; height:50px; background:#fef3c7; color:#b45309; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="fas fa-car-side"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.5rem; color:#1e293b;"><?php echo $active_rentals; ?></h3>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">Processing Orders</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="userSearch" placeholder="Search name, email...">
                </div>
            </div>

            <div class="table-container">
                <table class="table" id="userTable">
                    <thead>
                        <tr>
                            <th>Customer Profile</th>
                            <th>Contact Info</th>
                            <th>Lifetime Value (LTV)</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <div style="width:42px; height:42px; background:#f1f5f9; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; font-weight:700; color:#64748b; font-size:1rem; border:1px solid #e2e8f0;">
                                        <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700; color:#1e293b; font-size:0.95rem;"><?php echo $row['name']; ?></div>
                                        <div style="font-size:0.8rem; color:#94a3b8;">ID: #<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    <div style="display:flex; align-items:center; gap:8px; font-size:0.9rem; color:#475569;">
                                        <i class="fas fa-envelope" style="color:#cbd5e1; width:16px;"></i> <?php echo $row['email']; ?>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px; font-size:0.9rem; color:#475569;">
                                        <i class="fas fa-phone" style="color:#cbd5e1; width:16px;"></i> <?php echo $row['phone'] ?? '<span style="color:#cbd5e1; font-style:italic;">No phone</span>'; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:700; color:#1e293b;">RM <?php echo number_format($row['total_spent']); ?></div>
                                <div style="font-size:0.8rem; color:#64748b;">
                                    <?php echo $row['booking_count']; ?> Orders
                                </div>
                            </td>
                            <td>
                                <?php if($row['booking_count'] > 5): ?>
                                    <span class="badge-pill" style="background:linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e; border:1px solid #fcd34d;">
                                        <i class="fas fa-crown" style="font-size:0.7rem; margin-right:4px;"></i> VIP
                                    </span>
                                <?php elseif($row['booking_count'] > 0): ?>
                                    <span class="badge-pill" style="background:#f1f5f9; color:#475569;">Regular</span>
                                <?php else: ?>
                                    <span class="badge-pill" style="background:white; border:1px solid #e2e8f0; color:#94a3b8;">New</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#64748b; font-size:0.9rem;">
                                <?php echo date('d M, Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td style="text-align:right;">
                                <?php if(($_SESSION['role'] ?? '') == 'super_admin'): ?>
                                    <button class="btn btn-sm" style="background:#f1f5f9; color:#475569; margin-right:5px;" onclick='openEditModal(<?php echo json_encode($row); ?>)' title="Edit Details">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm" style="background:#fee2e2; color:#ef4444;" onclick="return confirm('Are you sure? All their orders will be affected.')" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#cbd5e1; font-size:0.85rem; display:inline-flex; align-items:center; gap:5px;">
                                        <i class="fas fa-lock"></i> Read Only
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="userModal" class="modal">
        <div class="modal-content" style="width: 450px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:25px; border-bottom:1px solid #f1f5f9; padding-bottom:15px;">
                <h3 style="margin:0; font-size:1.4rem; color:#1e293b;">Edit Customer</h3>
                <span onclick="closeModal()" style="cursor:pointer; font-size:1.5rem; color:#94a3b8; line-height:1;">&times;</span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="user_id" id="user_id">
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullname" id="fullname" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" id="phone" class="form-control" placeholder="e.g. 012-3456789">
                </div>
                
                <div style="text-align:right; margin-top:30px;">
                    <button type="button" class="btn" style="background:#f1f5f9; color:#64748b; margin-right:10px;" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        
        // 打开编辑窗口，并填充当前数据
        function openEditModal(user) {
            document.getElementById('user_id').value = user.id;
            // ★★★ 修复3：使用 user.name 填充 (数据库字段是 name) ★★★
            document.getElementById('fullname').value = user.name; 
            document.getElementById('email').value = user.email;
            document.getElementById('phone').value = user.phone ? user.phone : '';
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        document.getElementById('userSearch').addEventListener('keyup', function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll('#userTable tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
            });
        });
        
        window.onclick = function(e) { if(e.target == modal) closeModal(); }
    </script>
</body>
</html>