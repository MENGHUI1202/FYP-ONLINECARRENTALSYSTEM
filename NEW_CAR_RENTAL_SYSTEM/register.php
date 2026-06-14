<?php
require_once "config.php";
require_once "mail_config.php";
require_once "terms_helpers.php";

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

function strongPassword($password) {
    return strlen($password) >= 10
        && preg_match("/[A-Z]/", $password)
        && preg_match("/[a-z]/", $password)
        && preg_match("/[0-9]/", $password)
        && preg_match("/[^A-Za-z0-9]/", $password);
}

$submitted_step = $_POST["step"] ?? "register";
$step = $submitted_step;
$errors = [];
$success = "";
$termsVersion = "KHCR-2026-01";
ensureTermsAcceptanceTable($conn);

$name = "";
$email = "";
$phone = "";
$ic_number = "";
$license_number = "";
$license_expiry_date = "";
$date_of_birth = "";
$address = "";
$pending_id = trim($_POST["pending_id"] ?? "");

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && $submitted_step === "register") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $ic_number = trim($_POST["ic_number"] ?? "");
    $license_number = trim($_POST["license_number"] ?? "");
    $license_expiry_date = trim($_POST["license_expiry_date"] ?? "");
    $date_of_birth = trim($_POST["date_of_birth"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $agree_terms = !empty($_POST["agree_terms"]);

    if ($name === "") $errors[] = "Full Name is required.";
    if ($email === "") $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email format is invalid.";
    $phone_digits = preg_replace("/\D/", "", $phone);
    if ($phone === "") $errors[] = "Phone Number is required.";
    elseif (strlen($phone_digits) < 10 || strlen($phone_digits) > 12) $errors[] = "Phone Number must contain 10 to 12 digits.";
    if ($ic_number === "") $errors[] = "IC Number is required.";
    if ($license_number === "") $errors[] = "Driving License Number is required.";
    elseif (!preg_match("/^[A-Za-z0-9]{5,20}$/", $license_number)) $errors[] = "Driving License Number must be 5 to 20 letters or numbers only.";
    if ($license_expiry_date === "") {
        $errors[] = "Driving License Expiry Date is required.";
    } else {
        $minimum_license_expiry = date("Y-m-d", strtotime("+6 months"));
        if ($license_expiry_date < $minimum_license_expiry) {
            $errors[] = "Driving License Expiry Date must be at least 6 months from today.";
        }
    }
    if ($date_of_birth === "") $errors[] = "Date of Birth is required.";
    elseif ($date_of_birth > date("Y-m-d")) $errors[] = "Date of Birth cannot be a future date.";
    if ($address === "") $errors[] = "Address is required.";

    if ($password === "") {
        $errors[] = "Password is required.";
    } elseif (!strongPassword($password)) {
        $errors[] = "Password must be at least 10 characters long and include uppercase letter, lowercase letter, number and special symbol.";
    }

    if ($confirm_password === "") {
        $errors[] = "Confirm Password is required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Confirm Password must match Password.";
    }

    if (!$agree_terms) {
        $errors[] = "You must agree to KH Car Rental Terms & Conditions before registration.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR ic_number = ? OR license_number = ? LIMIT 1");
        $stmt->bind_param("sss", $email, $ic_number, $license_number);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $errors[] = "Email, IC Number or Driving License Number already exists.";
        }
    }

    if (empty($errors)) {
        $deleteOld = $conn->prepare("DELETE FROM pending_registrations WHERE email = ? OR ic_number = ? OR license_number = ?");
        $deleteOld->bind_param("sss", $email, $ic_number, $license_number);
        $deleteOld->execute();
        $deleteOld->close();

        $otp_code = (string)random_int(100000, 999999);
        $otp_hash = password_hash($otp_code, PASSWORD_DEFAULT);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $expires_at = date("Y-m-d H:i:s", time() + 600);

        $stmt = $conn->prepare("
            INSERT INTO pending_registrations
            (name, email, password, phone, ic_number, license_number, license_expiry_date, date_of_birth, address, otp_hash, expires_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sssssssssss",
            $name,
            $email,
            $password_hash,
            $phone,
            $ic_number,
            $license_number,
            $license_expiry_date,
            $date_of_birth,
            $address,
            $otp_hash,
            $expires_at
        );

        if ($stmt->execute()) {
            $pending_id = (string)$stmt->insert_id;
            $stmt->close();

            $sendResult = sendOtpEmail($email, $name, $otp_code);

            if ($sendResult === true) {
                $step = "verify";
                $success = "OTP has been sent to your email. Please check your inbox.";
            } else {
                $step = "register";
                $errors[] = "OTP email could not be sent. " . $sendResult;
            }
        } else {
            $stmt->close();
            $errors[] = "Registration request failed. Please try again.";
        }
    }
}

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && $submitted_step === "verify") {
    $pending_id = trim($_POST["pending_id"] ?? "");
    $otp_code = preg_replace("/\D/", "", $_POST["otp_code"] ?? "");
    $step = "verify";

    if ($pending_id === "") {
        $errors[] = "Invalid registration session. Please register again.";
        $step = "register";
    } elseif ($otp_code === "") {
        $errors[] = "OTP code is required.";
    } elseif (strlen($otp_code) !== 6) {
        $errors[] = "OTP code must be 6 digits.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM pending_registrations WHERE pending_id = ? LIMIT 1");
        $stmt->bind_param("i", $pending_id);
        $stmt->execute();
        $pending = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pending) {
            $errors[] = "Invalid registration session. Please register again.";
            $step = "register";
        } elseif (strtotime($pending["expires_at"]) < time()) {
            $errors[] = "OTP has expired. Please register again to get a new OTP.";
            $step = "register";
        } elseif (!password_verify($otp_code, $pending["otp_hash"])) {
            $errors[] = "Invalid OTP code.";
            $step = "verify";
        } else {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR ic_number = ? OR license_number = ? LIMIT 1");
            $stmt->bind_param("sss", $pending["email"], $pending["ic_number"], $pending["license_number"]);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($exists) {
                $errors[] = "Account already exists. Please login.";
                $step = "verify";
            } else {
                $role = "customer";
                $profile_picture = null;

                $stmt = $conn->prepare("
                    INSERT INTO users
                    (name, email, password, phone, ic_number, license_number, license_expiry_date, date_of_birth, address, role, profile_picture, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                $stmt->bind_param(
                    "sssssssssss",
                    $pending["name"],
                    $pending["email"],
                    $pending["password"],
                    $pending["phone"],
                    $pending["ic_number"],
                    $pending["license_number"],
                    $pending["license_expiry_date"],
                    $pending["date_of_birth"],
                    $pending["address"],
                    $role,
                    $profile_picture
                );

                if ($stmt->execute()) {
                    $new_user_id = (int)$stmt->insert_id;
                    $stmt->close();

                    if ($new_user_id > 0) {
                        $syncId = $conn->prepare("UPDATE users SET id = user_id WHERE user_id = ? AND id IS NULL");
                        $syncId->bind_param("i", $new_user_id);
                        $syncId->execute();
                        $syncId->close();
                        recordTermsAcceptance($conn, $new_user_id, "registration", $termsVersion);
                    }

                    $deletePending = $conn->prepare("DELETE FROM pending_registrations WHERE pending_id = ?");
                    $deletePending->bind_param("i", $pending_id);
                    $deletePending->execute();
                    $deletePending->close();

                    header("Location: login.php?registered=1");
                    exit;
                } else {
                    $stmt->close();
                    $errors[] = "Account creation failed. Please try again.";
                    $step = "verify";
                }
            }
        }
    }
}

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && $submitted_step === "resend") {
    $pending_id = trim($_POST["pending_id"] ?? "");
    $step = "verify";

    if ($pending_id === "") {
        $errors[] = "Invalid registration session. Please register again.";
        $step = "register";
    } else {
        $stmt = $conn->prepare("SELECT * FROM pending_registrations WHERE pending_id = ? LIMIT 1");
        $stmt->bind_param("i", $pending_id);
        $stmt->execute();
        $pending = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pending) {
            $errors[] = "Invalid registration session. Please register again.";
            $step = "register";
        } else {
            $otp_code = (string)random_int(100000, 999999);
            $otp_hash = password_hash($otp_code, PASSWORD_DEFAULT);
            $expires_at = date("Y-m-d H:i:s", time() + 600);

            $stmt = $conn->prepare("UPDATE pending_registrations SET otp_hash = ?, expires_at = ? WHERE pending_id = ?");
            $stmt->bind_param("ssi", $otp_hash, $expires_at, $pending_id);
            $stmt->execute();
            $stmt->close();

            $sendResult = sendOtpEmail($pending["email"], $pending["name"], $otp_code);

            if ($sendResult === true) {
                $success = "New OTP has been sent to your email.";
            } else {
                $errors[] = "OTP email could not be sent. " . $sendResult;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | KH Car Rental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --sky-50:#f5fbff;
            --sky-100:#eaf7ff;
            --sky-200:#d6efff;
            --sky-500:#28a8ea;
            --sky-600:#1284c6;
            --blue-dark:#17304f;
            --muted:#6e8297;
            --orange:#ff8a3d;
            --green:#21b573;
            --danger:#ff4d4f;
            --border:#d8ecfb;
            --shadow:0 24px 70px rgba(39,137,199,.16);
            --shadow-soft:0 12px 35px rgba(39,137,199,.10);
        }

        * { box-sizing:border-box; margin:0; padding:0; }

        body {
            min-height:100vh;
            font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color:var(--blue-dark);
            background:
                radial-gradient(circle at 12% 0%, rgba(184,228,255,.62), transparent 34%),
                radial-gradient(circle at 88% 12%, rgba(214,239,255,.76), transparent 30%),
                linear-gradient(180deg,#fff 0%,var(--sky-50) 46%,#fff 100%);
            overflow-x:hidden;
        }

        a { color:inherit; text-decoration:none; }

        .page {
            min-height:100vh;
            display:grid;
            grid-template-columns:.78fr 1.22fr;
        }

        .register-visual {
            position:sticky;
            top:0;
            height:100vh;
            overflow:hidden;
            padding:24px 28px;
            display:flex;
            flex-direction:column;
            justify-content:flex-start;
            gap:14px;
            background:
                radial-gradient(circle at 22% 14%, rgba(255,255,255,.28), transparent 25%),
                radial-gradient(circle at 92% 8%, rgba(115,199,244,.26), transparent 31%),
                radial-gradient(circle at 18% 92%, rgba(255,138,61,.18), transparent 28%),
                linear-gradient(145deg,#071f4d 0%,#0a4ea3 42%,#062f6f 100%);
            color:#fff;
        }

        .register-visual::before {
            content:"";
            position:absolute;
            width:520px;
            height:520px;
            right:-190px;
            top:-145px;
            border-radius:50%;
            background:linear-gradient(135deg,rgba(255,255,255,.16),rgba(115,199,244,.16));
            border:1px solid rgba(255,255,255,.18);
        }

        .premium-lines {
            position:absolute;
            inset:0;
            background-image:
                linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),
                linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);
            background-size:70px 70px;
            pointer-events:none;
        }

        .visual-content,
        .visual-cards,
        .brand { position:relative; z-index:2; }

        .brand {
            display:inline-flex;
            align-items:center;
            gap:13px;
            font-size:17px;
            font-weight:950;
        }

        .brand-logo {
            width:48px;
            height:48px;
            border-radius:16px;
            background:rgba(255,255,255,.16);
            border:1px solid rgba(255,255,255,.32);
            display:grid;
            place-items:center;
            box-shadow:0 16px 35px rgba(0,0,0,.14);
        }

        .visual-title { margin-top:38px; max-width:560px; }

        .visual-title span {
            display:inline-flex;
            align-items:center;
            gap:9px;
            padding:8px 13px;
            border-radius:999px;
            background:rgba(255,255,255,.16);
            border:1px solid rgba(255,255,255,.24);
            font-size:11px;
            font-weight:950;
            letter-spacing:.8px;
            margin-bottom:14px;
            backdrop-filter:blur(12px);
        }

        .visual-title h1 {
            font-size:clamp(36px,4vw,50px);
            line-height:1.02;
            letter-spacing:-2px;
            font-weight:950;
            margin-bottom:12px;
            text-shadow:0 18px 45px rgba(0,0,0,.16);
        }

        .visual-title p {
            max-width:560px;
            color:rgba(255,255,255,.84);
            line-height:1.45;
            font-size:13.5px;
            font-weight:650;
        }

        .promo-mini-card {
            margin-top:18px;
            padding:16px;
            border-radius:24px;
            background:
                radial-gradient(circle at 18% 0%,rgba(255,255,255,.30),transparent 34%),
                linear-gradient(135deg,rgba(255,255,255,.16),rgba(255,255,255,.08));
            border:1px solid rgba(255,255,255,.22);
            backdrop-filter:blur(18px);
            box-shadow:0 22px 50px rgba(0,0,0,.16);
            display:grid;
            grid-template-columns:1fr 86px;
            align-items:center;
            gap:14px;
        }

        .promo-mini-card span {
            display:inline-flex;
            align-items:center;
            gap:8px;
            width:fit-content;
            padding:7px 11px;
            border-radius:999px;
            background:rgba(255,138,61,.18);
            color:#ffd6bf;
            border:1px solid rgba(255,138,61,.26);
            font-size:10px;
            font-weight:950;
            letter-spacing:.7px;
            margin-bottom:8px;
        }

        .promo-mini-card h3 {
            font-size:19px;
            line-height:1.08;
            letter-spacing:-.7px;
            margin-bottom:5px;
        }

        .promo-mini-card p {
            color:rgba(255,255,255,.76);
            line-height:1.4;
            font-size:12px;
            font-weight:650;
        }

        .promo-percent {
            width:86px;
            height:86px;
            border-radius:24px;
            display:grid;
            place-items:center;
            text-align:center;
            background:linear-gradient(135deg,#ff9a4a,#f15f12);
            box-shadow:0 18px 36px rgba(255,122,26,.28);
            font-size:29px;
            font-weight:950;
            line-height:.92;
        }

        .promo-percent small {
            display:block;
            font-size:12px;
            letter-spacing:2px;
            margin-top:5px;
            color:#fff;
        }

        .visual-cards {
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:10px;
            margin-top:6px;
        }

        .visual-card {
            padding:10px;
            border-radius:18px;
            min-height:104px;
            background:rgba(255,255,255,.12);
            border:1px solid rgba(255,255,255,.20);
            backdrop-filter:blur(14px);
        }

        .visual-card i {
            width:30px;
            height:30px;
            border-radius:12px;
            display:grid;
            place-items:center;
            background:rgba(255,255,255,.14);
            margin-bottom:7px;
        }

        .visual-card strong {
            display:block;
            font-size:12.5px;
            margin-bottom:4px;
        }

        .visual-card small {
            color:rgba(255,255,255,.74);
            line-height:1.25;
            font-size:10.5px;
        }

        .register-panel {
            padding:14px 28px;
            display:grid;
            place-items:center;
            min-height:100vh;
        }

        .register-card {
            width:min(900px,100%);
            border-radius:28px;
            background:
                radial-gradient(circle at 88% 8%,rgba(184,228,255,.42),transparent 26%),
                linear-gradient(135deg,rgba(255,255,255,.96),rgba(234,247,255,.86));
            border:1px solid rgba(184,228,255,.95);
            box-shadow:var(--shadow);
            padding:20px 24px 18px;
            backdrop-filter:blur(16px);
        }

        .card-head {
            margin-bottom:9px;
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:16px;
        }

        .card-head-text span {
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:7px 12px;
            border-radius:999px;
            background:rgba(40,168,234,.12);
            color:var(--sky-600);
            border:1px solid rgba(40,168,234,.22);
            font-size:11px;
            font-weight:950;
            letter-spacing:.7px;
            margin-bottom:9px;
        }

        .card-head h2 {
            color:var(--blue-dark);
            font-size:clamp(26px,3vw,38px);
            font-weight:950;
            letter-spacing:-1.5px;
            line-height:1;
        }

        .card-head p {
            margin-top:6px;
            color:var(--muted);
            line-height:1.45;
            font-size:14px;
            font-weight:600;
        }

        .login-link {
            white-space:nowrap;
            padding:10px 15px;
            border-radius:999px;
            background:#fff;
            color:var(--sky-600);
            border:2px solid var(--sky-200);
            font-size:12px;
            font-weight:950;
            box-shadow:var(--shadow-soft);
            transition:.24s;
        }

        .login-link:hover {
            transform:translateY(-2px);
            background:var(--sky-100);
        }

        .alert {
            display:flex;
            align-items:flex-start;
            gap:12px;
            padding:10px 13px;
            border-radius:15px;
            margin-bottom:10px;
            font-size:12px;
            line-height:1.35;
            font-weight:650;
        }

        .alert-danger {
            background:#fff5f5;
            border:1px solid rgba(255,77,79,.22);
            color:#c92a2a;
        }

        .alert-success {
            background:rgba(33,181,115,.10);
            border:1px solid rgba(33,181,115,.22);
            color:#087f5b;
        }

        .alert ul { margin-left:18px; }

        .register-form {
            display:grid;
            gap:8px;
        }

        .form-row {
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:10px;
        }

        .form-group label {
            display:block;
            font-size:11px;
            font-weight:950;
            color:var(--blue-dark);
            margin-bottom:5px;
            text-transform:uppercase;
            letter-spacing:.5px;
        }

        .input-wrap { position:relative; }

        .input-wrap i {
            position:absolute;
            left:13px;
            top:50%;
            transform:translateY(-50%);
            color:var(--sky-600);
            font-size:13px;
            z-index:2;
        }

        .textarea-icon { top:20px !important; }

        .form-control {
            width:100%;
            min-height:42px;
            border:2px solid #e2f2ff;
            background:rgba(255,255,255,.82);
            color:var(--blue-dark);
            border-radius:13px;
            padding:9px 12px 9px 38px;
            outline:none;
            font-size:13px;
            font-weight:750;
            transition:.24s;
        }

        textarea.form-control {
            min-height:52px;
            padding-top:12px;
            resize:vertical;
        }

        .form-control:focus {
            border-color:var(--sky-500);
            box-shadow:0 0 0 .22rem rgba(40,168,234,.13);
            background:#fff;
        }

        .form-control.invalid-field {
            border-color:var(--danger);
            background:#fff5f5;
            box-shadow:0 0 0 .22rem rgba(255,77,79,.13);
        }

        .password-wrap .form-control { padding-right:48px; }

        /* Hide browser default password reveal icon, keep only custom Font Awesome eye */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear,
        input[type="password"]::-webkit-credentials-auto-fill-button,
        input[type="password"]::-webkit-caps-lock-indicator,
        input[type="password"]::-webkit-contacts-auto-fill-button {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
            opacity: 0 !important;
            width: 0 !important;
            height: 0 !important;
        }

        .field-remark {
            margin-top: 5px;
            color: var(--muted);
            font-size: 10.5px;
            line-height: 1.35;
            font-weight: 650;
        }

        .field-remark i {
            color: var(--sky-600);
            margin-right: 5px;
        }


        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }

        input[type="password"]::-webkit-credentials-auto-fill-button,
        input[type="password"]::-webkit-caps-lock-indicator,
        input[type="password"]::-webkit-contacts-auto-fill-button {
            visibility: hidden;
            display: none !important;
            pointer-events: none;
        }


        .password-toggle {
            position:absolute;
            right:9px;
            top:50%;
            transform:translateY(-50%);
            border:0;
            background:transparent;
            color:var(--muted);
            cursor:pointer;
            width:30px;
            height:30px;
            border-radius:10px;
            z-index:3;
        }

        .password-toggle:hover {
            background:var(--sky-100);
            color:var(--sky-600);
        }

        .password-strength {
            margin-top:6px;
            height:5px;
            border-radius:999px;
            background:#e9f5ff;
            overflow:hidden;
        }

        .password-strength span {
            display:block;
            width:0;
            height:100%;
            border-radius:inherit;
            background:var(--danger);
            transition:.25s;
        }

        .password-box {
            margin-top:6px;
            padding:7px 10px;
            border-radius:14px;
            background:rgba(255,255,255,.72);
            border:1px solid var(--border);
        }

        .password-box strong {
            display:block;
            color:var(--blue-dark);
            font-size:10px;
            font-weight:950;
            margin-bottom:4px;
            text-transform:uppercase;
            letter-spacing:.5px;
        }

        .requirement-list {
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:3px 9px;
            list-style:none;
            color:var(--muted);
            font-size:10px;
            font-weight:650;
        }

        .requirement-list li {
            display:flex;
            align-items:center;
            gap:6px;
            line-height:1.25;
        }

        .requirement-list i { color:#a2b4c6; }

        .requirement-list li.pass,
        .requirement-list li.pass i { color:var(--green); }

        .form-note {
            color:var(--muted);
            font-size:10px;
            line-height:1.3;
            font-weight:650;
            margin-top:4px;
        }

        .terms-check {
            display:flex;
            align-items:flex-start;
            gap:12px;
            padding:14px 16px;
            border-radius:16px;
            background:rgba(255,255,255,.76);
            border:1px solid var(--border);
            color:#24415f;
            font-size:12px;
            font-weight:850;
            line-height:1.45;
        }

        .terms-check input {
            width:18px;
            height:18px;
            margin-top:1px;
            accent-color:var(--blue);
            flex:0 0 auto;
        }

        .terms-check a {
            color:var(--blue);
            font-weight:950;
            text-decoration:underline;
        }

        .terms-check.invalid-field {
            border-color:#ff4d4f;
            background:#fff5f5;
        }

        .submit-btn {
            position:relative;
            overflow:hidden;
            min-height:42px;
            border:0;
            border-radius:15px;
            background:linear-gradient(135deg,#ff9a4a 0%,#ff7a1a 48%,#f15f12 100%);
            color:#fff;
            font-size:14px;
            font-weight:950;
            cursor:pointer;
            box-shadow:0 18px 34px rgba(255,122,26,.28), inset 0 1px 0 rgba(255,255,255,.32);
            transition:.25s;
        }

        .submit-btn:hover {
            transform:translateY(-3px);
            box-shadow:0 24px 42px rgba(255,122,26,.36), inset 0 1px 0 rgba(255,255,255,.38);
        }

        .otp-box {
            display:grid;
            gap:14px;
        }

        .otp-icon {
            width:70px;
            height:70px;
            border-radius:22px;
            display:grid;
            place-items:center;
            margin:0 auto;
            background:linear-gradient(135deg,var(--sky-500),var(--sky-600));
            color:#fff;
            font-size:28px;
            box-shadow:0 18px 38px rgba(40,168,234,.26);
        }

        .otp-title { text-align:center; }

        .otp-title h3 {
            color:var(--blue-dark);
            font-size:28px;
            font-weight:950;
            letter-spacing:-1px;
            margin-bottom:8px;
        }

        .otp-title p {
            color:var(--muted);
            line-height:1.65;
            font-size:14px;
            font-weight:600;
        }

        .otp-input {
            text-align:center;
            padding:15px;
            font-size:24px;
            letter-spacing:7px;
            font-weight:950;
        }

        .resend-btn {
            border:0;
            background:transparent;
            color:var(--sky-600);
            font-weight:950;
            cursor:pointer;
            padding:12px;
            width:fit-content;
        }

        .bottom-link {
            display:block;
            text-align:center;
            margin-top:8px;
            color:var(--muted);
            font-size:12.5px;
            line-height:1.3;
            font-weight:650;
            position:relative;
            z-index:5;
        }

        .bottom-link a {
            color:var(--sky-600);
            font-weight:950;
        }

        @media (max-width:1100px) {
            .page { grid-template-columns:1fr; }
            .register-visual {
                position:relative;
                height:auto;
                min-height:auto;
            }
        }

        @media (max-width:720px) {
            .register-panel,
            .register-visual { padding:18px; }

            .register-card { padding:18px; }

            .form-row,
            .visual-cards,
            .requirement-list,
            .promo-mini-card { grid-template-columns:1fr; }

            .card-head { display:grid; }

            .login-link { width:fit-content; }

            .promo-percent {
                width:100%;
                height:86px;
            }
        }
    
        .field-remark {
            max-width: 100%;
        }

        @media (max-height: 760px) {
            .field-remark {
                font-size: 9.6px;
                margin-top: 3px;
                line-height: 1.25;
            }

            .register-form {
                gap: 6px;
            }

            .form-group label {
                margin-bottom: 4px;
            }
        }

    </style>
</head>
<body>
    <div class="page">
        <aside class="register-visual">
            <div class="premium-lines"></div>

            <div class="visual-content">
                <a class="brand" href="homepage.php">
                    <span class="brand-logo"><i class="fa-solid fa-car-side"></i></span>
                    <span>KH Car Rental</span>
                </a>

                <div class="visual-title">
                    <span><i class="fa-solid fa-envelope-circle-check"></i> EMAIL OTP VERIFICATION</span>
                    <h1>Create your rental account.</h1>
                    <p>Register with a valid email address. We will send an OTP code before creating your customer account.</p>

                    <div class="promo-mini-card">
                        <div>
                            <span><i class="fa-solid fa-gift"></i> NEW USER PROMOTION</span>
                            <h3>Enjoy your first rental deal</h3>
                            <p>Register now and your 5% first booking voucher will appear in your profile after login.</p>
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
                    <i class="fa-solid fa-lock"></i>
                    <strong>Strong Password</strong>
                    <small>Use uppercase, lowercase, number and symbol.</small>
                </div>

                <div class="visual-card">
                    <i class="fa-solid fa-envelope-open-text"></i>
                    <strong>Email OTP</strong>
                    <small>Verify your email before account creation.</small>
                </div>

                <div class="visual-card">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <strong>Login Next</strong>
                    <small>After successful verification, continue to login page.</small>
                </div>
            </div>
        </aside>

        <main class="register-panel">
            <section class="register-card">
                <div class="card-head">
                    <div class="card-head-text">
                        <span><i class="fa-solid fa-user-plus"></i> REGISTER ACCOUNT</span>
                        <h2><?= $step === "verify" ? "Verify Your Email" : "Join KH Car Rental" ?></h2>
                        <p><?= $step === "verify" ? "Enter the OTP code sent to your email address." : "Fill in your personal and driving details to create a customer account." ?></p>
                    </div>

                    <a class="login-link" href="login.php">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Login
                    </a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div>
                            <strong>Please check your details:</strong>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ""): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <div>
                            <strong>Success!</strong><br>
                            <?= e($success) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($step === "verify"): ?>
                    <div class="otp-box">
                        <div class="otp-icon">
                            <i class="fa-solid fa-envelope-circle-check"></i>
                        </div>

                        <div class="otp-title">
                            <h3>Enter OTP Code</h3>
                            <p>We sent a 6-digit OTP code to your email. The code will expire in 10 minutes.</p>
                        </div>

                        <form class="register-form" method="POST" action="register.php">
                            <input type="hidden" name="step" value="verify">
                            <input type="hidden" name="pending_id" value="<?= e($pending_id) ?>">

                            <div class="form-group">
                                <label for="otp_code">OTP Code</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-key"></i>
                                    <input class="form-control otp-input" type="text" name="otp_code" id="otp_code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Enter 6-digit OTP" required>
                                </div>
                            </div>

                            <button class="submit-btn" type="submit">
                                <i class="fa-solid fa-check"></i>
                                Verify OTP
                            </button>
                        </form>

                        <form method="POST" action="register.php">
                            <input type="hidden" name="step" value="resend">
                            <input type="hidden" name="pending_id" value="<?= e($pending_id) ?>">
                            <button class="resend-btn" type="submit">
                                <i class="fa-solid fa-rotate-right"></i>
                                Resend OTP
                            </button>
                        </form>

                        <p class="bottom-link">
                            Wrong email?
                            <a href="register.php">Register again</a>
                        </p>
                    </div>
                <?php else: ?>
                    <form class="register-form" id="registerForm" method="POST" action="register.php" novalidate>
                        <input type="hidden" name="step" value="register">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-user"></i>
                                    <input class="form-control" type="text" name="name" id="name" value="<?= e($name) ?>" placeholder="Enter your full name" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-envelope"></i>
                                    <input class="form-control" type="email" name="email" id="email" value="<?= e($email) ?>" placeholder="example@email.com" required>
                                </div>
                                <p class="field-remark"><i class="fa-solid fa-circle-info"></i>Use an email address that can receive OTP verification code.</p>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-phone"></i>
                                    <input class="form-control" type="tel" name="phone" id="phone" value="<?= e($phone) ?>" placeholder="+60 12-345 6789" maxlength="16" inputmode="tel" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-calendar-days"></i>
                                    <input class="form-control" type="date" name="date_of_birth" id="date_of_birth" value="<?= e($date_of_birth) ?>" max="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="ic_number">IC Number</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-id-card"></i>
                                    <input class="form-control" type="text" name="ic_number" id="ic_number" value="<?= e($ic_number) ?>" placeholder="010101-01-0101" required>
                                </div>
                                <p class="field-remark"><i class="fa-solid fa-circle-info"></i>Enter your real IC number for rental verification.</p>
                            </div>

                            <div class="form-group">
                                <label for="license_number">Driving License Number</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-id-badge"></i>
                                    <input class="form-control" type="text" name="license_number" id="license_number" value="<?= e($license_number) ?>" placeholder="D1234567" maxlength="20" pattern="[A-Za-z0-9]{5,20}" required>
                                </div>
                                <p class="field-remark"><i class="fa-solid fa-circle-info"></i>Enter your real driving license number for booking approval.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="license_expiry_date">Driving License Expiry Date</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-calendar-check"></i>
                                <input class="form-control" type="date" name="license_expiry_date" id="license_expiry_date" value="<?= e($license_expiry_date) ?>" min="<?= date('Y-m-d', strtotime('+6 months')) ?>" required>
                            </div>
                            <p class="field-remark"><i class="fa-solid fa-circle-info"></i>Your driving license must be valid for at least 6 more months.</p>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-location-dot textarea-icon"></i>
                                <textarea class="form-control" name="address" id="address" placeholder="Enter your address" required><?= e($address) ?></textarea>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="input-wrap password-wrap">
                                    <i class="fa-solid fa-lock"></i>
                                    <input class="form-control" type="password" name="password" id="password" placeholder="Create strong password" required>
                                    <button class="password-toggle" type="button" data-target="password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>

                                <div class="password-strength"><span id="strengthBar"></span></div>

                                <div class="password-box">
                                    <strong>Password Requirement</strong>
                                    <ul class="requirement-list">
                                        <li id="reqLength"><i class="fa-solid fa-circle"></i> At least 10 characters</li>
                                        <li id="reqUpper"><i class="fa-solid fa-circle"></i> At least 1 uppercase letter</li>
                                        <li id="reqLower"><i class="fa-solid fa-circle"></i> At least 1 lowercase letter</li>
                                        <li id="reqNumber"><i class="fa-solid fa-circle"></i> At least 1 number</li>
                                        <li id="reqSymbol"><i class="fa-solid fa-circle"></i> At least 1 special symbol</li>
                                    </ul>
                                </div>

                                <p class="form-note">Password must be at least 10 characters long and include uppercase letter, lowercase letter, number and special symbol.</p>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <div class="input-wrap password-wrap">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    <input class="form-control" type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter password" required>
                                    <button class="password-toggle" type="button" data-target="confirm_password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <p class="form-note" id="matchText">Confirm Password must match Password.</p>
                            </div>
                        </div>

                        <label class="terms-check">
                            <input type="checkbox" name="agree_terms" id="agree_terms" value="1" required>
                            <span>
                                I agree to KH Car Rental <a href="terms_conditions.php" target="_blank">Terms & Conditions</a>, including KYC verification, accident responsibility, traffic summons, vehicle damage, payment and privacy rules.
                            </span>
                        </label>

                        <button class="submit-btn" type="submit">
                            <i class="fa-solid fa-envelope-circle-check"></i>
                            Send OTP
                        </button>
                    </form>

                    <p class="bottom-link">
                        Already have an account?
                        <a href="login.php">Login</a>
                    </p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        const form = document.getElementById("registerForm");
        const password = document.getElementById("password");
        const confirmPassword = document.getElementById("confirm_password");
        const phoneInput = document.getElementById("phone");
        const licenseNumberInput = document.getElementById("license_number");
        const dateOfBirth = document.getElementById("date_of_birth");
        const licenseExpiryDate = document.getElementById("license_expiry_date");
        const agreeTerms = document.getElementById("agree_terms");
        const strengthBar = document.getElementById("strengthBar");
        const matchText = document.getElementById("matchText");
        const otpInput = document.getElementById("otp_code");

        const requirements = {
            reqLength: value => value.length >= 10,
            reqUpper: value => /[A-Z]/.test(value),
            reqLower: value => /[a-z]/.test(value),
            reqNumber: value => /[0-9]/.test(value),
            reqSymbol: value => /[^A-Za-z0-9]/.test(value)
        };

        function setInvalid(input, isInvalid) {
            if (input) input.classList.toggle("invalid-field", isInvalid);
        }

        function updateRequirement(id, passed) {
            const item = document.getElementById(id);
            if (!item) return;
            const icon = item.querySelector("i");
            item.classList.toggle("pass", passed);
            icon.className = passed ? "fa-solid fa-circle-check" : "fa-solid fa-circle";
        }

        function validatePassword(value) {
            return Object.values(requirements).every(test => test(value));
        }

        function updatePasswordStrength() {
            if (!password || !strengthBar) return;

            const value = password.value;
            let score = 0;

            Object.keys(requirements).forEach(id => {
                const passed = requirements[id](value);
                updateRequirement(id, passed);
                if (passed) score++;
            });

            const widths = ["0%", "20%", "40%", "60%", "80%", "100%"];
            const colors = ["var(--danger)", "var(--danger)", "var(--orange)", "var(--orange)", "var(--sky-600)", "var(--green)"];

            strengthBar.style.width = widths[score];
            strengthBar.style.background = colors[score];
            setInvalid(password, value.length > 0 && !validatePassword(value));
        }

        function updatePasswordMatch() {
            if (!password || !confirmPassword || !matchText) return;

            if (confirmPassword.value === "") {
                matchText.textContent = "Confirm Password must match Password.";
                matchText.style.color = "var(--muted)";
                setInvalid(confirmPassword, false);
                return;
            }

            if (password.value === confirmPassword.value) {
                matchText.textContent = "Password matched.";
                matchText.style.color = "var(--green)";
                setInvalid(confirmPassword, false);
            } else {
                matchText.textContent = "Password does not match.";
                matchText.style.color = "var(--danger)";
                setInvalid(confirmPassword, true);
            }
        }

        if (password && confirmPassword) {
            password.addEventListener("input", () => {
                updatePasswordStrength();
                updatePasswordMatch();
            });

            confirmPassword.addEventListener("input", updatePasswordMatch);
        }

        if (otpInput) {
            otpInput.addEventListener("input", () => {
                otpInput.value = otpInput.value.replace(/\D/g, "").slice(0, 6);
            });
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

        if (form) {
            form.addEventListener("submit", event => {
                let valid = true;

                form.querySelectorAll("[required]").forEach(input => {
                    if (input.type === "checkbox") {
                        const unchecked = !input.checked;
                        input.closest(".terms-check")?.classList.toggle("invalid-field", unchecked);
                        if (unchecked) valid = false;
                    } else {
                        const empty = input.value.trim() === "";
                        setInvalid(input, empty);
                        if (empty) valid = false;
                    }
                });

                if (agreeTerms && agreeTerms.checked) {
                    agreeTerms.closest(".terms-check")?.classList.remove("invalid-field");
                }

                if (dateOfBirth && dateOfBirth.value && dateOfBirth.value > new Date().toISOString().split("T")[0]) {
                    setInvalid(dateOfBirth, true);
                    valid = false;
                }

                if (phoneInput && phoneInput.value) {
                    const phoneDigits = phoneInput.value.replace(/\D/g, "");
                    if (phoneDigits.length < 10 || phoneDigits.length > 12) {
                        setInvalid(phoneInput, true);
                        valid = false;
                    }
                }

                if (licenseNumberInput && licenseNumberInput.value && !/^[A-Za-z0-9]{5,20}$/.test(licenseNumberInput.value.trim())) {
                    setInvalid(licenseNumberInput, true);
                    valid = false;
                }

                if (licenseExpiryDate && licenseExpiryDate.value) {
                    const minimumLicenseExpiry = new Date();
                    minimumLicenseExpiry.setMonth(minimumLicenseExpiry.getMonth() + 6);
                    const minDate = minimumLicenseExpiry.toISOString().split("T")[0];
                    if (licenseExpiryDate.value < minDate) {
                        setInvalid(licenseExpiryDate, true);
                        valid = false;
                    }
                }

                if (!validatePassword(password.value)) {
                    setInvalid(password, true);
                    valid = false;
                }

                if (password.value !== confirmPassword.value) {
                    setInvalid(confirmPassword, true);
                    valid = false;
                }

                if (!valid) {
                    event.preventDefault();
                    window.scrollTo({ top: 0, behavior: "smooth" });
                }
            });
        }
    </script>
</body>
</html>
