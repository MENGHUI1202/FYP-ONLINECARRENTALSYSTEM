<?php
session_start();
include('../includes/config.php');

$msg = "";
$error = "";
$valid_token = false;
$admin_email = "";

// 1. 检查网址里有没有 token
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);

    // 2. 去数据库验证 token，并且确保它没有过期 (token_expiry > NOW())
    $stmt = $conn->prepare("SELECT email FROM admin WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Token 有效
        $valid_token = true;
        $row = $result->fetch_assoc();
        $admin_email = $row['email'];

        // 3. 处理表单提交 (用户输入了新密码)
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    
                    // ★★★ 将新密码安全加密 (Hash) ★★★
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // ★★★ 更新数据库：存入新密码，并且清空 token 和过期时间 ★★★
                    $update_stmt = $conn->prepare("UPDATE admin SET password = ?, reset_token = NULL, token_expiry = NULL WHERE email = ?");
                    $update_stmt->bind_param("ss", $hashed_password, $admin_email);

                    if ($update_stmt->execute()) {
                        $msg = "Password has been successfully reset! You can now login.";
                        $valid_token = false; // 隐藏表单
                    } else {
                        $error = "System error. Could not update password.";
                    }
                } else {
                    $error = "Password must be at least 6 characters long.";
                }
            } else {
                $error = "Passwords do not match.";
            }
        }
    } else {
        // Token 无效或已过期
        $error = "This password reset link is invalid or has expired. Please request a new one.";
    }
} else {
    $error = "No reset token provided. Access denied.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password | Fleet Command Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        /* 复用相同的 UI 风格 */
        :root {
            --brand-primary: #3b82f6;
            --brand-secondary: #4f46e5;
            --bg-overlay: rgba(10, 12, 16, 0.75);
            --glass-bg: rgba(20, 24, 30, 0.65);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
        }

        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Inter', sans-serif; background-color: #000; }

        .portal-wrapper { position: relative; width: 100%; height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(var(--bg-overlay), var(--bg-overlay)), url('../assets/img/FYP CARRENTAL BG.jpeg') no-repeat center center fixed; background-size: cover; }

        .login-panel { position: relative; z-index: 10; width: 100%; max-width: 440px; background: var(--glass-bg); backdrop-filter: blur(24px); border-radius: 16px; border: 1px solid var(--glass-border); padding: 45px 40px; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6); }

        .portal-title h2 { font-family: 'Space Grotesk', sans-serif; font-size: 1.6rem; color: var(--text-main); margin: 0 0 4px 0; }
        .system-desc { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 25px; line-height: 1.5; border-bottom: 1px solid var(--glass-border); padding-bottom: 20px; }
        
        .input-group { position: relative; margin-bottom: 24px; }
        .input-group label { display: block; color: var(--text-muted); font-size: 0.8rem; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; }
        .input-group .left-icon { position: absolute; left: 16px; bottom: 15px; color: #64748b; font-size: 1rem; }
        .auth-input { width: 100%; padding: 14px 16px 14px 45px; background: rgba(0, 0, 0, 0.4); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-main); font-size: 1rem; box-sizing: border-box; }
        .auth-input:focus { outline: none; border-color: var(--brand-primary); }

        .btn-submit { background: linear-gradient(135deg, var(--brand-secondary), var(--brand-primary)); color: #fff; border: none; padding: 14px 25px; border-radius: 8px; width: 100%; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4); }

        .back-link { display: inline-block; margin-top: 20px; color: #fff; text-decoration: none; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 5px; transition: 0.3s;}
        .back-link:hover { background: rgba(255,255,255,0.2); }

        .msg-box { background: rgba(16, 185, 129, 0.1); color: #34d399; border-left: 3px solid #10b981; padding: 12px 16px; border-radius: 4px; margin-bottom: 24px; font-size: 0.85rem; }
        .error-box { background: rgba(220, 38, 38, 0.1); color: #f87171; border-left: 3px solid #ef4444; padding: 12px 16px; border-radius: 4px; margin-bottom: 24px; font-size: 0.85rem; line-height: 1.4; }
    </style>
</head>
<body>

    <div class="portal-wrapper">
        <div class="login-panel">
            
            <div class="portal-title">
                <h2>Set New Password</h2>
            </div>
            
            <div class="system-desc">
                Please enter your new secure password below.
            </div>

            <?php if(!empty($msg)): ?>
                <div class="msg-box">
                    <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
                </div>
                <div style="text-align: center;">
                    <a href="index.php" class="back-link">Return to Login</a>
                </div>
            <?php endif; ?>

            <?php if(!empty($error)): ?>
                <div class="error-box">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
                <?php if(!$valid_token && empty($msg)): ?>
                    <div style="text-align: center;">
                        <a href="forgot-password.php" class="back-link">Request New Link</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($valid_token): ?>
                <form method="post">
                    <div class="input-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="auth-input" placeholder="At least 6 characters" required>
                        <i class="fas fa-lock left-icon"></i>
                    </div>
                    
                    <div class="input-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="auth-input" placeholder="Repeat new password" required>
                        <i class="fas fa-lock left-icon"></i>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        Update Password <i class="fas fa-save" style="margin-left: 5px;"></i>
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>