<?php
include("config.php");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $license = trim($_POST["license"]);
    $address = trim($_POST["address"]);
    $dob = $_POST["dob"];
    $password = $_POST["password"];
    $confirm = $_POST["confirm"];

    if (empty($name) || empty($email) || empty($phone) || empty($license) || empty($dob) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email already registered. Please login.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, date_of_birth, license_number) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssss", $name, $email, $hashed, $phone, $address, $dob, $license);

            if ($stmt->execute()) {
                $success = "Registration successful! Redirecting to login...";
                echo '<meta http-equiv="refresh" content="2;url=login.php">';
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Toyota Car Selling</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --toyota-red: #e50012;
            --dark-red: #99000d;
            --black: #050505;
            --carbon: #0b0d10;
            --dark-card: #11141a;
            --dark-field: #191d25;
            --white: #ffffff;
            --soft-white: #d9dde5;
            --muted: #8b929e;
            --border: rgba(255, 255, 255, 0.12);
            --red-glow: rgba(229, 0, 18, 0.45);
        }

        body {
            font-family: 'Segoe UI', 'Poppins', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 45px 20px;
            position: relative;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 15% 20%, rgba(229, 0, 18, 0.16), transparent 24%),
                radial-gradient(circle at 85% 70%, rgba(229, 0, 18, 0.12), transparent 28%),
                linear-gradient(135deg, #030303 0%, #0b0d10 45%, #15171c 100%);
            color: var(--white);
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                linear-gradient(120deg, transparent 0%, transparent 40%, rgba(255, 255, 255, 0.035) 41%, transparent 42%),
                repeating-linear-gradient(
                    135deg,
                    rgba(255, 255, 255, 0.02) 0px,
                    rgba(255, 255, 255, 0.02) 1px,
                    transparent 1px,
                    transparent 18px
                );
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            width: 620px;
            height: 620px;
            right: -230px;
            top: -190px;
            border-radius: 50%;
            border: 90px solid rgba(229, 0, 18, 0.08);
            box-shadow: 0 0 90px rgba(229, 0, 18, 0.08);
            pointer-events: none;
            z-index: 0;
        }

        .particles {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 160px;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(229, 0, 18, 0.55), transparent);
            border-radius: 999px;
            animation: speedLine 7s linear infinite;
            opacity: 0.55;
            transform: rotate(-18deg);
        }

        @keyframes speedLine {
            0% {
                transform: translateX(-220px) rotate(-18deg);
                opacity: 0;
            }
            20% {
                opacity: 0.6;
            }
            100% {
                transform: translateX(130vw) rotate(-18deg);
                opacity: 0;
            }
        }

        .register-container {
            max-width: 760px;
            width: 100%;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(35px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-card {
            position: relative;
            background:
                linear-gradient(145deg, rgba(17, 20, 26, 0.98), rgba(7, 8, 11, 0.98)),
                linear-gradient(135deg, rgba(229, 0, 18, 0.18), transparent);
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow:
                0 28px 80px rgba(0, 0, 0, 0.75),
                0 0 0 1px rgba(229, 0, 18, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.07);
        }

        .register-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 28px;
            padding: 1px;
            background: linear-gradient(135deg, rgba(229, 0, 18, 0.9), rgba(255, 255, 255, 0.12), rgba(229, 0, 18, 0.35));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        .register-card::after {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            right: -110px;
            bottom: -130px;
            background: radial-gradient(circle, rgba(229, 0, 18, 0.25), transparent 70%);
            pointer-events: none;
        }

        .register-header {
            position: relative;
            padding: 42px 42px 36px;
            color: var(--white);
            overflow: hidden;
            background:
                linear-gradient(135deg, rgba(229, 0, 18, 0.95) 0%, rgba(140, 0, 12, 0.98) 55%, rgba(20, 20, 20, 0.95) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .register-header::before {
            content: '';
            position: absolute;
            width: 360px;
            height: 140px;
            right: -90px;
            bottom: -45px;
            background:
                linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.08) 50%, transparent 100%);
            transform: skewX(-24deg);
        }

        .register-header::after {
            content: '';
            position: absolute;
            right: 38px;
            bottom: 28px;
            width: 190px;
            height: 52px;
            background:
                linear-gradient(8deg, transparent 35%, rgba(255,255,255,0.18) 36%, rgba(255,255,255,0.18) 42%, transparent 43%),
                linear-gradient(to right, transparent 0 15%, rgba(255,255,255,0.18) 16% 38%, transparent 39% 43%, rgba(255,255,255,0.18) 44% 67%, transparent 68%),
                linear-gradient(to bottom, transparent 0 48%, rgba(255,255,255,0.16) 49% 60%, transparent 61%);
            clip-path: polygon(7% 62%, 20% 28%, 46% 18%, 71% 27%, 90% 52%, 96% 75%, 84% 82%, 15% 82%);
            opacity: 0.38;
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 7px 13px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.18);
            backdrop-filter: blur(10px);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }

        .brand-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--white);
            box-shadow: 0 0 16px rgba(255, 255, 255, 0.9);
        }

        .register-header h1 {
            position: relative;
            z-index: 1;
            font-size: 36px;
            line-height: 1.1;
            font-weight: 900;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .register-header p {
            position: relative;
            z-index: 1;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.86);
            font-weight: 500;
            letter-spacing: 0.4px;
        }

        .register-form {
            position: relative;
            z-index: 1;
            padding: 38px 46px 42px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 22px;
        }

        .form-full {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            color: var(--soft-white);
            margin-bottom: 9px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .form-label .required {
            color: var(--toyota-red);
            margin-left: 3px;
        }

        .form-label .hint {
            font-weight: 500;
            font-size: 10px;
            color: var(--muted);
            margin-left: 8px;
            text-transform: none;
        }

        .input-group {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 15px 48px 15px 18px;
            font-size: 14px;
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            transition: all 0.25s ease;
            font-family: inherit;
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.035));
            color: var(--white);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.04),
                0 8px 22px rgba(0, 0, 0, 0.2);
        }

        .form-input::placeholder {
            color: rgba(217, 221, 229, 0.45);
        }

        .form-input:hover,
        .form-textarea:hover {
            border-color: rgba(255, 255, 255, 0.24);
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.045));
        }

        .form-input:focus {
            outline: none;
            border-color: var(--toyota-red);
            background: rgba(255, 255, 255, 0.1);
            box-shadow:
                0 0 0 4px rgba(229, 0, 18, 0.16),
                0 0 26px rgba(229, 0, 18, 0.18);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            transition: 0.25s;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--white);
            border-color: rgba(229, 0, 18, 0.6);
            background: rgba(229, 0, 18, 0.18);
            box-shadow: 0 0 18px rgba(229, 0, 18, 0.25);
        }

        .form-textarea {
            width: 100%;
            padding: 15px 18px;
            font-size: 14px;
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            transition: all 0.25s ease;
            font-family: inherit;
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.035));
            color: var(--white);
            resize: vertical;
            min-height: 88px;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.04),
                0 8px 22px rgba(0, 0, 0, 0.2);
        }

        .form-textarea::placeholder {
            color: rgba(217, 221, 229, 0.45);
        }

        .form-textarea:focus {
            outline: none;
            border-color: var(--toyota-red);
            background: rgba(255, 255, 255, 0.1);
            box-shadow:
                0 0 0 4px rgba(229, 0, 18, 0.16),
                0 0 26px rgba(229, 0, 18, 0.18);
        }

        input[type="date"] {
            color-scheme: dark;
            padding: 15px 18px;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
            opacity: 0.7;
        }

        .error-message {
            background: rgba(229, 0, 18, 0.12);
            color: #ffb3b9;
            padding: 13px 18px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 13px;
            border: 1px solid rgba(229, 0, 18, 0.35);
            border-left: 4px solid var(--toyota-red);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
        }

        .success-message {
            background: rgba(39, 174, 96, 0.12);
            color: #9be7bc;
            padding: 13px 18px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 13px;
            border: 1px solid rgba(39, 174, 96, 0.35);
            border-left: 4px solid #27ae60;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
        }

        .register-btn {
            width: 100%;
            padding: 16px;
            background:
                linear-gradient(135deg, var(--toyota-red) 0%, #bd0010 48%, #720008 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.28s ease;
            margin-top: 14px;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            box-shadow:
                0 14px 30px rgba(229, 0, 18, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.22);
        }

        .register-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -120%;
            width: 75%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
            transform: skewX(-25deg);
            transition: 0.65s;
        }

        .register-btn:hover::before {
            left: 130%;
        }

        .register-btn:hover {
            transform: translateY(-3px);
            box-shadow:
                0 20px 42px rgba(229, 0, 18, 0.42),
                0 0 26px rgba(229, 0, 18, 0.28);
        }

        .register-btn:active {
            transform: translateY(-1px);
        }

        .register-btn span {
            font-size: 18px;
            transition: 0.25s ease;
        }

        .register-btn:hover span {
            transform: translateX(4px);
        }

        .login-link {
            text-align: center;
            margin-top: 28px;
            padding-top: 23px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 13px;
            color: var(--muted);
        }

        .login-link a {
            color: var(--white);
            text-decoration: none;
            font-weight: 800;
            transition: 0.25s;
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -4px;
            width: 100%;
            height: 2px;
            background: var(--toyota-red);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.25s ease;
        }

        .login-link a:hover {
            color: var(--toyota-red);
        }

        .login-link a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        @media (max-width: 640px) {
            body {
                padding: 25px 14px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .register-header {
                padding: 32px 26px 30px;
            }

            .register-header h1 {
                font-size: 28px;
            }

            .register-form {
                padding: 30px 24px 34px;
            }

            .register-container {
                max-width: 96%;
            }

            .register-card {
                border-radius: 22px;
            }

            .register-card::before {
                border-radius: 22px;
            }

            .register-header::after {
                opacity: 0.18;
                right: -20px;
            }
        }
    </style>
</head>
<body>

    <div class="particles" id="particles"></div>

    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="brand-mark">
                    <span class="brand-dot"></span>
                    Toyota Drive Account
                </div>
                <h1>Create Account</h1>
                <p>Register your Toyota Car Selling account</p>
            </div>

            <div class="register-form">
                <?php if ($error): ?>
                    <div class="error-message">
                        ⚠️ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success-message">
                        ✓ <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-row">
                        <div>
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" name="name" class="form-input" placeholder="John Doe" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                        </div>
                        <div>
                            <label class="form-label">Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-input" placeholder="john@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label">Phone Number <span class="required">*</span></label>
                            <input type="tel" name="phone" class="form-input" placeholder="0123456789" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                        </div>
                        <div>
                            <label class="form-label">License Number <span class="required">*</span></label>
                            <input type="text" name="license" class="form-input" placeholder="D1234567" value="<?php echo isset($_POST['license']) ? htmlspecialchars($_POST['license']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-full">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-textarea" placeholder="Your address..."><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>

                    <div class="form-full">
                        <label class="form-label">
                            Date of Birth <span class="required">*</span>
                            <span class="hint">(Must be at least 17 years old)</span>
                        </label>
                        <input type="date" name="dob" class="form-input" value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>" required>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label">Password <span class="required">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="passwordInput" class="form-input" placeholder="At least 8 characters" required>
                                <button type="button" class="password-toggle" id="togglePassword">👁️</button>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Confirm Password <span class="required">*</span></label>
                            <div class="input-group">
                                <input type="password" name="confirm" id="confirmPasswordInput" class="form-input" placeholder="Re-enter password" required>
                                <button type="button" class="password-toggle" id="toggleConfirmPassword">👁️</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="register-btn">
                        Create Account <span>→</span>
                    </button>
                </form>

                <div class="login-link">
                    Already have an account? <a href="login.php">Sign in here</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 24;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');

                const width = Math.random() * 160 + 80;
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                const delay = Math.random() * 6;
                const duration = Math.random() * 5 + 5;

                particle.style.width = width + 'px';
                particle.style.left = posX + '%';
                particle.style.top = posY + '%';
                particle.style.animationDelay = delay + 's';
                particle.style.animationDuration = duration + 's';

                container.appendChild(particle);
            }
        }

        createParticles();

        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('passwordInput');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });

        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirmPasswordInput');

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    </script>

</body>
</html>