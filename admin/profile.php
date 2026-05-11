<?php
include('../includes/config.php');
include('../includes/auth.php');

// 设置时区为马来西亚，确保时间显示准确
date_default_timezone_set("Asia/Kuala_Lumpur");

// --- 1. 获取 Admin ID ---
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0;

if ($admin_id == 0) {
    header("Location: index.php");
    exit;
}

$msg = "";
$error = "";

// --- 2. 处理提交 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 查出当前管理员信息
    $stmt = $conn->prepare("SELECT password, avatar FROM admin WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $admin_data = $res->fetch_assoc();
        $db_password = $admin_data['password'];
        $is_password_correct = false;

        // ★★★ 核心修复：双重验证逻辑 ★★★
        // 1. 先尝试用 password_verify 验证（针对加密过的密码）
        if (password_verify($current_password, $db_password)) {
            $is_password_correct = true;
        } 
        // 2. 如果失败，再尝试直接对比（针对数据库里手动输入的明文密码）
        elseif ($current_password === $db_password) {
            $is_password_correct = true;
        }

        if (!$is_password_correct) {
            $error = "Error: Your current password is incorrect."; 
        } 
        else {
            // 密码正确，继续处理
            
            // --- 处理头像上传 ---
            $avatar_path = $admin_data['avatar']; 
            if (!empty($_FILES['profile_avatar']['name'])) {
                $target_dir = "../assets/uploads/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $file_name = time() . "_" . basename($_FILES["profile_avatar"]["name"]);
                $target_file = $target_dir . $file_name;
                if (move_uploaded_file($_FILES["profile_avatar"]["tmp_name"], $target_file)) {
                    $avatar_path = "assets/uploads/" . $file_name;
                } else {
                    $error = "Failed to upload image.";
                }
            }

            // --- 处理新密码逻辑 ---
            if (empty($error)) {
                $password_sql_part = ""; 
                $params = [$username, $avatar_path, $admin_id];
                $types = "ssi";

                if (!empty($new_password)) {
                    // 检查新密码强度
                    if (strlen($new_password) < 8) {
                        $error = "New password is too short! (Min 8 characters)";
                    } 
                    elseif (!preg_match("/[A-Z]/", $new_password) || !preg_match("/[0-9]/", $new_password)) {
                        $error = "New password needs at least 1 Uppercase letter and 1 Number.";
                    }
                    elseif ($new_password !== $confirm_password) {
                        $error = "New password and Confirmation do not match!";
                    } 
                    else {
                        // 更新为新密码（加密）
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $password_sql_part = ", password = ?";
                        $params = [$username, $avatar_path, $new_hash, $admin_id];
                        $types = "sssi";
                    }
                } elseif ($current_password === $db_password && $current_password !== password_hash($current_password, PASSWORD_DEFAULT)) {
                    // ★ 自动修复：如果用户没改密码，但旧密码是明文，趁机把它加密存回去
                    $new_hash = password_hash($current_password, PASSWORD_DEFAULT);
                    $password_sql_part = ", password = ?";
                    $params = [$username, $avatar_path, $new_hash, $admin_id];
                    $types = "sssi";
                }

                // 执行更新
                if (empty($error)) {
                    $sql = "UPDATE admin SET username = ?, avatar = ? $password_sql_part WHERE id = ?";
                    $update = $conn->prepare($sql);
                    $update->bind_param($types, ...$params);

                    if ($update->execute()) {
                        $msg = "Profile updated successfully!";
                        $_SESSION['username'] = $username;
                        if ($avatar_path != $admin_data['avatar']) {
                            $_SESSION['avatar'] = $avatar_path; 
                        }
                    } else {
                        $error = "Database Error: " . $conn->error;
                    }
                }
            }
        }
    } else {
        $error = "Admin account not found.";
    }
}

// --- 3. 重新获取信息用于显示 ---
$stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// 头像路径处理
$initial = strtoupper(substr($user['username'], 0, 1));
$db_img = !empty($user['avatar']) ? $user['avatar'] : ($user['profile_picture'] ?? '');
$img_path = '';
if (!empty($db_img)) {
    if (strpos($db_img, 'assets/') === 0) $img_path = '../' . $db_img;
    elseif (strpos($db_img, '../') === 0) $img_path = $db_img;
    else $img_path = '../assets/uploads/' . $db_img;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .profile-header-card {
            background: white; border-radius: 16px; padding: 40px; text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); height: 100%;
        }
        .profile-avatar-lg {
            width: 120px; height: 120px; border-radius: 50%; object-fit: cover;
            border: 4px solid #e0e7ff; margin-bottom: 20px;
        }
        .profile-initial-lg {
            width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white; font-size: 3rem; font-weight: bold; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px auto; border: 4px solid #e0e7ff;
        }
        .info-card {
            background: white; border-radius: 16px; padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 20px;
        }
        .section-title {
            font-size: 1.1rem; font-weight: 700; color: #1f2937; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .form-control { background-color: #f9fafb; border: 1px solid #e5e7eb; padding: 12px; }
        .form-control:focus { background-color: #fff; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        .session-info-item {
            display: flex; align-items: center; gap: 15px; margin-bottom: 15px;
            padding: 15px; background: #f8fafc; border-radius: 10px; text-align: left;
        }
        .session-icon {
            width: 40px; height: 40px; background: #e0e7ff; color: #4338ca;
            border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        #avatar-upload { display: none; }
    </style>
</head>
<body>
    <?php include('include/sidebar.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
            <p class="page-subtitle">Manage your profile security and preferences.</p>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <strong>Operation Failed:</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="profile-header-card">
                        <div class="position-relative d-inline-block">
                            <?php if (!empty($img_path)): ?>
                                <img src="<?php echo htmlspecialchars($img_path); ?>" class="profile-avatar-lg" id="avatar-preview" onerror="this.style.display='none'; document.getElementById('backup-avatar').style.display='flex'">
                                <div id="backup-avatar" class="profile-initial-lg" style="display:none;"><?php echo $initial; ?></div>
                            <?php else: ?>
                                <img src="" class="profile-avatar-lg" id="avatar-preview" style="display:none;">
                                <div id="backup-avatar" class="profile-initial-lg"><?php echo $initial; ?></div>
                            <?php endif; ?>
                            
                            <label for="avatar-upload" style="position:absolute; bottom:20px; right:0; background:#1f2937; color:white; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; border:3px solid white;">
                                <i class="fas fa-camera" style="font-size:0.8rem;"></i>
                            </label>
                            <input type="file" name="profile_avatar" id="avatar-upload" accept="image/*" onchange="previewImage(event)">
                        </div>

                        <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($user['username']); ?></h3>
                        <p class="text-muted mb-3">System Administrator</p>

                        <div class="d-flex justify-content-center gap-2 mb-4">
                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                <i class="fas fa-shield-alt me-1"></i> ADMIN
                            </span>
                            <span class="badge bg-info-subtle text-info px-3 py-2 rounded-pill">
                                <i class="fas fa-check-circle me-1"></i> VERIFIED
                            </span>
                        </div>

                        <hr class="my-4" style="opacity:0.08">
                        
                        <div class="text-start">
                            <h5 class="text-muted small fw-bold text-uppercase mb-3">Current Session</h5>
                            <div class="session-info-item">
                                <div class="session-icon"><i class="fas fa-globe"></i></div>
                                <div>
                                    <small class="text-muted d-block">IP Address</small>
                                    <strong><?php echo $_SERVER['REMOTE_ADDR']; ?></strong>
                                </div>
                            </div>
                            <div class="session-info-item mb-0">
                                <div class="session-icon"><i class="far fa-clock"></i></div>
                                <div>
                                    <small class="text-muted d-block">Server Time</small>
                                    <strong><?php echo date('d M Y, h:i A'); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="info-card">
                        <div class="section-title text-primary"><i class="fas fa-user-cog"></i> Profile Details</div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold small">Username / Login ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" name="username" class="form-control border-start-0" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="section-title text-danger"><i class="fas fa-lock"></i> Security Zone</div>
                        
                        <div class="mb-4">
                            <label class="form-label text-danger fw-bold small">Current Password (Required)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-danger-subtle border-danger-subtle border-end-0 text-danger"><i class="fas fa-key"></i></span>
                                <input type="password" name="current_password" class="form-control border-danger-subtle" placeholder="Verify your current password" required>
                            </div>
                        </div>

                        <div class="mb-3 pt-3 border-top">
                            <label class="form-label text-muted fw-bold small">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-unlock-alt text-muted"></i></span>
                                <input type="password" name="new_password" class="form-control border-start-0" placeholder="Create new password">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold small">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-check-double text-muted"></i></span>
                                <input type="password" name="confirm_password" class="form-control border-start-0" placeholder="Retype new password">
                            </div>
                        </div>

                        <div class="form-text mt-2 small">
                            <i class="fas fa-shield-alt me-1"></i> Requirements: Min 8 chars, 1 Uppercase, 1 Number.
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold" style="border-radius:10px;">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('avatar-preview');
                var backup = document.getElementById('backup-avatar');
                output.src = reader.result;
                output.style.display = 'block';
                if(backup) backup.style.display = 'none';
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>