<?php
session_start();
include('../includes/config.php');

$error = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. 使用 trim 去除首尾空格，防止因多打空格导致错误
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // ★★★ 核心修复：只根据用户名查询 (不要在 SQL 里查密码) ★★★
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // 获取数据库里的密码
        $db_password = $row['password'];

        // ★★★ 双重验证逻辑 ★★★
        if (password_verify($password, $db_password) || $password === $db_password) {
            
            // 【新增】：检查账号是否被 Super Admin 封禁
            if (isset($row['is_active']) && $row['is_active'] == 0) {
                $error = "Access Denied: Your account has been suspended by Super Admin.";
            } else {
                // --- 登录成功 ---
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $username;
                
                // ★★★ 兼容修复：同时设置 admin_id 和 id ★★★
                $_SESSION['admin_id'] = $row['id']; 
                $_SESSION['id'] = $row['id']; 
                $_SESSION['role'] = $row['role']; 
                
                // 【新增】：将细粒度权限存入 Session，用于控制页面和菜单访问
                $_SESSION['perm_fleet'] = $row['perm_fleet'];
                $_SESSION['perm_bookings'] = $row['perm_bookings'];
                $_SESSION['perm_users'] = $row['perm_users'];
                
                // 设置时区
                date_default_timezone_set('Asia/Kuala_Lumpur');
                
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Invalid Password";
        }
    } else {
        $error = "Username not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Car Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
/* 1. 背景设置 */
        body.login-body {
            background: linear-gradient(rgba(0, 0, 0, 0.50), rgba(0, 0, 0, 0.50)), url('../assets/img/FYP CARRENTAL BG.jpeg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
            position: relative;
            z-index: 0;
        }

.login-card {
    background: rgba(25, 25, 25, 0.9);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    position: relative;
    box-shadow: 0 0 30px rgba(79, 70, 229, 0.3);
    z-index: 10;
}

        .brand-logo-wrapper {
            position: absolute;
            top: -70px;
            left: 50%;
            transform: translateX(-50%);
            width: 140px;
            z-index: 2;
        }

        .brand-logo-wrapper img {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.6));
        }

        .login-card h2 {
            font-family: 'Outfit', sans-serif;
            margin-top: 20px; 
            font-size: 1.8rem;
            color: #fff;
            letter-spacing: 1px;
        }

        .login-card h2 span {
            color: #ef4444;
            font-weight: 500;
        }

        .login-card > p {
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group .left-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            font-size: 1rem;
        }

        .input-group .auth-input {
            width: 100%;
            padding: 14px 15px 14px 45px; 
            border-radius: 10px;
            border: 1px solid #333;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            font-size: 1rem;
            transition: 0.3s;
            box-sizing: border-box;
        }

        .input-group .auth-input:focus {
            outline: none;
            border-color: #4f46e5;
        }

        .input-group .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            cursor: pointer;
            visibility: hidden;
            opacity: 0;
            transition: 0.3s;
        }

        .input-group .toggle-password.show {
            visibility: visible;
            opacity: 1;
        }

        /* ★★★ 新增：忘记密码链接样式 ★★★ */
        .forgot-link {
            display: block;
            text-align: right;
            color: #aaa;
            font-size: 0.85rem;
            text-decoration: none;
            margin-top: -10px; /* 让它稍微贴近密码框 */
            margin-bottom: 20px;
            transition: color 0.3s;
        }

        .forgot-link:hover {
            color: #4f46e5;
        }

        .btn-login {
            background: linear-gradient(90deg, #4f46e5, #3b82f6); 
            color: #fff;
            border: none;
            padding: 14px 25px;
            border-radius: 10px;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .btn-login:hover {
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.4);
            transform: translateY(-2px);
        }

        .login-footer {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            text-align: center;
            color: #ccc;
            z-index: 1;
        }

        .error-box {
            background: rgba(220, 38, 38, 0.15); 
            color: #ff6b6b; 
            border: 1px solid rgba(220, 38, 38, 0.3);
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            font-size: 0.9rem; 
            display: flex; 
            align-items: center; 
            gap: 10px;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="login-body">

    <div class="login-card">
        <div class="brand-logo-wrapper">
        
        </div>
        
        <h2>CAR RENTAL <span>Admin</span></h2>
        <p>Secure Fleet Management System</p>
        
        <?php if(!empty($error)): ?>
            <div id="loginError" class="error-box">
                <i class="fas fa-exclamation-circle"></i> 
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="input-group">
                <i class="fas fa-user left-icon"></i>
                <input type="text" name="username" class="auth-input" placeholder="Username" required autocomplete="off" oninput="hideError()">
            </div>
            
            <div class="input-group" style="margin-bottom: 15px;">
                <i class="fas fa-lock left-icon"></i>
                <input type="password" name="password" id="password" class="auth-input" placeholder="Password" required oninput="hideError()">
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>

            <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
            
            <button type="submit" class="btn-login">
                Access Dashboard <i class="fas fa-arrow-right"></i>
            </button>
        </form>
    </div>

    <div class="login-footer">
        <p style="opacity: 0.6; font-size: 0.85rem; letter-spacing: 0.5px;">
            &copy; <?php echo date('Y'); ?> Car Rental System <span style="opacity: 0.5;">|</span> v1.2.0
        </p>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');

        passwordInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                togglePassword.classList.add('show');
            } else {
                togglePassword.classList.remove('show');
            }
        });

        togglePassword.addEventListener('click', function (e) {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        function hideError() {
            const errorBox = document.getElementById('loginError');
            if (errorBox) {
                errorBox.style.display = 'none'; 
            }
        }
    </script>

</body>
</html>