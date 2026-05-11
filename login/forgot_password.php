<?php
include("config.php");

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($username) || empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
    } elseif (strlen($new_password) < 8) {
        $message = "Password must be at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {

        $stmt = $conn->prepare("SELECT * FROM users WHERE name=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password=? WHERE name=?");
            $update->bind_param("ss", $hashed, $username);

            if ($update->execute()) {
                $success = true;
                $message = "Password updated successfully! Redirecting to login...";
                echo '<meta http-equiv="refresh" content="2;url=login.php">';
            } else {
                $message = "Error updating password. Please try again.";
            }

        } else {
            $message = "Username not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Toyota Car Selling</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Poppins', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: #0a0a0a;
            position: relative;
            overflow-x: hidden;
        }

        /* 动态网格背景 */
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(227, 25, 39, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(227, 25, 39, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 0;
        }

        /* 发光光晕 */
        .glow {
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(227, 25, 39, 0.15), transparent 70%);
            border-radius: 50%;
            top: 20%;
            left: 50%;
            transform: translateX(-50%);
            filter: blur(60px);
            z-index: 0;
        }

        /* 主容器 */
        .reset-container {
            max-width: 460px;
            width: 100%;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 霓虹边框卡片 */
        .reset-card {
            background: rgba(10, 10, 10, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            overflow: hidden;
            border: 1px solid rgba(227, 25, 39, 0.3);
            box-shadow: 0 0 20px rgba(227, 25, 39, 0.1), 0 20px 40px rgba(0, 0, 0, 0.5);
            transition: all 0.3s;
        }

        .reset-card:hover {
            border-color: rgba(227, 25, 39, 0.6);
            box-shadow: 0 0 30px rgba(227, 25, 39, 0.2), 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        /* Header */
        .reset-header {
            text-align: center;
            padding: 40px 35px 20px 35px;
        }

        .reset-header h1 {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, #e31927);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
        }

        .reset-header p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* 表单区域 */
        .reset-form {
            padding: 20px 35px 40px 35px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .form-label .required {
            color: #e31927;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            font-size: 14px;
            border: 1.5px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            transition: all 0.3s;
            font-family: inherit;
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .form-input:focus {
            outline: none;
            border-color: #e31927;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(227, 25, 39, 0.2);
        }

        /* 错误/成功消息 */
        .error-message {
            background: rgba(227, 25, 39, 0.15);
            color: #ff6b6b;
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 13px;
            border-left: 3px solid #e31927;
        }

        .success-message {
            background: rgba(46, 125, 50, 0.15);
            color: #81c784;
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 13px;
            border-left: 3px solid #2e7d32;
        }

        /* 重置按钮 */
        .reset-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #e31927 0%, #b81520 100%);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .reset-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }

        .reset-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(227, 25, 39, 0.4);
            gap: 15px;
        }

        /* 返回链接 */
        .back-link {
            text-align: center;
            margin-top: 28px;
        }

        .back-link a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .back-link a:hover {
            color: #e31927;
        }

        /* 底部装饰 */
        .footer-note {
            text-align: center;
            margin-top: 25px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.25);
        }

        /* 响应式 */
        @media (max-width: 500px) {
            .reset-container {
                max-width: 95%;
            }
            .reset-header {
                padding: 30px 25px 15px 25px;
            }
            .reset-header h1 {
                font-size: 26px;
            }
            .reset-form {
                padding: 15px 25px 35px 25px;
            }
        }
    </style>
</head>
<body>

    <div class="glow"></div>

    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <h1>Reset Password</h1>
                <p>Enter your details to reset</p>
            </div>

            <div class="reset-form">
                <?php if ($message && !$success): ?>
                    <div class="error-message">
                        ⚠️ <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success-message">
                        ✓ <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Username <span class="required">*</span></label>
                        <input type="text" name="username" class="form-input" placeholder="Enter your username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Password <span class="required">*</span></label>
                        <input type="password" name="new_password" class="form-input" placeholder="Enter new password (min 8 characters)" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" class="form-input" placeholder="Confirm your new password" required>
                    </div>

                    <button type="submit" class="reset-btn">
                        Reset Password →
                    </button>
                </form>

                <div class="back-link">
                    <a href="login.php">← Back to Login</a>
                </div>
                <div class="footer-note">
                    🔒 Secure reset process
                </div>
            </div>
        </div>
    </div>

</body>
</html>