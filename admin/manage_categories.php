<?php
include('../includes/config.php');
include('../includes/auth.php');

// 自动建表并确保字段存在
$conn->query("CREATE TABLE IF NOT EXISTS vehicle_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("UPDATE vehicle_categories SET created_at = NOW() WHERE created_at IS NULL OR created_at = '0000-00-00 00:00:00'");

$msg = "";
$error = "";

function isSuperAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['del'])) {
    if (!isSuperAdmin()) {
        $error = "Access Denied: You do not have permission to perform this action.";
    } else {
        // 1. 处理添加
        if (isset($_POST['add_category'])) {
            $name = trim($_POST['category_name']);
            if (!empty($name)) {
                // ★★★ 专家级防注入升级 ★★★
                $stmt_check = $conn->prepare("SELECT id FROM vehicle_categories WHERE name = ?");
                $stmt_check->bind_param("s", $name);
                $stmt_check->execute();
                
                if ($stmt_check->get_result()->num_rows > 0) {
                    $error = "Category '$name' already exists!";
                } else {
                    $stmt = $conn->prepare("INSERT INTO vehicle_categories (name) VALUES (?)");
                    $stmt->bind_param("s", $name);
                    if ($stmt->execute()) {
                        $msg = "Category added successfully!";
                    } else {
                        $error = "Something went wrong.";
                    }
                }
            }
        }

        // 2. 处理编辑 (加入事务保护)
        if (isset($_POST['edit_category'])) {
            $id = intval($_POST['cat_id']);
            $name = trim($_POST['category_name']);
            
            $stmt_old = $conn->prepare("SELECT name FROM vehicle_categories WHERE id = ?");
            $stmt_old->bind_param("i", $id);
            $stmt_old->execute();
            $old_q = $stmt_old->get_result();
            
            if($old_q && $old_q->num_rows > 0) {
                $old_name = $old_q->fetch_assoc()['name'];
                if (!empty($name)) {
                    // ★★★ 专家级事务处理：确保两个表要么一起成功，要么一起失败 ★★★
                    $conn->begin_transaction();
                    try {
                        $stmt1 = $conn->prepare("UPDATE vehicle_categories SET name=? WHERE id=?");
                        $stmt1->bind_param("si", $name, $id);
                        $stmt1->execute();
                        
                        $stmt2 = $conn->prepare("UPDATE cars SET type=? WHERE type=?");
                        $stmt2->bind_param("ss", $name, $old_name);
                        $stmt2->execute();
                        
                        $conn->commit();
                        $msg = "Category updated successfully!";
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = "Update failed: System rolled back to protect data.";
                    }
                }
            }
        }

        // 3. 处理删除
        if (isset($_GET['del'])) {
            $id = intval($_GET['del']);
            $stmt_cat = $conn->prepare("SELECT name FROM vehicle_categories WHERE id = ?");
            $stmt_cat->bind_param("i", $id);
            $stmt_cat->execute();
            $cat_q = $stmt_cat->get_result();
            
            if ($cat_q && $cat_q->num_rows > 0) {
                $cat_name = $cat_q->fetch_assoc()['name'];
                
                $stmt_check_cars = $conn->prepare("SELECT id FROM cars WHERE type = ? LIMIT 1");
                $stmt_check_cars->bind_param("s", $cat_name);
                $stmt_check_cars->execute();

                if ($stmt_check_cars->get_result()->num_rows > 0) {
                    header("Location: manage_categories.php?err=not_empty");
                    exit;
                } else {
                    $stmt_del = $conn->prepare("DELETE FROM vehicle_categories WHERE id = ?");
                    $stmt_del->bind_param("i", $id);
                    $stmt_del->execute();
                    header("Location: manage_categories.php?msg=deleted"); 
                    exit;
                }
            }
        }
    }
}

if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') $msg = "Category deleted successfully!";
if(isset($_GET['err']) && $_GET['err'] == 'not_empty') $error = "Cannot delete! This category contains vehicles.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- 【你的原版 HTML/CSS 保持 100% 不变】 -->
    <meta charset="UTF-8">
    <title>Manage Categories | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        :root { --primary-color: #4f46e5; --secondary-color: #64748b; --success-color: #10b981; --danger-color: #ef4444; --bg-light: #f3f4f6; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        .main-content { padding: 30px; }
        .stat-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-left: 5px solid var(--primary-color); transition: transform 0.2s; margin-bottom: 30px; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-title { font-size: 0.9rem; color: var(--secondary-color); text-transform: uppercase; font-weight: 600; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #1f2937; margin: 10px 0; }
        .stat-icon { float: right; font-size: 2rem; color: #e0e7ff; }
        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        .table-header { padding: 20px 25px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; }
        .table thead th { background: #f9fafb; color: #4b5563; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; padding: 15px 25px; border-bottom: none; }
        .table tbody td { padding: 15px 25px; color: #374151; font-size: 0.95rem; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
        .action-btn { width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: all 0.2s; margin-right: 5px; }
        .btn-edit { background: #eff6ff; color: var(--primary-color); }
        .btn-edit:hover { background: var(--primary-color); color: white; }
        .btn-del { background: #fef2f2; color: var(--danger-color); }
        .btn-del:hover { background: var(--danger-color); color: white; }
        .badge-count { background: #eef2ff; color: var(--primary-color); padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
    </style>
</head>
<body>
    <?php include('include/sidebar.php'); ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark">Category Management</h2>
                <p class="text-muted">Manage vehicle types and classifications.</p>
            </div>
            <?php if (isSuperAdmin()): ?>
            <button class="btn btn-primary px-4 py-2" onclick="openAddModal()">
                <i class="fas fa-plus me-2"></i> Create New
            </button>
            <?php endif; ?>
        </div>

        <?php if($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>
        <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="fas fa-layer-group stat-icon"></i>
                    <div class="stat-title">Total Categories</div>
                    <div class="stat-value"><?php echo $conn->query("SELECT COUNT(*) as c FROM vehicle_categories")->fetch_assoc()['c']; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="border-left-color: #10b981;">
                    <i class="fas fa-car stat-icon" style="color: #ecfdf5;"></i>
                    <div class="stat-title">Total Vehicles Linked</div>
                    <div class="stat-value"><?php echo $conn->query("SELECT COUNT(*) as c FROM cars WHERE type IS NOT NULL AND type != ''")->fetch_assoc()['c']; ?></div>
                </div>
            </div>
        </div>

        <div class="table-card mt-4">
            <div class="table-header">
                <h5 class="mb-0 fw-bold">All Categories</h5>
                <input type="text" class="form-control" style="width: 250px;" id="catSearch" placeholder="Search..." onkeyup="searchTable()">
            </div>
            <div class="table-responsive">
                <table class="table mb-0" id="catTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category Name</th>
                            <th>Vehicles Count</th>
                            <th>Creation Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sql = "SELECT * FROM vehicle_categories ORDER BY id DESC";
                        $result = $conn->query($sql);
                        $cnt = 1;
                        
                        // ★★★ 优化：预编译统计语句，放在循环外部，提升页面加载速度 ★★★
                        $stmt_count = $conn->prepare("SELECT COUNT(*) as c FROM cars WHERE type = ?");

                        while($row = $result->fetch_assoc()){
                            $cat_name = $row['name'];
                            
                            $stmt_count->bind_param("s", $cat_name);
                            $stmt_count->execute();
                            $v_count = $stmt_count->get_result()->fetch_assoc()['c'];
                        ?>
                        <tr>
                            <td><?php echo $cnt++; ?></td>
                            <td><span class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></span></td>
                            <td><span class="badge-count"><?php echo $v_count; ?> Vehicles</span></td>
                            <td>
                                <span class="text-dark">
                                    <i class="far fa-clock me-1 text-primary"></i> 
                                    <?php echo date("d M Y, H:i", strtotime($row['created_at'])); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if (isSuperAdmin()): ?>
                                    <button class="action-btn btn-edit" onclick='openEditModal(<?php echo json_encode($row); ?>)'><i class="fas fa-pen"></i></button>
                                    <a href="manage_categories.php?del=<?php echo $row['id']; ?>" class="action-btn btn-del" onclick="return confirm('Delete?')"><i class="fas fa-trash-alt"></i></a>
                                <?php else: ?>
                                    <i class="fas fa-lock text-muted"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title fw-bold" id="modalTitle">Add Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="cat_id" id="cat_id">
                        <div class="mb-3"><label class="form-label fw-bold">Name</label><input type="text" name="category_name" id="category_name" class="form-control" required></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" name="add_category" id="submitBtn" class="btn btn-primary">Save</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var myModal = new bootstrap.Modal(document.getElementById('categoryModal'));
        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Add New Category';
            document.getElementById('submitBtn').name = 'add_category';
            document.getElementById('cat_id').value = '';
            document.getElementById('category_name').value = '';
            myModal.show();
        }
        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = 'Edit Category';
            document.getElementById('submitBtn').name = 'edit_category';
            document.getElementById('cat_id').value = data.id;
            document.getElementById('category_name').value = data.name;
            myModal.show();
        }
        function searchTable() {
            var input = document.getElementById("catSearch"), filter = input.value.toUpperCase(), table = document.getElementById("catTable"), tr = table.getElementsByTagName("tr");
            for (var i = 0; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName("td")[1];
                if (td) {
                    if (td.textContent.toUpperCase().indexOf(filter) > -1) tr[i].style.display = "";
                    else tr[i].style.display = "none";
                }       
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>