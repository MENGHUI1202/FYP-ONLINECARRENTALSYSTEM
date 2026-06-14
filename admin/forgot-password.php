<?php
// admin/forgot-password.php
session_start();
include('../includes/config.php');

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
$msg = "";
$error = "";
$debug_link = ""; 

// 【关键增强】计算当前剩余的冷却时间，传给前端 JS
$remaining_time = 0;
if (isset($_SESSION['last_mail_time'])) {
    $time_passed = time() - $_SESSION['last_mail_time'];
    if ($time_passed < 60) {
        $remaining_time = 60 - $time_passed;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // 1. 检查后端限流
    if ($remaining_time > 0) {
        $error = "Security Policy: Please wait {$remaining_time} seconds before sending another link.";
    } 
    // 【新增安全增强】检查邮箱格式是否合法，防止 SQL 注入或恶意字符
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format. Please check and try again.";
    } 
    else {
        // 2. 检查邮箱在数据库中是否存在
        $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            // 3. 如果存在，生成一个安全的随机 Token 和过期时间 (30分钟后)
            $token = bin2hex(random_bytes(32)); 
            date_default_timezone_set('Asia/Kuala_Lumpur');
            $expiry = date("Y-m-d H:i:s", strtotime('+30 minutes'));

            // 4. 把 Token 和过期时间存进数据库
            $update_stmt = $conn->prepare("UPDATE admin SET reset_token = ?, token_expiry = ? WHERE email = ?");
            $update_stmt->bind_param("sss", $token, $expiry, $email);
            
            if ($update_stmt->execute()) {
                
                // 5. 准备重置密码的链接
                $reset_link = "http://localhost/onlinecarrentalsystem/admin/reset-password.php?token=" . $token;

                // ★★★ 6. 实例化 PHPMailer 发送真实邮件 ★★★
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                try {
                    // 服务器配置
                    $mail->isSMTP();                                            
                    $mail->Host       = 'smtp.gmail.com';                     
                    $mail->SMTPAuth   = true;                                   
                    
                    // 使用你提供的凭据
                    $mail->Username   = 'carrentalmmu@gmail.com';             
                    $mail->Password   = 'ssxg iwld xece abqd';                   
                    
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; 
                    $mail->Port       = 465;                                    

                    // 设置发件人和收件人
                    $mail->setFrom('carrentalmmu@gmail.com', 'DONT BE SCARE');
                    $mail->addAddress($email);                                  

                    // 设置邮件内容
                    $mail->isHTML(true);                                        
                    $mail->Subject = 'Password Reset Request - DONT BE SCARE';
                    
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px;'>
                            <h2 style='color: #1e293b;'>Password Reset Request</h2>
                            <p style='color: #475569; line-height: 1.6;'>Hello,</p>
                            <p style='color: #475569; line-height: 1.6;'>We received a request to reset the password for your administrator account. Please click the button below to set a new password. <strong>This link is valid for 30 minutes.</strong></p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='{$reset_link}' style='padding: 12px 24px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Reset My Password</a>
                            </div>
                            <p style='color: #94a3b8; font-size: 0.85em; margin-top: 30px;'>If you did not request a password reset, please ignore this email.</p>
                        </div>
                    ";
                    
                    $mail->AltBody = "Please copy and paste this link into your browser to reset your password: {$reset_link}";

                    $mail->send();
                    
                    // 邮件发送成功，重置冷却时间
                    $_SESSION['last_mail_time'] = time();
                    $remaining_time = 60; // 立刻通知前端进入 60 秒冷却
                    $msg = "A password reset link has been successfully sent to your email.";

                } catch (Exception $e) {
                    $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $error = "Database update failed.";
            }
        } else {
            // 模糊提示，依然触发 60 秒冷却以防恶意探测邮箱是否存在
            $_SESSION['last_mail_time'] = time();
            $remaining_time = 60;
            $msg = "If that email address exists in our system, we have sent a password reset link.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Fleet Command Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
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

        .portal-wrapper {
            position: relative; width: 100%; height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(var(--bg-overlay), var(--bg-overlay)), url('../assets/img/FYP CARRENTAL BG.jpeg') no-repeat center center fixed; background-size: cover;
        }

        .login-panel {
            position: relative; z-index: 10; width: 100%; max-width: 440px; background: var(--glass-bg); backdrop-filter: blur(24px); border-radius: 16px; border: 1px solid var(--glass-border); padding: 45px 40px; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6);
        }

        .portal-title h2 { font-family: 'Space Grotesk', sans-serif; font-size: 1.6rem; color: var(--text-main); margin: 0 0 4px 0; }
        .system-desc { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 25px; line-height: 1.5; border-bottom: 1px solid var(--glass-border); padding-bottom: 20px; }
        
        .input-group { position: relative; margin-bottom: 24px; }
        .input-group label { display: block; color: var(--text-muted); font-size: 0.8rem; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; }
        .input-group .left-icon { position: absolute; left: 16px; bottom: 15px; color: #64748b; font-size: 1rem; }
        
        .auth-input { width: 100%; padding: 14px 16px 14px 45px; background: rgba(0, 0, 0, 0.4); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-main); font-size: 1rem; box-sizing: border-box; }
        .auth-input:focus { outline: none; border-color: var(--brand-primary); }

        .btn-submit { background: linear-gradient(135deg, var(--brand-secondary), var(--brand-primary)); color: #fff; border: none; padding: 14px 25px; border-radius: 8px; width: 100%; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4); }
        .btn-submit:disabled { background: #475569; color: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; }

        .back-link { display: block; text-align: center; color: var(--text-muted); font-size: 0.85rem; text-decoration: none; margin-top: 25px; transition: color 0.3s; }
        .back-link:hover { color: var(--brand-primary); }

        .msg-box { background: rgba(16, 185, 129, 0.1); color: #34d399; border-left: 3px solid #10b981; padding: 12px 16px; border-radius: 4px; margin-bottom: 24px; font-size: 0.85rem; }
        .error-box { background: rgba(220, 38, 38, 0.1); color: #f87171; border-left: 3px solid #ef4444; padding: 12px 16px; border-radius: 4px; margin-bottom: 24px; font-size: 0.85rem; }
    </style>
</head>
<body>

    <div class="portal-wrapper">
        <div class="login-panel">
            <div class="portal-title">
                <h2>Reset Password</h2>
            </div>
            
            <div class="system-desc">
                Enter your registered administrator email address. We will send you a real reset link to your inbox.
            </div>
            
            <?php if(!empty($msg)): ?>
                <div class="msg-box">
                    <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($error)): ?>
                <div class="error-box">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" id="resetForm">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="auth-input" placeholder="admin@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required autocomplete="off">
                    <i class="fas fa-envelope left-icon"></i>
                </div>
                
                <button type="submit" class="btn-submit" id="submitBtn">
                    Send Reset Link
                </button>
            </form>

            <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>

    <script>
        const form = document.getElementById('resetForm');
        const submitBtn = document.getElementById('submitBtn');
        
        // 【核心机制】从 PHP 抓取真实的剩余冷却时间
        let timeLeft = <?php echo $remaining_time; ?>;

        // 如果页面加载时已经在冷却中，直接启动倒计时
        if (timeLeft > 0) {
            startCountdown();
        }

        form.addEventListener('submit', function(e) {
            // 防御机制：如果在冷却期内尝试强行提交，拦截请求
            if (timeLeft > 0) {
                e.preventDefault();
                return;
            }
            
            // 点击后立刻锁定，防止网络卡顿导致的重复提交
            submitBtn.disabled = true;
            submitBtn.innerText = "Sending...";
        });

        function startCountdown() {
            submitBtn.disabled = true;
            submitBtn.innerText = "Wait " + timeLeft + "s";
            
            let countdown = setInterval(function() {
                timeLeft--;
                submitBtn.innerText = "Wait " + timeLeft + "s";
                
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    submitBtn.disabled = false;
                    submitBtn.innerText = "Send Reset Link";
                }
            }, 1000);
        }
    </script>
</body>
</html>