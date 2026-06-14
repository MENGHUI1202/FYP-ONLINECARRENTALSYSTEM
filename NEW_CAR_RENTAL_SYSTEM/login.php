<?php
require_once "config.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$errors = [];
$email = "";
$registeredMessage = isset($_GET["registered"]) && $_GET["registered"] == "1";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $errors[] = "Invalid email or password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email or password.";
    } else {
        $stmt = $conn->prepare("
            SELECT user_id, name, email, password, role, profile_picture
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user["password"])) {
            $errors[] = "Invalid email or password.";
        } else {
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["user_role"] = $user["role"];
            $_SESSION["profile_picture"] = $user["profile_picture"];

            header("Location: homepage.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | KH Car Rental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --sky-50: #f5fbff;
            --sky-100: #eaf7ff;
            --sky-200: #d6efff;
            --sky-300: #b8e4ff;
            --sky-500: #28a8ea;
            --sky-600: #1284c6;
            --blue-dark: #17304f;
            --text: #17304f;
            --muted: #6e8297;
            --white: #ffffff;
            --border: #d8ecfb;
            --orange: #ff8a3d;
            --green: #21b573;
            --danger: #ff4d4f;
            --shadow: 0 24px 70px rgba(39, 137, 199, 0.16);
            --shadow-soft: 0 12px 35px rgba(39, 137, 199, 0.10);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 12% 0%, rgba(184, 228, 255, 0.62), transparent 34%),
                radial-gradient(circle at 88% 12%, rgba(214, 239, 255, 0.76), transparent 30%),
                linear-gradient(180deg, #ffffff 0%, var(--sky-50) 46%, #ffffff 100%);
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 0.82fr 1.18fr;
        }

        .login-visual {
            position: sticky;
            top: 0;
            min-height: 100vh;
            height: 100vh;
            overflow: hidden;
            padding: 38px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                radial-gradient(circle at 22% 14%, rgba(255, 255, 255, 0.28), transparent 25%),
                radial-gradient(circle at 92% 8%, rgba(115, 199, 244, 0.26), transparent 31%),
                radial-gradient(circle at 18% 92%, rgba(255, 138, 61, 0.18), transparent 28%),
                linear-gradient(145deg, #071f4d 0%, #0a4ea3 42%, #062f6f 100%);
            color: #ffffff;
        }

        .login-visual::before {
            content: "";
            position: absolute;
            width: 520px;
            height: 520px;
            right: -190px;
            top: -145px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255,255,255,0.16), rgba(115,199,244,0.16));
            border: 1px solid rgba(255,255,255,0.18);
            box-shadow: inset 0 0 60px rgba(255,255,255,0.08);
        }

        .login-visual::after {
            content: "";
            position: absolute;
            width: 410px;
            height: 410px;
            left: -155px;
            bottom: -155px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(40,168,234,0.26), transparent 68%);
        }

        .premium-lines {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px);
            background-size: 70px 70px;
            mask-image: linear-gradient(180deg, rgba(0,0,0,0.75), transparent 82%);
            pointer-events: none;
        }

        .visual-content,
        .visual-cards {
            position: relative;
            z-index: 2;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 13px;
            font-size: 18px;
            font-weight: 950;
            letter-spacing: -0.4px;
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.32);
            display: grid;
            place-items: center;
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.14);
        }

        .visual-title {
            margin-top: 72px;
            max-width: 560px;
        }

        .visual-title span {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.24);
            font-size: 11px;
            font-weight: 950;
            letter-spacing: 0.9px;
            margin-bottom: 18px;
            backdrop-filter: blur(12px);
        }

        .visual-title h1 {
            font-size: clamp(38px, 4.2vw, 58px);
            line-height: 1.03;
            letter-spacing: -2.1px;
            font-weight: 950;
            margin-bottom: 18px;
            max-width: 560px;
            text-shadow: 0 18px 45px rgba(0,0,0,0.16);
        }

        .visual-title p {
            max-width: 500px;
            color: rgba(255, 255, 255, 0.84);
            line-height: 1.7;
            font-size: 15px;
            font-weight: 650;
        }

        .promo-mini-card {
            position: relative;
            z-index: 2;
            margin-top: 24px;
            padding: 22px;
            border-radius: 28px;
            background:
                radial-gradient(circle at 18% 0%, rgba(255,255,255,0.30), transparent 34%),
                linear-gradient(135deg, rgba(255,255,255,0.16), rgba(255,255,255,0.08));
            border: 1px solid rgba(255,255,255,0.22);
            backdrop-filter: blur(18px);
            box-shadow: 0 22px 50px rgba(0,0,0,0.16);
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 18px;
        }

        .promo-mini-card span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,138,61,0.18);
            color: #ffd6bf;
            border: 1px solid rgba(255,138,61,0.26);
            font-size: 11px;
            font-weight: 950;
            letter-spacing: 0.7px;
            margin-bottom: 10px;
        }

        .promo-mini-card h3 {
            font-size: 22px;
            line-height: 1.08;
            letter-spacing: -0.7px;
            margin-bottom: 7px;
        }

        .promo-mini-card p {
            color: rgba(255,255,255,0.76);
            line-height: 1.55;
            font-size: 13px;
            font-weight: 650;
        }

        .promo-percent {
            width: 118px;
            height: 118px;
            border-radius: 30px;
            display: grid;
            place-items: center;
            text-align: center;
            background: linear-gradient(135deg, #ff9a4a, #f15f12);
            box-shadow: 0 18px 36px rgba(255,122,26,0.28);
            font-size: 38px;
            font-weight: 950;
            line-height: 0.92;
        }

        .promo-percent small {
            display: block;
            font-size: 14px;
            letter-spacing: 2px;
            margin-top: 6px;
            color: #ffffff;
        }

        .visual-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 24px;
        }

        .visual-card {
            padding: 18px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.20);
            backdrop-filter: blur(14px);
        }

        .visual-card i {
            width: 38px;
            height: 38px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, 0.14);
            margin-bottom: 12px;
        }

        .visual-card strong {
            display: block;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .visual-card small {
            color: rgba(255, 255, 255, 0.74);
            line-height: 1.45;
        }

        .login-panel {
            padding: 38px;
            display: grid;
            place-items: center;
            min-height: 100vh;
        }

        .login-card {
            width: min(560px, 100%);
            border-radius: 34px;
            background:
                radial-gradient(circle at 88% 8%, rgba(184, 228, 255, 0.42), transparent 26%),
                linear-gradient(135deg, rgba(255,255,255,0.96), rgba(234,247,255,0.86));
            border: 1px solid rgba(184, 228, 255, 0.95);
            box-shadow: var(--shadow);
            padding: 36px;
            backdrop-filter: blur(16px);
        }

        .card-head {
            margin-bottom: 26px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .card-head-text span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(40, 168, 234, 0.12);
            color: var(--sky-600);
            border: 1px solid rgba(40, 168, 234, 0.22);
            font-size: 12px;
            font-weight: 950;
            letter-spacing: 0.7px;
            margin-bottom: 12px;
        }

        .card-head h2 {
            color: var(--blue-dark);
            font-size: clamp(32px, 4vw, 48px);
            font-weight: 950;
            letter-spacing: -1.6px;
            line-height: 1.04;
        }

        .card-head p {
            margin-top: 8px;
            color: var(--muted);
            line-height: 1.65;
            font-weight: 600;
        }

        .home-link {
            white-space: nowrap;
            padding: 12px 16px;
            border-radius: 999px;
            background: #ffffff;
            color: var(--sky-600);
            border: 2px solid var(--sky-200);
            font-size: 13px;
            font-weight: 950;
            box-shadow: var(--shadow-soft);
            transition: 0.24s;
        }

        .home-link:hover {
            transform: translateY(-2px);
            background: var(--sky-100);
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 18px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.55;
            font-weight: 650;
        }

        .alert-danger {
            background: #fff5f5;
            border: 1px solid rgba(255, 77, 79, 0.22);
            color: #c92a2a;
        }

        .alert-success {
            background: rgba(33, 181, 115, 0.10);
            border: 1px solid rgba(33, 181, 115, 0.22);
            color: #087f5b;
        }

        .login-form {
            display: grid;
            gap: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 950;
            color: var(--blue-dark);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--sky-600);
            font-size: 14px;
            z-index: 2;
        }

        .form-control {
            width: 100%;
            min-height: 54px;
            border: 2px solid #e2f2ff;
            background: rgba(255, 255, 255, 0.82);
            color: var(--blue-dark);
            border-radius: 16px;
            padding: 13px 15px 13px 42px;
            outline: none;
            font-size: 14px;
            font-weight: 750;
            transition: 0.24s;
        }

        .password-wrap .form-control {
            padding-right: 48px;
        }

        .form-control:focus {
            border-color: var(--sky-500);
            box-shadow: 0 0 0 0.22rem rgba(40,168,234,0.13);
            background: #ffffff;
        }

        .form-control.invalid-field {
            border-color: var(--danger);
            background: #fff5f5;
            box-shadow: 0 0 0 0.22rem rgba(255,77,79,0.13);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            z-index: 3;
        }

        .password-toggle:hover {
            background: var(--sky-100);
            color: var(--sky-600);
        }

        .form-row-link {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: -4px;
        }

        .forgot-link {
            color: var(--sky-600);
            font-size: 13px;
            font-weight: 950;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .submit-btn {
            position: relative;
            overflow: hidden;
            min-height: 56px;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(135deg, #ff9a4a 0%, #ff7a1a 48%, #f15f12 100%);
            color: #ffffff;
            font-size: 15px;
            font-weight: 950;
            cursor: pointer;
            box-shadow: 0 18px 34px rgba(255,122,26,0.28), inset 0 1px 0 rgba(255,255,255,0.32);
            transition: 0.25s;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #ffad66 0%, #ff8429 48%, #f26b1d 100%);
            box-shadow: 0 24px 42px rgba(255,122,26,0.36), inset 0 1px 0 rgba(255,255,255,0.38);
        }

        .submit-btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -80%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.36), transparent);
            transform: skewX(-20deg);
            transition: 0.55s;
        }

        .submit-btn:hover::before {
            left: 125%;
        }

        .submit-btn span {
            position: relative;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
        }

        .bottom-link {
            text-align: center;
            margin-top: 18px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 650;
        }

        .bottom-link a {
            color: var(--sky-600);
            font-weight: 950;
        }

        @media (max-width: 1100px) {
            .page {
                grid-template-columns: 1fr;
            }

            .login-visual {
                position: relative;
                height: auto;
                min-height: 560px;
            }
        }

        @media (max-width: 720px) {
            .login-panel,
            .login-visual {
                padding: 24px;
            }

            .login-card {
                padding: 24px;
            }

            .visual-cards,
            .promo-mini-card {
                grid-template-columns: 1fr;
            }

            .promo-percent {
                width: 100%;
                height: 100px;
            }

            .card-head {
                display: grid;
            }

            .home-link {
                width: fit-content;
            }

            .visual-title {
                margin-top: 58px;
            }
        }
    
        /* Left visual text balance update */
        @media (max-width: 1280px) {
            .visual-title h1 {
                font-size: clamp(34px, 4vw, 50px);
            }

            .visual-title {
                margin-top: 56px;
            }

            .promo-mini-card {
                grid-template-columns: 1fr 96px;
            }

            .promo-percent {
                width: 96px;
                height: 96px;
                font-size: 32px;
            }
        }

    
        /* ===== FIX LEFT SIDE FIT SCREEN ===== */
        .login-visual {
            padding: 28px 34px !important;
            justify-content: flex-start !important;
            gap: 18px !important;
        }

        .brand-logo {
            width: 48px !important;
            height: 48px !important;
            border-radius: 16px !important;
        }

        .brand {
            font-size: 17px !important;
        }

        .visual-title {
            margin-top: 46px !important;
            max-width: 560px !important;
        }

        .visual-title span {
            padding: 8px 13px !important;
            font-size: 11px !important;
            margin-bottom: 15px !important;
        }

        .visual-title h1 {
            font-size: clamp(36px, 4vw, 56px) !important;
            line-height: 1.02 !important;
            letter-spacing: -2px !important;
            margin-bottom: 14px !important;
        }

        .visual-title p {
            max-width: 560px !important;
            font-size: 14px !important;
            line-height: 1.55 !important;
        }

        .promo-mini-card {
            margin-top: 22px !important;
            padding: 18px !important;
            border-radius: 24px !important;
            grid-template-columns: 1fr 96px !important;
            gap: 14px !important;
        }

        .promo-mini-card span {
            padding: 7px 11px !important;
            font-size: 10px !important;
            margin-bottom: 8px !important;
        }

        .promo-mini-card h3 {
            font-size: 21px !important;
            margin-bottom: 5px !important;
        }

        .promo-mini-card p {
            font-size: 12px !important;
            line-height: 1.4 !important;
        }

        .promo-percent {
            width: 96px !important;
            height: 96px !important;
            border-radius: 24px !important;
            font-size: 32px !important;
        }

        .promo-percent small {
            font-size: 12px !important;
            margin-top: 5px !important;
        }

        .visual-cards {
            margin-top: 8px !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 10px !important;
        }

        .visual-card {
            padding: 13px !important;
            border-radius: 18px !important;
            min-height: 126px !important;
        }

        .visual-card i {
            width: 34px !important;
            height: 34px !important;
            border-radius: 12px !important;
            margin-bottom: 9px !important;
        }

        .visual-card strong {
            font-size: 13px !important;
            margin-bottom: 4px !important;
        }

        .visual-card small {
            font-size: 11.5px !important;
            line-height: 1.32 !important;
        }

        @media (max-width: 1366px) {
            .login-visual {
                padding: 24px 28px !important;
                gap: 14px !important;
            }

            .visual-title {
                margin-top: 38px !important;
            }

            .visual-title h1 {
                font-size: 50px !important;
            }

            .visual-cards {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }

            .visual-card {
                min-height: 116px !important;
                padding: 12px !important;
            }

            .visual-card small {
                font-size: 11px !important;
            }
        }

        @media (max-height: 760px) {
            .login-visual {
                padding: 22px 28px !important;
                gap: 12px !important;
            }

            .visual-title {
                margin-top: 34px !important;
            }

            .visual-title h1 {
                font-size: 46px !important;
                margin-bottom: 12px !important;
            }

            .visual-title p {
                font-size: 13.5px !important;
                line-height: 1.45 !important;
            }

            .promo-mini-card {
                margin-top: 18px !important;
                padding: 16px !important;
            }

            .promo-mini-card h3 {
                font-size: 19px !important;
            }

            .promo-percent {
                width: 86px !important;
                height: 86px !important;
                font-size: 29px !important;
            }

            .visual-cards {
                margin-top: 6px !important;
            }

            .visual-card {
                min-height: 104px !important;
                padding: 10px !important;
            }

            .visual-card i {
                width: 30px !important;
                height: 30px !important;
                margin-bottom: 7px !important;
            }

            .visual-card strong {
                font-size: 12.5px !important;
            }

            .visual-card small {
                font-size: 10.5px !important;
                line-height: 1.25 !important;
            }
        }

    </style>
</head>
<body>
    <div class="page">
        <aside class="login-visual">
            <div class="visual-content">
                <div class="premium-lines"></div>

                <a class="brand" href="homepage.php">
                    <span class="brand-logo"><i class="fa-solid fa-car-side"></i></span>
                    <span>KH Car Rental</span>
                </a>

                <div class="visual-title">
                    <span><i class="fa-solid fa-user-shield"></i> CUSTOMER LOGIN</span>
                    <h1>Welcome back,<br>ready to drive?</h1>
                    <p>Login to manage your profile, check booking status, view your cart and continue your rental journey with KH Car Rental.</p>

                    <div class="promo-mini-card">
                        <div>
                            <span><i class="fa-solid fa-gift"></i> NEW USER PROMOTION</span>
                            <h3>First booking voucher</h3>
                            <p>New customers can register and receive a 5% first booking voucher in their profile.</p>
                        </div>
                        <div class="promo-percent">
                            5%
                            <small>OFF</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="visual-cards">
                <div class="visual-card">
                    <i class="fa-solid fa-user"></i>
                    <strong>My Profile</strong>
                    <small>Manage your personal details and customer information.</small>
                </div>

                <div class="visual-card">
                    <i class="fa-solid fa-calendar-check"></i>
                    <strong>My Bookings</strong>
                    <small>Check your booking status and rental progress.</small>
                </div>

                <div class="visual-card">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <strong>My Cart</strong>
                    <small>Continue your selected rental cars and checkout process.</small>
                </div>
            </div>
        </aside>

        <main class="login-panel">
            <section class="login-card">
                <div class="card-head">
                    <div class="card-head-text">
                        <span><i class="fa-solid fa-right-to-bracket"></i> LOGIN ACCOUNT</span>
                        <h2>Login</h2>
                        <p>Enter your registered email and password to continue.</p>
                    </div>

                    <a class="home-link" href="homepage.php">
                        <i class="fa-solid fa-house"></i>
                        Home
                    </a>
                </div>

                <?php if ($registeredMessage): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <div>
                            <strong>Registration successful!</strong><br>
                            Please login using your email and password.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div>
                            <strong>Login failed.</strong><br>
                            Invalid email or password.
                        </div>
                    </div>
                <?php endif; ?>

                <form class="login-form" id="loginForm" method="POST" action="login.php" novalidate>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-envelope"></i>
                            <input class="form-control" type="email" name="email" id="email" value="<?= e($email) ?>" placeholder="example@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap password-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input class="form-control" type="password" name="password" id="password" placeholder="Enter your password" required>
                            <button class="password-toggle" type="button" data-target="password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-row-link">
                        <a class="forgot-link" href="forgot_password.php">
                            Forgot Password?
                        </a>
                    </div>

                    <button class="submit-btn" type="submit">
                        <span><i class="fa-solid fa-right-to-bracket"></i> Login</span>
                    </button>
                </form>

                <p class="bottom-link">
                    Don't have an account?
                    <a href="register.php">Register</a>
                </p>
            </section>
        </main>
    </div>

    <script>
        const form = document.getElementById("loginForm");
        const email = document.getElementById("email");
        const password = document.getElementById("password");

        function setInvalid(input, isInvalid) {
            if (input) {
                input.classList.toggle("invalid-field", isInvalid);
            }
        }

        function validEmail(value) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }

        document.querySelectorAll(".password-toggle").forEach(button => {
            button.addEventListener("click", () => {
                const input = document.getElementById(button.dataset.target);
                const icon = button.querySelector("i");

                input.type = input.type === "password" ? "text" : "password";
                icon.classList.toggle("fa-eye");
                icon.classList.toggle("fa-eye-slash");
            });
        });

        form.addEventListener("submit", event => {
            let valid = true;

            if (email.value.trim() === "" || !validEmail(email.value.trim())) {
                setInvalid(email, true);
                valid = false;
            } else {
                setInvalid(email, false);
            }

            if (password.value.trim() === "") {
                setInvalid(password, true);
                valid = false;
            } else {
                setInvalid(password, false);
            }

            if (!valid) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
