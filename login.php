<?php 
session_start();
include("config.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {

        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];

                header("Location: homepage.php");
                exit();

            } else {
                $error = "Wrong password.";
            }

        } else {
            $error = "User not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toyota Car Selling</title>

    <style>
        :root {
            --toyota-red: #e60012;
            --dark-red: #b0000e;
            --black: #101010;
            --soft-black: #1b1b1b;
            --white: #ffffff;
            --light-bg: #f5f5f5;
            --gray: #777777;
            --border: #e5e5e5;
            --shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", "Poppins", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow-x: hidden;
            background: var(--black);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background:
                linear-gradient(120deg, rgba(0, 0, 0, 0.82), rgba(0, 0, 0, 0.58)),
                url("https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=1920") center/cover no-repeat;
            z-index: -3;
        }

        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 18% 18%, rgba(230, 0, 18, 0.34), transparent 26%),
                radial-gradient(circle at 85% 85%, rgba(230, 0, 18, 0.30), transparent 28%),
                linear-gradient(135deg, rgba(0, 0, 0, 0.28), rgba(255, 255, 255, 0.04));
            z-index: -2;
        }

        .floating-shape {
            position: fixed;
            border-radius: 50%;
            z-index: -1;
            pointer-events: none;
            animation: float 7s ease-in-out infinite;
        }

        .shape-1 {
            width: 360px;
            height: 360px;
            top: -130px;
            right: -100px;
            background: rgba(230, 0, 18, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .shape-2 {
            width: 210px;
            height: 210px;
            bottom: 60px;
            left: -80px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            animation-delay: 1s;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            right: 80px;
            bottom: 120px;
            background: rgba(230, 0, 18, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.10);
            animation-delay: 2s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-22px) scale(1.05);
            }
        }

        .login-container {
            max-width: 470px;
            width: 100%;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(35px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 34px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
        }

        .login-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            height: 6px;
            width: 100%;
            background: linear-gradient(90deg, var(--toyota-red), #ff4b55, var(--toyota-red));
            z-index: 5;
        }

        .login-header {
            background:
                radial-gradient(circle at right bottom, rgba(230, 0, 18, 0.22), transparent 35%),
                linear-gradient(135deg, #0d0d0d, #1f1f1f);
            padding: 44px 34px 38px;
            text-align: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: "TOYOTA";
            position: absolute;
            top: 16px;
            left: 22px;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 4px;
            color: rgba(255, 255, 255, 0.16);
        }

        .login-header::after {
            content: "🚘";
            position: absolute;
            font-size: 96px;
            opacity: 0.10;
            bottom: -24px;
            right: 18px;
        }

        .brand-badge {
            width: 72px;
            height: 72px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--toyota-red), var(--dark-red));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 15px 35px rgba(230, 0, 18, 0.35);
            border: 4px solid rgba(255, 255, 255, 0.10);
        }

        .login-header h1 {
            font-size: 34px;
            font-weight: 900;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .login-header h1 span {
            color: var(--toyota-red);
        }

        .login-header p {
            font-size: 14px;
            color: #d6d6d6;
            line-height: 1.6;
        }

        .login-form {
            padding: 38px 36px 34px;
            background:
                linear-gradient(180deg, #ffffff, #fafafa);
        }

        .form-group {
            margin-bottom: 23px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 900;
            color: var(--black);
            margin-bottom: 9px;
        }

        .form-label .required {
            color: var(--toyota-red);
            margin-left: 3px;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 17px;
            color: var(--toyota-red);
            z-index: 1;
        }

        .form-input {
            width: 100%;
            padding: 16px 50px 16px 52px;
            font-size: 14px;
            border: 2px solid var(--border);
            border-radius: 18px;
            transition: all 0.3s ease;
            font-family: inherit;
            background: var(--white);
            color: var(--black);
        }

        .form-input::placeholder {
            color: #9a9a9a;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--toyota-red);
            box-shadow: 0 0 0 5px rgba(230, 0, 18, 0.10);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: #f3f3f3;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: var(--gray);
            transition: 0.3s;
            z-index: 1;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            background: var(--toyota-red);
            color: var(--white);
        }

        .error-message {
            background: #fff1f2;
            color: var(--dark-red);
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 25px;
            font-size: 13px;
            font-weight: 700;
            border-left: 5px solid var(--toyota-red);
            line-height: 1.5;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--toyota-red), var(--dark-red));
            color: var(--white);
            border: none;
            border-radius: 999px;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 15px 28px rgba(230, 0, 18, 0.24);
        }

        .login-btn span {
            transition: 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 36px rgba(230, 0, 18, 0.34);
            background: linear-gradient(135deg, #ff1b2b, var(--dark-red));
        }

        .login-btn:hover span {
            transform: translateX(5px);
        }

        .extra-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 26px;
            padding-top: 22px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            font-size: 13px;
            gap: 14px;
        }

        .register-link {
            color: #333333;
        }

        .register-link a {
            color: var(--black);
            text-decoration: none;
            font-weight: 900;
            transition: 0.3s;
        }

        .register-link a:hover {
            color: var(--toyota-red);
        }

        .forgot-link a {
            color: #777777;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: 0.3s;
        }

        .forgot-link a:hover {
            color: var(--toyota-red);
        }

        .mini-footer {
            margin-top: 18px;
            text-align: center;
            color: rgba(255, 255, 255, 0.72);
            font-size: 12px;
            letter-spacing: 0.3px;
        }

        .mini-footer strong {
            color: var(--white);
        }

        @media (max-width: 500px) {
            body {
                padding: 18px;
            }

            .login-container {
                max-width: 100%;
            }

            .login-header {
                padding: 36px 24px 32px;
            }

            .login-header h1 {
                font-size: 28px;
            }

            .login-form {
                padding: 30px 24px 30px;
            }

            .extra-links {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .shape-1 {
                width: 260px;
                height: 260px;
            }

            .shape-2,
            .shape-3 {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-badge">🚗</div>
                <h1>Welcome <span>Back</span></h1>
                <p>Login to continue your Toyota car buying journey</p>
            </div>

            <div class="login-form">
                <?php if ($error): ?>
                    <div class="error-message">
                        ⚠️ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-icon">✉️</span>
                            <input type="email" name="email" class="form-input" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="password" id="passwordInput" class="form-input" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                👁️
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">
                        Login <span>→</span>
                    </button>
                </form>

                <div class="extra-links">
                    <div class="register-link">
                        Don't have an account? <a href="register.php">Register here</a>
                    </div>
                    <div class="forgot-link">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mini-footer">
            <strong>Toyota Car Selling</strong> · Secure User Login
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('passwordInput');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    </script>

</body>
</html>