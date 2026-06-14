<?php
require_once "config.php";
require_once "mail_config.php";
require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function getMailSettingValue($constantName, $variableNames, $default = null) {
    if (defined($constantName)) {
        return constant($constantName);
    }

    foreach ($variableNames as $variableName) {
        if (isset($GLOBALS[$variableName]) && $GLOBALS[$variableName] !== "") {
            return $GLOBALS[$variableName];
        }
    }

    return $default;
}

if (!function_exists("sendResetLinkEmail")) {
    function sendResetLinkEmail($toEmail, $toName, $resetLink) {
        $smtpHost = getMailSettingValue("SMTP_HOST", ["smtp_host", "mail_host", "host"]);
        $smtpPort = getMailSettingValue("SMTP_PORT", ["smtp_port", "mail_port", "port"], 587);
        $smtpUsername = getMailSettingValue("SMTP_USERNAME", ["smtp_username", "mail_username", "username"]);
        $smtpPassword = getMailSettingValue("SMTP_PASSWORD", ["smtp_password", "mail_password", "password"]);
        $fromEmail = getMailSettingValue("SMTP_FROM_EMAIL", ["smtp_from_email", "from_email", "mail_from"], $smtpUsername);
        $fromName = getMailSettingValue("SMTP_FROM_NAME", ["smtp_from_name", "from_name", "mail_from_name"], "KH Car Rental");

        if (!$smtpHost || !$smtpUsername || !$smtpPassword || !$fromEmail) {
            return "Mail configuration is incomplete. Please check mail_config.php.";
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)$smtpPort;

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = "KH Car Rental Password Reset Link";
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; background:#f5fbff; padding:28px; color:#17304f;'>
                    <div style='max-width:560px; margin:auto; background:#ffffff; border:1px solid #d8ecfb; border-radius:22px; padding:28px;'>
                        <h2 style='margin:0 0 12px; color:#1284c6;'>Reset Your KH Car Rental Password</h2>
                        <p style='font-size:15px; line-height:1.7;'>Hi <b>" . e($toName) . "</b>,</p>
                        <p style='font-size:15px; line-height:1.7;'>Click the button below to reset your password. This link will expire in 15 minutes.</p>
                        <div style='text-align:center; margin:28px 0;'>
                            <a href='" . e($resetLink) . "' style='display:inline-block; padding:15px 24px; background:linear-gradient(135deg,#ff9a4a,#f15f12); color:#ffffff; text-decoration:none; border-radius:16px; font-weight:800;'>
                                Reset Password
                            </a>
                        </div>
                        <p style='font-size:13px; line-height:1.7; color:#6e8297;'>If the button does not work, copy and paste this link into your browser:</p>
                        <p style='font-size:13px; line-height:1.7; word-break:break-all; color:#1284c6;'>" . e($resetLink) . "</p>
                        <p style='font-size:13px; color:#6e8297; margin-top:24px;'>KH Car Rental System</p>
                    </div>
                </div>
            ";
            $mail->AltBody = "Reset your KH Car Rental password using this link: " . $resetLink . " . This link will expire in 15 minutes.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return "Reset link email could not be sent. " . $mail->ErrorInfo;
        }
    }
}

$errors = [];
$success = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid registered email address.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id, name, email FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires_at = date("Y-m-d H:i:s", time() + 900);

            $disableOld = $conn->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0");
            $disableOld->bind_param("i", $user["user_id"]);
            $disableOld->execute();
            $disableOld->close();

            $insert = $conn->prepare("
                INSERT INTO password_resets
                (user_id, email, reset_token, expires_at, used, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            $insert->bind_param("isss", $user["user_id"], $user["email"], $token, $expires_at);

            if ($insert->execute()) {
                $insert->close();

                $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
                $host = $_SERVER["HTTP_HOST"];
                $basePath = rtrim(str_replace("\\", "/", dirname($_SERVER["PHP_SELF"])), "/");
                $resetLink = $scheme . "://" . $host . $basePath . "/reset_password.php?token=" . urlencode($token);

                $sendResult = sendResetLinkEmail($user["email"], $user["name"], $resetLink);

                if ($sendResult === true) {
                    $success = "Password reset link has been sent to your email. Please check your inbox.";
                    $email = "";
                } else {
                    $errors[] = $sendResult;
                }
            } else {
                $errors[] = "Unable to create reset link. Please try again.";
                $insert->close();
            }
        } else {
            $success = "If the email exists in our system, a password reset link has been sent.";
            $email = "";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | KH Car Rental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --sky-50: #f5fbff;
            --sky-100: #eaf7ff;
            --sky-200: #d6efff;
            --sky-600: #1284c6;
            --blue-dark: #17304f;
            --muted: #6e8297;
            --border: #d8ecfb;
            --danger: #ff4d4f;
            --green: #21b573;
            --shadow: 0 24px 70px rgba(39,137,199,0.16);
            --shadow-soft: 0 12px 35px rgba(39,137,199,0.10);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--blue-dark);
            background:
                radial-gradient(circle at 12% 0%, rgba(184,228,255,0.62), transparent 34%),
                radial-gradient(circle at 88% 12%, rgba(214,239,255,0.76), transparent 30%),
                linear-gradient(180deg, #ffffff 0%, var(--sky-50) 46%, #ffffff 100%);
            display: grid;
            place-items: center;
            padding: 28px;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .auth-shell {
            width: min(1080px, 100%);
            display: grid;
            grid-template-columns: 0.92fr 1.08fr;
            border-radius: 36px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(184,228,255,0.95);
            background: rgba(255,255,255,0.72);
        }

        .auth-visual {
            position: relative;
            min-height: 640px;
            padding: 38px;
            overflow: hidden;
            background:
                radial-gradient(circle at 22% 14%, rgba(255,255,255,0.28), transparent 25%),
                radial-gradient(circle at 92% 8%, rgba(115,199,244,0.26), transparent 31%),
                radial-gradient(circle at 18% 92%, rgba(255,138,61,0.18), transparent 28%),
                linear-gradient(145deg, #071f4d 0%, #0a4ea3 42%, #062f6f 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .auth-visual::before {
            content: "";
            position: absolute;
            width: 520px;
            height: 520px;
            right: -190px;
            top: -145px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255,255,255,0.16), rgba(115,199,244,0.16));
            border: 1px solid rgba(255,255,255,0.18);
        }

        .premium-lines {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px);
            background-size: 70px 70px;
            pointer-events: none;
        }

        .brand,
        .visual-content,
        .visual-card {
            position: relative;
            z-index: 2;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 13px;
            font-size: 18px;
            font-weight: 950;
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.32);
            display: grid;
            place-items: center;
            box-shadow: 0 16px 35px rgba(0,0,0,0.14);
        }

        .visual-content span {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.24);
            font-size: 11px;
            font-weight: 950;
            letter-spacing: 0.9px;
            margin-bottom: 18px;
        }

        .visual-content h1 {
            font-size: clamp(38px, 4.3vw, 60px);
            line-height: 1.02;
            letter-spacing: -2.1px;
            font-weight: 950;
            margin-bottom: 16px;
        }

        .visual-content p {
            color: rgba(255,255,255,0.84);
            line-height: 1.7;
            font-weight: 650;
            max-width: 520px;
        }

        .visual-card {
            padding: 22px;
            border-radius: 26px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.20);
            backdrop-filter: blur(14px);
        }

        .visual-card i {
            width: 42px;
            height: 42px;
            border-radius: 15px;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,0.14);
            margin-bottom: 14px;
        }

        .visual-card strong {
            display: block;
            margin-bottom: 6px;
            font-size: 17px;
        }

        .visual-card small {
            color: rgba(255,255,255,0.76);
            line-height: 1.55;
        }

        .auth-panel {
            padding: 46px;
            display: grid;
            place-items: center;
        }

        .auth-card {
            width: min(520px, 100%);
            padding: 34px;
            border-radius: 32px;
            background:
                radial-gradient(circle at 88% 8%, rgba(184,228,255,0.42), transparent 26%),
                linear-gradient(135deg, rgba(255,255,255,0.96), rgba(234,247,255,0.86));
            border: 1px solid rgba(184,228,255,0.95);
            box-shadow: var(--shadow-soft);
        }

        .card-head {
            margin-bottom: 24px;
        }

        .card-head span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(40,168,234,0.12);
            color: var(--sky-600);
            border: 1px solid rgba(40,168,234,0.22);
            font-size: 12px;
            font-weight: 950;
            letter-spacing: 0.7px;
            margin-bottom: 12px;
        }

        .card-head h2 {
            font-size: clamp(32px, 4vw, 46px);
            font-weight: 950;
            letter-spacing: -1.5px;
            line-height: 1.05;
            margin-bottom: 8px;
        }

        .card-head p {
            color: var(--muted);
            line-height: 1.65;
            font-weight: 600;
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
            border: 1px solid rgba(255,77,79,0.22);
            color: #c92a2a;
        }

        .alert-success {
            background: rgba(33,181,115,0.10);
            border: 1px solid rgba(33,181,115,0.22);
            color: #087f5b;
        }

        .form {
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
            background: rgba(255,255,255,0.82);
            color: var(--blue-dark);
            border-radius: 16px;
            padding: 13px 15px 13px 42px;
            outline: none;
            font-size: 14px;
            font-weight: 750;
            transition: 0.24s;
        }

        .form-control:focus {
            border-color: var(--sky-600);
            box-shadow: 0 0 0 0.22rem rgba(40,168,234,0.13);
            background: #ffffff;
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
            box-shadow: 0 18px 34px rgba(255,122,26,0.28);
            transition: 0.25s;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 24px 42px rgba(255,122,26,0.36);
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

        @media (max-width: 900px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }

            .auth-visual {
                min-height: 460px;
            }
        }

        @media (max-width: 600px) {
            body {
                padding: 14px;
            }

            .auth-panel,
            .auth-visual,
            .auth-card {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <aside class="auth-visual">
            <div class="premium-lines"></div>

            <a class="brand" href="homepage.php">
                <span class="brand-logo"><i class="fa-solid fa-car-side"></i></span>
                <span>KH Car Rental</span>
            </a>

            <div class="visual-content">
                <span><i class="fa-solid fa-key"></i> PASSWORD RESET</span>
                <h1>Reset your account access.</h1>
                <p>Enter your registered email address and we will send a secure reset link to help you create a new password.</p>
            </div>

            <div class="visual-card">
                <i class="fa-solid fa-shield-halved"></i>
                <strong>Secure reset link</strong>
                <small>The reset link is valid for 15 minutes and can only be used once.</small>
            </div>
        </aside>

        <main class="auth-panel">
            <section class="auth-card">
                <div class="card-head">
                    <span><i class="fa-solid fa-envelope"></i> FORGOT PASSWORD</span>
                    <h2>Forgot Password</h2>
                    <p>Enter your registered email to receive a password reset link.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div>
                            <?php foreach ($errors as $error): ?>
                                <div><?= e($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ""): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <div><?= e($success) ?></div>
                    </div>
                <?php endif; ?>

                <form class="form" method="POST" action="forgot_password.php">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-envelope"></i>
                            <input class="form-control" type="email" name="email" id="email" value="<?= e($email) ?>" placeholder="example@email.com" required>
                        </div>
                    </div>

                    <button class="submit-btn" type="submit">
                        <i class="fa-solid fa-paper-plane"></i>
                        Send Reset Link
                    </button>
                </form>

                <p class="bottom-link">
                    Remember your password?
                    <a href="login.php">Login</a>
                </p>
            </section>
        </main>
    </div>
</body>
</html>
