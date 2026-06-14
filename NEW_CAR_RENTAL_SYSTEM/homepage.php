<?php
require_once "config.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rows($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    if ($types !== "" && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

function one($conn, $sql, $types = "", $params = []) {
    $data = rows($conn, $sql, $types, $params);
    return $data[0] ?? null;
}

function statePageUrl($state) {
    $slug = "";

    if (!empty($state["state_slug"])) {
        $slug = $state["state_slug"];
    } elseif (!empty($state["slug"])) {
        $slug = $state["slug"];
    } elseif (!empty($state["state_name"])) {
        $slug = $state["state_name"];
    } elseif (!empty($state["name"])) {
        $slug = $state["name"];
    }

    $slug = strtolower(trim((string)$slug));
    $slug = str_replace("_", "-", $slug);
    $slug = preg_replace("/[^a-z0-9]+/", "-", $slug);
    $slug = trim($slug, "-");

    if ($slug === "kl" || $slug === "kuala-lumpur" || $slug === "kuala-lumpur-city") {
        $slug = "kuala-lumpur";
    }

    if ($slug === "") {
        $slug = "johor";
    }

    return "state.php?state=" . $slug;
}


function homepageTableExists($conn, $table) {
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

function homepageColumnExists($conn, $table, $column) {
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

function getHomepageCartCount($conn) {
    $sessionCount = 0;

    if (!empty($_SESSION["cart"]) && is_array($_SESSION["cart"])) {
        $sessionCount += count($_SESSION["cart"]);
    }

    if (!empty($_SESSION["cart_items"]) && is_array($_SESSION["cart_items"])) {
        $sessionCount += count($_SESSION["cart_items"]);
    }

    if (empty($_SESSION["user_id"]) || !homepageTableExists($conn, "cart_items")) {
        return $sessionCount;
    }

    $userId = (int)$_SESSION["user_id"];
    $statusFilter = "";

    if (homepageColumnExists($conn, "cart_items", "status")) {
        $statusFilter = " AND LOWER(COALESCE(status, 'active')) NOT IN ('removed','checked_out','paid','completed')";
    }

    $row = one(
        $conn,
        "SELECT COUNT(*) AS total FROM cart_items WHERE user_id = ? $statusFilter",
        "i",
        [$userId]
    );

    return (int)($row["total"] ?? $sessionCount);
}

function homepageKycNeedsAttention($conn, $userId) {
    if (empty($userId) || !homepageTableExists($conn, "user_documents")) {
        return true;
    }

    $statusCol = homepageColumnExists($conn, "user_documents", "verification_status")
        ? "verification_status"
        : (homepageColumnExists($conn, "user_documents", "status") ? "status" : null);

    if (!$statusCol || !homepageColumnExists($conn, "user_documents", "document_type")) {
        return true;
    }

    $requiredTypes = ["IC Photo", "Driving License Photo"];
    $fileFilter = homepageColumnExists($conn, "user_documents", "file_path")
        ? "AND TRIM(COALESCE(file_path,'')) <> ''"
        : "";

    foreach ($requiredTypes as $type) {
        $row = one(
            $conn,
            "SELECT $statusCol AS verification_status
             FROM user_documents
             WHERE user_id = ? AND document_type = ? $fileFilter
             ORDER BY uploaded_at DESC, document_id DESC
             LIMIT 1",
            "is",
            [(int)$userId, $type]
        );

        if (!$row || strtolower(trim((string)($row["verification_status"] ?? ""))) !== "verified") {
            return true;
        }
    }

    return false;
}


$navLinks = [
    ["title" => "HOME", "url" => "homepage.php", "icon" => "fa-solid fa-house"],
    ["title" => "CATALOGUE", "url" => "catalogue.php", "icon" => "fa-solid fa-car"],
    ["title" => "FIND CAR SMART", "url" => "find_car_smart.php", "icon" => "fa-solid fa-wand-magic-sparkles"],
    ["title" => "COMPARE CAR", "url" => "compare_car.php", "icon" => "fa-solid fa-code-compare"],
    ["title" => "ABOUT US", "url" => "aboutus.php", "icon" => "fa-solid fa-circle-info"],
    ["title" => "CONTACT US", "url" => "contactus.php", "icon" => "fa-solid fa-envelope"],
    ["title" => "CART", "url" => "cart.php", "icon" => "fa-solid fa-cart-shopping"]
];

$settings = one($conn, "SELECT * FROM homepage_settings WHERE setting_key = 'main' LIMIT 1");
$promoPublicFilter = homepageTableExists($conn, "promo_code_assignments")
    ? "AND NOT EXISTS (SELECT 1 FROM promo_code_assignments pca WHERE pca.promo_id = promo_codes.id)"
    : "";
$promoDeletedFilter = homepageColumnExists($conn, "promo_codes", "deleted_at") ? "AND deleted_at IS NULL" : "";
$newUserPromo = one($conn, "SELECT promo_name, promo_code, discount_percent, description FROM promo_codes WHERE status = 'active' AND show_on_homepage = 1 $promoDeletedFilter $promoPublicFilter ORDER BY id DESC LIMIT 1");
$heroSlides = rows($conn, "SELECT * FROM homepage_hero_slides WHERE is_active = 1 ORDER BY sort_order ASC");
$featureBadges = rows($conn, "SELECT * FROM homepage_feature_badges WHERE is_active = 1 ORDER BY sort_order ASC");
$states = rows($conn, "SELECT * FROM rental_states WHERE status = 'active' ORDER BY sort_order ASC");

$locations = rows($conn, "
    SELECT rl.*, rs.state_slug
    FROM rental_locations rl
    INNER JOIN rental_states rs ON rl.state_id = rs.state_id
    WHERE rl.status = 'active' AND rs.status = 'active'
    ORDER BY rs.sort_order ASC, rl.location_name ASC
");

$popularCars = rows($conn, "
    SELECT
        c.car_id,
        c.car_name,
        c.model,
        c.year,
        c.type,
        c.price_per_day,
        c.engine,
        c.horsepower,
        c.acceleration_0_100,
        c.torque,
        c.transmission,
        c.drivetrain,
        c.fuel_type,
        c.fuel_consumption,
        c.seats,
        c.doors,
        c.luggage_capacity,
        c.air_conditioning,
        c.main_image,
        c.status,
        b.brand_name,
        cat.category_name,
        COUNT(cu.unit_id) AS available_units
    FROM cars c
    INNER JOIN brands b ON c.brand_id = b.brand_id
    INNER JOIN categories cat ON c.category_id = cat.category_id
    LEFT JOIN car_units cu
        ON c.car_id = cu.car_id
        AND cu.current_status = 'available'
    WHERE c.is_featured = 1
    AND c.status = 'active'
    GROUP BY c.car_id
    ORDER BY c.sort_order ASC, c.car_id DESC
    LIMIT 6
");

$carImagesRows = rows($conn, "
    SELECT image_id, car_id, image_url, image_type, sort_order
    FROM car_images
    ORDER BY car_id ASC, sort_order ASC
");

$whyChoose = rows($conn, "SELECT * FROM homepage_why_choose WHERE is_active = 1 ORDER BY sort_order ASC");
$howItWorks = rows($conn, "SELECT * FROM homepage_how_it_works WHERE is_active = 1 ORDER BY step_number ASC");
$faqItems = rows($conn, "SELECT * FROM homepage_faq WHERE is_active = 1 ORDER BY sort_order ASC");
$footerContacts = rows($conn, "SELECT * FROM footer_contacts WHERE is_active = 1 ORDER BY sort_order ASC");

$locationMap = [];
foreach ($locations as $location) {
    $locationMap[$location['state_slug']][] = [
        "id" => $location["location_id"],
        "name" => $location["location_name"]
    ];
}

$carImageMap = [];
foreach ($carImagesRows as $img) {
    $carImageMap[$img['car_id']][] = [
        "url" => $img["image_url"],
        "type" => $img["image_type"]
    ];
}

$user = null;
$homepageKycNeedsAttention = false;

if (!empty($_SESSION['user_id'])) {
    $user = one($conn, "SELECT user_id, name, email, profile_picture FROM users WHERE user_id = ? LIMIT 1", "i", [$_SESSION['user_id']]);
    $homepageKycNeedsAttention = $user ? homepageKycNeedsAttention($conn, (int)$user["user_id"]) : false;
}

$navCartCount = getHomepageCartCount($conn);

/* ===== Customer Comment Section ===== */
$conn->query("
    CREATE TABLE IF NOT EXISTS customer_comments (
        comment_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        customer_name VARCHAR(120) NOT NULL,
        email VARCHAR(150) NULL,
        rating INT NOT NULL DEFAULT 5,
        content TEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

if (homepageTableExists($conn, "customer_comments") && !homepageColumnExists($conn, "customer_comments", "status")) {
    $conn->query("ALTER TABLE customer_comments ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'active' AFTER content");
}

if (homepageTableExists($conn, "customer_comments") && !homepageColumnExists($conn, "customer_comments", "content")) {
    $conn->query("ALTER TABLE customer_comments ADD COLUMN content TEXT NOT NULL AFTER rating");
}

$commentSuccess = "";
$commentError = "";

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && isset($_POST["submit_customer_comment"])) {
    $commentUserId = !empty($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : null;
    $commentName = trim($_POST["customer_name"] ?? "");
    $commentEmail = trim($_POST["email"] ?? "");
    $commentRating = (int)($_POST["rating"] ?? 5);
    $commentContent = trim($_POST["content"] ?? "");

    if ($commentRating < 1 || $commentRating > 5) {
        $commentRating = 5;
    }

    if ($commentName === "" || $commentContent === "") {
        $commentError = "Please complete your name and comment.";
    } elseif ($commentEmail !== "" && !filter_var($commentEmail, FILTER_VALIDATE_EMAIL)) {
        $commentError = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO customer_comments
                (user_id, customer_name, email, rating, content, status, created_at)
            VALUES
                (?, ?, ?, ?, ?, 'active', NOW())
        ");

        if ($stmt) {
            $stmt->bind_param("issis", $commentUserId, $commentName, $commentEmail, $commentRating, $commentContent);

            if ($stmt->execute()) {
                $commentSuccess = "Your comment has been submitted successfully.";
                $_POST = [];
            } else {
                $commentError = "Unable to submit comment. Please try again.";
            }

            $stmt->close();
        } else {
            $commentError = "Unable to submit comment. Please try again.";
        }
    }
}

$customerComments = rows($conn, "
    SELECT comment_id, user_id, customer_name, email, rating, content, created_at
    FROM customer_comments
    WHERE status = 'active'
    ORDER BY created_at DESC
    LIMIT 6
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($settings['site_name'] ?? 'KH Car Rental') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
:root {
    --primary: #28a8ea;
    --primary-2: #73c7f4;
    --primary-3: #d6efff;
    --blue-deep: #1284c6;
    --blue-dark: #17304f;
    --dark: #17304f;
    --dark-2: #31516f;
    --white: #ffffff;
    --glass: rgba(255, 255, 255, 0.86);
    --glass-blue: rgba(234, 247, 255, 0.86);
    --light: #f5fbff;
    --soft: #eaf7ff;
    --text: #17304f;
    --muted: #6e8297;
    --border: #d8ecfb;
    --shadow: 0 18px 55px rgba(39, 137, 199, 0.13);
    --shadow-2: 0 12px 35px rgba(39, 137, 199, 0.10);
    --radius: 24px;
    --sky-50: #f5fbff;
    --sky-100: #eaf7ff;
    --sky-200: #d6efff;
    --sky-300: #b8e4ff;
    --sky-400: #73c7f4;
    --sky-500: #28a8ea;
    --sky-600: #1284c6;
    --sky-700: #075f95;
    --orange: #ff8a3d;
    --orange-dark: #f26f1d;
    --shadow-soft: 0 12px 35px rgba(39, 137, 199, 0.10);
    --primary-dark: #1284c6;
    --green: #21b573;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    scroll-behavior: smooth;
    scroll-padding-top: 115px !important;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: radial-gradient(circle at 18% 0%, rgba(184, 228, 255, 0.50), transparent 34%), radial-gradient(circle at 86% 12%, rgba(214, 239, 255, 0.55), transparent 30%), linear-gradient(180deg, #ffffff 0%, #f5fbff 22%, #eef9ff 45%, #f7fcff 70%, #ffffff 100%) !important;
    color: var(--text) !important;
}

a {
    text-decoration: none;
    color: inherit;
}

img {
    max-width: 100%;
    display: block;
}

.container {
    width: min(1200px, calc(100% - 40px));
    margin: auto;
}

.navbar {
    position: sticky;
    top: 0;
    z-index: 999;
    background: linear-gradient(135deg, rgba(227, 247, 255, 0.96), rgba(255, 255, 255, 0.92)), radial-gradient(circle at 12% 50%, rgba(40,168,234,0.22), transparent 35%) !important;
    backdrop-filter: blur(14px) !important;
    -webkit-backdrop-filter: blur(14px) !important;
    border-bottom: 1px solid rgba(40,168,234,0.26) !important;
    box-shadow: 0 14px 38px rgba(18,132,198,0.13) !important;
}

.nav-wrap {
    min-height: 82px;
    display: flex;
    align-items: center !important;
    justify-content: space-between;
    gap: 18px;
    position: relative;
    padding-left: 0 !important;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 950;
    letter-spacing: -0.4px;
    color: var(--dark);
    white-space: nowrap;
}

.brand-logo {
    width: 50px;
    height: 50px;
    border-radius: 18px;
    background: linear-gradient(135deg, #d8f2ff, #ffffff) !important;
    color: var(--sky-600) !important;
    display: grid;
    place-items: center;
    box-shadow: 0 10px 20px rgba(18,132,198,0.13) !important;
    border: 1px solid var(--border) !important;
}

.brand-logo i {
    font-size: 21px;
    filter: none !important;
    color: var(--sky-600) !important;
}

.nav-links {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    flex-wrap: wrap;
}

.nav-link {
    position: relative;
    padding: 11px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 900;
    color: #31516f !important;
    transition: 0.24s;
    overflow: hidden;
    background: transparent !important;
    box-shadow: none !important;
}

.nav-link::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: linear-gradient(135deg, rgba(255,255,255,0.72), rgba(191,232,255,0.32));
    border: 1px solid rgba(137, 207, 255, 0.36);
    opacity: 0;
    transition: 0.24s;
    display: none !important;
}

.nav-link span {
    position: relative;
    z-index: 2;
}

.nav-link:hover::before, .nav-link.active::before {
    opacity: 1;
}

.nav-link:hover, .nav-link.active {
    color: var(--sky-600) !important;
    box-shadow: none !important;
    transform: translateY(-1px) !important;
    background: var(--sky-100) !important;
}

.nav-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}

.login-btn {
    padding: 12px 18px;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(255,255,255,0.28), rgba(255,255,255,0.06)), linear-gradient(135deg, #39a9ff, #0874d8);
    color: var(--white);
    font-size: 13px;
    font-weight: 950;
    white-space: nowrap;
    box-shadow: 0 14px 30px rgba(37, 140, 228, 0.25);
    border: 1px solid rgba(255,255,255,0.42);
}

.avatar-btn {
    border: 0;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px;
    border-radius: 999px;
}

.avatar-img, .avatar-initial {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #7fd0ff, #117de3);
    color: white;
    font-weight: 900;
}

.avatar-alert-wrap {
    position: relative;
    display: inline-grid;
    place-items: center;
}

.avatar-kyc-dot {
    position: absolute;
    top: -1px;
    right: -1px;
    width: 13px;
    height: 13px;
    border-radius: 999px;
    background: #ff3045;
    border: 3px solid #fff;
    box-shadow: 0 0 0 5px rgba(255,48,69,.16), 0 10px 22px rgba(255,48,69,.28);
    z-index: 4;
}

.avatar-kyc-dot::after {
    content: "";
    position: absolute;
    inset: -5px;
    border-radius: inherit;
    background: rgba(255,48,69,.28);
    animation: kycPulse 1.6s infinite;
}

@keyframes kycPulse {
    0% { transform: scale(.75); opacity: .72; }
    70% { transform: scale(1.45); opacity: 0; }
    100% { transform: scale(1.45); opacity: 0; }
}

.profile-dropdown {
    position: absolute;
    top: 62px;
    right: 0;
    width: 230px;
    background: rgba(255, 255, 255, 0.96) !important;
    backdrop-filter: blur(18px);
    border: 1px solid var(--border) !important;
    box-shadow: var(--shadow) !important;
    border-radius: 20px;
    padding: 10px;
    display: none;
}

.profile-dropdown.show {
    display: block;
}

.profile-dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 13px;
    border-radius: 14px;
    color: var(--text);
    font-size: 14px;
    font-weight: 800;
}

.profile-dropdown a:hover {
    background: var(--sky-100) !important;
    color: var(--sky-600) !important;
}

.hero {
    position: relative;
    min-height: 750px;
    overflow: hidden;
    background: radial-gradient(circle at 20% 15%, rgba(184,228,255,0.65), transparent 34%), radial-gradient(circle at 85% 90%, rgba(214,239,255,0.55), transparent 32%), linear-gradient(135deg, #ffffff 0%, var(--sky-50) 48%, #eef9ff 100%) !important;
}

.hero-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 0.55s ease;
    background-size: cover;
    background-position: center;
    filter: saturate(1.08);
}

.hero-slide.active {
    opacity: 0.82;
}

.hero-slide::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(255,255,255,0.96) 0%, rgba(255,255,255,0.91) 48%, rgba(255,255,255,0.58) 100%), radial-gradient(circle at 20% 15%, rgba(184,228,255,0.65), transparent 34%) !important;
}

.hero-content {
    position: relative;
    z-index: 2;
    min-height: 750px;
    padding: 92px 0 70px;
    display: grid;
    align-items: center;
}

.hero-grid {
    display: grid;
    grid-template-columns: 1.04fr 0.96fr;
    gap: 34px;
    align-items: center;
}

.hero-text {
    color: var(--text);
}

.hero-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.58);
    border: 1px solid rgba(111, 198, 255, 0.42);
    box-shadow: 0 12px 28px rgba(44, 142, 220, 0.12);
    font-weight: 900;
    font-size: 13px;
    color: var(--blue-deep);
    margin-bottom: 16px;
    backdrop-filter: blur(14px);
}

.hero-label i, .hero-badge i, .section-label i {
    color: var(--blue-deep);
}

.hero-title {
    font-size: clamp(42px, 6vw, 74px);
    line-height: 0.98;
    max-width: 720px;
    letter-spacing: -2.8px;
    font-weight: 950;
    margin-bottom: 18px;
    color: var(--dark);
    text-shadow: 0 10px 32px rgba(74, 170, 240, 0.12);
}

.hero-desc {
    max-width: 620px;
    color: #385a78;
    line-height: 1.8;
    font-size: 17px;
    margin-bottom: 26px;
    font-weight: 600;
}

.hero-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.hero-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 17px;
    border-radius: 18px;
    background: rgba(255,255,255,0.86) !important;
    color: var(--blue-dark) !important;
    border: 1px solid var(--border) !important;
    font-weight: 900;
    backdrop-filter: blur(10px) !important;
    box-shadow: var(--shadow-2) !important;
}

.hero-badge i {
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(191, 231, 255, 0.72);
}

.search-card {
    background: linear-gradient(145deg, rgba(255,255,255,0.82), rgba(229,246,255,0.72));
    border-radius: 34px;
    padding: 28px;
    box-shadow: 0 26px 70px rgba(39,137,199,0.16);
    border: 1px solid rgba(184,228,255,0.95);
    backdrop-filter: blur(18px);
}

.search-title {
    margin-bottom: 17px;
}

.search-title h2 {
    font-size: 28px;
    color: var(--dark);
    margin-bottom: 5px;
    letter-spacing: -0.7px;
}

.search-title p {
    color: var(--muted);
    font-size: 14px;
}

.search-form {
    display: grid;
    gap: 14px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 13px;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 950;
    color: var(--blue-dark) !important;
    margin-bottom: 7px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-control {
    width: 100%;
    border: 2px solid #e2f2ff;
    background: #fbfdff;
    color: var(--blue-dark);
    border-radius: 16px;
    padding: 13px 14px;
    outline: none;
    font-size: 14px;
    min-height: 50px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.75);
    font-weight: 750;
}

.form-control:focus {
    border-color: var(--sky-500) !important;
    box-shadow: 0 0 0 0.22rem rgba(40,168,234,0.13) !important;
    background: var(--white);
}

.search-btn {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.48);
    min-height: 56px;
    border-radius: 18px;
    background: linear-gradient(135deg, #ff9a4a 0%, #ff7a1a 48%, #f15f12 100%);
    color: var(--white);
    font-size: 15px;
    font-weight: 950;
    cursor: pointer;
    box-shadow: 0 18px 34px rgba(255,122,26,0.28), inset 0 1px 0 rgba(255,255,255,0.32);
}

.search-btn::before, .btn::before, .login-btn::before {
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

.search-btn:hover::before, .btn:hover::before, .login-btn:hover::before {
    left: 125%;
}

.hero-controls {
    position: absolute;
    z-index: 4;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    align-items: center;
}

.hero-dot {
    width: 28px;
    height: 6px;
    border: 0;
    border-radius: 999px;
    background: rgba(18,132,198,0.20) !important;
    cursor: pointer;
}

.hero-dot.active {
    width: 50px;
    background: linear-gradient(90deg, #6cc8ff, #0b7fe8);
}

.hero-arrow {
    position: absolute;
    z-index: 4;
    top: 50%;
    transform: translateY(-50%);
    width: 46px;
    height: 46px;
    border: 1px solid var(--border) !important;
    background: rgba(255, 255, 255, 0.86) !important;
    color: var(--sky-600) !important;
    border-radius: 50%;
    cursor: pointer;
    backdrop-filter: blur(12px);
    box-shadow: var(--shadow-2) !important;
}

.hero-arrow.prev {
    left: 20px;
}

.hero-arrow.next {
    right: 20px;
}

section {
    padding: 88px 0;
}

.section-head {
    text-align: center;
    margin-bottom: 42px;
    position: relative;
    width: 100% !important;
    max-width: 100% !important;
    margin-left: auto !important;
    margin-right: auto !important;
    padding: 0 !important;
    border-radius: 32px;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    margin: 0 auto 42px !important;
    padding-top: 0 !important;
}

.section-label {
    display: inline-flex;
    padding: 9px 15px;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(221, 243, 255, 0.9), rgba(255,255,255,0.72));
    color: var(--blue-deep);
    font-size: 12px;
    font-weight: 950;
    letter-spacing: 0.7px;
    margin-bottom: 14px;
    border: 1px solid rgba(128, 203, 255, 0.45);
    box-shadow: 0 10px 24px rgba(53, 151, 229, 0.11);
}

.section-head h2 {
    font-size: clamp(34px, 4.5vw, 64px) !important;
    color: #ffffff !important;
    letter-spacing: -1.6px;
    margin-bottom: 10px !important;
    display: block;
    width: 100%;
    padding: 28px 24px;
    margin: 0 0 18px !important;
    border-radius: 0;
    background: radial-gradient(circle at 18% 20%, rgba(255,255,255,0.20), transparent 28%), linear-gradient(135deg, #9ac2ff 0%, #4e7ec2 52%, #6dbdff 100%);
    text-align: center;
    font-weight: 950 !important;
    line-height: 1.08;
    box-shadow: 0 18px 42px rgba(7, 61, 136, 0.18);
}

.section-head p {
    max-width: 780px;
    margin: 0 auto !important;
    color: var(--muted) !important;
    line-height: 1.7;
    margin-bottom: 0 !important;
    text-align: center;
}

.car-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.car-card, .why-card, .step-card, .state-card, .faq-item {
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid rgba(127, 202, 255, 0.36);
    border-radius: var(--radius);
    box-shadow: var(--shadow-2);
    overflow: hidden;
    backdrop-filter: blur(16px);
}

.car-card {
    transition: 0.25s;
}

.car-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 26px 58px rgba(42, 142, 220, 0.18);
}

.car-carousel {
    position: relative;
    height: 235px;
    background: var(--sky-100) !important;
    overflow: hidden;
}

.car-carousel-track {
    height: 100%;
    display: flex;
    transition: transform 0.38s ease;
}

.car-carousel-slide {
    min-width: 100%;
    height: 100%;
    position: relative;
}

.car-carousel-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-fallback {
    width: 100%;
    height: 100%;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #ffffff, var(--sky-100)) !important;
    color: var(--sky-600) !important;
    font-size: 42px;
}

.car-carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: 1px solid var(--border) !important;
    background: rgba(255, 255, 255, 0.86) !important;
    color: var(--sky-600) !important;
    cursor: pointer;
    display: grid;
    place-items: center;
    backdrop-filter: blur(12px);
    z-index: 5;
    box-shadow: var(--shadow-2) !important;
}

.car-carousel-btn.prev {
    left: 12px;
}

.car-carousel-btn.next {
    right: 12px;
}

.car-carousel-dots {
    position: absolute;
    left: 50%;
    bottom: 12px;
    transform: translateX(-50%);
    display: flex;
    gap: 6px;
    z-index: 5;
    padding: 7px 9px;
    border-radius: 999px;
    background: rgba(255,255,255,0.72) !important;
    backdrop-filter: blur(10px);
}

.car-carousel-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    border: 0;
    background: rgba(15, 104, 190, 0.25);
    cursor: pointer;
}

.car-carousel-dot.active {
    width: 18px;
    border-radius: 999px;
    background: linear-gradient(90deg, #6bc9ff, #087be2);
}

.car-body {
    padding: 22px;
}

.car-meta {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.brand-pill, .category-pill, .status-pill {
    padding: 7px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 950;
    text-transform: capitalize;
    border: 1px solid rgba(124, 200, 255, 0.32);
}

.brand-pill {
    background: rgba(218, 242, 255, 0.72);
    color: var(--blue-deep);
}

.category-pill {
    background: #ffffff !important;
    color: var(--blue-dark) !important;
    border: 1px solid var(--border) !important;
}

.status-pill {
    background: rgba(33,181,115,0.12) !important;
    color: var(--green) !important;
}

.status-pill.none {
    background: rgba(255,138,61,0.13) !important;
    color: var(--orange-dark) !important;
}

.car-body h3 {
    color: var(--dark);
    font-size: 22px;
    margin-bottom: 13px;
    letter-spacing: -0.4px;
}

.spec-list {
    display: grid;
    grid-template-columns: 1fr 1fr !important;
    gap: 8px;
    color: var(--muted);
    font-size: 14px;
    margin-bottom: 18px;
}

.spec-list i {
    color: var(--blue-deep);
    width: 18px;
}

.price-row {
    display: flex;
    align-items: end;
    justify-content: space-between;
    margin-bottom: 15px;
}

.price {
    background: none !important;
    -webkit-background-clip: initial !important;
    background-clip: initial !important;
    color: var(--sky-600) !important;
    font-size: 25px;
    font-weight: 950;
}

.price span {
    color: var(--muted);
    font-size: 12px;
    font-weight: 800;
}

.card-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.btn {
    position: relative;
    overflow: hidden;
    border: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 13px 14px;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 950;
    cursor: pointer;
    transition: 0.22s;
    isolation: isolate;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-primary {
    background: linear-gradient(135deg, rgba(255,255,255,0.22), rgba(255,255,255,0.04)), linear-gradient(135deg, #65c5ff, #118ded 55%, #086bd7);
    color: var(--white);
    box-shadow: 0 14px 30px rgba(43, 145, 225, 0.24);
    border: 1px solid rgba(255,255,255,0.44);
}

.btn-dark {
    background: #ffffff;
    color: var(--sky-600);
    box-shadow: 0 8px 18px rgba(39,137,199,0.06);
    border: 2px solid var(--sky-200);
}

.why {
    background: radial-gradient(circle at 10% 20%, rgba(184, 228, 255, 0.32), transparent 30%), linear-gradient(180deg, #ffffff 0%, var(--sky-50) 100%);
}

.why-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.why-card {
    padding: 30px;
    transition: 0.25s;
    position: relative;
    min-height: 220px;
    isolation: isolate;
}

.why-card:hover, .step-card:hover, .faq-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 25px 54px rgba(55, 150, 230, 0.15);
}

.why-icon, .step-icon {
    width: 58px;
    height: 58px;
    border-radius: 20px;
    background: var(--sky-100);
    color: var(--sky-600);
    display: grid;
    place-items: center;
    font-size: 22px;
    margin-bottom: 18px;
    border: 1px solid var(--border);
    box-shadow: 0 10px 24px rgba(40,168,234,0.12);
}

.why-card h3, .step-card h3 {
    color: var(--dark);
    margin-bottom: 10px;
    font-size: 20px;
    letter-spacing: -0.3px;
}

.why-card p, .step-card p {
    color: var(--muted);
    line-height: 1.65;
    font-size: 14px;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 17px;
    position: relative;
}

.step-card {
    padding: 23px;
    position: relative;
    transition: 0.25s;
    z-index: 1;
    min-height: 250px;
    background: linear-gradient(135deg, rgba(255,255,255,0.98), rgba(245,251,255,0.96));
}

.step-number {
    position: absolute;
    top: 18px;
    right: 18px;
    font-size: 34px;
    font-weight: 950;
    color: rgba(18,132,198,0.12) !important;
}

.smart-cta {
    background: radial-gradient(circle at 8% 50%, rgba(184,228,255,0.55), transparent 28%), radial-gradient(circle at 92% 50%, rgba(184,228,255,0.55), transparent 28%), linear-gradient(90deg, #f5fbff 0%, #ffffff 50%, #f5fbff 100%);
    color: var(--text);
    padding: 0;
}

.smart-box {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 26px;
    align-items: center;
    padding: 64px;
    border-radius: 36px;
    background: linear-gradient(135deg, rgba(255,255,255,0.92), rgba(234,247,255,0.92));
    border: 1px solid rgba(184,228,255,0.95);
    box-shadow: 0 26px 70px rgba(39,137,199,0.16);
    backdrop-filter: blur(18px);
    position: relative;
    overflow: hidden;
}

.smart-box h2 {
    font-size: clamp(30px, 4vw, 50px);
    letter-spacing: -1.5px;
    margin-bottom: 14px;
    color: var(--dark);
}

.smart-box p {
    color: var(--muted);
    line-height: 1.75;
    margin-bottom: 24px;
}

.smart-visual {
    display: grid;
    place-items: center;
    min-height: 230px;
    border-radius: 30px;
    background: linear-gradient(135deg, #ffffff, var(--sky-50)) !important;
    border: 1px solid var(--border) !important;
    box-shadow: var(--shadow-2) !important;
}

.smart-visual i {
    font-size: 92px;
    background: none !important;
    -webkit-background-clip: initial !important;
    background-clip: initial !important;
    color: var(--sky-600) !important;
    filter: drop-shadow(0 18px 24px rgba(40,168,234,0.22));
}

.states-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.state-card {
    position: relative;
    min-height: 295px;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: end;
    color: var(--white);
    isolation: isolate;
    transition: 0.25s;
    box-shadow: 0 18px 46px rgba(39,137,199,0.14) !important;
}

.state-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 26px 58px rgba(39,137,199,0.20) !important;
    filter: saturate(1.08) contrast(1.03);
}

.state-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(23,48,79,0.88), rgba(23,48,79,0.18) 58%, rgba(255,255,255,0.08)) !important;
    z-index: -1;
}

.state-content {
    padding: 26px;
}

.state-content h3 {
    font-size: 30px;
    margin-bottom: 8px;
    text-shadow: 0 3px 10px rgba(0,0,0,0.2);
}

.state-content p {
    color: rgba(255, 255, 255, 0.88);
    line-height: 1.6;
    margin-bottom: 16px;
}

.faq {
    background: linear-gradient(180deg, rgba(248,253,255,0.9), rgba(238,248,255,0.8));
}

.faq-grid {
    columns: 3 300px;
    column-gap: 20px;
}

.faq-item {
    break-inside: avoid;
    margin-bottom: 20px;
}

.faq-question {
    width: 100%;
    border: 0;
    background: transparent;
    padding: 18px 20px;
    text-align: left;
    display: flex;
    justify-content: space-between;
    gap: 15px;
    font-weight: 950;
    color: var(--dark);
    cursor: pointer;
    font-size: 15px;
}

.faq-question i {
    color: var(--blue-deep);
    transition: 0.25s;
}

.faq-answer {
    display: none;
    padding: 0 20px 20px;
    color: var(--muted);
    line-height: 1.7;
    font-size: 14px;
}

.faq-item.active .faq-answer {
    display: block;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.footer {
    background: var(--blue-dark) !important;
    color: #ffffff !important;
    padding: 70px 0 24px;
    border-top: none !important;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.35fr 0.85fr 1.1fr;
    gap: 40px;
    margin-bottom: 36px;
}

.footer h3 {
    margin-bottom: 16px;
    font-size: 21px;
    color: #ffffff !important;
}

.footer p, .footer a {
    color: rgba(255,255,255,0.78) !important;
    line-height: 1.75;
    font-size: 14px;
}

.footer .btn {
    color: var(--white);
}

.footer-links, .contact-list {
    list-style: none;
    display: grid;
    gap: 10px;
}

.contact-list li {
    display: flex;
    align-items: flex-start;
    gap: 11px;
}

.contact-list i {
    color: var(--blue-deep);
    margin-top: 5px;
    width: 17px;
}

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.14) !important;
    padding-top: 20px;
    text-align: center;
    color: rgba(255,255,255,0.65) !important;
    font-size: 13px;
}

.back-top {
    position: fixed;
    right: 22px;
    bottom: 22px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.5);
    background: linear-gradient(135deg, rgba(255,255,255,0.26), rgba(255,255,255,0.04)), linear-gradient(135deg, #65c5ff, #0873d8);
    color: var(--white);
    box-shadow: 0 16px 34px rgba(43, 145, 225, 0.3);
    cursor: pointer;
    display: none;
    z-index: 800;
}

.back-top.show {
    display: grid;
    place-items: center;
}

.form-control.invalid-field {
    border-color: #ff4d4f !important;
    background: #fff7f7 !important;
    box-shadow: 0 0 0 0.22rem rgba(255, 77, 79, 0.13) !important;
}

.validation-toast {
    position: fixed;
    top: 100px;
    right: 28px;
    z-index: 2000;
    width: min(390px, calc(100% - 32px));
    display: none;
    align-items: flex-start;
    gap: 13px;
    padding: 16px 18px;
    border-radius: 20px;
    background: rgba(255,255,255,0.98) !important;
    border: 1px solid #ffd2d2 !important;
    box-shadow: 0 18px 55px rgba(255,77,79,0.15) !important;
    backdrop-filter: blur(16px);
    color: var(--blue-dark);
    animation: toastSlide 0.25s ease;
}

.validation-toast.show {
    display: flex;
}

.validation-toast-icon {
    width: 42px;
    height: 42px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    color: #ffffff;
    background: linear-gradient(135deg, #ff7b7b, #ff4d4f);
    box-shadow: 0 10px 22px rgba(255, 77, 79, 0.22);
}

.validation-toast strong {
    display: block;
    font-size: 15px;
    font-weight: 950;
    margin-bottom: 3px;
    color: #d9363e;
}

.validation-toast span {
    display: block;
    color: #6e8297;
    font-size: 13px;
    line-height: 1.55;
    font-weight: 650;
}

@keyframes toastSlide {
from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
}

.brand, .hero-title, .search-title h2, .section-head h2, .smart-box h2, .why-card h3, .step-card h3, .car-body h3, .faq-question, .footer h3 {
    color: var(--blue-dark);
}

.login-btn, .btn-primary, .back-top {
    background: linear-gradient(135deg, var(--sky-500), var(--sky-600));
    box-shadow: 0 10px 22px rgba(40,168,234,0.22);
    border: none;
}

.btn-dark:hover {
    background: linear-gradient(135deg, #dff4ff, #c9ebff);
    color: var(--sky-700);
    border-color: var(--sky-400);
    box-shadow: 0 12px 24px rgba(18,132,198,0.16);
}

.hero-label, .hero-badge, .search-card, .car-card, .why-card, .step-card, .faq-item {
    background: rgba(255,255,255,0.96);
    border: 1px solid var(--border);
    box-shadow: 0 10px 30px rgba(39,137,199,0.07);
}

.hero-label, .section-label, .brand-pill, .hero-badge i, .spec-list i, .faq-question i, .contact-list i {
    color: var(--sky-600);
}

.hero-desc, .search-title p, .section-head p, .smart-box p, .why-card p, .step-card p, .faq-answer, .spec-list, .footer p, .footer a {
    color: var(--muted);
    font-weight: 600;
}

.hero-badge i, .brand-pill, .section-label {
    background: var(--sky-100);
}

.search-btn:hover {
    transform: translateY(-3px);
    background: linear-gradient(135deg, #ffad66 0%, #ff8429 48%, #f26b1d 100%);
    box-shadow: 0 24px 42px rgba(255,122,26,0.36), inset 0 1px 0 rgba(255,255,255,0.38);
}

.hero-dot.active, .car-carousel-dot.active {
    background: linear-gradient(90deg, var(--sky-500), var(--sky-600)) !important;
}

.hero-arrow, .car-carousel-btn {
    background: rgba(255, 255, 255, 0.82);
    color: var(--sky-600);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-soft);
}

.why-card::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    background: radial-gradient(circle at 85% 12%, rgba(184,228,255,0.54), transparent 30%), linear-gradient(135deg, #ffffff, #f8fdff);
}

.why-card::after {
    content: "";
    position: absolute;
    left: 28px;
    right: 28px;
    bottom: 0;
    height: 4px;
    border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, var(--sky-500), var(--sky-300));
    opacity: 0;
    transition: 0.25s;
}

.why-card:hover::after {
    opacity: 1;
}

.why-card:hover .why-icon, .step-card:hover .step-icon {
    background: linear-gradient(135deg, var(--sky-500), var(--sky-600));
    color: #ffffff;
    box-shadow: 0 14px 30px rgba(40,168,234,0.24);
}

.steps-grid::before {
    content: "";
    position: absolute;
    top: 54px;
    left: 8%;
    right: 8%;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--sky-300), var(--sky-500), var(--sky-300), transparent) !important;
    z-index: 0;
    box-shadow: 0 8px 20px rgba(40,168,234,0.15) !important;
}

.step-card::before {
    content: "";
    position: absolute;
    inset: 12px;
    border-radius: 18px;
    border: 1px dashed rgba(40,168,234,0.22);
    pointer-events: none;
}

.smart-box::before {
    content: "";
    position: absolute;
    width: 360px;
    height: 360px;
    border-radius: 50%;
    right: -110px;
    top: -120px;
    background: rgba(184,228,255,0.55);
}

.smart-box::after {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    left: -90px;
    bottom: -110px;
    background: rgba(214,239,255,0.70);
}

.smart-box > * {
    position: relative;
    z-index: 1;
}

.footer a:hover {
    color: var(--sky-300) !important;
}

.brand, .search-title h2, .hero-title, .section-head h2, .car-body h3, .why-card h3, .step-card h3, .smart-box h2, .footer h3 {
    color: var(--blue-dark) !important;
}

.hero-label, .section-label {
    background: rgba(255,255,255,0.86) !important;
    color: var(--sky-600) !important;
    border: 1px solid var(--border) !important;
    box-shadow: var(--shadow-2) !important;
    backdrop-filter: blur(10px) !important;
}

.hero-label i, .hero-badge i, .section-label i, .spec-list i, .faq-question i, .contact-list i {
    color: var(--sky-600) !important;
}

.hero-desc, .search-title p, .section-head p, .why-card p, .step-card p, .smart-box p, .faq-answer, .footer p, .footer a, .price span {
    color: var(--muted) !important;
}

.hero-badge i, .why-icon, .step-icon {
    background: var(--sky-100) !important;
    color: var(--sky-600) !important;
    border: 1px solid var(--border) !important;
    box-shadow: 0 10px 24px rgba(40,168,234,0.12) !important;
}

.search-card, .smart-box {
    background: rgba(255,255,255,0.98) !important;
    border: 1px solid rgba(184,228,255,0.95) !important;
    box-shadow: 0 26px 70px rgba(39,137,199,0.16) !important;
    backdrop-filter: blur(16px) !important;
}

.form-control, .fixed-time-display {
    border: 2px solid #e2f2ff !important;
    background-color: #fbfdff !important;
    color: var(--blue-dark) !important;
    box-shadow: none !important;
}

.car-card, .why-card, .step-card, .faq-item {
    background: #ffffff !important;
    border: 1px solid var(--border) !important;
    box-shadow: 0 10px 30px rgba(39,137,199,0.07) !important;
}

.car-card:hover, .why-card:hover, .step-card:hover, .faq-item:hover {
    transform: translateY(-7px) !important;
    box-shadow: var(--shadow) !important;
    border-color: var(--sky-300) !important;
}

.brand-pill, .car-badge {
    background: var(--sky-100) !important;
    color: var(--sky-600) !important;
    border: 1px solid transparent !important;
}

.why, .smart-cta, .faq {
    background: var(--sky-50) !important;
}

.step-card::before, .smart-box::before, .smart-box::after, .why-card::before, .why-card::after {
    display: none !important;
}

main {
    background: linear-gradient(180deg, rgba(245,251,255,0.96), rgba(255,255,255,0.96));
}

main > section:not(.hero) {
    position: relative;
    background: transparent !important;
}

.time-combo {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.time-part {
    cursor: pointer;
}

.fixed-time-display {
    min-height: 52px;
    width: 100%;
    border: 2px solid #e2f2ff;
    background-color: #fbfdff;
    color: var(--blue-dark);
    border-radius: 14px;
    padding: 12px 13px;
    font-size: 14px;
    font-weight: 850;
    display: flex;
    align-items: center;
    gap: 10px;
}

.fixed-time-display i {
    color: var(--sky-600);
}

.fixed-time-display.invalid-field, .time-combo.invalid-field .form-control, .form-control.invalid-field {
    border-color: #ff4d4f !important;
    background: #fff5f5 !important;
    box-shadow: 0 0 0 0.22rem rgba(255,77,79,0.13) !important;
}

.popular-pill {
    background: rgba(255, 72, 72, 0.12) !important;
    color: #e02525 !important;
    border: 1px solid rgba(255, 72, 72, 0.22) !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 8px 18px rgba(224, 37, 37, 0.08);
}

.popular-pill i {
    color: #ff3b30 !important;
}

.car-card, .why-card, .step-card, .state-card, .smart-box, .faq-item {
    transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease, background 0.28s ease !important;
}

.car-card:hover, .why-card:hover, .step-card:hover, .state-card:hover, .smart-box:hover, .faq-item:hover {
    transform: translateY(-10px) scale(1.012) !important;
    box-shadow: 0 30px 75px rgba(18, 132, 198, 0.22), 0 10px 25px rgba(23, 48, 79, 0.06) !important;
    border-color: rgba(40,168,234,0.55) !important;
}

.why-card:hover, .step-card:hover {
    background: radial-gradient(circle at 88% 8%, rgba(184,228,255,0.48), transparent 34%), linear-gradient(135deg, #ffffff, #f5fbff) !important;
}

.promo-section {
    padding: 76px 0 82px !important;
    background: radial-gradient(circle at 12% 0%, rgba(255, 138, 61, 0.14), transparent 30%), radial-gradient(circle at 88% 35%, rgba(40, 168, 234, 0.18), transparent 32%), linear-gradient(135deg, #ffffff 0%, #f5fbff 100%) !important;
}

.new-user-deal {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: 1.3fr 0.7fr;
    align-items: center;
    gap: 32px;
    border-radius: 34px;
    padding: 42px 48px;
    background: radial-gradient(circle at 80% 18%, rgba(255,138,61,0.12), transparent 30%), linear-gradient(135deg, rgba(255,255,255,0.96), rgba(234,247,255,0.90)) !important;
    border: 1px solid rgba(184, 228, 255, 0.95);
    box-shadow: 0 28px 75px rgba(39,137,199,0.16), inset 0 1px 0 rgba(255,255,255,0.92);
    isolation: isolate;
}

.new-user-deal::before {
    content: "";
    position: absolute;
    width: 420px;
    height: 420px;
    right: -140px;
    top: -160px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,138,61,0.22), transparent 64%);
    z-index: -1;
}

.new-user-deal::after {
    content: "";
    position: absolute;
    width: 320px;
    height: 320px;
    left: -110px;
    bottom: -130px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(40,168,234,0.22), transparent 66%);
    z-index: -1;
}

.deal-kicker {
    width: fit-content;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 9px 15px;
    border-radius: 999px;
    background: rgba(255,138,61,0.13);
    color: #f26f1d;
    border: 1px solid rgba(255,138,61,0.22);
    font-size: 12px;
    font-weight: 950;
    letter-spacing: 1px;
    margin-bottom: 16px;
}

.deal-content h2 {
    color: var(--blue-dark);
    font-size: clamp(30px, 4vw, 48px);
    font-weight: 950;
    letter-spacing: -1.4px;
    line-height: 1.08;
    margin-bottom: 12px;
}

.deal-content p {
    max-width: 720px;
    color: var(--muted);
    line-height: 1.75;
    font-size: 16px;
    font-weight: 650;
    margin-bottom: 20px;
}

.deal-action-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0 !important;
}

.deal-code-row {
    width: fit-content;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 16px;
    background: #ffffff;
    border: 1px dashed rgba(255,138,61,0.45);
    box-shadow: 0 10px 24px rgba(255,138,61,0.10);
}

.deal-code-label {
    color: var(--muted);
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.deal-code {
    color: #f26f1d;
    font-size: 18px;
    font-weight: 950;
    letter-spacing: 1px;
}

.deal-register-btn {
    min-width: 170px;
}

.deal-discount-card {
    min-height: 230px;
    border-radius: 30px;
    display: grid;
    place-items: center;
    align-content: center;
    background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.35), transparent 34%), linear-gradient(135deg, #ff9a4a 0%, #ff7a1a 48%, #f15f12 100%);
    color: #ffffff;
    box-shadow: 0 24px 55px rgba(255,122,26,0.30), inset 0 1px 0 rgba(255,255,255,0.35);
    border: 1px solid rgba(255,255,255,0.35);
}

.discount-value {
    font-size: clamp(62px, 8vw, 92px);
    line-height: 0.9;
    font-weight: 950;
    letter-spacing: -4px;
}

.discount-label {
    font-size: 28px;
    font-weight: 950;
    letter-spacing: 3px;
}

.discount-small {
    margin-top: 8px;
    font-size: 14px;
    font-weight: 850;
    opacity: 0.92;
}

.nav-wrap::before {
    content: "";
    position: absolute;
    inset: 12px -18px;
    border-radius: 28px;
    background: rgba(255,255,255,0.38);
    border: 1px solid rgba(255,255,255,0.55);
    pointer-events: none;
    z-index: -1;
}

.promo-section .section-head {
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 0;
}

main > section:not(.hero)::before, main > section:not(.hero)::after, .section-head::before, .section-head::after, .nav-wrap::before {
    display: none !important;
    content: none !important;
}

.section-head .section-label {
    margin-bottom: 18px !important;
}

.promo-section .section-head h2 {
    background: none !important;
    color: var(--blue-dark) !important;
    box-shadow: none !important;
    padding: 0 !important;
}

.hero .section-head h2 {
    background: none !important;
    color: var(--blue-dark) !important;
    box-shadow: none !important;
    padding: 0 !important;
}

.availability-modal {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 26px;
}

.availability-modal.show {
    display: flex;
}

.availability-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(8, 31, 55, 0.46);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.availability-modal-card {
    position: relative;
    z-index: 2;
    width: min(650px, 100%);
    max-height: calc(100vh - 52px);
    overflow-y: auto;
    border-radius: 34px;
    animation: modalPop 0.22s ease;
}

.availability-modal-close {
    position: absolute;
    top: 18px;
    right: 18px;
    z-index: 5;
    width: 42px;
    height: 42px;
    border: 1px solid rgba(184, 228, 255, 0.95);
    border-radius: 50%;
    background: rgba(255,255,255,0.92);
    color: var(--sky-600);
    display: grid;
    place-items: center;
    cursor: pointer;
    box-shadow: 0 12px 26px rgba(39,137,199,0.16);
}

.modal-search-card {
    margin: 0;
    width: 100%;
}

.modal-car-label {
    display: inline-flex;
    width: fit-content;
    padding: 8px 13px;
    border-radius: 999px;
    margin-bottom: 10px;
    background: rgba(40,168,234,0.12);
    color: var(--sky-600);
    border: 1px solid rgba(40,168,234,0.22);
    font-size: 12px;
    font-weight: 950;
    letter-spacing: 0.6px;
    text-transform: uppercase;
}

body.modal-open {
    overflow: hidden;
}

@keyframes modalPop {
from {
        transform: translateY(16px) scale(0.98);
        opacity: 0;
    }
    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.section-menu-toggle {
    position: fixed;
    top: 96px;
    left: 24px;
    z-index: 1500;
    width: 48px;
    height: 48px;
    border: 1px solid rgba(184, 228, 255, 0.95);
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(234,247,255,0.88));
    color: var(--sky-600);
    box-shadow: 0 16px 36px rgba(39,137,199,0.16);
    cursor: pointer;
    display: grid;
    place-items: center;
    transition: 0.25s ease;
}

.section-menu-toggle:hover {
    transform: translateY(-2px);
    background: linear-gradient(135deg, var(--sky-500), var(--sky-600));
    color: #ffffff;
    box-shadow: 0 22px 45px rgba(18,132,198,0.26);
}

.section-side-nav {
    position: fixed !important;
    top: 0 !important;
    left: -330px !important;
    width: 310px !important;
    height: 100vh !important;
    z-index: 2100 !important;
    padding: 26px 18px !important;
    background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(234,247,255,0.94)) !important;
    border-right: 1px solid rgba(184, 228, 255, 0.95) !important;
    box-shadow: 30px 0 80px rgba(23,48,79,0.18) !important;
    backdrop-filter: blur(18px) !important;
    -webkit-backdrop-filter: blur(18px) !important;
    transition: left 0.28s ease !important;
    overflow-y: auto !important;
}

.section-side-nav.show {
    left: 0 !important;
}

.section-side-close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 40px;
    height: 40px;
    border: 1px solid rgba(184, 228, 255, 0.95);
    border-radius: 14px;
    background: #ffffff;
    color: var(--sky-600);
    cursor: pointer;
}

.section-side-head {
    display: grid;
    gap: 6px;
    padding: 18px 12px 22px;
    margin-bottom: 10px;
}

.section-side-head span {
    color: var(--sky-600);
    font-weight: 950;
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.section-side-head strong {
    color: var(--blue-dark);
    font-size: 24px;
    font-weight: 950;
}

.section-side-nav a {
    display: flex !important;
    align-items: center;
    gap: 12px;
    padding: 14px 14px;
    margin-bottom: 8px;
    border-radius: 18px;
    color: var(--blue-dark);
    font-size: 14px;
    font-weight: 950;
    border: 1px solid transparent;
    transition: 0.22s ease;
}

.section-side-nav a i {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: var(--sky-100);
    color: var(--sky-600);
}

.section-side-nav a:hover {
    background: #ffffff;
    border-color: rgba(184, 228, 255, 0.95);
    box-shadow: 0 14px 30px rgba(39,137,199,0.12);
    transform: translateX(4px);
    color: var(--sky-600);
}

.section-side-overlay {
    position: fixed !important;
    inset: 0 !important;
    z-index: 2050 !important;
    display: none !important;
    background: rgba(8,31,55,0.38) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
}

.section-side-overlay.show {
    display: block !important;
}

.section-nav-pills {
    width: min(1180px, calc(100% - 40px));
    margin: -2px auto 12px;
    display: none !important;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
    padding: 10px 14px 14px;
}

.section-nav-pills a {
    padding: 9px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,0.72);
    border: 1px solid rgba(184, 228, 255, 0.82);
    color: var(--blue-dark);
    font-size: 11px;
    font-weight: 950;
    letter-spacing: 0.45px;
    box-shadow: 0 8px 20px rgba(39,137,199,0.07);
    transition: 0.2s ease;
}

.section-nav-pills a:hover {
    background: linear-gradient(135deg, var(--sky-500), var(--sky-600));
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(18,132,198,0.22);
}

.nav-section-menu-toggle, .section-menu-toggle.nav-section-menu-toggle {
    position: static !important;
    z-index: auto !important;
    width: 46px !important;
    height: 46px !important;
    flex: 0 0 46px !important;
    margin: 0 6px 0 0 !important;
    border: 1px solid rgba(184, 228, 255, 0.95) !important;
    border-radius: 16px !important;
    background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(234,247,255,0.88)) !important;
    color: var(--sky-600) !important;
    box-shadow: 0 12px 26px rgba(39,137,199,0.14) !important;
    cursor: pointer !important;
    display: grid !important;
    place-items: center !important;
    transition: 0.25s ease;
    top: auto !important;
    left: auto !important;
    align-self: center !important;
    transform: none !important;
}

.nav-section-menu-toggle:hover, .section-menu-toggle.nav-section-menu-toggle:hover {
    transform: translateY(-2px) !important;
    background: linear-gradient(135deg, var(--sky-500), var(--sky-600));
    color: #ffffff;
    box-shadow: 0 18px 36px rgba(18,132,198,0.24);
}

.promo-section-head, .smart-section-head {
    margin-bottom: 42px !important;
}

.promo-section-head h2, .smart-section-head h2 {
    display: block;
    width: 100%;
    padding: 28px 24px;
    margin: 0 0 18px !important;
    background: radial-gradient(circle at 18% 20%, rgba(255,255,255,0.20), transparent 28%), linear-gradient(135deg, #9ac2ff 0%, #4e7ec2 52%, #6dbdff 100%) !important;
    color: #ffffff !important;
    text-align: center;
    font-size: clamp(34px, 4.5vw, 64px) !important;
    font-weight: 950 !important;
    letter-spacing: -1.6px;
    line-height: 1.08;
    box-shadow: 0 18px 42px rgba(7, 61, 136, 0.18);
}

.promo-section-head p, .smart-section-head p {
    max-width: 780px;
    margin: 0 auto !important;
    text-align: center;
}

.promo-section-head::before, .promo-section-head::after, .smart-section-head::before, .smart-section-head::after {
    display: none !important;
    content: none !important;
}

.promo-section .promo-section-head h2, .smart-cta .smart-section-head h2 {
    display: block !important;
    width: 100% !important;
    padding: 28px 24px !important;
    margin: 0 0 18px !important;
    border-radius: 0 !important;
    background: radial-gradient(circle at 18% 20%, rgba(255,255,255,0.20), transparent 28%), linear-gradient(135deg, #0a4ea3 0%, #073d88 52%, #062f6f 100%) !important;
    color: #ffffff !important;
    text-align: center !important;
    font-size: clamp(34px, 4.5vw, 64px) !important;
    font-weight: 950 !important;
    letter-spacing: -1.6px !important;
    line-height: 1.08 !important;
    box-shadow: 0 18px 42px rgba(7, 61, 136, 0.18) !important;
}

.promo-section .promo-section-head, .smart-cta .smart-section-head {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 auto 42px !important;
    padding: 0 !important;
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
}

.promo-section .promo-section-head p, .smart-cta .smart-section-head p {
    max-width: 780px !important;
    margin: 0 auto !important;
    text-align: center !important;
}

#popular-cars, #promotion, #why-choose-us, #how-it-works, #find-car-smart-section, #rental-states, #faqs {
    scroll-margin-top: 115px !important;
}

@media (max-width: 1080px) {
    .hero-grid, .smart-box, .footer-grid {
        grid-template-columns: 1fr;
    }

    .car-grid, .why-grid, .states-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .steps-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .nav-wrap {
        align-items: flex-start;
        padding: 14px 0;
        flex-direction: column;
    }

    .steps-grid::before {
        display: none;
    }

    .section-nav-pills {
        justify-content: flex-start;
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 12px;
    }

    .section-nav-pills a {
        white-space: nowrap;
    }

    .nav-wrap {
        padding-left: 70px !important;
    }

    html {
        scroll-padding-top: 155px !important;
    }

    #popular-cars, #promotion, #why-choose-us, #how-it-works, #find-car-smart-section, #rental-states, #faqs {
        scroll-margin-top: 155px !important;
    }

    .nav-wrap {
        padding-left: 0 !important;
    }
}

@media (max-width: 680px) {
    .container {
        width: min(100% - 24px, 1200px);
    }

    .hero {
        min-height: auto;
    }

    .hero-content {
        min-height: auto;
        padding: 54px 0 76px;
    }

    .form-row, .car-grid, .why-grid, .steps-grid, .states-grid, .card-actions {
        grid-template-columns: 1fr;
    }

    .search-card {
        padding: 18px;
    }

    .smart-box {
        padding: 30px;
    }

    .hero-arrow {
        display: none;
    }

    .time-combo {
        grid-template-columns: 1fr;
    }

    .section-head {
        padding: 20px 18px 24px;
        border-radius: 24px;
    }

    .availability-modal {
        padding: 14px;
    }

    .availability-modal-card {
        max-height: calc(100vh - 28px);
    }

    .section-menu-toggle {
        top: 92px;
        left: 14px;
        width: 44px;
        height: 44px;
    }

    .section-side-nav {
        width: 286px;
        left: -306px;
    }

    .nav-section-menu-toggle, .section-menu-toggle.nav-section-menu-toggle {
        width: 44px;
        height: 44px;
        flex-basis: 44px;
    }

    .nav-section-menu-toggle, .section-menu-toggle.nav-section-menu-toggle {
        top: 22px !important;
        left: 12px !important;
        width: 44px !important;
        height: 44px !important;
    }

    .nav-wrap {
        padding-left: 58px !important;
    }

    html {
        scroll-padding-top: 135px !important;
    }

    #popular-cars, #promotion, #why-choose-us, #how-it-works, #find-car-smart-section, #rental-states, #faqs {
        scroll-margin-top: 135px !important;
    }

    .nav-wrap {
        padding-left: 0 !important;
    }

    .nav-section-menu-toggle, .section-menu-toggle.nav-section-menu-toggle {
        position: static !important;
        top: auto !important;
        left: auto !important;
        width: 44px !important;
        height: 44px !important;
        flex-basis: 44px !important;
    }
}

@media (max-width: 860px) {
    .new-user-deal {
        grid-template-columns: 1fr;
        padding: 30px;
    }
}


/* ===== Homepage footer / about-us style update to match catalogue pattern ===== */
.footer{
    background:#12304f!important;
    color:#ffffff!important;
    padding:82px 0 28px!important;
    border-top:none!important;
}
.footer-grid{
    width:min(1200px,calc(100% - 40px))!important;
    margin:0 auto 42px!important;
    display:grid!important;
    grid-template-columns:1.35fr 1fr 1.2fr!important;
    gap:62px!important;
}
.footer h3{
    font-size:22px!important;
    font-weight:950!important;
    color:#ffffff!important;
    margin-bottom:18px!important;
    letter-spacing:-.3px!important;
}
.footer p,
.footer a,
.footer li{
    color:rgba(218,235,248,.76)!important;
    font-size:15.5px!important;
    line-height:1.85!important;
    font-weight:650!important;
}
.footer a{
    transition:.22s ease!important;
}
.footer a:hover{
    color:#ffffff!important;
    transform:translateX(4px)!important;
}
.footer-links,
.contact-list{
    list-style:none!important;
    display:grid!important;
    gap:10px!important;
}
.contact-list li{
    display:flex!important;
    align-items:flex-start!important;
    gap:11px!important;
}
.contact-list i,
.footer i{
    color:var(--sky-500)!important;
    width:18px!important;
    margin-top:5px!important;
}
.footer .btn,
.footer .start-btn{
    margin-top:22px!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:8px!important;
    background:linear-gradient(135deg,var(--sky-500),var(--sky-600))!important;
    color:#ffffff!important;
    padding:14px 24px!important;
    border-radius:17px!important;
    font-size:15px!important;
    font-weight:950!important;
    box-shadow:0 16px 34px rgba(40,168,234,.25)!important;
}
.footer-bottom{
    width:min(1200px,calc(100% - 40px))!important;
    margin:0 auto!important;
    padding-top:22px!important;
    border-top:1px solid rgba(255,255,255,.14)!important;
    text-align:center!important;
    color:rgba(218,235,248,.86)!important;
    font-size:14px!important;
}
@media(max-width:900px){
    .footer-grid{
        grid-template-columns:1fr!important;
        gap:34px!important;
    }
}


/* ===== Unified footer / about-us design matching Catalogue ===== */
.footer{
    background:#12304f!important;
    color:#ffffff!important;
    padding:82px 0 28px!important;
    border-top:none!important;
}
.footer-grid{
    width:min(1200px,calc(100% - 40px))!important;
    margin:0 auto 42px!important;
    display:grid!important;
    grid-template-columns:1.35fr 1fr 1.2fr!important;
    gap:62px!important;
}
.footer h3{
    font-size:22px!important;
    font-weight:950!important;
    color:#ffffff!important;
    margin-bottom:18px!important;
    letter-spacing:-.3px!important;
}
.footer p,
.footer a,
.footer li{
    color:rgba(218,235,248,.76)!important;
    font-size:15.5px!important;
    line-height:1.85!important;
    font-weight:650!important;
}
.footer-links,
.contact-list{
    list-style:none!important;
    display:grid!important;
    gap:10px!important;
}
.footer-links a,
.contact-list a,
.contact-list li{
    width:fit-content!important;
    display:inline-flex!important;
    align-items:flex-start!important;
    gap:10px!important;
    padding:3px 0!important;
    border-radius:12px!important;
    transition:color .22s ease, transform .22s ease, background .22s ease, padding .22s ease!important;
}
.footer-links a::before{
    content:"";
    width:18px;
    height:18px;
    margin-top:5px;
    flex:0 0 18px;
    border-radius:6px;
    background:rgba(40,168,234,.18);
    border:1px solid rgba(40,168,234,.25);
    transition:.22s ease;
}
.footer-links a:hover,
.contact-list a:hover,
.contact-list li:hover{
    color:#ffffff!important;
    transform:translateX(6px)!important;
    background:rgba(255,255,255,.055)!important;
    padding-left:8px!important;
    padding-right:10px!important;
}
.footer-links a:hover::before{
    background:var(--sky-500)!important;
    border-color:var(--sky-500)!important;
    box-shadow:0 8px 18px rgba(40,168,234,.22);
}
.contact-list i,
.footer i{
    color:var(--sky-500)!important;
    width:18px!important;
    margin-top:5px!important;
    transition:.22s ease!important;
}
.contact-list a:hover i,
.contact-list li:hover i{
    color:#7fd0ff!important;
    transform:scale(1.08)!important;
}
.footer .btn,
.footer .start-btn{
    margin-top:22px!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:8px!important;
    background:linear-gradient(135deg,var(--sky-500),var(--sky-600))!important;
    color:#ffffff!important;
    padding:14px 24px!important;
    border-radius:17px!important;
    font-size:15px!important;
    font-weight:950!important;
    box-shadow:0 16px 34px rgba(40,168,234,.25)!important;
}
.footer-bottom{
    width:min(1200px,calc(100% - 40px))!important;
    margin:0 auto!important;
    padding-top:22px!important;
    border-top:1px solid rgba(255,255,255,.14)!important;
    text-align:center!important;
    color:rgba(218,235,248,.86)!important;
    font-size:14px!important;
}
@media(max-width:900px){
    .footer-grid{
        grid-template-columns:1fr!important;
        gap:34px!important;
    }
}


/* ===== FINAL FIX: Homepage footer should match catalogue footer target (no extra square) ===== */
.footer{
    background:#12304f!important;
    color:#ffffff!important;
    padding:82px 0 28px!important;
    border-top:none!important;
}

.footer-grid{
    width:min(1200px,calc(100% - 40px))!important;
    margin:0 auto 42px!important;
    display:grid!important;
    grid-template-columns:1.35fr 1fr 1.2fr!important;
    gap:62px!important;
    align-items:start!important;
}

.footer h3{
    font-size:22px!important;
    font-weight:950!important;
    color:#ffffff!important;
    margin-bottom:18px!important;
    letter-spacing:-.3px!important;
}

.footer p,
.footer a,
.footer li{
    color:rgba(218,235,248,.76)!important;
    font-size:15.5px!important;
    line-height:1.85!important;
    font-weight:650!important;
}

.footer-links,
.contact-list{
    list-style:none!important;
    display:grid!important;
    gap:10px!important;
    padding:0!important;
    margin:0!important;
}

/* remove the unwanted small square from previous version */
.footer-links a::before{
    content:none!important;
    display:none!important;
}

.footer-links a,
.contact-list a,
.contact-list li{
    width:fit-content!important;
    display:inline-flex!important;
    align-items:center!important;
    gap:10px!important;
    padding:3px 0!important;
    border-radius:12px!important;
    transition:color .22s ease, transform .22s ease, background .22s ease, padding .22s ease!important;
}

.footer-links i,
.contact-list i,
.footer i{
    color:var(--sky-500)!important;
    width:18px!important;
    min-width:18px!important;
    margin-top:0!important;
    text-align:center!important;
    transition:.22s ease!important;
}

.footer-links a:hover,
.contact-list a:hover,
.contact-list li:hover{
    color:#ffffff!important;
    transform:translateX(6px)!important;
    background:rgba(255,255,255,.055)!important;
    padding-left:8px!important;
    padding-right:10px!important;
}

.footer-links a:hover i,
.contact-list a:hover i,
.contact-list li:hover i{
    color:#7fd0ff!important;
    transform:scale(1.08)!important;
}

.footer .btn,
.footer .start-btn{
    margin-top:22px!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:8px!important;
    background:linear-gradient(135deg,var(--sky-500),var(--sky-600))!important;
    color:#ffffff!important;
    padding:14px 24px!important;
    border-radius:17px!important;
    font-size:15px!important;
    font-weight:950!important;
    box-shadow:0 16px 34px rgba(40,168,234,.25)!important;
}

.footer-bottom{
    width:min(1200px,calc(100% - 40px))!important;
    margin:0 auto!important;
    padding-top:22px!important;
    border-top:1px solid rgba(255,255,255,.14)!important;
    text-align:center!important;
    color:rgba(218,235,248,.86)!important;
    font-size:14px!important;
}

@media(max-width:900px){
    .footer-grid{
        grid-template-columns:1fr!important;
        gap:34px!important;
    }
}


/* ===== FINAL FIX: make START Browse show as button again ===== */
.footer a[href*="catalogue"],
.footer a[href="catalogue.php"],
.footer .start-btn,
.footer .footer-start-btn,
.footer .footer-cta,
.footer .btn,
.footer .btn-primary{
    transition:.22s ease!important;
}

/* Only turn the left-column START Browse into button */
.footer a[href="catalogue.php"]:has(i.fa-car-side),
.footer a[href*="catalogue.php"]:has(i.fa-car-side),
.footer .start-btn,
.footer .footer-start-btn,
.footer .footer-cta{
    margin-top:22px!important;
    width:fit-content!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:9px!important;
    background:linear-gradient(135deg,var(--sky-500),var(--sky-600))!important;
    color:#ffffff!important;
    padding:14px 24px!important;
    border-radius:17px!important;
    font-size:15px!important;
    font-weight:950!important;
    line-height:1!important;
    box-shadow:0 16px 34px rgba(40,168,234,.25)!important;
    text-decoration:none!important;
}

/* Fallback for browsers / layouts not matching :has() */
.footer .footer-brand a,
.footer .footer-about a,
.footer .footer-desc a{
    margin-top:22px!important;
    width:fit-content!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:9px!important;
    background:linear-gradient(135deg,var(--sky-500),var(--sky-600))!important;
    color:#ffffff!important;
    padding:14px 24px!important;
    border-radius:17px!important;
    font-size:15px!important;
    font-weight:950!important;
    line-height:1!important;
    box-shadow:0 16px 34px rgba(40,168,234,.25)!important;
    text-decoration:none!important;
}

.footer a[href="catalogue.php"]:has(i.fa-car-side):hover,
.footer a[href*="catalogue.php"]:has(i.fa-car-side):hover,
.footer .start-btn:hover,
.footer .footer-start-btn:hover,
.footer .footer-cta:hover,
.footer .footer-brand a:hover,
.footer .footer-about a:hover,
.footer .footer-desc a:hover{
    color:#ffffff!important;
    transform:translateY(-3px)!important;
    box-shadow:0 22px 42px rgba(40,168,234,.32)!important;
    padding-left:24px!important;
    padding-right:24px!important;
    background:linear-gradient(135deg,var(--sky-500),var(--sky-600))!important;
}

.footer a[href="catalogue.php"]:has(i.fa-car-side) i,
.footer a[href*="catalogue.php"]:has(i.fa-car-side) i,
.footer .start-btn i,
.footer .footer-start-btn i,
.footer .footer-cta i,
.footer .footer-brand a i,
.footer .footer-about a i,
.footer .footer-desc a i{
    color:#ffffff!important;
    width:auto!important;
    min-width:auto!important;
    margin:0!important;
}

    



/* ===== Clean Final Navbar Position ===== */
.navbar .container.nav-wrap{
    width:min(1500px, calc(100% - 24px))!important;
    max-width:none!important;
    margin:0 auto!important;
    min-height:82px!important;
    display:flex!important;
    align-items:center!important;
    gap:10px!important;
    padding-left:0!important;
    padding-right:0!important;
}

.navbar .nav-section-menu-toggle,
.navbar .section-menu-toggle.nav-section-menu-toggle{
    width:46px!important;
    height:46px!important;
    flex:0 0 46px!important;
    margin:0 8px 0 0!important;
}

.navbar .brand{
    margin-left:34px!important;
    margin-right:24px!important;
    flex:0 0 auto!important;
    gap:12px!important;
    white-space:nowrap!important;
}

.navbar .brand-logo{
    width:50px!important;
    height:50px!important;
    min-width:50px!important;
    flex:0 0 50px!important;
}

.navbar .nav-links{
    flex:1 1 auto!important;
    display:flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:8px!important;
    flex-wrap:nowrap!important;
    min-width:0!important;
}

.navbar .nav-link{
    display:inline-flex!important;
    align-items:center!important;
    gap:7px!important;
    padding:10px 9px!important;
    font-size:12px!important;
    white-space:nowrap!important;
    flex:0 0 auto!important;
    overflow:visible!important;
}

.navbar .nav-link i{
    position:relative!important;
    z-index:2!important;
    color:#31516f!important;
    font-size:13px!important;
    line-height:1!important;
    flex:0 0 auto!important;
}

.navbar .nav-link:hover i,
.navbar .nav-link.active i{
    color:var(--sky-600)!important;
}

.navbar .nav-actions{
    flex:0 0 auto!important;
    margin-left:auto!important;
    margin-right:18px!important;
    min-width:max-content!important;
}

.navbar .avatar-btn{
    flex:0 0 auto!important;
    min-width:max-content!important;
}

.navbar .avatar-img,
.navbar .avatar-initial{
    width:44px!important;
    height:44px!important;
    min-width:44px!important;
    flex:0 0 44px!important;
    aspect-ratio:1/1!important;
    object-fit:cover!important;
}

.navbar .nav-cart-link{
    position:relative!important;
    overflow:visible!important;
}

.navbar .cart-count-badge{
    position:absolute!important;
    top:-9px!important;
    right:-10px!important;
    min-width:18px!important;
    height:18px!important;
    padding:0 5px!important;
    border-radius:999px!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    background:linear-gradient(135deg,#ff4d4f,#d90429)!important;
    color:#fff!important;
    border:2px solid #fff!important;
    box-shadow:0 8px 18px rgba(217,4,41,.25)!important;
    font-size:10px!important;
    font-weight:950!important;
    line-height:1!important;
    z-index:20!important;
    pointer-events:none!important;
}


/* ===== Customer Comment Section ===== */
.comment-section {
    background: radial-gradient(circle at 12% 10%, rgba(184,228,255,0.42), transparent 32%), linear-gradient(180deg, #ffffff 0%, var(--sky-50) 100%) !important;
}

.comment-layout {
    display: grid;
    grid-template-columns: 0.95fr 1.05fr;
    gap: 26px;
    align-items: start;
}

.comment-form-card,
.comment-list-card {
    background: rgba(255,255,255,0.96);
    border: 1px solid rgba(184,228,255,0.95);
    border-radius: 34px;
    box-shadow: 0 26px 70px rgba(39,137,199,0.14);
    padding: 30px;
    backdrop-filter: blur(16px);
}

.comment-form-title {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 22px;
}

.comment-form-title i {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 18px;
    background: var(--sky-100);
    color: var(--sky-600);
    border: 1px solid var(--border);
    font-size: 20px;
}

.comment-form-title h3 {
    color: var(--blue-dark);
    font-size: 26px;
    font-weight: 950;
    letter-spacing: -0.7px;
    margin-bottom: 3px;
}

.comment-form-title p {
    color: var(--muted);
    font-size: 13px;
    font-weight: 700;
}

.comment-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.comment-field.full {
    grid-column: 1 / -1;
}

.comment-field label {
    display: block;
    margin-bottom: 8px;
    color: var(--blue-dark);
    font-size: 12px;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.comment-input {
    width: 100%;
    min-height: 50px;
    border: 2px solid #e2f2ff;
    background: #fbfdff;
    color: var(--blue-dark);
    border-radius: 16px;
    padding: 13px 14px;
    outline: none;
    font-size: 14px;
    font-weight: 750;
}

.comment-input:focus {
    border-color: var(--sky-500);
    box-shadow: 0 0 0 0.22rem rgba(40,168,234,0.13);
    background: #ffffff;
}

textarea.comment-input {
    min-height: 130px;
    resize: vertical;
}

.comment-alert {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 15px;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 850;
}

.comment-alert.success {
    color: #087747;
    background: #eefbf4;
    border: 1px solid rgba(33,181,115,0.22);
}

.comment-alert.error {
    color: #b42318;
    background: #fff4f2;
    border: 1px solid rgba(244,67,54,0.22);
}

.rating-select {
    appearance: auto;
}

.comment-list {
    display: grid;
    gap: 14px;
}

.comment-card {
    border-radius: 24px;
    background: linear-gradient(135deg, #ffffff, #f7fcff);
    border: 1px solid var(--border);
    padding: 20px;
    box-shadow: 0 10px 26px rgba(39,137,199,0.08);
}

.comment-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.comment-person {
    display: flex;
    align-items: center;
    gap: 11px;
}

.comment-avatar {
    width: 42px;
    height: 42px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, var(--sky-500), var(--sky-600));
    color: #fff;
    font-weight: 950;
    flex: 0 0 42px;
}

.comment-person strong {
    display: block;
    color: var(--blue-dark);
    font-size: 15px;
    font-weight: 950;
}

.comment-person span,
.comment-date {
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
}

.comment-stars {
    color: #ff9f1a;
    font-size: 13px;
    white-space: nowrap;
}

.comment-card h4 {
    color: var(--blue-dark);
    font-size: 17px;
    font-weight: 950;
    margin-bottom: 7px;
}

.comment-card p {
    color: var(--muted);
    line-height: 1.65;
    font-size: 14px;
    font-weight: 650;
}

.empty-comments {
    border: 2px dashed rgba(40,168,234,0.25);
    border-radius: 26px;
    padding: 34px;
    text-align: center;
    color: var(--muted);
    background: rgba(234,247,255,0.54);
    font-weight: 750;
}

@media(max-width: 920px) {
    .comment-layout,
    .comment-form-grid {
        grid-template-columns: 1fr;
    }
}


/* ===== Comment Star Rating Drag Style ===== */
.comment-rating-field{
    grid-column:1 / -1;
}

.rating-select-hidden{
    position:absolute!important;
    opacity:0!important;
    pointer-events:none!important;
    width:1px!important;
    height:1px!important;
}

.star-rating-box{
    width:100%;
    min-height:74px;
    border:2px solid #e2f2ff;
    background:#fbfdff;
    border-radius:18px;
    padding:13px 16px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    user-select:none;
}

.star-rating-stars{
    display:flex;
    align-items:center;
    gap:8px;
    cursor:pointer;
    touch-action:none;
}

.star-rating-stars i{
    font-size:30px;
    color:#d6e5ef;
    transition:.16s ease;
    filter:drop-shadow(0 6px 10px rgba(255,159,26,.10));
}

.star-rating-stars i.active{
    color:#ff9f1a;
    transform:scale(1.05);
}

.star-rating-text{
    min-width:150px;
    text-align:right;
    color:var(--blue-dark);
    font-size:14px;
    font-weight:950;
}

.star-rating-hint{
    display:block;
    margin-top:5px;
    color:var(--muted);
    font-size:11px;
    font-weight:750;
}

.comment-form-grid.no-title-grid{
    grid-template-columns:1fr 1fr;
}

.comment-form-grid.no-title-grid .comment-field.email-field{
    grid-column:auto;
}

@media(max-width:680px){
    .star-rating-box{
        display:grid;
        justify-content:stretch;
    }

    .star-rating-text{
        text-align:left;
        min-width:0;
    }
}

</style>
</head>

<body>

<div class="validation-toast" id="validationToast">
    <div class="validation-toast-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div>
        <strong>Invalid rental time</strong>
        <span id="validationToastText">Return date and time must be later than pickup date and time.</span>
    </div>
</div>

<header class="navbar">
    <div class="container nav-wrap">
        <button class="section-menu-toggle nav-section-menu-toggle" id="sectionMenuToggle" type="button" aria-label="Open section menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <a href="homepage.php" class="brand">
            <span class="brand-logo"><i class="<?= e($settings['logo_icon'] ?? 'fa-solid fa-car-side') ?>"></i></span>
            <span><?= e($settings['site_name'] ?? 'KH Car Rental') ?></span>
        </a>

        <nav class="nav-links">
            <?php foreach ($navLinks as $link): ?>
                <?php $isCartLink = strtoupper($link['title']) === 'CART'; ?>
                <a class="nav-link <?= $isCartLink ? 'nav-cart-link' : '' ?> <?= basename($_SERVER['PHP_SELF']) === $link['url'] ? 'active' : '' ?>" href="<?= e($link['url']) ?>">
                    <i class="<?= e($link['icon']) ?>"></i>
                    <span><?= e($link['title']) ?></span>
                    <?php if ($isCartLink && $navCartCount > 0): ?>
                        <span class="cart-count-badge"><?= e($navCartCount > 99 ? '99+' : $navCartCount) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="nav-actions">
            <?php if ($user): ?>
                <button class="avatar-btn" id="avatarBtn" type="button">
                    <span class="avatar-alert-wrap">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img class="avatar-img" src="<?= e($user['profile_picture']) ?>" alt="<?= e($user['name']) ?>">
                        <?php else: ?>
                            <span class="avatar-initial"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></span>
                        <?php endif; ?>
                        <?php if ($homepageKycNeedsAttention): ?>
                            <span class="avatar-kyc-dot" title="KYC verification required"></span>
                        <?php endif; ?>
                    </span>
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


<div class="section-side-nav" id="sectionSideNav" aria-hidden="true">
    <button class="section-side-close" id="sectionSideClose" type="button" aria-label="Close section menu">
        <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="section-side-head">
        <span>Homepage Menu</span>
        <strong>KH Car Rental</strong>
    </div>

    <a href="#popular-cars"><i class="fa-solid fa-fire"></i> Popular Cars</a>
    <a href="#promotion"><i class="fa-solid fa-gift"></i> Promotion</a>
    <a href="#why-choose-us"><i class="fa-solid fa-star"></i> Why Choose Us</a>
    <a href="#how-it-works"><i class="fa-solid fa-route"></i> How It Works</a>
    <a href="#find-car-smart-section"><i class="fa-solid fa-wand-magic-sparkles"></i> Find Car Smart</a>
    <a href="#rental-states"><i class="fa-solid fa-map-location-dot"></i> Rental States</a>
    <a href="#faqs"><i class="fa-solid fa-circle-question"></i> FAQs</a>
    <a href="#comments"><i class="fa-solid fa-comments"></i> Comments</a>
</div>

<div class="section-side-overlay" id="sectionSideOverlay"></div>


<main>
    <section class="hero" id="home">
        <?php foreach ($heroSlides as $index => $slide): ?>
            <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>" style="background-image: url('<?= e($slide['image_url']) ?>');"></div>
        <?php endforeach; ?>

        <button class="hero-arrow prev" type="button" id="heroPrev"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="hero-arrow next" type="button" id="heroNext"><i class="fa-solid fa-chevron-right"></i></button>

        <div class="hero-content">
            <div class="container hero-grid">
                <div class="hero-text">
                    <div class="hero-label">
                        <i class="<?= e($settings['hero_label_icon'] ?? 'fa-solid fa-location-dot') ?>"></i>
                        <?= e($settings['hero_label'] ?? 'Johor • Melaka • Kuala Lumpur') ?>
                    </div>

                    <h1 class="hero-title" id="heroTitle"><?= e($heroSlides[0]['slide_title'] ?? ($settings['hero_title'] ?? 'Drive Your Journey With Confidence')) ?></h1>
                    <p class="hero-desc" id="heroDesc"><?= e($heroSlides[0]['slide_subtitle'] ?? ($settings['hero_description'] ?? 'Search available rental cars, compare models and manage your booking through one simple online car rental system.')) ?></p>

                    <div class="hero-badges">
                        <?php foreach ($featureBadges as $badge): ?>
                            <div class="hero-badge">
                                <i class="<?= e($badge['icon_class']) ?>"></i>
                                <span><?= e($badge['badge_title']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="search-card" id="search">
                    <div class="search-title">
                        <h2>Search Available Cars</h2>
                        <p>Select your rental location and time to find available cars.</p>
                    </div>

                    <form class="search-form" action="available_cars.php" method="GET">
                        <div class="form-group">
                            <label for="pickup_state">Pickup State</label>
                            <select class="form-control" name="state" id="pickup_state" required>
                                <option value="">Select State</option>
                                <?php foreach ($states as $state): ?>
                                    <option value="<?= e($state['state_slug']) ?>"><?= e($state['state_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="pickup_location">Pickup Location</label>
                                <select class="form-control" name="pickup_location" id="pickup_location" required>
                                    <option value="">Select Pickup Location</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="dropoff_location">Drop-off Location</label>
                                <select class="form-control" name="dropoff_location" id="dropoff_location" required>
                                    <option value="">Select Drop-off Location</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="pickup_date">Pickup Date</label>
                                <input class="form-control" type="date" name="pickup_date" id="pickup_date" required>
                            </div>

                            <div class="form-group">
                                <label>Pickup Time</label>
                                <div class="time-combo" id="pickup_time_group">
                                    <select class="form-control time-part" id="pickup_hour" required>
                                        <option value="">Hour</option>
                                    </select>
                                    <select class="form-control time-part" id="pickup_minute" required>
                                        <option value="">Minute</option>
                                    </select>
                                </div>
                                <input type="hidden" name="pickup_time" id="pickup_time" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="return_date">Return Date</label>
                                <input class="form-control" type="date" name="return_date" id="return_date" required>
                            </div>

                            <div class="form-group">
                                <label for="return_time_display">Return Time</label>
                                <div class="fixed-time-display" id="return_time_display">
                                    <i class="fa-solid fa-lock"></i>
                                    <span>Same as pickup time</span>
                                </div>
                                <input type="hidden" name="return_time" id="return_time" required>
                            </div>
                        </div>

                        <button class="search-btn" type="submit">
                            <i class="fa-solid fa-magnifying-glass"></i> Search Available Cars
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="hero-controls">
            <?php foreach ($heroSlides as $index => $slide): ?>
                <button class="hero-dot <?= $index === 0 ? 'active' : '' ?>" type="button" data-slide="<?= $index ?>"></button>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="popular-cars">
        <div class="container">
            <div class="section-head">
                <span class="section-label">POPULAR CARS</span>
                <h2>Featured Rental Cars</h2>
                <p>Choose from our popular cars for city drives, family trips and business travel.</p>
            </div>

            <div class="car-grid">
                <?php foreach ($popularCars as $car): ?>
                    <?php
                        $images = $carImageMap[$car['car_id']] ?? [];
                        if (empty($images)) {
                            $images[] = ["url" => $car['main_image'], "type" => "front"];
                        }
                    ?>
                    <article class="car-card">
                        <div class="car-carousel" data-carousel>
                            <div class="car-carousel-track" data-track>
                                <?php foreach ($images as $img): ?>
                                    <div class="car-carousel-slide">
                                        <img src="<?= e($img['url']) ?>" alt="<?= e($car['car_name']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                        <div class="image-fallback" style="display:none;"><i class="fa-solid fa-car-side"></i></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (count($images) > 1): ?>
                                <button class="car-carousel-btn prev" type="button" data-prev><i class="fa-solid fa-chevron-left"></i></button>
                                <button class="car-carousel-btn next" type="button" data-next><i class="fa-solid fa-chevron-right"></i></button>

                                <div class="car-carousel-dots">
                                    <?php foreach ($images as $index => $img): ?>
                                        <button class="car-carousel-dot <?= $index === 0 ? 'active' : '' ?>" type="button" data-dot="<?= $index ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="car-body">
                            <div class="car-meta">
                                <span class="brand-pill"><?= e($car['brand_name']) ?></span>
                                <span class="category-pill"><?= e($car['category_name']) ?></span>
                                <span class="status-pill popular-pill">
                                    <i class="fa-solid fa-fire"></i> Popular
                                </span>
                            </div>

                            <h3><?= e($car['car_name']) ?></h3>

                            <div class="spec-list">
                                <span><i class="fa-solid fa-car"></i> <?= e($car['type']) ?></span>
                                <span><i class="fa-solid fa-user-group"></i> <?= e($car['seats']) ?> Seats</span>
                                <span><i class="fa-solid fa-gears"></i> <?= e($car['transmission']) ?></span>
                                <span><i class="fa-solid fa-gas-pump"></i> <?= e($car['fuel_type']) ?></span>
                                <span><i class="fa-solid fa-gauge-high"></i> <?= e($car['engine']) ?></span>
                                <span><i class="fa-solid fa-bolt"></i> <?= e($car['horsepower']) ?></span>
                                <span><i class="fa-solid fa-stopwatch"></i> <?= e($car['acceleration_0_100'] ?? '0-100: N/A') ?></span>
                                <span><i class="fa-solid fa-suitcase"></i> <?= e($car['luggage_capacity']) ?></span>
                            </div>

                            <div class="price-row">
                                <div class="price">RM <?= e(number_format((float)$car['price_per_day'], 2)) ?> <span>/ day</span></div>
                            </div>

                            <div class="card-actions">
                                <a class="btn btn-dark" href="car_details.php?car_id=<?= e($car['car_id']) ?>">View Details</a>
                                <button class="btn btn-primary check-availability-btn" type="button" data-car-id="<?= e($car['car_id']) ?>" data-car-name="<?= e($car['car_name']) ?>">Check Availability</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <?php if (!$user && $newUserPromo): ?>
        <section class="promo-section" id="promotion">
            <div class="container">
                <div class="section-head promo-section-head">
                    <span class="section-label">PROMOTION</span>
                    <h2>New User Promotion</h2>
                    <p>Register a new account and enjoy a special discount voucher for your first booking.</p>
                </div>

                <div class="new-user-deal">
                    <div class="deal-content">
                        <span class="deal-kicker">
                            <i class="fa-solid fa-gift"></i>
                            NEW USER PROMOTION
                        </span>

                        <h2><?= e($newUserPromo['promo_name'] ?? 'Register Today and Enjoy Your First Rental Deal') ?></h2>

                        <p><?= e($newUserPromo['description'] ?? 'Create a KH Car Rental account and receive a 5% discount voucher for your first booking.') ?></p>

                        <div class="deal-action-row">
<a class="btn btn-primary deal-register-btn" href="register.php">
                                <i class="fa-solid fa-user-plus"></i>
                                Register Now
                            </a>
                        </div>
                    </div>

                    <div class="deal-discount-card">
                        <span class="discount-value"><?= e(($newUserPromo['discount_percent'] ?? 5) . '%') ?></span>
                        <span class="discount-label">OFF</span>
                        <span class="discount-small">First Booking</span>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="why" id="why-choose-us">
        <div class="container">
            <div class="section-head">
                <span class="section-label">WHY CHOOSE US</span>
                <h2>Why Choose KH Car Rental</h2>
                <p>Our system is designed to make car rental easier, clearer and more reliable for customers.</p>
            </div>

            <div class="why-grid">
                <?php foreach ($whyChoose as $item): ?>
                    <article class="why-card">
                        <div class="why-icon"><i class="<?= e($item['icon_class']) ?>"></i></div>
                        <h3><?= e($item['title']) ?></h3>
                        <p><?= e($item['description']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="how-it-works">
        <div class="container">
            <div class="section-head">
                <span class="section-label">HOW IT WORKS</span>
                <h2>Simple Booking Flow</h2>
                <p>Search your trip, choose your car, make payment and wait for admin approval.</p>
            </div>

            <div class="steps-grid">
                <?php foreach ($howItWorks as $step): ?>
                    <article class="step-card">
                        <div class="step-number"><?= e(str_pad($step['step_number'], 2, '0', STR_PAD_LEFT)) ?></div>
                        <div class="step-icon"><i class="<?= e($step['icon_class']) ?>"></i></div>
                        <h3><?= e($step['title']) ?></h3>
                        <p><?= e($step['description']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="smart-cta" id="find-car-smart-section">
        <div class="container">
            <div class="section-head smart-section-head">
                <span class="section-label">FIND CAR SMART</span>
                <h2>Find Car Smart</h2>
                <p>Not sure which car to rent? Let the system guide customers to a suitable vehicle.</p>
            </div>

            <div class="smart-box">
                <div>
                    <span class="section-label">FIND CAR SMART</span>
                    <h2>Not sure which car to rent?</h2>
                    <p>Answer a few simple questions and our smart recommendation assistant will suggest the most suitable available car based on your rental time and state.</p>
                    <a class="btn btn-primary" href="find_car_smart.php"><i class="fa-solid fa-wand-magic-sparkles"></i> Start Smart Recommendation</a>
                </div>

                <div class="smart-visual">
                    <i class="fa-solid fa-brain"></i>
                </div>
            </div>
        </div>
    </section>

    <section id="rental-states">
        <div class="container">
            <div class="section-head">
                <span class="section-label">RENTAL STATES</span>
                <h2>Available Rental States</h2>
                <p>KH Car Rental currently supports car rental services in Johor, Melaka and Kuala Lumpur.</p>
            </div>

            <div class="states-grid">
                <?php foreach ($states as $state): ?>
                    <a class="state-card" href="<?= e(statePageUrl($state)) ?>" style="background-image: url('<?= e($state['image_url']) ?>');">
                        <div class="state-content">
                            <h3><?= e($state['state_name']) ?></h3>
                            <p><?= e($state['description']) ?></p>
                            <span class="btn btn-primary">View Locations</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="faq" id="faqs">
        <div class="container">
            <div class="section-head">
                <span class="section-label">FAQ</span>
                <h2>Frequently Asked Questions</h2>
                <p>Quick answers about booking, availability, payment, approval and booking status.</p>
            </div>

            <div class="faq-grid">
                <?php foreach ($faqItems as $faq): ?>
                    <article class="faq-item">
                        <button class="faq-question" type="button">
                            <span><?= e($faq['question']) ?></span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer"><?= e($faq['answer']) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="comment-section" id="comments">
        <div class="container">
            <div class="section-head">
                <span class="section-label"><i class="fa-solid fa-comments"></i> CUSTOMER COMMENTS</span>
                <h2>What Our Customers Say</h2>
                <p>Share your rental experience with KH Car Rental. Your comment will be saved into the database and displayed on this homepage.</p>
            </div>

            <div class="comment-layout">
                <div class="comment-form-card">
                    <div class="comment-form-title">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <div>
                            <h3>Write a Comment</h3>
                            <p>Tell us about your experience.</p>
                        </div>
                    </div>

                    <form method="POST" action="homepage.php#comments" class="comment-form-grid no-title-grid">
                        <?php if ($commentSuccess): ?>
                            <div class="comment-alert success">
                                <i class="fa-solid fa-circle-check"></i>
                                <?= e($commentSuccess) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($commentError): ?>
                            <div class="comment-alert error">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <?= e($commentError) ?>
                            </div>
                        <?php endif; ?>

                        <div class="comment-field">
                            <label>Name</label>
                            <input class="comment-input" type="text" name="customer_name" value="<?= e($_POST['customer_name'] ?? ($user['name'] ?? '')) ?>" required>
                        </div>

                        <div class="comment-field">
                            <label>Email</label>
                            <input class="comment-input" type="email" name="email" value="<?= e($_POST['email'] ?? ($user['email'] ?? '')) ?>">
                        </div>

                        <div class="comment-field comment-rating-field">
                            <label>Rating</label>
                            <?php $selectedRating = (int)($_POST['rating'] ?? 5); ?>
                            <input type="hidden" name="rating" id="commentRatingValue" value="<?= e($selectedRating) ?>">
                            <div class="star-rating-box">
                                <div class="star-rating-stars" id="commentStarRating" data-rating="<?= e($selectedRating) ?>">
                                    <i class="fa-solid fa-star" data-value="1"></i>
                                    <i class="fa-solid fa-star" data-value="2"></i>
                                    <i class="fa-solid fa-star" data-value="3"></i>
                                    <i class="fa-solid fa-star" data-value="4"></i>
                                    <i class="fa-solid fa-star" data-value="5"></i>
                                </div>
                                <div class="star-rating-text">
                                    <span id="commentRatingText">5 Stars - Excellent</span>
                                    <span class="star-rating-hint">Drag or click the stars</span>
                                </div>
                            </div>
                        </div>

                        <div class="comment-field full">
                            <label>Comment</label>
                            <textarea class="comment-input" name="content" placeholder="Write your comment here..." required><?= e($_POST['content'] ?? '') ?></textarea>
                        </div>

                        <div class="comment-field full">
                            <button class="btn btn-primary" type="submit" name="submit_customer_comment" value="1">
                                <i class="fa-solid fa-paper-plane"></i> Submit Comment
                            </button>
                        </div>
                    </form>
                </div>

                <div class="comment-list-card">
                    <div class="comment-form-title">
                        <i class="fa-solid fa-star"></i>
                        <div>
                            <h3>Latest Comments</h3>
                            <p>Recent feedback from customers.</p>
                        </div>
                    </div>

                    <div class="comment-list">
                        <?php if (!empty($customerComments)): ?>
                            <?php foreach ($customerComments as $comment): ?>
                                <article class="comment-card">
                                    <div class="comment-top">
                                        <div class="comment-person">
                                            <div class="comment-avatar"><?= e(strtoupper(substr($comment['customer_name'], 0, 1))) ?></div>
                                            <div>
                                                <strong><?= e($comment['customer_name']) ?></strong>
                                                <span><?= e(date('d M Y', strtotime($comment['created_at']))) ?></span>
                                            </div>
                                        </div>

                                        <div class="comment-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fa-<?= $i <= (int)$comment['rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <p><?= e($comment['content']) ?></p>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-comments">
                                <i class="fa-solid fa-comments"></i><br><br>
                                No comments yet. Be the first customer to write a comment.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>


<div class="availability-modal" id="availabilityModal" aria-hidden="true">
    <div class="availability-modal-backdrop" data-close-availability></div>

    <div class="availability-modal-card" role="dialog" aria-modal="true">
        <button class="availability-modal-close" type="button" data-close-availability>
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="search-card modal-search-card">
            <div class="search-title">
                <span class="modal-car-label">Check Selected Vehicle</span>
                <h2>Check Availability</h2>
                <p id="modalCarName">Select rental location and time to check this vehicle.</p>
            </div>

            <form class="search-form modal-search-form" action="available_cars.php" method="GET">
                <input type="hidden" name="car_id" id="modal_car_id">

                <div class="form-group">
                    <label for="modal_pickup_state">Pickup State</label>
                    <select class="form-control" name="state" id="modal_pickup_state" required>
                        <option value="">Select State</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?= e($state['state_slug']) ?>"><?= e($state['state_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_pickup_location">Pickup Location</label>
                        <select class="form-control" name="pickup_location" id="modal_pickup_location" required>
                            <option value="">Select Pickup Location</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modal_dropoff_location">Drop-off Location</label>
                        <select class="form-control" name="dropoff_location" id="modal_dropoff_location" required>
                            <option value="">Select Drop-off Location</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_pickup_date">Pickup Date</label>
                        <input class="form-control" type="date" name="pickup_date" id="modal_pickup_date" required>
                    </div>

                    <div class="form-group">
                        <label>Pickup Time</label>
                        <div class="time-combo" id="modal_pickup_time_group">
                            <select class="form-control time-part" id="modal_pickup_hour" required>
                                <option value="">Hour</option>
                            </select>
                            <select class="form-control time-part" id="modal_pickup_minute" required>
                                <option value="">Minute</option>
                            </select>
                        </div>
                        <input type="hidden" name="pickup_time" id="modal_pickup_time" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_return_date">Return Date</label>
                        <input class="form-control" type="date" name="return_date" id="modal_return_date" required>
                    </div>

                    <div class="form-group">
                        <label for="modal_return_time_display">Return Time</label>
                        <div class="fixed-time-display" id="modal_return_time_display">
                            <i class="fa-solid fa-lock"></i>
                            <span>Same as pickup time</span>
                        </div>
                        <input type="hidden" name="return_time" id="modal_return_time" required>
                    </div>
                </div>

                <button class="search-btn" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i> Check This Vehicle
                </button>
            </form>
        </div>
    </div>
</div>


<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <h3><?= e($settings['site_name'] ?? 'KH Car Rental') ?></h3>
                <p><?= e($settings['footer_description'] ?? '') ?></p>
                <br>
                <a class="btn btn-primary" href="catalogue.php"><i class="fa-solid fa-car-side"></i> START Browse</a>
            </div>

            <div>
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <?php foreach ($navLinks as $link): ?>
                        <li><a href="<?= e($link['url']) ?>"><?= e($link['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <h3>Contact Us</h3>
                <ul class="contact-list">
                    <?php foreach ($footerContacts as $contact): ?>
                        <li>
                            <i class="<?= e($contact['icon_class']) ?>"></i>
                            <a href="<?= e($contact['contact_url']) ?>" <?= $contact['open_new_tab'] ? 'target="_blank"' : '' ?>>
                                <?= e($contact['contact_text']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            © <?= date('Y') ?> <?= e($settings['site_name'] ?? 'KH Car Rental') ?>. All rights reserved.
        </div>
    </div>
</footer>

<button class="back-top" id="backTop" type="button"><i class="fa-solid fa-arrow-up"></i></button>

    <script>
        const locationMap = <?= json_encode($locationMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const heroSlideTexts = <?= json_encode(array_map(function ($slide) use ($settings) {
        return [
            "title" => $slide["slide_title"] ?? ($settings["hero_title"] ?? "Drive Your Journey With Confidence"),
            "subtitle" => $slide["slide_subtitle"] ?? ($settings["hero_description"] ?? "Search available rental cars, compare models and manage your booking through one simple online car rental system.")
        ];
    }, $heroSlides), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script>
const stateSelect = document.getElementById('pickup_state');
    const pickupSelect = document.getElementById('pickup_location');
    const dropoffSelect = document.getElementById('dropoff_location');

    function loadLocations() {
        const selectedState = stateSelect.value;
        const stateLocations = locationMap[selectedState] || [];

        pickupSelect.innerHTML = '<option value="">Select Pickup Location</option>';
        dropoffSelect.innerHTML = '<option value="">Select Drop-off Location</option>';

        stateLocations.forEach(location => {
            pickupSelect.add(new Option(location.name, location.id));
            dropoffSelect.add(new Option(location.name, location.id));
        });
    }

    stateSelect.addEventListener('change', loadLocations);

    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    const prev = document.getElementById('heroPrev');
    const next = document.getElementById('heroNext');
    let currentSlide = 0;

    const heroTitle = document.getElementById('heroTitle');
    const heroDesc = document.getElementById('heroDesc');

    function showSlide(index) {
        if (slides.length === 0) {
            return;
        }

        currentSlide = (index + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === currentSlide);
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === currentSlide);
        });

        if (heroSlideTexts[currentSlide]) {
            heroTitle.textContent = heroSlideTexts[currentSlide].title;
            heroDesc.textContent = heroSlideTexts[currentSlide].subtitle;
        }
    }

    if (slides.length > 0) {
        prev.addEventListener('click', () => showSlide(currentSlide - 1));
        next.addEventListener('click', () => showSlide(currentSlide + 1));

        dots.forEach(dot => {
            dot.addEventListener('click', () => showSlide(Number(dot.dataset.slide)));
        });

        setInterval(() => showSlide(currentSlide + 1), 5200);
    }

    document.querySelectorAll('[data-carousel]').forEach(carousel => {
        const track = carousel.querySelector('[data-track]');
        const slides = carousel.querySelectorAll('.car-carousel-slide');
        const prevBtn = carousel.querySelector('[data-prev]');
        const nextBtn = carousel.querySelector('[data-next]');
        const dots = carousel.querySelectorAll('[data-dot]');
        let index = 0;

        function updateCarousel(newIndex) {
            if (!slides.length) {
                return;
            }

            index = (newIndex + slides.length) % slides.length;
            track.style.transform = `translateX(-${index * 100}%)`;

            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('active', dotIndex === index);
            });
        }

        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => updateCarousel(index - 1));
            nextBtn.addEventListener('click', () => updateCarousel(index + 1));
        }

        dots.forEach(dot => {
            dot.addEventListener('click', () => updateCarousel(Number(dot.dataset.dot)));
        });

        if (slides.length > 1) {
            setInterval(() => updateCarousel(index + 1), 4300);
        }
    });

    document.querySelectorAll('.faq-question').forEach(button => {
        button.addEventListener('click', () => {
            button.closest('.faq-item').classList.toggle('active');
        });
    });

    const avatarBtn = document.getElementById('avatarBtn');
    const profileDropdown = document.getElementById('profileDropdown');

    if (avatarBtn && profileDropdown) {
        avatarBtn.addEventListener('click', () => profileDropdown.classList.toggle('show'));

        document.addEventListener('click', event => {
            if (!event.target.closest('.nav-actions')) {
                profileDropdown.classList.remove('show');
            }
        });
    }

    const backTop = document.getElementById('backTop');

    window.addEventListener('scroll', () => {
        backTop.classList.toggle('show', window.scrollY > 450);
    });

    backTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    const searchForm = document.querySelector('.search-form');
    const pickupDateInput = document.getElementById('pickup_date');
    const pickupHourInput = document.getElementById('pickup_hour');
    const pickupMinuteInput = document.getElementById('pickup_minute');
    const pickupTimeInput = document.getElementById('pickup_time');
    const pickupTimeGroup = document.getElementById('pickup_time_group');
    const returnDateInput = document.getElementById('return_date');
    const returnTimeInput = document.getElementById('return_time');
    const returnTimeDisplay = document.getElementById('return_time_display');
    const validationToast = document.getElementById('validationToast');
    const validationToastText = document.getElementById('validationToastText');
    let validationTimer = null;

    function formatHourLabel(hour) {
        const displayHour = hour % 12 === 0 ? 12 : hour % 12;
        const ampm = hour >= 12 ? 'PM' : 'AM';
        return `${String(displayHour).padStart(2, '0')} ${ampm}`;
    }

    function formatTimeLabel(timeValue) {
        if (!timeValue || !timeValue.includes(':')) {
            return 'Same as pickup time';
        }

        const [hourText, minuteText] = timeValue.split(':');
        const hour = Number(hourText);
        const displayHour = hour % 12 === 0 ? 12 : hour % 12;
        const ampm = hour >= 12 ? 'PM' : 'AM';
        return `${String(displayHour).padStart(2, '0')}:${minuteText} ${ampm} (Fixed)`;
    }

    function buildHourOptions() {
        pickupHourInput.innerHTML = '<option value="">Hour</option>';

        for (let hour = 0; hour < 24; hour++) {
            const value = String(hour).padStart(2, '0');
            pickupHourInput.add(new Option(formatHourLabel(hour), value));
        }
    }

    function buildMinuteOptions() {
        pickupMinuteInput.innerHTML = '<option value="">Minute</option>';

        for (let minute = 0; minute < 60; minute += 5) {
            const value = String(minute).padStart(2, '0');
            pickupMinuteInput.add(new Option(value, value));
        }
    }

    function updatePickupAndReturnTime() {
        if (pickupHourInput.value !== '' && pickupMinuteInput.value !== '') {
            const selectedTime = `${pickupHourInput.value}:${pickupMinuteInput.value}`;
            pickupTimeInput.value = selectedTime;
            returnTimeInput.value = selectedTime;
            returnTimeDisplay.querySelector('span').textContent = formatTimeLabel(selectedTime);
        } else {
            pickupTimeInput.value = '';
            returnTimeInput.value = '';
            returnTimeDisplay.querySelector('span').textContent = 'Same as pickup time';
        }

        validateRentalDateTime(false);
    }

    function addDays(dateText, days) {
        const date = new Date(`${dateText}T00:00:00`);
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    }

    function getMinimumPickupDateTime() {
        const minimumPickup = new Date();
        minimumPickup.setHours(minimumPickup.getHours() + 1);
        minimumPickup.setSeconds(0, 0);
        return minimumPickup;
    }

    function toDateInputValue(date) {
        return date.getFullYear() + '-' +
            String(date.getMonth() + 1).padStart(2, '0') + '-' +
            String(date.getDate()).padStart(2, '0');
    }

    function refreshPickupTimeRestrictions(dateInput, hourInput, minuteInput) {
        if (!dateInput || !hourInput || !minuteInput) return;

        const minimumPickup = getMinimumPickupDateTime();
        const minimumDate = toDateInputValue(minimumPickup);
        const minimumHour = String(minimumPickup.getHours()).padStart(2, '0');
        const minimumMinute = String(minimumPickup.getMinutes()).padStart(2, '0');

        dateInput.setAttribute('min', minimumDate);

        Array.from(hourInput.options).forEach(option => {
            if (!option.value) {
                option.disabled = false;
                return;
            }

            option.disabled = dateInput.value === minimumDate && option.value < minimumHour;
        });

        Array.from(minuteInput.options).forEach(option => {
            if (!option.value) {
                option.disabled = false;
                return;
            }

            option.disabled = dateInput.value === minimumDate &&
                hourInput.value === minimumHour &&
                option.value < minimumMinute;
        });

        if (hourInput.selectedOptions[0] && hourInput.selectedOptions[0].disabled) {
            hourInput.value = '';
            minuteInput.value = '';
        }

        if (minuteInput.selectedOptions[0] && minuteInput.selectedOptions[0].disabled) {
            minuteInput.value = '';
        }
    }

    function showValidationToast(message) {
        validationToastText.textContent = message;
        validationToast.classList.add('show');
        clearTimeout(validationTimer);
        validationTimer = setTimeout(() => {
            validationToast.classList.remove('show');
        }, 4200);
    }

    function clearDateTimeError() {
        [pickupDateInput, pickupHourInput, pickupMinuteInput, returnDateInput].forEach(input => {
            input.classList.remove('invalid-field');
        });

        pickupTimeGroup.classList.remove('invalid-field');
        returnTimeDisplay.classList.remove('invalid-field');
    }

    function markDateTimeError() {
        [pickupDateInput, pickupHourInput, pickupMinuteInput, returnDateInput].forEach(input => {
            input.classList.add('invalid-field');
        });

        pickupTimeGroup.classList.add('invalid-field');
        returnTimeDisplay.classList.add('invalid-field');
    }

    function validateRentalDateTime(showMessage = false) {
        clearDateTimeError();

        if (!pickupDateInput.value || !pickupTimeInput.value || !returnDateInput.value || !returnTimeInput.value) {
            return false;
        }

        const pickupDateTime = new Date(`${pickupDateInput.value}T${pickupTimeInput.value}:00`);
        const returnDateTime = new Date(`${returnDateInput.value}T${returnTimeInput.value}:00`);
        const minimumPickupDateTime = getMinimumPickupDateTime();
        const minimumReturnDate = addDays(pickupDateInput.value, 1);

        if (pickupDateTime < minimumPickupDateTime) {
            markDateTimeError();

            if (showMessage) {
                showValidationToast('Pickup time must be at least 1 hour from now.');
            }

            return false;
        }

        if (returnDateInput.value < minimumReturnDate || returnDateTime <= pickupDateTime) {
            markDateTimeError();

            if (showMessage) {
                showValidationToast('Invalid rental date. Minimum rental period is 1 day, and return date must be after pickup date.');
            }

            return false;
        }

        return true;
    }

    buildHourOptions();
    buildMinuteOptions();

    const today = toDateInputValue(getMinimumPickupDateTime());
    pickupDateInput.setAttribute('min', today);
    returnDateInput.setAttribute('min', addDays(today, 1));
    refreshPickupTimeRestrictions(pickupDateInput, pickupHourInput, pickupMinuteInput);

    pickupDateInput.addEventListener('change', () => {
        if (pickupDateInput.value) {
            returnDateInput.setAttribute('min', addDays(pickupDateInput.value, 1));
        } else {
            returnDateInput.setAttribute('min', addDays(today, 1));
        }

        refreshPickupTimeRestrictions(pickupDateInput, pickupHourInput, pickupMinuteInput);
        updatePickupAndReturnTime();
        validateRentalDateTime(false);
    });

    pickupHourInput.addEventListener('change', () => {
        refreshPickupTimeRestrictions(pickupDateInput, pickupHourInput, pickupMinuteInput);
        updatePickupAndReturnTime();
    });
    pickupMinuteInput.addEventListener('change', () => {
        refreshPickupTimeRestrictions(pickupDateInput, pickupHourInput, pickupMinuteInput);
        updatePickupAndReturnTime();
    });
    returnDateInput.addEventListener('change', () => validateRentalDateTime(false));

    searchForm.addEventListener('submit', event => {
        updatePickupAndReturnTime();

        if (!validateRentalDateTime(true)) {
            event.preventDefault();
            document.querySelector('.search-card').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });


// ===== availability modal check selected vehicle =====
const availabilityModal = document.getElementById('availabilityModal');
const modalCarIdInput = document.getElementById('modal_car_id');
const modalCarNameText = document.getElementById('modalCarName');
const modalStateSelect = document.getElementById('modal_pickup_state');
const modalPickupSelect = document.getElementById('modal_pickup_location');
const modalDropoffSelect = document.getElementById('modal_dropoff_location');
const modalPickupDateInput = document.getElementById('modal_pickup_date');
const modalPickupHourInput = document.getElementById('modal_pickup_hour');
const modalPickupMinuteInput = document.getElementById('modal_pickup_minute');
const modalPickupTimeInput = document.getElementById('modal_pickup_time');
const modalPickupTimeGroup = document.getElementById('modal_pickup_time_group');
const modalReturnDateInput = document.getElementById('modal_return_date');
const modalReturnTimeInput = document.getElementById('modal_return_time');
const modalReturnTimeDisplay = document.getElementById('modal_return_time_display');
const modalSearchForm = document.querySelector('.modal-search-form');

function loadModalLocations() {
    const selectedState = modalStateSelect.value;
    const stateLocations = locationMap[selectedState] || [];

    modalPickupSelect.innerHTML = '<option value="">Select Pickup Location</option>';
    modalDropoffSelect.innerHTML = '<option value="">Select Drop-off Location</option>';

    stateLocations.forEach(location => {
        modalPickupSelect.add(new Option(location.name, location.id));
        modalDropoffSelect.add(new Option(location.name, location.id));
    });
}

function buildModalHourOptions() {
    modalPickupHourInput.innerHTML = '<option value="">Hour</option>';

    for (let hour = 0; hour < 24; hour++) {
        const value = String(hour).padStart(2, '0');
        modalPickupHourInput.add(new Option(formatHourLabel(hour), value));
    }
}

function buildModalMinuteOptions() {
    modalPickupMinuteInput.innerHTML = '<option value="">Minute</option>';

    for (let minute = 0; minute < 60; minute += 5) {
        const value = String(minute).padStart(2, '0');
        modalPickupMinuteInput.add(new Option(value, value));
    }
}

function updateModalPickupAndReturnTime() {
    if (modalPickupHourInput.value !== '' && modalPickupMinuteInput.value !== '') {
        const selectedTime = `${modalPickupHourInput.value}:${modalPickupMinuteInput.value}`;
        modalPickupTimeInput.value = selectedTime;
        modalReturnTimeInput.value = selectedTime;
        modalReturnTimeDisplay.querySelector('span').textContent = formatTimeLabel(selectedTime);
    } else {
        modalPickupTimeInput.value = '';
        modalReturnTimeInput.value = '';
        modalReturnTimeDisplay.querySelector('span').textContent = 'Same as pickup time';
    }

    validateModalRentalDateTime(false);
}

function clearModalDateTimeError() {
    [modalPickupDateInput, modalPickupHourInput, modalPickupMinuteInput, modalReturnDateInput].forEach(input => {
        input.classList.remove('invalid-field');
    });

    modalPickupTimeGroup.classList.remove('invalid-field');
    modalReturnTimeDisplay.classList.remove('invalid-field');
}

function markModalDateTimeError() {
    [modalPickupDateInput, modalPickupHourInput, modalPickupMinuteInput, modalReturnDateInput].forEach(input => {
        input.classList.add('invalid-field');
    });

    modalPickupTimeGroup.classList.add('invalid-field');
    modalReturnTimeDisplay.classList.add('invalid-field');
}

function validateModalRentalDateTime(showMessage = false) {
    clearModalDateTimeError();

    if (!modalPickupDateInput.value || !modalPickupTimeInput.value || !modalReturnDateInput.value || !modalReturnTimeInput.value) {
        return false;
    }

    const pickupDateTime = new Date(`${modalPickupDateInput.value}T${modalPickupTimeInput.value}:00`);
    const returnDateTime = new Date(`${modalReturnDateInput.value}T${modalReturnTimeInput.value}:00`);
    const minimumPickupDateTime = getMinimumPickupDateTime();
    const minimumReturnDate = addDays(modalPickupDateInput.value, 1);

    if (pickupDateTime < minimumPickupDateTime) {
        markModalDateTimeError();

        if (showMessage) {
            showValidationToast('Pickup time must be at least 1 hour from now.');
        }

        return false;
    }

    if (modalReturnDateInput.value < minimumReturnDate || returnDateTime <= pickupDateTime) {
        markModalDateTimeError();

        if (showMessage) {
            showValidationToast('Invalid rental date. Minimum rental period is 1 day, and return date must be after pickup date.');
        }

        return false;
    }

    return true;
}

function openAvailabilityModal(carId, carName) {
    modalCarIdInput.value = carId;
    modalCarNameText.textContent = `Check availability for ${carName}`;
    availabilityModal.classList.add('show');
    availabilityModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    refreshPickupTimeRestrictions(modalPickupDateInput, modalPickupHourInput, modalPickupMinuteInput);
}

function closeAvailabilityModal() {
    availabilityModal.classList.remove('show');
    availabilityModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
}

if (availabilityModal) {
    buildModalHourOptions();
    buildModalMinuteOptions();

    modalPickupDateInput.setAttribute('min', today);
    modalReturnDateInput.setAttribute('min', addDays(today, 1));
    refreshPickupTimeRestrictions(modalPickupDateInput, modalPickupHourInput, modalPickupMinuteInput);

    document.querySelectorAll('.check-availability-btn').forEach(button => {
        button.addEventListener('click', () => {
            openAvailabilityModal(button.dataset.carId, button.dataset.carName);
        });
    });

    document.querySelectorAll('[data-close-availability]').forEach(button => {
        button.addEventListener('click', closeAvailabilityModal);
    });

    modalStateSelect.addEventListener('change', loadModalLocations);

    modalPickupDateInput.addEventListener('change', () => {
        if (modalPickupDateInput.value) {
            modalReturnDateInput.setAttribute('min', addDays(modalPickupDateInput.value, 1));
        } else {
            modalReturnDateInput.setAttribute('min', addDays(today, 1));
        }

        refreshPickupTimeRestrictions(modalPickupDateInput, modalPickupHourInput, modalPickupMinuteInput);
        updateModalPickupAndReturnTime();
        validateModalRentalDateTime(false);
    });

    modalPickupHourInput.addEventListener('change', () => {
        refreshPickupTimeRestrictions(modalPickupDateInput, modalPickupHourInput, modalPickupMinuteInput);
        updateModalPickupAndReturnTime();
    });
    modalPickupMinuteInput.addEventListener('change', () => {
        refreshPickupTimeRestrictions(modalPickupDateInput, modalPickupHourInput, modalPickupMinuteInput);
        updateModalPickupAndReturnTime();
    });
    modalReturnDateInput.addEventListener('change', () => validateModalRentalDateTime(false));

    modalSearchForm.addEventListener('submit', event => {
        updateModalPickupAndReturnTime();

        if (!validateModalRentalDateTime(true)) {
            event.preventDefault();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && availabilityModal.classList.contains('show')) {
            closeAvailabilityModal();
        }
    });
}



// ===== left section navigation menu =====
const sectionMenuToggle = document.getElementById('sectionMenuToggle');
const sectionSideNav = document.getElementById('sectionSideNav');
const sectionSideClose = document.getElementById('sectionSideClose');
const sectionSideOverlay = document.getElementById('sectionSideOverlay');

function openSectionMenu() {
    sectionSideNav.classList.add('show');
    sectionSideOverlay.classList.add('show');
    document.body.classList.add('modal-open');
}

function closeSectionMenu() {
    sectionSideNav.classList.remove('show');
    sectionSideOverlay.classList.remove('show');
    document.body.classList.remove('modal-open');
}

if (sectionMenuToggle && sectionSideNav && sectionSideOverlay) {
    sectionMenuToggle.addEventListener('click', openSectionMenu);
    sectionSideClose.addEventListener('click', closeSectionMenu);
    sectionSideOverlay.addEventListener('click', closeSectionMenu);



    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && sectionSideNav.classList.contains('show')) {
            closeSectionMenu();
        }
    });
}



// ===== consistent homepage section scroll position =====
function scrollToHomepageSection(targetSelector) {
    const target = document.querySelector(targetSelector);

    if (!target) {
        return;
    }

    const navbar = document.querySelector('.navbar');
    const navbarHeight = navbar ? navbar.offsetHeight : 0;
    const extraGap = 22;
    const targetTop = target.getBoundingClientRect().top + window.pageYOffset - navbarHeight - extraGap;

    window.scrollTo({
        top: targetTop,
        behavior: 'smooth'
    });
}

document.querySelectorAll('.section-side-nav a[href^="#"]').forEach(link => {
    link.addEventListener('click', event => {
        event.preventDefault();
        const targetSelector = link.getAttribute('href');
        closeSectionMenu();
        setTimeout(() => scrollToHomepageSection(targetSelector), 80);
    });
});

document.querySelectorAll('.section-nav-pills a[href^="#"]').forEach(link => {
    link.addEventListener('click', event => {
        event.preventDefault();
        scrollToHomepageSection(link.getAttribute('href'));
    });
});

    </script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const stateLinks = {
        "johor": "state.php?state=johor",
        "melaka": "state.php?state=melaka",
        "kuala lumpur": "state.php?state=kuala-lumpur"
    };

    document.querySelectorAll(".state-card, .rental-state-card, .state-item").forEach(function(card) {
        const text = card.textContent.toLowerCase();

        Object.keys(stateLinks).forEach(function(stateName) {
            if(text.includes(stateName)) {
                card.style.cursor = "pointer";
                card.addEventListener("click", function(event) {
                    if(event.target.closest("a")) return;
                    window.location.href = stateLinks[stateName];
                });

                const button = Array.from(card.querySelectorAll("a, button")).find(function(el) {
                    return el.textContent.toLowerCase().includes("view locations");
                });

                if(button) {
                    if(button.tagName.toLowerCase() === "a") {
                        button.href = stateLinks[stateName];
                    } else {
                        button.addEventListener("click", function(event) {
                            event.preventDefault();
                            event.stopPropagation();
                            window.location.href = stateLinks[stateName];
                        });
                    }
                }
            }
        });
    });
});
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".state-card").forEach(function(card) {
        const title = (card.querySelector("h3")?.textContent || "").trim().toLowerCase();

        if (title.includes("johor")) {
            card.href = "state.php?state=johor";
        } else if (title.includes("melaka")) {
            card.href = "state.php?state=melaka";
        } else if (title.includes("kuala lumpur") || title.includes("kl")) {
            card.href = "state.php?state=kuala-lumpur";
        }
    });
});
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const ratingBox = document.getElementById("commentStarRating");
    const ratingInput = document.getElementById("commentRatingValue");
    const ratingText = document.getElementById("commentRatingText");

    if (!ratingBox || !ratingInput || !ratingText) {
        return;
    }

    const labels = {
        1: "1 Star - Bad",
        2: "2 Stars - Poor",
        3: "3 Stars - Average",
        4: "4 Stars - Good",
        5: "5 Stars - Excellent"
    };

    const stars = Array.from(ratingBox.querySelectorAll("i"));

    function setRating(value) {
        value = Math.max(1, Math.min(5, parseInt(value || 5, 10)));
        ratingInput.value = value;
        ratingBox.dataset.rating = value;
        ratingText.textContent = labels[value];

        stars.forEach(star => {
            const starValue = parseInt(star.dataset.value, 10);
            star.classList.toggle("active", starValue <= value);
        });
    }

    function ratingFromPointer(event) {
        const rect = ratingBox.getBoundingClientRect();
        const x = Math.max(0, Math.min(rect.width, event.clientX - rect.left));
        return Math.ceil((x / rect.width) * 5);
    }

    let dragging = false;

    ratingBox.addEventListener("pointerdown", function (event) {
        dragging = true;
        ratingBox.setPointerCapture(event.pointerId);
        setRating(ratingFromPointer(event));
    });

    ratingBox.addEventListener("pointermove", function (event) {
        if (!dragging) {
            return;
        }

        setRating(ratingFromPointer(event));
    });

    ratingBox.addEventListener("pointerup", function () {
        dragging = false;
    });

    ratingBox.addEventListener("pointercancel", function () {
        dragging = false;
    });

    stars.forEach(star => {
        star.addEventListener("click", function () {
            setRating(star.dataset.value);
        });
    });

    setRating(ratingInput.value || 5);
});
</script>

</body>
</html>
