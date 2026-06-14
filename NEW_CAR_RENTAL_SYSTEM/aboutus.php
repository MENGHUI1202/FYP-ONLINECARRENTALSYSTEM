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

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us | KH Car Rental</title>
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
                <span class="kicker"><i class="fa-solid fa-circle-info"></i> About KH Car Rental</span>
                <h1>Reliable car rental made simple.</h1>
                <p>KH Car Rental is a web-based car rental service designed to help customers search, compare and book rental vehicles easily across Johor, Melaka and Kuala Lumpur.</p>
            </div>

            <div class="hero-visual">
                <i class="fa-solid fa-car-side"></i>
            </div>
        </section>

        <section class="section">
            <div class="section-head">
                <h2>Who We Are</h2>
                <p>We focus on making the rental process easier, safer and more organised for customers who need a vehicle for city travel, family trips, business travel or long-distance journeys.</p>
            </div>

            <div class="grid-2">
                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-building"></i></div>
                    <h3>Company Introduction</h3>
                    <p>KH Car Rental provides convenient car rental services with a user-friendly online system. Customers can browse vehicles, check availability, compare specifications and submit bookings from one platform.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-route"></i></div>
                    <h3>Our Story</h3>
                    <p>This system was created to reduce manual rental problems such as unclear availability, difficult car comparison and slow booking management. KH Car Rental brings the rental process into a cleaner digital workflow.</p>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-head">
                <h2>Our Mission</h2>
                <p>To provide a convenient, safe and reliable car rental experience for customers through verified vehicle information, clear booking steps and organised admin approval.</p>
            </div>

            <div class="grid-3">
                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-shield-halved"></i></div>
                    <h3>Safety</h3>
                    <p>We focus on verified cars, booking review and clear rental information so customers can rent with more confidence.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-handshake"></i></div>
                    <h3>Trust</h3>
                    <p>Every booking is managed with a structured process, including payment record, booking status and admin approval.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-bolt"></i></div>
                    <h3>Convenience</h3>
                    <p>Customers can search available cars, use Find Car Smart, compare vehicles and manage bookings online.</p>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-head">
                <h2>Why Choose Us</h2>
                <p>KH Car Rental is designed to make vehicle rental easier from browsing until booking confirmation.</p>
            </div>

            <div class="grid-3">
                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-tags"></i></div>
                    <h3>Affordable Price</h3>
                    <p>Customers can compare rental prices and choose a car that fits their budget and travel needs.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-calendar-check"></i></div>
                    <h3>Easy Booking</h3>
                    <p>The system supports trip search, availability checking, cart, payment and booking status tracking.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-car-rear"></i></div>
                    <h3>Verified Cars</h3>
                    <p>Car details include brand, category, engine, horsepower, seats, transmission and price per day.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-user-shield"></i></div>
                    <h3>Admin Approval</h3>
                    <p>Bookings are reviewed by admin before the rental process is fully approved and ready for pickup.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-map-location-dot"></i></div>
                    <h3>3 Rental States</h3>
                    <p>KH Car Rental supports Johor, Melaka and Kuala Lumpur with multiple pickup and drop-off locations.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-headset"></i></div>
                    <h3>Customer Support</h3>
                    <p>Customers can contact us for booking issues, payment questions or general enquiries.</p>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="stat-row">
                <div class="stat-card">
                    <strong>50+</strong>
                    <span>Rental Cars</span>
                </div>

                <div class="stat-card">
                    <strong>3</strong>
                    <span>Supported States</span>
                </div>

                <div class="stat-card">
                    <strong>36</strong>
                    <span>Pickup Locations</span>
                </div>

                <div class="stat-card">
                    <strong>24/7</strong>
                    <span>Online Access</span>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-head">
                <h2>Our Team</h2>
                <p>This system is developed as part of the KH Car Rental project to support a more professional online rental experience.</p>
            </div>

            <div class="grid-3">
                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-user"></i></div>
                    <h3>Hoo Meng Hui</h3>
                    <p>Project team member for KH Car Rental System.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-user"></i></div>
                    <h3>Pang Kang Horng</h3>
                    <p>Project team member for KH Car Rental System.</p>
                </div>

                <div class="info-card">
                    <div class="icon-box"><i class="fa-solid fa-user"></i></div>
                    <h3>Ng Meng Xin</h3>
                    <p>Project team member for KH Car Rental System.</p>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="cta-box">
                <div>
                    <h2>Ready to rent your next car?</h2>
                    <p>Browse our catalogue or let KH Smart Assistant recommend the best vehicle for your trip.</p>
                </div>

                <div class="btn-row">
                    <a class="btn btn-light" href="catalogue.php"><i class="fa-solid fa-car"></i> Browse Cars</a>
                    <a class="btn btn-primary" href="find_car_smart.php"><i class="fa-solid fa-wand-magic-sparkles"></i> Find Car Smart</a>
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
