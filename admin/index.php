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
        
        $db_password = $row['password'];

        if (password_verify($password, $db_password) || $password === $db_password) {
            
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $username;
            $_SESSION['admin_id'] = $row['id']; 
            $_SESSION['id'] = $row['id']; 
            $_SESSION['role'] = $row['role']; 
            
            date_default_timezone_set('Asia/Kuala_Lumpur');
            
            header("Location: dashboard.php");
            exit;
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
    <title>Admin Login | Toyota Dealership</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .error-box {
            background: rgba(220, 38, 38, 0.1); 
            color: #ef4444; 
            border: 1px solid rgba(220, 38, 38, 0.2);
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
        
        /* =========================================
           ★★★ 3D 立体硬币翻转 & 闪光特效 ★★★
        ========================================= */

        /* 1. 左右 3D 翻转动画 (rotateY 产生前后翻转效果) */
        @keyframes spin3DLeft {
            0% { transform: translateY(-50%) perspective(1000px) rotateY(0deg); }
            100% { transform: translateY(-50%) perspective(1000px) rotateY(-360deg); }
        }
        @keyframes spin3DRight {
            0% { transform: translateY(-50%) perspective(1000px) rotateY(0deg); }
            100% { transform: translateY(-50%) perspective(1000px) rotateY(360deg); }
        }

        /* 2. 巨轮的共用设定 */
        .side-logo {
            position: absolute;
            top: 50%;
            width: 260px;
            height: auto;
            opacity: 0.95;
            z-index: 1;
            /* 开启 3D 渲染模式 */
            transform-style: preserve-3d;
            
            /* 闪闪发光 + 立体阴影 */
            filter: 
                drop-shadow(0 0 10px #ffffff) 
                drop-shadow(0 0 25px rgba(255,255,255,0.7)) 
                drop-shadow(15px 15px 25px rgba(0,0,0,0.8));
            
            transition: all 0.5s ease;
        }
        
        .side-logo:hover {
            opacity: 1;
            filter: 
                drop-shadow(0 0 20px #ffffff)
                drop-shadow(0 0 40px rgba(255,255,255,0.9))
                drop-shadow(20px 20px 30px rgba(0,0,0,0.9));
        }

        /* 独立控制左右动画速度 (12秒翻转一圈) */
        .left-logo { 
            left: 12%; 
            animation: spin3DLeft 12s linear infinite; 
        }
        .right-logo { 
            right: 12%; 
            animation: spin3DRight 12s linear infinite; 
        }

        /* 3. 顶部超大静态 Logo (已向上移动) */
        .top-main-logo {
            width: 200px;
            /* ★ 核心修改：增加下边距，并使用 translateY 让它向上跳起 ★ */
            margin-bottom: 15px; 
            transform: translateY(-20px); 
            
            z-index: 20; 
            position: relative;
            filter: 
                drop-shadow(0 0 15px #ffffff)
                drop-shadow(0 0 25px rgba(255,255,255,0.6))
                drop-shadow(10px 20px 25px rgba(0,0,0,0.9));
        }

        /* 4. 确保登录卡片在最上层，给文字留空间 */
        .login-card {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 35px;
        }
    </style>
</head>
<body class="login-body">
    
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>
    <div class="circle circle-3"></div>

    <img src="http://localhost/toyotacarsellingsystem/assets/img/toyota-car-logo.png" class="side-logo left-logo" alt="Left Logo">

    <img src="http://localhost/toyotacarsellingsystem/assets/img/toyota-car-logo.png" class="side-logo right-logo" alt="Right Logo">

    <img src="http://localhost/toyotacarsellingsystem/assets/img/3-35231_toyota-logo-3d.png" class="top-main-logo" alt="Top Logo">

    <div class="login-card">
        
        <h1 style="font-family: 'Arial Black', sans-serif; font-size: 2.5rem; letter-spacing: 6px; color: #ffffff; margin: 0 0 5px 0; text-transform: uppercase;">TOYOTA</h1>
        <h2 style="font-size: 1.2rem; font-weight: 700; color: #ffffff; margin: 0 0 5px 0; font-family: 'Inter', sans-serif;">Toyota Dealership Admin</h2>
        <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 30px;">Secure Sales Management System</p>
        
        <?php if(!empty($error)): ?>
            <div id="loginError" class="error-box">
                <i class="fas fa-exclamation-circle"></i> 
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <form method="post" style="width: 100%;">
            <div class="input-group">
                <i class="fas fa-user left-icon"></i>
                <input type="text" name="username" class="auth-input" placeholder="Username" required autocomplete="off" oninput="hideError()">
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock left-icon"></i>
                <input type="password" name="password" id="password" class="auth-input" placeholder="Password" required oninput="hideError()">
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>
            
            <button type="submit" class="btn-login">
                Access Dashboard <i class="fas fa-arrow-right"></i>
            </button>
        </form>
    </div>

    <div class="login-footer">
        <p style="opacity: 0.6; font-size: 0.85rem; letter-spacing: 0.5px;">
            &copy; <?php echo date('Y'); ?> Toyota Sales System <span style="opacity: 0.5;">|</span> v1.2.0
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