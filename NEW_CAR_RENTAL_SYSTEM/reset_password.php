<?php
require_once "config.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function passwordIsStrong($password) {
    return strlen($password) >= 10
        && preg_match("/[A-Z]/", $password)
        && preg_match("/[a-z]/", $password)
        && preg_match("/[0-9]/", $password)
        && preg_match("/[^A-Za-z0-9]/", $password);
}

$token = trim($_GET["token"] ?? $_POST["token"] ?? "");
$errors = [];
$success = "";
$validToken = false;
$resetData = null;

if ($token !== "") {
    $stmt = $conn->prepare("
        SELECT pr.reset_id, pr.user_id, pr.email, pr.expires_at, pr.used, u.name
        FROM password_resets pr
        INNER JOIN users u ON pr.user_id = u.user_id
        WHERE pr.reset_token = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resetData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($resetData && (int)$resetData["used"] === 0 && strtotime($resetData["expires_at"]) >= time()) {
        $validToken = true;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $validToken) {
    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($newPassword === "") {
        $errors[] = "New password is required.";
    } elseif (!passwordIsStrong($newPassword)) {
        $errors[] = "Password must be at least 10 characters long and include uppercase letter, lowercase letter, number and special symbol.";
    }

    if ($confirmPassword === "") {
        $errors[] = "Confirm password is required.";
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = "Confirm password must match new password.";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $updateUser = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $updateUser->bind_param("si", $hashedPassword, $resetData["user_id"]);

        if ($updateUser->execute()) {
            $updateUser->close();

            $markUsed = $conn->prepare("UPDATE password_resets SET used = 1 WHERE reset_id = ?");
            $markUsed->bind_param("i", $resetData["reset_id"]);
            $markUsed->execute();
            $markUsed->close();

            $success = "Password reset successful. Redirecting to login page...";
            $validToken = false;
        } else {
            $errors[] = "Password update failed. Please try again.";
            $updateUser->close();
        }
    }
}

$invalidToken = $token === "" || (!$validToken && $success === "");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | KH Car Rental</title>
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
            --orange: #ff8a3d;
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

        .auth-card {
            width: min(620px, 100%);
            padding: 36px;
            border-radius: 34px;
            background:
                radial-gradient(circle at 88% 8%, rgba(184,228,255,0.42), transparent 26%),
                linear-gradient(135deg, rgba(255,255,255,0.96), rgba(234,247,255,0.86));
            border: 1px solid rgba(184,228,255,0.95);
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 13px;
            font-size: 18px;
            font-weight: 950;
            margin-bottom: 26px;
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            background: linear-gradient(135deg, #d8f2ff, #ffffff);
            border: 1px solid var(--border);
            color: var(--sky-600);
            display: grid;
            place-items: center;
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

        .alert ul {
            margin-left: 18px;
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
            padding: 13px 48px 13px 42px;
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

        .password-box {
            margin-top: 8px;
            padding: 13px 14px;
            border-radius: 18px;
            background: rgba(255,255,255,0.72);
            border: 1px solid var(--border);
        }

        .password-box strong {
            display: block;
            color: var(--blue-dark);
            font-size: 12px;
            font-weight: 950;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .requirement-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 10px;
            list-style: none;
            color: var(--muted);
            font-size: 12px;
            font-weight: 650;
        }

        .requirement-list li {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirement-list li.pass,
        .requirement-list li.pass i {
            color: var(--green);
        }

        .form-note {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
            font-weight: 650;
            margin-top: 7px;
        }

        .submit-btn {
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

        @media (max-width: 600px) {
            body {
                padding: 14px;
            }

            .auth-card {
                padding: 24px;
            }

            .requirement-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <section class="auth-card">
        <a class="brand" href="homepage.php">
            <span class="brand-logo"><i class="fa-solid fa-car-side"></i></span>
            <span>KH Car Rental</span>
        </a>

        <div class="card-head">
            <span><i class="fa-solid fa-key"></i> RESET PASSWORD</span>
            <h2>Create New Password</h2>
            <p>Enter a new strong password for your account.</p>
        </div>

        <?php if ($success !== ""): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <div><?= e($success) ?></div>
            </div>

            <script>
                setTimeout(() => {
                    window.location.href = "login.php?reset=1";
                }, 1800);
            </script>
        <?php endif; ?>

        <?php if ($invalidToken): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>
                    This reset link is invalid, expired, or already used.
                    Please request a new password reset link.
                </div>
            </div>

            <p class="bottom-link">
                <a href="forgot_password.php">Request new reset link</a>
            </p>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div>
                        <strong>Please check your password:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form class="form" method="POST" action="reset_password.php" id="resetForm" novalidate>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input class="form-control" type="password" name="new_password" id="new_password" placeholder="Create new password" required>
                        <button class="password-toggle" type="button" data-target="new_password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>

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
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-shield-halved"></i>
                        <input class="form-control" type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter new password" required>
                        <button class="password-toggle" type="button" data-target="confirm_password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <p class="form-note" id="matchText">Confirm Password must match New Password.</p>
                </div>

                <button class="submit-btn" type="submit">
                    <i class="fa-solid fa-check"></i>
                    Reset Password
                </button>
            </form>
        <?php endif; ?>

        <p class="bottom-link">
            Remember your password?
            <a href="login.php">Login</a>
        </p>
    </section>

    <script>
        const form = document.getElementById("resetForm");
        const password = document.getElementById("new_password");
        const confirmPassword = document.getElementById("confirm_password");
        const matchText = document.getElementById("matchText");

        const requirements = {
            reqLength: value => value.length >= 10,
            reqUpper: value => /[A-Z]/.test(value),
            reqLower: value => /[a-z]/.test(value),
            reqNumber: value => /[0-9]/.test(value),
            reqSymbol: value => /[^A-Za-z0-9]/.test(value)
        };

        function updateRequirement(id, passed) {
            const item = document.getElementById(id);

            if (!item) {
                return;
            }

            const icon = item.querySelector("i");
            item.classList.toggle("pass", passed);
            icon.className = passed ? "fa-solid fa-circle-check" : "fa-solid fa-circle";
        }

        function validatePassword(value) {
            return Object.keys(requirements).every(id => {
                const passed = requirements[id](value);
                updateRequirement(id, passed);
                return passed;
            });
        }

        function updatePasswordMatch() {
            if (!password || !confirmPassword || !matchText) {
                return;
            }

            validatePassword(password.value);

            if (confirmPassword.value === "") {
                matchText.textContent = "Confirm Password must match New Password.";
                matchText.style.color = "var(--muted)";
                return;
            }

            if (password.value === confirmPassword.value) {
                matchText.textContent = "Password matched.";
                matchText.style.color = "var(--green)";
            } else {
                matchText.textContent = "Password does not match.";
                matchText.style.color = "var(--danger)";
            }
        }

        if (password && confirmPassword) {
            password.addEventListener("input", updatePasswordMatch);
            confirmPassword.addEventListener("input", updatePasswordMatch);
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
                if (!validatePassword(password.value) || password.value !== confirmPassword.value) {
                    event.preventDefault();
                    updatePasswordMatch();
                }
            });
        }
    </script>
</body>
</html>
