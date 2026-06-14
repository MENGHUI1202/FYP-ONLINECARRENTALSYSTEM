<?php
require_once "config.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function kh_table_exists($conn, $table) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row["total"] ?? 0) > 0;
}

function kh_column_exists($conn, $table, $column) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
        AND COLUMN_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row["total"] ?? 0) > 0;
}

function one($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return null;
    }

    if ($types !== "" && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function get_nav_cart_count($conn) {
    $sessionCount = 0;

    if (!empty($_SESSION["cart"]) && is_array($_SESSION["cart"])) {
        $sessionCount += count($_SESSION["cart"]);
    }

    if (!empty($_SESSION["cart_items"]) && is_array($_SESSION["cart_items"])) {
        $sessionCount += count($_SESSION["cart_items"]);
    }

    if (empty($_SESSION["user_id"]) || !kh_table_exists($conn, "cart_items")) {
        return $sessionCount;
    }

    $statusFilter = "";

    if (kh_column_exists($conn, "cart_items", "status")) {
        $statusFilter = " AND LOWER(COALESCE(status, 'active')) NOT IN ('removed','checked_out','paid','completed')";
    }

    $row = one(
        $conn,
        "SELECT COUNT(*) AS total FROM cart_items WHERE user_id = ? $statusFilter",
        "i",
        [(int)$_SESSION["user_id"]]
    );

    return (int)($row["total"] ?? $sessionCount);
}

$user = null;

if (!empty($_SESSION["user_id"]) && kh_table_exists($conn, "users")) {
    $user = one($conn, "SELECT user_id, name, email, profile_picture FROM users WHERE user_id = ? LIMIT 1", "i", [(int)$_SESSION["user_id"]]);
}

$navCartCount = get_nav_cart_count($conn);

$navLinks = [
    ["title" => "HOME", "url" => "homepage.php", "icon" => "fa-solid fa-house"],
    ["title" => "CATALOGUE", "url" => "catalogue.php", "icon" => "fa-solid fa-car"],
    ["title" => "FIND CAR SMART", "url" => "find_car_smart.php", "icon" => "fa-solid fa-wand-magic-sparkles"],
    ["title" => "COMPARE CAR", "url" => "compare_car.php", "icon" => "fa-solid fa-code-compare"],
    ["title" => "ABOUT US", "url" => "aboutus.php", "icon" => "fa-solid fa-circle-info"],
    ["title" => "CONTACT US", "url" => "contactus.php", "icon" => "fa-solid fa-envelope"],
    ["title" => "CART", "url" => "cart.php", "icon" => "fa-solid fa-cart-shopping"]
];

$currentPage = basename($_SERVER["PHP_SELF"]);

$successMessage = "";
$errorMessage = "";

$conn->query("
    CREATE TABLE IF NOT EXISTS contact_messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(150) NOT NULL,
        support_category VARCHAR(80) NOT NULL DEFAULT 'General Enquiry',
        subject VARCHAR(180) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

if (kh_table_exists($conn, "contact_messages") && !kh_column_exists($conn, "contact_messages", "support_category")) {
    $conn->query("ALTER TABLE contact_messages ADD COLUMN support_category VARCHAR(80) NOT NULL DEFAULT 'General Enquiry' AFTER email");
}

if (kh_table_exists($conn, "contact_messages") && !kh_column_exists($conn, "contact_messages", "status")) {
    $conn->query("ALTER TABLE contact_messages ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'new' AFTER message");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_contact_message"])) {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $supportCategory = trim($_POST["support_category"] ?? "General Enquiry");
    $subject = trim($_POST["subject"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if ($name === "" || $email === "" || $supportCategory === "" || $subject === "" || $message === "") {
        $errorMessage = "Please complete all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO contact_messages
                (name, email, support_category, subject, message, status, created_at)
            VALUES
                (?, ?, ?, ?, ?, 'new', NOW())
        ");

        if ($stmt) {
            $stmt->bind_param("sssss", $name, $email, $supportCategory, $subject, $message);

            if ($stmt->execute()) {
                $successMessage = "Your message has been submitted successfully.";
                $_POST = [];
            } else {
                $errorMessage = "Unable to submit message. Please try again.";
            }

            $stmt->close();
        } else {
            $errorMessage = "Unable to submit message. Please try again.";
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us | KH Car Rental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>

:root{
    --sky50:#f5fbff;
    --sky100:#eaf7ff;
    --sky200:#d6efff;
    --sky300:#b8e4ff;
    --sky500:#28a8ea;
    --sky600:#1284c6;
    --dark:#17304f;
    --text:#17304f;
    --muted:#6e8297;
    --border:#d8ecfb;
    --white:#ffffff;
    --orange:#ff8a3d;
    --shadow:0 18px 55px rgba(39,137,199,.13);
    --shadow2:0 10px 30px rgba(39,137,199,.08);
}

*{
    box-sizing:border-box;
    margin:0;
    padding:0;
}

html{
    scroll-behavior:smooth;
}

body{
    font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at 18% 0%, rgba(184,228,255,.52), transparent 34%),
        radial-gradient(circle at 86% 12%, rgba(214,239,255,.58), transparent 30%),
        linear-gradient(180deg,#ffffff 0%,#f5fbff 26%,#eef9ff 58%,#ffffff 100%);
    min-height:100vh;
}

a{
    text-decoration:none;
    color:inherit;
}

img{
    max-width:100%;
    display:block;
}

.container{
    width:min(1200px,calc(100% - 40px));
    margin:auto;
}

/* ===== Navbar same system style ===== */
.navbar{
    position:sticky;
    top:0;
    z-index:999;
    background:
        linear-gradient(135deg,rgba(227,247,255,.96),rgba(255,255,255,.92)),
        radial-gradient(circle at 12% 50%,rgba(40,168,234,.22),transparent 35%);
    backdrop-filter:blur(14px);
    -webkit-backdrop-filter:blur(14px);
    border-bottom:1px solid rgba(40,168,234,.26);
    box-shadow:0 14px 38px rgba(18,132,198,.13);
}

.nav-wrap{
    min-height:82px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    position:relative;
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
    font-weight:950;
    letter-spacing:-.4px;
    color:var(--dark);
    white-space:nowrap;
    flex:0 0 auto;
}

.brand-logo{
    width:50px;
    height:50px;
    border-radius:18px;
    background:linear-gradient(135deg,#d8f2ff,#ffffff);
    color:var(--sky600);
    display:grid;
    place-items:center;
    box-shadow:0 10px 20px rgba(18,132,198,.13);
    border:1px solid var(--border);
    flex:0 0 50px;
}

.brand-logo i{
    color:var(--sky600);
    font-size:21px;
}

.nav-links{
    flex:1 1 auto;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    flex-wrap:nowrap;
    min-width:0;
}

.nav-link{
    position:relative;
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:10px 9px;
    border-radius:999px;
    color:#31516f;
    font-size:12px;
    font-weight:950;
    white-space:nowrap;
    transition:.22s ease;
    overflow:visible;
}

.nav-link i{
    color:#31516f;
    font-size:13px;
    line-height:1;
}

.nav-link:hover,
.nav-link.active{
    color:var(--sky600);
    background:var(--sky100);
    transform:translateY(-1px);
}

.nav-link:hover i,
.nav-link.active i{
    color:var(--sky600);
}

.nav-cart-link{
    position:relative;
}

.cart-count-badge{
    position:absolute;
    top:-9px;
    right:-10px;
    min-width:18px;
    height:18px;
    padding:0 5px;
    border-radius:999px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#ff4d4f,#d90429);
    color:#fff;
    border:2px solid #fff;
    box-shadow:0 8px 18px rgba(217,4,41,.25);
    font-size:10px;
    font-weight:950;
    line-height:1;
    z-index:20;
}

.nav-actions{
    position:relative;
    flex:0 0 auto;
}

.login-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:13px 20px;
    border-radius:999px;
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    font-weight:950;
    white-space:nowrap;
    box-shadow:0 14px 30px rgba(37,140,228,.22);
}

.avatar-btn{
    border:0;
    background:transparent;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:10px;
    padding:6px;
    border-radius:999px;
    color:var(--dark);
    font-weight:950;
}

.avatar-img,
.avatar-initial{
    width:44px;
    height:44px;
    min-width:44px;
    border-radius:50%;
    object-fit:cover;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#7fd0ff,#117de3);
    color:white;
    font-weight:950;
    border:3px solid white;
}

.profile-dropdown{
    position:absolute;
    top:62px;
    right:0;
    width:235px;
    background:rgba(255,255,255,.96);
    backdrop-filter:blur(18px);
    border:1px solid var(--border);
    box-shadow:var(--shadow);
    border-radius:20px;
    padding:10px;
    display:none;
    z-index:9999;
}

.profile-dropdown.show{
    display:block;
}

.profile-dropdown a{
    display:flex;
    align-items:center;
    gap:10px;
    padding:12px 13px;
    border-radius:14px;
    color:var(--text);
    font-size:14px;
    font-weight:850;
}

.profile-dropdown a:hover{
    background:var(--sky100);
    color:var(--sky600);
}

/* ===== Page shared style ===== */
.page-shell{
    padding:46px 0 80px;
}

.hero-card{
    position:relative;
    overflow:hidden;
    border-radius:36px;
    border:1px solid rgba(184,228,255,.95);
    background:
        radial-gradient(circle at 88% 15%, rgba(184,228,255,.75), transparent 32%),
        linear-gradient(135deg,rgba(255,255,255,.96),rgba(234,247,255,.92));
    box-shadow:var(--shadow);
    padding:54px;
    min-height:330px;
    display:grid;
    grid-template-columns:1.1fr .9fr;
    gap:30px;
    align-items:center;
}

.hero-card::before{
    content:"";
    position:absolute;
    right:-120px;
    top:-150px;
    width:430px;
    height:430px;
    border-radius:50%;
    background:rgba(40,168,234,.16);
}

.hero-card::after{
    content:"";
    position:absolute;
    right:80px;
    bottom:-130px;
    width:330px;
    height:330px;
    border-radius:50%;
    border:42px solid rgba(184,228,255,.55);
}

.hero-copy{
    position:relative;
    z-index:2;
}

.kicker{
    display:inline-flex;
    align-items:center;
    gap:9px;
    width:fit-content;
    padding:9px 15px;
    border-radius:999px;
    color:var(--sky600);
    background:rgba(40,168,234,.10);
    border:1px solid rgba(40,168,234,.20);
    font-size:12px;
    font-weight:950;
    letter-spacing:.8px;
    text-transform:uppercase;
    margin-bottom:18px;
}

.hero-copy h1{
    font-size:clamp(42px,5vw,72px);
    line-height:.98;
    letter-spacing:-2.5px;
    color:var(--dark);
    font-weight:950;
    margin-bottom:16px;
}

.hero-copy p{
    max-width:680px;
    color:var(--muted);
    font-size:16px;
    line-height:1.75;
    font-weight:700;
}

.hero-visual{
    position:relative;
    z-index:2;
    min-height:230px;
    border-radius:30px;
    display:grid;
    place-items:center;
    background:
        radial-gradient(circle at 30% 20%, rgba(255,255,255,.42), transparent 34%),
        linear-gradient(135deg,#17304f,#0f4f7d);
    color:#fff;
    box-shadow:0 26px 60px rgba(23,48,79,.22);
}

.hero-visual i{
    font-size:96px;
    color:#7fd0ff;
    filter:drop-shadow(0 18px 30px rgba(40,168,234,.28));
}

.section{
    padding:70px 0 0;
}

.section-head{
    text-align:center;
    margin-bottom:34px;
}

.section-head h2{
    color:var(--dark);
    font-size:clamp(32px,4vw,52px);
    font-weight:950;
    letter-spacing:-1.4px;
    margin-bottom:10px;
}

.section-head p{
    max-width:760px;
    margin:auto;
    color:var(--muted);
    font-weight:700;
    line-height:1.7;
}

.grid-3{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:22px;
}

.grid-2{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:22px;
}

.info-card{
    position:relative;
    overflow:hidden;
    border-radius:28px;
    border:1px solid var(--border);
    background:rgba(255,255,255,.92);
    box-shadow:var(--shadow2);
    padding:28px;
    transition:.25s ease;
}

.info-card:hover{
    transform:translateY(-6px);
    box-shadow:var(--shadow);
    border-color:var(--sky300);
}

.icon-box{
    width:58px;
    height:58px;
    border-radius:20px;
    display:grid;
    place-items:center;
    color:var(--sky600);
    background:var(--sky100);
    border:1px solid var(--border);
    font-size:22px;
    margin-bottom:16px;
}

.info-card h3{
    color:var(--dark);
    font-size:20px;
    font-weight:950;
    margin-bottom:9px;
}

.info-card p{
    color:var(--muted);
    line-height:1.7;
    font-size:14px;
    font-weight:650;
}

.stat-row{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
}

.stat-card{
    border-radius:28px;
    padding:26px;
    background:linear-gradient(135deg,#fff,#f4fbff);
    border:1px solid var(--border);
    box-shadow:var(--shadow2);
    text-align:center;
}

.stat-card strong{
    display:block;
    color:var(--sky600);
    font-size:42px;
    font-weight:950;
    line-height:1;
}

.stat-card span{
    display:block;
    margin-top:8px;
    color:var(--muted);
    font-size:13px;
    font-weight:850;
}

.cta-box{
    border-radius:34px;
    padding:42px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:22px;
    background:linear-gradient(135deg,#17304f,#0e5d90);
    color:#fff;
    box-shadow:0 28px 75px rgba(23,48,79,.22);
}

.cta-box h2{
    font-size:34px;
    font-weight:950;
    letter-spacing:-1px;
    margin-bottom:8px;
}

.cta-box p{
    color:rgba(255,255,255,.75);
    line-height:1.7;
    font-weight:650;
}

.btn-row{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
}

.btn{
    border:0;
    min-height:48px;
    padding:0 18px;
    border-radius:16px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    font-weight:950;
    cursor:pointer;
    transition:.22s ease;
}

.btn:hover{
    transform:translateY(-2px);
}

.btn-primary{
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    box-shadow:0 14px 30px rgba(40,168,234,.22);
}

.btn-light{
    color:var(--sky600);
    background:#fff;
    border:1px solid var(--border);
}

.footer{
    background:var(--dark);
    color:#fff;
    padding:70px 0 24px;
    margin-top:80px;
}

.footer-grid{
    display:grid;
    grid-template-columns:1.35fr .85fr 1.1fr;
    gap:40px;
    margin-bottom:36px;
}

.footer h3{
    margin-bottom:16px;
    font-size:21px;
    color:#fff;
}

.footer p,
.footer a{
    color:rgba(255,255,255,.78);
    line-height:1.75;
    font-size:14px;
}

.footer-links,
.contact-list{
    list-style:none;
    display:grid;
    gap:10px;
}

.contact-list li{
    display:flex;
    align-items:flex-start;
    gap:11px;
}

.contact-list i{
    color:var(--sky300);
    margin-top:5px;
    width:17px;
}

.footer-bottom{
    border-top:1px solid rgba(255,255,255,.14);
    padding-top:20px;
    text-align:center;
    color:rgba(255,255,255,.65);
    font-size:13px;
}

@media(max-width:1080px){
    .nav-wrap{
        flex-wrap:wrap;
        padding:10px 0;
    }

    .nav-links{
        order:5;
        width:100%;
        overflow-x:auto;
        justify-content:flex-start;
        padding-bottom:4px;
    }

    .hero-card,
    .grid-2{
        grid-template-columns:1fr;
    }

    .grid-3,
    .stat-row{
        grid-template-columns:repeat(2,1fr);
    }

    .footer-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:640px){
    .container{
        width:min(100% - 26px,1200px);
    }

    .hero-card{
        padding:32px 22px;
    }

    .grid-3,
    .stat-row{
        grid-template-columns:1fr;
    }

    .cta-box{
        display:grid;
    }
}


.contact-layout{
    display:grid;
    grid-template-columns:1.06fr .94fr;
    gap:24px;
    align-items:start;
}

.contact-form-card{
    border-radius:32px;
    padding:30px;
    background:rgba(255,255,255,.94);
    border:1px solid var(--border);
    box-shadow:var(--shadow);
}

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}

.form-field.full{
    grid-column:1/-1;
}

.form-field label{
    display:block;
    margin-bottom:8px;
    color:var(--dark);
    font-size:12px;
    font-weight:950;
    text-transform:uppercase;
    letter-spacing:.5px;
}

.form-input{
    width:100%;
    min-height:52px;
    border:2px solid #e2f2ff;
    border-radius:16px;
    background:#fbfdff;
    color:var(--dark);
    padding:13px 14px;
    outline:none;
    font-size:14px;
    font-weight:750;
}

textarea.form-input{
    min-height:150px;
    resize:vertical;
}

.form-input:focus{
    border-color:var(--sky500);
    box-shadow:0 0 0 .22rem rgba(40,168,234,.13);
    background:#fff;
}

.alert{
    margin-bottom:18px;
    padding:14px 16px;
    border-radius:18px;
    font-size:14px;
    font-weight:850;
    display:flex;
    align-items:center;
    gap:10px;
}

.alert-success{
    color:#087747;
    background:#eefbf4;
    border:1px solid rgba(33,181,115,.22);
}

.alert-error{
    color:#b42318;
    background:#fff4f2;
    border:1px solid rgba(244,67,54,.22);
}

.contact-stack{
    display:grid;
    gap:18px;
}

.contact-card{
    border-radius:28px;
    padding:24px;
    background:#fff;
    border:1px solid var(--border);
    box-shadow:var(--shadow2);
}

.contact-card h3{
    color:var(--dark);
    font-size:22px;
    font-weight:950;
    margin-bottom:14px;
}

.contact-line{
    display:flex;
    gap:12px;
    align-items:flex-start;
    margin:13px 0;
    color:var(--muted);
    font-weight:700;
    line-height:1.55;
}

.contact-line i{
    width:36px;
    height:36px;
    border-radius:14px;
    display:grid;
    place-items:center;
    color:var(--sky600);
    background:var(--sky100);
    flex:0 0 36px;
}

.map-box{
    height:300px;
    border-radius:24px;
    overflow:hidden;
    border:1px solid var(--border);
    box-shadow:var(--shadow2);
    background:var(--sky100);
}

.map-box iframe{
    width:100%;
    height:100%;
    border:0;
}

.support-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
}

@media(max-width:980px){
    .contact-layout,
    .form-grid,
    .support-grid{
        grid-template-columns:1fr;
    }
}

    </style>
</head>
<body>

<header class="navbar">
    <div class="container nav-wrap">
        <a href="homepage.php" class="brand">
            <span class="brand-logo"><i class="fa-solid fa-car-side"></i></span>
            <span>KH Car Rental</span>
        </a>

        <nav class="nav-links">
            <?php foreach ($navLinks as $link): ?>
                <?php $isCartLink = strtoupper($link["title"]) === "CART"; ?>
                <a class="nav-link <?= $isCartLink ? "nav-cart-link" : "" ?> <?= $currentPage === $link["url"] ? "active" : "" ?>" href="<?= e($link["url"]) ?>">
                    <i class="<?= e($link["icon"]) ?>"></i>
                    <span><?= e($link["title"]) ?></span>
                    <?php if ($isCartLink && $navCartCount > 0): ?>
                        <span class="cart-count-badge"><?= e($navCartCount > 99 ? "99+" : $navCartCount) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="nav-actions">
            <?php if ($user): ?>
                <button class="avatar-btn" id="avatarBtn" type="button">
                    <?php if (!empty($user["profile_picture"])): ?>
                        <img class="avatar-img" src="<?= e($user["profile_picture"]) ?>" alt="<?= e($user["name"]) ?>">
                    <?php else: ?>
                        <span class="avatar-initial"><?= e(strtoupper(substr($user["name"], 0, 1))) ?></span>
                    <?php endif; ?>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>

                <div class="profile-dropdown" id="profileDropdown">
                    <a href="my_profile.php"><i class="fa-solid fa-user"></i> Manage My Profile</a>
                    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            <?php else: ?>
                <a class="login-btn" href="login.php"><i class="fa-solid fa-user"></i> Login / Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>


<main class="page-shell">
    <div class="container">
        <section class="hero-card">
            <div class="hero-copy">
                <span class="kicker"><i class="fa-solid fa-envelope"></i> Contact KH Car Rental</span>
                <h1>Need help with your rental?</h1>
                <p>Send us your enquiry, booking issue or payment question. Our team will review your message and assist you as soon as possible.</p>
            </div>

            <div class="hero-visual">
                <i class="fa-solid fa-headset"></i>
            </div>
        </section>

        <section class="section">
            <div class="contact-layout">
                <div class="contact-form-card">
                    <div class="section-head" style="text-align:left;margin-bottom:22px;">
                        <span class="kicker"><i class="fa-solid fa-paper-plane"></i> Send Message</span>
                        <h2 style="font-size:34px;">Contact Form</h2>
                        <p style="margin:0;">Fill in your details and your message will be saved into the contact messages record.</p>
                    </div>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check"></i>
                            <?= e($successMessage) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-error">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <?= e($errorMessage) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="contactus.php" class="form-grid">
                        <div class="form-field">
                            <label>Name</label>
                            <input class="form-input" type="text" name="name" value="<?= e($_POST["name"] ?? ($user["name"] ?? "")) ?>" required>
                        </div>

                        <div class="form-field">
                            <label>Email</label>
                            <input class="form-input" type="email" name="email" value="<?= e($_POST["email"] ?? ($user["email"] ?? "")) ?>" required>
                        </div>

                        <div class="form-field">
                            <label>Support Category</label>
                            <select class="form-input" name="support_category">
                                <?php $selectedCategory = $_POST["support_category"] ?? "General Enquiry"; ?>
                                <option value="General Enquiry" <?= $selectedCategory === "General Enquiry" ? "selected" : "" ?>>General Enquiry</option>
                                <option value="Booking Issue" <?= $selectedCategory === "Booking Issue" ? "selected" : "" ?>>Booking Issue</option>
                                <option value="Payment Issue" <?= $selectedCategory === "Payment Issue" ? "selected" : "" ?>>Payment Issue</option>
                                <option value="Vehicle Availability" <?= $selectedCategory === "Vehicle Availability" ? "selected" : "" ?>>Vehicle Availability</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label>Subject</label>
                            <input class="form-input" type="text" name="subject" value="<?= e($_POST["subject"] ?? "") ?>" required>
                        </div>

                        <div class="form-field full">
                            <label>Message</label>
                            <textarea class="form-input" name="message" required><?= e($_POST["message"] ?? "") ?></textarea>
                        </div>

                        <div class="form-field full">
                            <button class="btn btn-primary" type="submit" name="submit_contact_message" value="1">
                                <i class="fa-solid fa-paper-plane"></i> Submit Message
                            </button>
                        </div>
                    </form>
                </div>

                <div class="contact-stack">
                    <div class="contact-card">
                        <h3>Contact Information</h3>

                        <div class="contact-line">
                            <i class="fa-solid fa-phone"></i>
                            <span>+60 12-345 6789</span>
                        </div>

                        <div class="contact-line">
                            <i class="fa-solid fa-envelope"></i>
                            <span>hoomenghui@student.mmu.edu.my</span>
                        </div>

                        <div class="contact-line">
                            <i class="fa-solid fa-envelope"></i>
                            <span>pangkanghorng@student.mmu.edu.my</span>
                        </div>

                        <div class="contact-line">
                            <i class="fa-solid fa-envelope"></i>
                            <span>ngmengxin@student.mmu.edu.my</span>
                        </div>

                        <div class="contact-line">
                            <i class="fa-solid fa-location-dot"></i>
                            <span>Multimedia University, Jalan Ayer Keroh Lama, 75450 Bukit Beruang, Melaka</span>
                        </div>
                    </div>

                    <div class="contact-card">
                        <h3>MMU Melaka Location</h3>
                        <div class="map-box">
                            <iframe src="https://maps.google.com/maps?q=Multimedia%20University%20Melaka&t=&z=15&ie=UTF8&iwloc=&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-head">
                <h2>How We Can Help</h2>
                <p>Choose the correct support category when submitting the form so we can understand your enquiry faster.</p>
            </div>

            <div class="support-grid">
                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-calendar-check"></i></div>
                    <h3>Booking Issue</h3>
                    <p>For questions about submitted bookings, booking status, pickup date or return date.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-credit-card"></i></div>
                    <h3>Payment Issue</h3>
                    <p>For payment status, receipt, invoice or voucher discount enquiries.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-circle-question"></i></div>
                    <h3>General Enquiry</h3>
                    <p>For general questions about KH Car Rental services, locations or available cars.</p>
                </div>
            </div>
        </section>
    </div>
</main>


<footer class="footer">
    <div class="container footer-grid">
        <div>
            <h3>KH Car Rental</h3>
            <p>KH Car Rental provides reliable, affordable and convenient car rental services across Johor, Melaka and Kuala Lumpur. Customers can search available cars, compare vehicles and manage bookings easily through our online system.</p>
            <br>
            <a href="catalogue.php" class="btn btn-primary"><i class="fa-solid fa-car"></i> START Browse</a>
        </div>

        <div>
            <h3>Quick Links</h3>
            <ul class="footer-links">
                <li><a href="homepage.php">HOME</a></li>
                <li><a href="catalogue.php">CATALOGUE</a></li>
                <li><a href="find_car_smart.php">FIND CAR SMART</a></li>
                <li><a href="compare_car.php">COMPARE CAR</a></li>
                <li><a href="aboutus.php">ABOUT US</a></li>
                <li><a href="contactus.php">CONTACT US</a></li>
                <li><a href="cart.php">CART</a></li>
            </ul>
        </div>

        <div>
            <h3>Contact</h3>
            <ul class="contact-list">
                <li><i class="fa-solid fa-phone"></i><span>+60 12-345 6789</span></li>
                <li><i class="fa-solid fa-envelope"></i><span>hoomenghui@student.mmu.edu.my</span></li>
                <li><i class="fa-solid fa-envelope"></i><span>pangkanghorng@student.mmu.edu.my</span></li>
                <li><i class="fa-solid fa-envelope"></i><span>ngmengxin@student.mmu.edu.my</span></li>
                <li><i class="fa-solid fa-location-dot"></i><span>Multimedia University, Melaka</span></li>
            </ul>
        </div>
    </div>

    <div class="container footer-bottom">© 2026 KH Car Rental. All rights reserved.</div>
</footer>

<script>
const avatarBtn = document.getElementById("avatarBtn");
const profileDropdown = document.getElementById("profileDropdown");

if (avatarBtn && profileDropdown) {
    avatarBtn.addEventListener("click", function(event) {
        event.stopPropagation();
        profileDropdown.classList.toggle("show");
    });

    document.addEventListener("click", function() {
        profileDropdown.classList.remove("show");
    });
}
</script>

</body>
</html>
