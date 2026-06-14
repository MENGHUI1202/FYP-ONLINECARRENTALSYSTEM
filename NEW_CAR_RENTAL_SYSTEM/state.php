    <?php
    require_once "config.php";

    function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
    }

    function tableExists($conn, $table) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
        ");
        if(!$stmt) return false;
        $stmt->bind_param("s", $table);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row["total"] ?? 0) > 0;
    }

    function columnExists($conn, $table, $column) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
        ");
        if(!$stmt) return false;
        $stmt->bind_param("ss", $table, $column);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row["total"] ?? 0) > 0;
    }

    function firstColumn($conn, $table, $columns, $fallback = null) {
        foreach($columns as $column) {
            if(columnExists($conn, $table, $column)) return $column;
        }
        return $fallback;
    }

    function fetchRows($conn, $sql) {
        $result = $conn->query($sql);
        if(!$result) return [];
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function fetchOne($conn, $sql) {
        $rows = fetchRows($conn, $sql);
        return $rows[0] ?? null;
    }

    function slugify($text) {
        $text = strtolower(trim((string)$text));
        $text = str_replace("&", "and", $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    function getNavCartCount($conn) {
        $sessionCount = 0;
        if(!empty($_SESSION["cart"]) && is_array($_SESSION["cart"])) {
            $sessionCount = count($_SESSION["cart"]);
        }

        if(empty($_SESSION["user_id"]) || !tableExists($conn, "cart_items")) {
            return $sessionCount;
        }

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM cart_items
            WHERE user_id = ?
            AND LOWER(COALESCE(status, 'active')) NOT IN ('removed','checked_out')
        ");

        if(!$stmt) return $sessionCount;

        $userId = (int)$_SESSION["user_id"];
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row["total"] ?? 0);
    }

    function formatMapEmbed($location) {
        $embed = trim((string)($location["map_embed_url"] ?? ""));
        if($embed !== "") return $embed;

        $lat = trim((string)($location["lat"] ?? ""));
        $lng = trim((string)($location["lng"] ?? ""));
        if($lat !== "" && $lng !== "") {
            return "https://maps.google.com/maps?q=" . rawurlencode($lat . "," . $lng) . "&z=15&output=embed";
        }

        $query = trim(($location["location_name"] ?? "") . " " . ($location["address"] ?? ""));
        return "https://maps.google.com/maps?q=" . rawurlencode($query) . "&z=14&output=embed";
    }

    function formatMapUrl($location) {
        $url = trim((string)($location["map_url"] ?? ""));
        if($url !== "") return $url;

        $lat = trim((string)($location["lat"] ?? ""));
        $lng = trim((string)($location["lng"] ?? ""));
        if($lat !== "" && $lng !== "") {
            return "https://www.google.com/maps/search/?api=1&query=" . rawurlencode($lat . "," . $lng);
        }

        $query = trim(($location["location_name"] ?? "") . " " . ($location["address"] ?? ""));
        return "https://www.google.com/maps/search/?api=1&query=" . rawurlencode($query);
    }

    function fallbackStateData() {
        return [
            [
                "state_id" => 1,
                "state_name" => "Johor",
                "state_slug" => "johor",
                "description" => "Choose from convenient pickup and drop-off locations across Johor.",
                "landmark" => "Johor Bahru City Centre",
                "locations" => [
                    ["location_id"=>101,"location_name"=>"JB Sentral","address"=>"Jalan Jim Quee, Bukit Chagar, 80300 Johor Bahru, Johor"],
                    ["location_id"=>102,"location_name"=>"Johor Bahru City Centre","address"=>"Johor Bahru City Centre, 80000 Johor Bahru, Johor"],
                    ["location_id"=>103,"location_name"=>"Larkin Sentral","address"=>"Larkin Sentral, Jalan Garuda, 80350 Johor Bahru, Johor"],
                    ["location_id"=>104,"location_name"=>"KSL City Mall","address"=>"33, Jalan Seladang, Taman Abad, 80250 Johor Bahru, Johor"],
                    ["location_id"=>105,"location_name"=>"Aeon Tebrau City","address"=>"1, Jalan Desa Tebrau, Taman Desa Tebrau, 81100 Johor Bahru, Johor"],
                    ["location_id"=>106,"location_name"=>"Bukit Indah","address"=>"Bukit Indah, 81200 Johor Bahru, Johor"],
                    ["location_id"=>107,"location_name"=>"Mount Austin","address"=>"Taman Mount Austin, 81100 Johor Bahru, Johor"],
                    ["location_id"=>108,"location_name"=>"Skudai","address"=>"Skudai, 81300 Johor Bahru, Johor"],
                    ["location_id"=>109,"location_name"=>"Senai International Airport","address"=>"Senai International Airport, 81250 Senai, Johor"],
                    ["location_id"=>110,"location_name"=>"Pasir Gudang","address"=>"Pasir Gudang, 81700 Johor"],
                    ["location_id"=>111,"location_name"=>"Muar Town","address"=>"Muar Town, 84000 Muar, Johor"],
                    ["location_id"=>112,"location_name"=>"Paradigm Mall Johor Bahru","address"=>"Paradigm Mall Johor Bahru, Jalan Skudai, 81200 Johor Bahru, Johor"]
                ]
            ],
            [
                "state_id" => 2,
                "state_name" => "Melaka",
                "state_slug" => "melaka",
                "description" => "Find your nearest pickup point and start your rental journey easily in Melaka.",
                "landmark" => "A Famosa Melaka",
                "locations" => [
                    ["location_id"=>201,"location_name"=>"Melaka Sentral","address"=>"Jalan Tun Razak, Plaza Melaka Sentral, 75400 Melaka"],
                    ["location_id"=>202,"location_name"=>"MMU Melaka","address"=>"Multimedia University, Jalan Ayer Keroh Lama, 75450 Bukit Beruang, Melaka"],
                    ["location_id"=>203,"location_name"=>"Ayer Keroh","address"=>"Ayer Keroh, 75450 Melaka"],
                    ["location_id"=>204,"location_name"=>"Jonker Street","address"=>"Jalan Hang Jebat, 75200 Melaka"],
                    ["location_id"=>205,"location_name"=>"Dataran Pahlawan","address"=>"Dataran Pahlawan Melaka Megamall, 75000 Melaka"],
                    ["location_id"=>206,"location_name"=>"Mahkota Parade","address"=>"Mahkota Parade, Jalan Merdeka, 75000 Melaka"],
                    ["location_id"=>207,"location_name"=>"Klebang Beach","address"=>"Pantai Klebang, 75200 Melaka"],
                    ["location_id"=>208,"location_name"=>"Batu Berendam Airport","address"=>"Melaka International Airport, Batu Berendam, 75350 Melaka"],
                    ["location_id"=>209,"location_name"=>"Alor Gajah","address"=>"Alor Gajah, 78000 Melaka"],
                    ["location_id"=>210,"location_name"=>"Jasin Town","address"=>"Jasin, 77000 Melaka"],
                    ["location_id"=>211,"location_name"=>"Masjid Tanah","address"=>"Masjid Tanah, 78300 Melaka"],
                    ["location_id"=>212,"location_name"=>"MITC Melaka","address"=>"Melaka International Trade Centre, Ayer Keroh, 75450 Melaka"]
                ]
            ],
            [
                "state_id" => 3,
                "state_name" => "Kuala Lumpur",
                "state_slug" => "kuala-lumpur",
                "description" => "Premium and convenient car rental locations around Kuala Lumpur.",
                "landmark" => "KLCC / KL Sentral",
                "locations" => [
                    ["location_id"=>301,"location_name"=>"KL Sentral","address"=>"KL Sentral, 50470 Kuala Lumpur"],
                    ["location_id"=>302,"location_name"=>"Bukit Bintang","address"=>"Bukit Bintang, 55100 Kuala Lumpur"],
                    ["location_id"=>303,"location_name"=>"TBS Kuala Lumpur","address"=>"Terminal Bersepadu Selatan, Bandar Tasik Selatan, Kuala Lumpur"],
                    ["location_id"=>304,"location_name"=>"KLCC","address"=>"Kuala Lumpur City Centre, 50088 Kuala Lumpur"],
                    ["location_id"=>305,"location_name"=>"Cheras","address"=>"Cheras, Kuala Lumpur"],
                    ["location_id"=>306,"location_name"=>"Kepong","address"=>"Kepong, Kuala Lumpur"],
                    ["location_id"=>307,"location_name"=>"Setapak","address"=>"Setapak, Kuala Lumpur"],
                    ["location_id"=>308,"location_name"=>"Wangsa Maju","address"=>"Wangsa Maju, Kuala Lumpur"],
                    ["location_id"=>309,"location_name"=>"Mid Valley","address"=>"Mid Valley City, Lingkaran Syed Putra, 59200 Kuala Lumpur"],
                    ["location_id"=>310,"location_name"=>"Mont Kiara","address"=>"Mont Kiara, 50480 Kuala Lumpur"],
                    ["location_id"=>311,"location_name"=>"Sunway Velocity","address"=>"Sunway Velocity, Cheras, 55100 Kuala Lumpur"],
                    ["location_id"=>312,"location_name"=>"Pavilion Kuala Lumpur","address"=>"Pavilion Kuala Lumpur, 168 Jalan Bukit Bintang, 55100 Kuala Lumpur"]
                ]
            ]
        ];
    }

    $user = null;
    if(!empty($_SESSION["user_id"]) && tableExists($conn, "users")) {
        $userIdCol = firstColumn($conn, "users", ["user_id", "id"], "user_id");
        $stmt = $conn->prepare("SELECT * FROM users WHERE $userIdCol = ? LIMIT 1");
        if($stmt) {
            $stmt->bind_param("i", $_SESSION["user_id"]);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }

    $navCartCount = getNavCartCount($conn);

    $fallbackStates = fallbackStateData();
    $stateRows = [];

    if(tableExists($conn, "rental_states")) {
        $stateIdCol = firstColumn($conn, "rental_states", ["state_id", "id"], "state_id");
        $stateNameCol = firstColumn($conn, "rental_states", ["state_name", "name"], "state_name");
        $stateSlugCol = firstColumn($conn, "rental_states", ["state_slug", "slug"], null);
        $stateStatusCol = firstColumn($conn, "rental_states", ["status"], null);

        $select = [
            "$stateIdCol AS state_id",
            "$stateNameCol AS state_name",
            ($stateSlugCol ? "$stateSlugCol" : "''") . " AS state_slug"
        ];

        $where = $stateStatusCol ? "WHERE LOWER(COALESCE($stateStatusCol,'active')) IN ('active','available') OR $stateStatusCol = 1" : "";
        $stateRows = fetchRows($conn, "SELECT " . implode(", ", $select) . " FROM rental_states $where ORDER BY $stateNameCol ASC");
    }

    if(!$stateRows) {
        foreach($fallbackStates as $fallback) {
            $stateRows[] = [
                "state_id" => $fallback["state_id"],
                "state_name" => $fallback["state_name"],
                "state_slug" => $fallback["state_slug"]
            ];
        }
    }

    foreach($stateRows as &$stateRow) {
        if(empty($stateRow["state_slug"])) {
            $stateRow["state_slug"] = slugify($stateRow["state_name"]);
        }
    }
    unset($stateRow);

    $requestedSlug = strtolower(trim($_GET["state"] ?? "johor"));
    $requestedSlug = str_replace("_", "-", $requestedSlug);
    if($requestedSlug === "kl" || $requestedSlug === "kuala-lumpur" || $requestedSlug === "kuala lumpur") {
        $requestedSlug = "kuala-lumpur";
    }

    $currentState = null;
    foreach($stateRows as $stateRow) {
        if(slugify($stateRow["state_slug"]) === $requestedSlug || slugify($stateRow["state_name"]) === $requestedSlug) {
            $currentState = $stateRow;
            break;
        }
    }

    if(!$currentState) {
        $currentState = $stateRows[0] ?? ["state_id"=>1,"state_name"=>"Johor","state_slug"=>"johor"];
    }

    $currentSlug = slugify($currentState["state_slug"] ?: $currentState["state_name"]);
    $currentStateId = (int)$currentState["state_id"];
    $currentStateName = $currentState["state_name"];

    $fallbackCurrent = null;
    foreach($fallbackStates as $fallback) {
        if($fallback["state_slug"] === $currentSlug || $fallback["state_name"] === $currentStateName) {
            $fallbackCurrent = $fallback;
            break;
        }
    }
    if(!$fallbackCurrent) $fallbackCurrent = $fallbackStates[0];

    $locations = [];

    if(tableExists($conn, "rental_locations")) {
        $locationIdCol = firstColumn($conn, "rental_locations", ["location_id", "id"], "location_id");
        $locationNameCol = firstColumn($conn, "rental_locations", ["location_name", "name"], "location_name");
        $locationStateCol = firstColumn($conn, "rental_locations", ["state_id"], "state_id");
        $addressCol = firstColumn($conn, "rental_locations", ["address", "location_address"], null);
        $mapUrlCol = firstColumn($conn, "rental_locations", ["map_url", "google_map_url", "google_maps_url"], null);
        $mapEmbedCol = firstColumn($conn, "rental_locations", ["map_embed_url", "embed_url", "google_map_embed"], null);
        $latCol = firstColumn($conn, "rental_locations", ["lat", "latitude"], null);
        $lngCol = firstColumn($conn, "rental_locations", ["lng", "longitude"], null);
        $statusCol = firstColumn($conn, "rental_locations", ["status"], null);

        $select = [
            "$locationIdCol AS location_id",
            "$locationNameCol AS location_name",
            "$locationStateCol AS state_id",
            ($addressCol ? "$addressCol" : "''") . " AS address",
            ($mapUrlCol ? "$mapUrlCol" : "''") . " AS map_url",
            ($mapEmbedCol ? "$mapEmbedCol" : "''") . " AS map_embed_url",
            ($latCol ? "$latCol" : "''") . " AS lat",
            ($lngCol ? "$lngCol" : "''") . " AS lng"
        ];

        $where = "$locationStateCol = $currentStateId";
        if($statusCol) {
            $where .= " AND (LOWER(COALESCE($statusCol,'active')) IN ('active','available') OR $statusCol = 1)";
        }

        $locations = fetchRows($conn, "SELECT " . implode(", ", $select) . " FROM rental_locations WHERE $where ORDER BY $locationNameCol ASC");
    }

    if(!$locations) {
        foreach($fallbackCurrent["locations"] as $location) {
            $location["state_id"] = $currentStateId;
            $location["map_url"] = "";
            $location["map_embed_url"] = "";
            $location["lat"] = "";
            $location["lng"] = "";
            $locations[] = $location;
        }
    }

    $stateDescription = $fallbackCurrent["description"];
    $stateLandmark = $fallbackCurrent["landmark"];
    $locationCount = count($locations);

    $popularCars = [];
    if(tableExists($conn, "cars")) {
        $carIdCol = firstColumn($conn, "cars", ["car_id", "id"], "car_id");
        $carNameCol = firstColumn($conn, "cars", ["car_name", "name"], "car_name");
        $priceCol = firstColumn($conn, "cars", ["price_per_day", "daily_rate", "price"], null);
        $imageCol = firstColumn($conn, "cars", ["image", "main_image", "car_image"], null);
        $brandCol = firstColumn($conn, "cars", ["brand", "brand_name"], null);
        $brandIdCol = firstColumn($conn, "cars", ["brand_id"], null);
        $categoryCol = firstColumn($conn, "cars", ["type", "category", "category_name"], null);
        $categoryIdCol = firstColumn($conn, "cars", ["category_id"], null);
        $statusCol = firstColumn($conn, "cars", ["status", "availability"], null);
        $popularCol = firstColumn($conn, "cars", ["is_popular"], null);

        $select = [
            "c.$carIdCol AS car_id",
            "c.$carNameCol AS car_name",
            ($priceCol ? "c.$priceCol" : "0") . " AS price_per_day",
            ($imageCol ? "c.$imageCol" : "''") . " AS image"
        ];

        $join = "";

        if($brandIdCol && tableExists($conn, "brands")) {
            $brandPk = firstColumn($conn, "brands", ["brand_id", "id"], "brand_id");
            $brandNameCol = firstColumn($conn, "brands", ["brand_name", "name"], "brand_name");
            $select[] = "COALESCE(b.$brandNameCol, '-') AS brand";
            $join .= " LEFT JOIN brands b ON b.$brandPk = c.$brandIdCol ";
        } elseif($brandCol) {
            $select[] = "c.$brandCol AS brand";
        } else {
            $select[] = "'-' AS brand";
        }

        if($categoryIdCol && tableExists($conn, "categories")) {
            $categoryPk = firstColumn($conn, "categories", ["category_id", "id"], "category_id");
            $categoryNameCol = firstColumn($conn, "categories", ["category_name", "name"], "category_name");
            $select[] = "COALESCE(cat.$categoryNameCol, 'Others') AS category_name";
            $join .= " LEFT JOIN categories cat ON cat.$categoryPk = c.$categoryIdCol ";
        } elseif($categoryIdCol && tableExists($conn, "vehicle_categories")) {
            $categoryPk = firstColumn($conn, "vehicle_categories", ["category_id", "id"], "category_id");
            $categoryNameCol = firstColumn($conn, "vehicle_categories", ["category_name", "name"], "category_name");
            $select[] = "COALESCE(cat.$categoryNameCol, 'Others') AS category_name";
            $join .= " LEFT JOIN vehicle_categories cat ON cat.$categoryPk = c.$categoryIdCol ";
        } elseif($categoryCol) {
            $select[] = "c.$categoryCol AS category_name";
        } else {
            $select[] = "'Others' AS category_name";
        }

        $where = "1=1";
        if($statusCol) {
            $where .= " AND (LOWER(c.$statusCol) IN ('active','available') OR c.$statusCol = 1)";
        }
        if($popularCol) {
            $where .= " AND (c.$popularCol = 1 OR c.$popularCol = '1')";
        }

        $popularCars = fetchRows($conn, "SELECT " . implode(", ", $select) . " FROM cars c $join WHERE $where LIMIT 3");
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title><?= e($currentStateName) ?> Locations | KH Car Rental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    :root{
        --sky50:#f5fbff;
        --sky100:#eaf7ff;
        --sky200:#d8f2ff;
        --sky500:#28a8ea;
        --sky600:#1284c6;
        --dark:#10233d;
        --muted:#6e8297;
        --orange:#ff7a1a;
        --orange2:#f15f12;
        --border:#d8ecfb;
        --shadow:0 24px 70px rgba(39,137,199,.13);
        --soft:0 12px 35px rgba(39,137,199,.10);
    }
    *{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{
        font-family:"Segoe UI",Tahoma,sans-serif;
        color:var(--dark);
        background:
            radial-gradient(circle at 8% 0%,rgba(210,239,255,.42),transparent 30%),
            radial-gradient(circle at 95% 8%,rgba(234,247,255,.45),transparent 34%),
            linear-gradient(180deg,#ffffff 0%,#f8fcff 48%,#ffffff 100%);
    }
    a{text-decoration:none;color:inherit}
    button,input,select{font-family:inherit}

    /* ===== Navbar ===== */
    .navbar{
        position:sticky;
        top:0;
        z-index:100;
        height:64px;
        background:linear-gradient(135deg,rgba(224,247,255,.94),rgba(255,255,255,.96),rgba(240,250,255,.94));
        border-bottom:1px solid rgba(142,207,244,.42);
        backdrop-filter:blur(18px);
    }
    .nav-inner{
        width:min(1200px,calc(100% - 40px));
        height:64px;
        margin:auto;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:18px;
    }
    .menu-btn{display:none}
    .brand{
        display:flex;
        align-items:center;
        gap:13px;
        font-size:15px;
        font-weight:950;
        white-space:nowrap;
        margin-right:28px;
    }
    .brand-icon{
        width:42px;
        height:42px;
        display:grid;
        place-items:center;
        border-radius:15px;
        color:var(--sky600);
        background:linear-gradient(135deg,#d8f2ff,#fff);
        border:1px solid rgba(142,207,244,.46);
        box-shadow:0 14px 28px rgba(40,168,234,.13);
    }
    .nav-links{
        flex:1;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:18px;
        list-style:none;
    }
    .nav-links a{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:8px 7px;
        border-radius:999px;
        font-size:12px;
        font-weight:950;
        color:#2b4969;
        letter-spacing:.2px;
    }
    .nav-links a i{color:#2b4969;font-size:13px}
    .nav-links a.active,
    .nav-links a.active i,
    .nav-links a:hover,
    .nav-links a:hover i{color:var(--sky600)}
    .avatar-wrap{position:relative;margin-left:0}
    .avatar-btn{
        border:0;
        background:transparent;
        display:flex;
        align-items:center;
        gap:10px;
        cursor:pointer;
        font-weight:950;
        color:var(--dark);
    }
    .avatar-circle{
        width:40px;
        height:40px;
        border-radius:50%;
        display:grid;
        place-items:center;
        overflow:hidden;
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),#0d3f82);
        border:3px solid #fff;
        box-shadow:0 14px 28px rgba(40,168,234,.18);
    }
    .avatar-circle img{width:100%;height:100%;object-fit:cover}
    .dropdown{
        position:absolute;
        right:0;
        top:62px;
        width:260px;
        display:none;
        padding:12px;
        border-radius:24px;
        background:rgba(255,255,255,.96);
        border:1px solid var(--border);
        box-shadow:0 24px 70px rgba(39,137,199,.18);
    }
    .dropdown.show{display:block}
    .dropdown a{
        min-height:54px;
        display:flex;
        align-items:center;
        gap:12px;
        padding:14px 16px;
        border-radius:17px;
        font-weight:900;
        color:#24415f;
    }
    .dropdown a:hover{background:var(--sky100);color:var(--sky600)}
    .login-btn{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:14px 20px;
        border-radius:999px;
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
        font-weight:950;
    }

    /* ===== Footer ===== */
    .footer{
        background:#12304f;
        color:#ffffff;
        padding:82px 34px 28px;
    }
    .footer-inner{
        width:min(1200px,calc(100% - 40px));
        margin:0 auto 42px;
        display:grid;
        grid-template-columns:1.35fr 1fr 1.2fr;
        gap:62px;
    }
    .footer h3{
        font-size:22px;
        font-weight:950;
        color:#ffffff;
        margin-bottom:18px;
        letter-spacing:-.3px;
    }
    .footer p,
    .footer a{
        color:rgba(218,235,248,.76);
        font-size:15.5px;
        line-height:1.85;
        font-weight:650;
    }
    .footer-hover-link,
    .footer .contact-link{
        width:fit-content;
        display:inline-flex;
        align-items:center;
        gap:10px;
        padding:3px 0;
        border-radius:12px;
        transition:color .22s ease,transform .22s ease,background .22s ease,padding .22s ease;
    }
    .footer-hover-link i{display:none}
    .footer .contact-link i{
        width:18px;
        color:var(--sky500);
        transition:.22s ease;
    }
    .footer-hover-link:hover,
    .footer .contact-link:hover{
        color:#ffffff;
        transform:translateX(6px);
        background:rgba(255,255,255,.055);
        padding-left:8px;
        padding-right:10px;
    }
    .footer .contact-link:hover i{
        color:#7fd0ff;
        transform:scale(1.08);
    }
    .footer .start-btn{
        margin-top:22px;
        width:fit-content;
        min-width:176px;
        min-height:52px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:10px;
        padding:0 24px;
        border-radius:17px;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
        color:#ffffff;
        font-size:15.5px;
        font-weight:950;
        line-height:1;
        letter-spacing:.15px;
        box-shadow:0 18px 36px rgba(40,168,234,.25);
        border:1px solid rgba(113,210,255,.18);
    }
    .footer .start-btn i{color:#fff}
    .footer-bottom{
        width:min(1200px,calc(100% - 40px));
        margin:0 auto;
        padding-top:22px;
        border-top:1px solid rgba(255,255,255,.14);
        text-align:center;
        color:rgba(218,235,248,.86);
        font-size:14px;
    }
    .back-top{
        position:fixed;
        right:28px;
        bottom:28px;
        width:54px;
        height:54px;
        border-radius:50%;
        border:0;
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
        box-shadow:0 20px 40px rgba(40,168,234,.3);
        cursor:pointer;
    }

    /* ===== Responsive ===== */
    @media(max-width:1220px){
        .hero-card{grid-template-columns:1fr}
        .catalogue-control-layout{grid-template-columns:1fr}
        .car-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media(max-width:999px){
        .nav-links{display:none}
        .category-tabs{grid-template-columns:repeat(3,minmax(0,1fr))}
        .tools-form{grid-template-columns:1fr 1fr}
        .footer-inner{grid-template-columns:1fr;gap:34px}
    }
    @media(max-width:760px){
        .hero,.main{padding:0 14px}
        .hero-card{padding:20px}
        .category-header-row{
            flex-direction:column;
            align-items:stretch;
        }
        .category-header-row .category-all-tab{
            width:100%;
            flex:auto;
        }
        .category-tabs,
        .tools-form,
        .advanced-filter,
        .filter-actions-row,
        .car-grid,
        .modal-grid,
        .actions,
        .trip-form{
            grid-template-columns:1fr;
        }
        .result-head{display:grid}
        .hero h1{font-size:42px}
    }

    /* ===== FINAL FIX: footer START Browse slim long button ===== */
    footer.footer .footer-inner > div:first-child > a.start-btn,
    footer.footer a.start-btn,
    .footer a.start-btn,
    a.start-btn{
        display:inline-flex!important;
        align-items:center!important;
        justify-content:center!important;
        gap:10px!important;

        width:auto!important;
        min-width:210px!important;
        height:44px!important;
        min-height:44px!important;
        padding:0 28px!important;

        border-radius:16px!important;
        background:linear-gradient(135deg,#28a8ea,#1284c6)!important;
        color:#ffffff!important;
        border:1px solid rgba(113,210,255,.22)!important;
        box-shadow:0 16px 32px rgba(40,168,234,.24)!important;

        font-size:15.5px!important;
        font-weight:950!important;
        line-height:1!important;
        letter-spacing:.15px!important;
        text-decoration:none!important;
        white-space:nowrap!important;
        overflow:hidden!important;
    }

    footer.footer .footer-inner > div:first-child > a.start-btn i,
    footer.footer a.start-btn i,
    .footer a.start-btn i,
    a.start-btn i{
        color:#ffffff!important;
        font-size:15.5px!important;
        line-height:1!important;
    }

    footer.footer .footer-inner > div:first-child > a.start-btn:hover,
    footer.footer a.start-btn:hover,
    .footer a.start-btn:hover,
    a.start-btn:hover{
        transform:translateY(-2px)!important;
        color:#ffffff!important;
        box-shadow:0 20px 38px rgba(40,168,234,.32)!important;
    }

    /* ===== NAVBAR / MAP / PRICE FIX (requested) ===== */
    .nav-inner{
        width:min(1320px,calc(100% - 24px))!important;
        gap:12px!important;
    }
    .brand{
        margin-right:10px!important;
        flex-shrink:0!important;
    }
    .nav-links{
        gap:12px!important;
        flex-wrap:nowrap!important;
        min-width:0!important;
    }
    .nav-links a{
        white-space:nowrap!important;
        font-size:11.5px!important;
        padding:8px 5px!important;
    }
    .avatar-wrap{
        flex-shrink:0!important;
    }
    .login-btn{
        white-space:nowrap!important;
        flex-shrink:0!important;
        min-width:max-content!important;
        padding:13px 18px!important;
    }

    .nav-cart-link{position:relative;overflow:visible!important}
    .cart-count-badge{
        position:absolute;
        top:-3px;
        right:-10px;
        min-width:17px;
        height:17px;
        padding:0 5px;
        border-radius:999px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        background:linear-gradient(135deg,#ff5a52,#e11d2e);
        color:#fff;
        border:2px solid #fff;
        box-shadow:0 8px 18px rgba(225,29,46,.28);
        font-size:9px;
        font-weight:950;
        line-height:1;
        z-index:5;
    }

    @media(max-width:1180px){
        .nav-links{display:none!important;}
        .menu-btn{display:grid!important;}
    }

    .nav-cart-link{position:relative;overflow:visible!important}
    .cart-count-badge{
        position:absolute;
        top:-3px;
        right:-10px;
        min-width:17px;
        height:17px;
        padding:0 5px;
        border-radius:999px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        background:linear-gradient(135deg,#ff5a52,#e11d2e);
        color:#fff;
        border:2px solid #fff;
        box-shadow:0 8px 18px rgba(225,29,46,.28);
        font-size:9px;
        font-weight:950;
        line-height:1;
        z-index:5;
    }

    .page{
        width:min(1320px,100%);
        margin:14px auto 54px;
        padding:0 22px;
    }
    .state-hero{
        position:relative;
        min-height:300px;
        padding:34px 36px;
        border-radius:34px;
        overflow:hidden;
        display:grid;
        grid-template-columns:1.18fr .82fr;
        gap:28px;
        align-items:center;
        background:
            radial-gradient(circle at 88% 18%,rgba(40,168,234,.24),transparent 32%),
            radial-gradient(circle at 100% 100%,rgba(16,35,61,.16),transparent 34%),
            linear-gradient(135deg,#ffffff 0%,#eef9ff 100%);
        border:1px solid rgba(184,228,255,.95);
        box-shadow:var(--shadow);
    }
    .state-hero::after{
        content:"";
        position:absolute;
        right:-82px;
        bottom:-140px;
        width:420px;
        height:420px;
        border-radius:50%;
        border:42px solid rgba(40,168,234,.10);
        box-shadow:inset 0 0 0 42px rgba(16,35,61,.055);
    }
    .pill{
        display:inline-flex;
        align-items:center;
        gap:8px;
        width:fit-content;
        padding:7px 12px;
        border-radius:999px;
        background:rgba(40,168,234,.12);
        color:var(--sky600);
        border:1px solid rgba(40,168,234,.22);
        font-size:11px;
        font-weight:950;
        letter-spacing:.8px;
        text-transform:uppercase;
        margin-bottom:12px;
    }
    .state-hero h1{
        position:relative;
        z-index:2;
        max-width:760px;
        font-size:clamp(42px,4.8vw,70px);
        line-height:.96;
        letter-spacing:-2.5px;
        font-weight:950;
        margin-bottom:14px;
    }
    .state-hero p{
        position:relative;
        z-index:2;
        max-width:720px;
        color:var(--muted);
        font-size:15.5px;
        line-height:1.6;
        font-weight:750;
    }
    .hero-badges{
        position:relative;
        z-index:2;
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        margin-top:20px;
    }
    .hero-badge{
        min-height:42px;
        display:inline-flex;
        align-items:center;
        gap:10px;
        padding:0 14px;
        border-radius:15px;
        background:rgba(255,255,255,.86);
        border:1px solid var(--border);
        box-shadow:var(--soft);
        font-size:12.5px;
        font-weight:950;
    }
    .hero-badge i{color:var(--sky600)}
    .landmark-card{
        position:relative;
        z-index:2;
        min-height:220px;
        border-radius:30px;
        padding:24px;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        color:#fff;
        background:
            linear-gradient(135deg,rgba(16,35,61,.92),rgba(18,132,198,.74)),
            radial-gradient(circle at 20% 12%,rgba(255,255,255,.22),transparent 24%);
        box-shadow:0 28px 70px rgba(16,35,61,.16);
        overflow:hidden;
    }
    .landmark-card::after{
        content:"";
        position:absolute;
        right:-60px;
        bottom:-80px;
        width:230px;
        height:230px;
        border-radius:50%;
        border:28px solid rgba(255,255,255,.12);
    }
    .landmark-card h2{
        position:relative;
        z-index:2;
        font-size:30px;
        line-height:1.05;
        font-weight:950;
    }
    .landmark-card p{
        position:relative;
        z-index:2;
        color:rgba(255,255,255,.82);
        font-size:14px;
    }
    .state-tabs{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
        margin:16px 0;
    }
    .state-tab{
        min-height:44px;
        padding:0 18px;
        border-radius:999px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        border:1px solid var(--border);
        background:#fff;
        color:#24415f;
        font-size:13px;
        font-weight:950;
        box-shadow:var(--soft);
    }
    .state-tab.active,
    .state-tab:hover{
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
    }
    .search-panel{
        margin:16px 0;
        padding:22px;
        border-radius:30px;
        background:#fff;
        border:1px solid var(--border);
        box-shadow:var(--shadow);
    }
    .section-head{
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:18px;
        margin-bottom:18px;
    }
    .section-head h2{
        font-size:30px;
        line-height:1;
        font-weight:950;
        letter-spacing:-.8px;
    }
    .section-head p{
        margin-top:6px;
        color:var(--muted);
        font-size:13.5px;
        font-weight:750;
    }
    .search-form{
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:12px;
    }
    .field label{
        display:block;
        color:#61758d;
        font-size:10px;
        font-weight:950;
        letter-spacing:.6px;
        text-transform:uppercase;
        margin-bottom:6px;
    }
    .input{
        width:100%;
        height:45px;
        border:2px solid #e2f2ff;
        background:#fff;
        color:var(--dark);
        border-radius:14px;
        padding:8px 11px;
        outline:none;
        font-size:12.5px;
        font-weight:850;
    }
    .input:focus{
        border-color:var(--sky500);
        box-shadow:0 0 0 .2rem rgba(40,168,234,.13);
    }
    .fixed-time-display{
        width:100%;
        height:45px;
        border:2px solid #e2f2ff;
        background:var(--sky50);
        color:#607a92;
        border-radius:14px;
        padding:8px 11px;
        display:flex;
        align-items:center;
        gap:9px;
        font-size:12.5px;
        font-weight:850;
    }
    .fixed-time-display i{color:var(--sky600)}
    .search-btn{
        grid-column:span 4;
        min-height:48px;
        border:0;
        border-radius:16px;
        color:#fff;
        background:linear-gradient(135deg,#ff9a4a,#ff7a1a 48%,#f15f12);
        font-size:14px;
        font-weight:950;
        cursor:pointer;
        box-shadow:0 18px 34px rgba(255,122,26,.22);
    }
    .inline-error{
        display:none;
        grid-column:1/-1;
        color:#d63031;
        background:#fff5f5;
        border:1px solid rgba(255,77,79,.2);
        border-radius:14px;
        padding:10px 12px;
        font-size:12.5px;
        font-weight:850;
    }
    .inline-error.show{display:block}
    .locations-panel{
        margin-top:18px;
    }
    .location-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:16px;
    }
    .location-card{
        border-radius:28px;
        overflow:hidden;
        background:
            radial-gradient(circle at 100% 0%,rgba(40,168,234,.08),transparent 28%),
            linear-gradient(145deg,rgba(255,255,255,.98),rgba(246,252,255,.92));
        border:1px solid rgba(184,228,255,.98);
        box-shadow:0 18px 46px rgba(29,109,164,.12);
        transition:.25s ease;
    }
    .location-card:hover{
        transform:translateY(-6px);
        box-shadow:0 28px 70px rgba(29,109,164,.18);
    }
    .map-wrap{
        height:190px;
        background:var(--sky100);
        position:relative;
    }
    .map-wrap iframe{
        width:100%;
        height:100%;
        border:0;
        display:block;
    }
    .map-badge{
        position:absolute;
        top:12px;
        left:12px;
        min-height:32px;
        padding:0 11px;
        border-radius:999px;
        display:inline-flex;
        align-items:center;
        gap:7px;
        color:var(--sky600);
        background:rgba(255,255,255,.94);
        border:1px solid var(--border);
        font-size:11px;
        font-weight:950;
        box-shadow:var(--soft);
    }
    .location-body{
        padding:18px;
    }
    .location-number{
        width:38px;
        height:38px;
        display:grid;
        place-items:center;
        border-radius:14px;
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
        font-size:13px;
        font-weight:950;
        margin-bottom:12px;
    }
    .location-body h3{
        font-size:22px;
        line-height:1.15;
        font-weight:950;
        margin-bottom:8px;
    }
    .address{
        min-height:64px;
        color:var(--muted);
        font-size:13px;
        line-height:1.55;
        font-weight:750;
    }
    .available-for{
        margin:12px 0;
        display:flex;
        align-items:center;
        gap:8px;
        color:#087747;
        font-size:12px;
        font-weight:950;
    }
    .location-actions{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:9px;
        margin-top:14px;
    }
    .btn{
        min-height:42px;
        border-radius:14px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        border:1px solid var(--border);
        background:#fff;
        color:var(--sky600);
        font-size:12px;
        font-weight:950;
        cursor:pointer;
    }
    .btn-blue{
        border:0;
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
    }
    .btn:hover{
        transform:translateY(-2px);
        box-shadow:var(--soft);
    }
    .info-strip{
        margin:18px 0;
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:14px;
    }
    .info-card{
        padding:18px;
        border-radius:24px;
        background:#fff;
        border:1px solid var(--border);
        box-shadow:var(--soft);
    }
    .info-card i{
        width:42px;
        height:42px;
        display:grid;
        place-items:center;
        border-radius:16px;
        color:var(--sky600);
        background:var(--sky100);
        margin-bottom:12px;
    }
    .info-card h3{
        font-size:18px;
        font-weight:950;
        margin-bottom:6px;
    }
    .info-card p{
        color:var(--muted);
        font-size:13px;
        line-height:1.55;
        font-weight:750;
    }
    .popular-panel{
        margin-top:18px;
        padding:22px;
        border-radius:30px;
        background:#fff;
        border:1px solid var(--border);
        box-shadow:var(--shadow);
    }
    .popular-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:14px;
    }
    .popular-card{
        padding:16px;
        border-radius:24px;
        border:1px solid var(--border);
        background:linear-gradient(135deg,#fff,#f7fcff);
    }
    .popular-card h3{
        font-size:19px;
        font-weight:950;
        margin-bottom:6px;
    }
    .popular-card p{
        color:var(--muted);
        font-size:13px;
        font-weight:750;
    }
    .popular-price{
        margin-top:12px;
        color:var(--orange2);
        font-size:18px;
        font-weight:950;
    }
    .back-top{
        position:fixed;
        right:28px;
        bottom:28px;
        width:54px;
        height:54px;
        border-radius:50%;
        border:0;
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
        box-shadow:0 20px 40px rgba(40,168,234,.3);
        cursor:pointer;
    }
    @media(max-width:1180px){
        .state-hero{grid-template-columns:1fr}
        .search-form{grid-template-columns:1fr 1fr}
        .search-btn{grid-column:span 2}
        .location-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media(max-width:760px){
        .page{padding:0 14px}
        .state-hero{padding:28px 22px}
        .state-hero h1{font-size:40px}
        .section-head{display:grid}
        .search-form,
        .location-grid,
        .info-strip,
        .popular-grid{grid-template-columns:1fr}
        .search-btn{grid-column:span 1}
        .location-actions{grid-template-columns:1fr}
    }

    /* ===== State Location Modal / Card No-Map Fix ===== */
    .search-panel{
        display:none!important;
    }

    .location-card{
        cursor:pointer;
    }

    .location-card-top{
        position:relative;
        min-height:126px;
        padding:18px;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        overflow:hidden;
        background:
            radial-gradient(circle at 86% 16%,rgba(40,168,234,.22),transparent 32%),
            linear-gradient(135deg,#eaf7ff,#ffffff);
        border-bottom:1px solid rgba(184,228,255,.9);
    }

    .location-card-top::after{
        content:"";
        position:absolute;
        right:-44px;
        bottom:-76px;
        width:190px;
        height:190px;
        border-radius:50%;
        border:26px solid rgba(40,168,234,.12);
    }

    .location-card-top .location-number{
        margin:0;
        position:relative;
        z-index:2;
    }

    .location-icon-wrap{
        position:relative;
        z-index:2;
        width:58px;
        height:58px;
        border-radius:22px;
        display:grid;
        place-items:center;
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
        box-shadow:0 18px 34px rgba(40,168,234,.22);
        font-size:24px;
    }

    .location-card:hover .location-icon-wrap{
        transform:translateY(-3px) scale(1.03);
    }

    .location-modal{
        position:fixed;
        inset:0;
        z-index:999;
        display:none;
        align-items:center;
        justify-content:center;
        padding:24px;
    }

    .location-modal.show{
        display:flex;
    }

    .location-modal-backdrop{
        position:absolute;
        inset:0;
        background:rgba(16,35,61,.58);
        backdrop-filter:blur(12px);
    }

    .location-modal-card{
        position:relative;
        z-index:2;
        width:min(1180px,100%);
        max-height:92vh;
        overflow:auto;
        border-radius:34px;
        background:
            radial-gradient(circle at 100% 0%,rgba(40,168,234,.12),transparent 28%),
            linear-gradient(135deg,#ffffff,#f7fcff);
        border:1px solid rgba(184,228,255,.96);
        box-shadow:0 40px 110px rgba(16,35,61,.35);
    }

    .modal-close-btn{
        position:absolute;
        top:18px;
        right:18px;
        z-index:4;
        width:48px;
        height:48px;
        border-radius:18px;
        border:0;
        cursor:pointer;
        color:var(--sky600);
        background:rgba(234,247,255,.96);
        font-size:20px;
        box-shadow:0 14px 28px rgba(40,168,234,.14);
    }

    .modal-map-box{
        height:340px;
        background:var(--sky100);
        border-bottom:1px solid var(--border);
    }

    .modal-map-box iframe{
        width:100%;
        height:100%;
        border:0;
        display:block;
    }

    .modal-content-box{
        padding:24px;
    }

    .modal-location-head{
        padding:20px;
        border-radius:26px;
        border:1px solid var(--border);
        background:#fff;
        box-shadow:var(--soft);
        margin-bottom:18px;
    }

    .modal-location-head h2{
        font-size:34px;
        line-height:1.1;
        font-weight:950;
        letter-spacing:-.8px;
        margin-bottom:8px;
    }

    .modal-location-head p{
        color:var(--muted);
        font-size:14px;
        font-weight:750;
        line-height:1.55;
        margin-bottom:14px;
    }

    .modal-map-link{
        min-height:42px;
        width:fit-content;
        padding:0 16px;
        border-radius:14px;
        display:inline-flex;
        align-items:center;
        gap:8px;
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
        font-size:12px;
        font-weight:950;
    }

    .modal-search-area{
        padding:20px;
        border-radius:26px;
        border:1px solid var(--border);
        background:#fff;
        box-shadow:var(--soft);
    }

    .modal-section-head{
        margin-bottom:15px;
    }

    body.modal-open{
        overflow:hidden;
    }

    @media(max-width:760px){
        .location-modal{
            padding:12px;
        }

        .modal-map-box{
            height:260px;
        }

        .modal-content-box{
            padding:16px;
        }

        .modal-location-head h2{
            font-size:26px;
        }
    }

    
/* ===== State Pickup 1 Hour Direct Selection Rule ===== */
.time-combo{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}
.time-combo .input{
    width:100%;
}

</style>
    </head>
    <body>
    <header class="navbar">
        <div class="nav-inner">
            <a href="homepage.php" class="brand">
                <span class="brand-icon"><i class="fa-solid fa-car-side"></i></span>
                <span>KH Car Rental</span>
            </a>

            <ul class="nav-links">
                <li><a href="homepage.php"><i class="fa-solid fa-house"></i> HOME</a></li>
                <li><a href="catalogue.php"><i class="fa-solid fa-car"></i> CATALOGUE</a></li>
                <li><a href="find_car_smart.php"><i class="fa-solid fa-wand-magic-sparkles"></i> FIND CAR SMART</a></li>
                <li><a href="compare_car.php"><i class="fa-solid fa-code-compare"></i> COMPARE CAR</a></li>
                <li><a href="aboutus.php"><i class="fa-solid fa-circle-info"></i> ABOUT US</a></li>
                <li><a href="contactus.php"><i class="fa-solid fa-envelope"></i> CONTACT US</a></li>
                <li><a href="cart.php" class="nav-cart-link"><i class="fa-solid fa-cart-shopping"></i> CART<?php if($navCartCount > 0): ?><span class="cart-count-badge"><?= e($navCartCount > 99 ? "99+" : $navCartCount) ?></span><?php endif; ?></a></li>
            </ul>

            <div class="avatar-wrap">
                <?php if($user): ?>
                    <button class="avatar-btn" type="button" id="avatarBtn">
                        <span class="avatar-circle">
                            <?php if(!empty($user["profile_picture"])): ?>
                                <img src="<?= e($user["profile_picture"]) ?>" alt="Profile">
                            <?php else: ?>
                                <?= e(strtoupper(substr($user["name"] ?? "U",0,1))) ?>
                            <?php endif; ?>
                        </span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="dropdown" id="profileDropdown">
                            <a href="my_profile.php"><i class="fa-solid fa-user"></i> Manage My Profile</a>
                            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                        </div>
                <?php else: ?>
                    <a href="login.php" class="login-btn"><i class="fa-solid fa-user"></i> Login / Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="page">
        <section class="state-hero">
            <div>
                <span class="pill"><i class="fa-solid fa-location-dot"></i> State Location</span>
                <h1>KH Car Rental in <?= e($currentStateName) ?></h1>
                <p><?= e($stateDescription) ?> Select a pickup point first, then the map and search form will open in a popup.</p>

                <div class="hero-badges">
                    <span class="hero-badge"><i class="fa-solid fa-map-location-dot"></i> <?= e($locationCount) ?> Pickup Locations</span>
                    <span class="hero-badge"><i class="fa-solid fa-car-side"></i> Pickup & Drop-off</span>
                    <span class="hero-badge"><i class="fa-solid fa-magnifying-glass"></i> Search Available Cars</span>
                </div>
            </div>

            <div class="landmark-card">
                <div>
                    <span class="pill" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.18);">
                        <i class="fa-solid fa-location-crosshairs"></i> Current Area
                    </span>
                    <h2><?= e($stateLandmark) ?></h2>
                </div>
                <p>Choose your nearest KH Car Rental pickup location in <?= e($currentStateName) ?>.</p>
            </div>
        </section>

        <nav class="state-tabs">
            <?php foreach($stateRows as $stateTab): ?>
                <?php $tabSlug = slugify($stateTab["state_slug"] ?: $stateTab["state_name"]); ?>
                <a class="state-tab <?= $tabSlug === $currentSlug ? 'active' : '' ?>" href="state.php?state=<?= e($tabSlug) ?>">
                    <i class="fa-solid fa-location-dot"></i> <?= e($stateTab["state_name"]) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        
        <div class="location-modal" id="locationModal" aria-hidden="true">
            <div class="location-modal-backdrop" id="locationModalBackdrop"></div>

            <div class="location-modal-card" role="dialog" aria-modal="true" aria-labelledby="modalLocationTitle">
                <button class="modal-close-btn" type="button" id="locationModalClose">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="modal-map-box">
                    <iframe id="modalMapFrame" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src=""></iframe>
                </div>

                <div class="modal-content-box">
                    <div class="modal-location-head">
                        <span class="pill"><i class="fa-solid fa-location-crosshairs"></i> Selected Location</span>
                        <h2 id="modalLocationTitle">Location Name</h2>
                        <p id="modalLocationAddress">Location address will appear here.</p>

                        <a class="modal-map-link" id="modalMapLink" href="#" target="_blank" rel="noopener">
                            <i class="fa-solid fa-up-right-from-square"></i> Open in Google Maps
                        </a>
                    </div>

                    <div class="modal-search-area">
                        <div class="section-head modal-section-head">
                            <div>
                                <span class="pill"><i class="fa-solid fa-calendar-check"></i> Search Cars Here</span>
                                <h2>Search Available Cars</h2>
                                <p>Pickup location is selected from the card. Return time follows pickup time.</p>
                            </div>
                        </div>

                        <form class="search-form" method="GET" action="available_cars.php" id="stateSearchForm">
                            <input type="hidden" name="state" value="<?= e($currentStateId) ?>">

                            <div class="field">
                                <label>Pickup State</label>
                                <input class="input" type="text" value="<?= e($currentStateName) ?>" readonly>
                            </div>

                            <div class="field">
                                <label>Pickup Location</label>
                                <select class="input" name="pickup_location" id="pickupLocation" required>
                                    <option value="">Select Pickup Location</option>
                                    <?php foreach($locations as $location): ?>
                                        <option value="<?= e($location["location_id"]) ?>"><?= e($location["location_name"]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label>Drop-off Location</label>
                                <select class="input" name="dropoff_location" id="dropoffLocation" required>
                                    <option value="">Select Drop-off Location</option>
                                    <?php foreach($locations as $location): ?>
                                        <option value="<?= e($location["location_id"]) ?>"><?= e($location["location_name"]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label>Pickup Date</label>
                                <input class="input" type="date" name="pickup_date" id="pickupDate" required>
                            </div>

                            <div class="field">
                                <label>Pickup Time</label>
                                <div class="time-combo" id="pickupTimeGroup">
                                    <select class="input time-part" id="pickupHour" required>
                                        <option value="">Hour (AM/PM)</option>
                                    </select>
                                    <select class="input time-part" id="pickupMinute" required>
                                        <option value="">Minute</option>
                                    </select>
                                </div>
                                <input type="hidden" name="pickup_time" id="pickupTime" required>
                            </div>

                            <div class="field">
                                <label>Return Date</label>
                                <input class="input" type="date" name="return_date" id="returnDate" required>
                            </div>

                            <div class="field">
                                <label>Return Time</label>
                                <div class="fixed-time-display" id="returnTimeDisplay"><i class="fa-solid fa-lock"></i><span>Same as pickup time</span></div>
                                <input type="hidden" name="return_time" id="returnTime" required>
                            </div>

                            <div class="inline-error" id="tripError"></div>

                            <button class="search-btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search Available Cars</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <section class="info-strip">
            <div class="info-card">
                <i class="fa-solid fa-map-location-dot"></i>
                <h3>Choose Location</h3>
                <p>Pick your nearest branch in <?= e($currentStateName) ?> for easier handover.</p>
            </div>
            <div class="info-card">
                <i class="fa-solid fa-calendar-days"></i>
                <h3>Select Rental Time</h3>
                <p>Choose pickup and return date before checking available cars.</p>
            </div>
            <div class="info-card">
                <i class="fa-solid fa-car-side"></i>
                <h3>Book Available Cars</h3>
                <p>Search results will open in available_cars.php based on your location and time.</p>
            </div>
        </section>

        <section class="locations-panel">
            <div class="section-head">
                <div>
                    <span class="pill"><i class="fa-solid fa-location-crosshairs"></i> Pickup Points</span>
                    <h2><?= e($currentStateName) ?> Locations</h2>
                    <p>Each location can be used for pickup and drop-off. Click a location card to view its map and search cars.</p>
                </div>
            </div>

            <div class="location-grid">
                <?php foreach($locations as $index => $location): ?>
                    <?php
                        $mapEmbed = formatMapEmbed($location);
                        $mapUrl = formatMapUrl($location);
                        $locationAddress = $location["address"] ?: ($location["location_name"] . ", " . $currentStateName);
                    ?>
                    <article class="location-card location-select-card"
                        data-location-id="<?= e($location["location_id"]) ?>"
                        data-location-name="<?= e($location["location_name"]) ?>"
                        data-location-address="<?= e($locationAddress) ?>"
                        data-map-embed="<?= e($mapEmbed) ?>"
                        data-map-url="<?= e($mapUrl) ?>">
                        <div class="location-card-top">
                            <div class="location-number"><?= e(str_pad((string)($index + 1), 2, "0", STR_PAD_LEFT)) ?></div>
                            <div class="location-icon-wrap">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                        </div>

                        <div class="location-body">
                            <h3><?= e($location["location_name"]) ?></h3>
                            <p class="address"><?= e($locationAddress) ?></p>
                            <div class="available-for"><i class="fa-solid fa-circle-check"></i> Available for Pickup & Drop-off</div>

                            <div class="location-actions">
                                <button class="btn view-map-btn" type="button">
                                    <i class="fa-solid fa-map-location-dot"></i> View Map
                                </button>
                                <button class="btn btn-blue search-here" type="button">
                                    <i class="fa-solid fa-magnifying-glass"></i> Search Here
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if($popularCars): ?>
        <section class="popular-panel">
            <div class="section-head">
                <div>
                    <span class="pill"><i class="fa-solid fa-star"></i> Popular Cars</span>
                    <h2>Popular Cars in <?= e($currentStateName) ?></h2>
                    <p>This section is only a preview. Use Search Available Cars to check real availability.</p>
                </div>
            </div>

            <div class="popular-grid">
                <?php foreach($popularCars as $car): ?>
                    <article class="popular-card">
                        <h3><?= e($car["car_name"]) ?></h3>
                        <p><?= e($car["brand"]) ?> • <?= e($car["category_name"]) ?></p>
                        <div class="popular-price">RM <?= e(number_format((float)$car["price_per_day"], 2)) ?> / day</div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="footer-inner">
            <div>
                <h3>KH Car Rental</h3>
                <p>KH Car Rental provides reliable, affordable and convenient car rental services across Johor, Melaka and Kuala Lumpur. Customers can search available cars, compare vehicles and manage bookings easily through our online system.</p>
                <a class="start-btn" href="catalogue.php"><i class="fa-solid fa-car-side"></i> START Browse</a>
            </div>

            <div>
                <h3>Quick Links</h3>
                <p><a class="footer-hover-link" href="homepage.php">HOME</a></p>
                <p><a class="footer-hover-link" href="catalogue.php">CATALOGUE</a></p>
                <p><a class="footer-hover-link" href="find_car_smart.php">FIND CAR SMART</a></p>
                <p><a class="footer-hover-link" href="compare_car.php">COMPARE CAR</a></p>
                <p><a class="footer-hover-link" href="aboutus.php">ABOUT US</a></p>
                <p><a class="footer-hover-link" href="contactus.php">CONTACT US</a></p>
                <p><a class="footer-hover-link" href="cart.php">CART</a></p>
            </div>

            <div>
                <h3>Contact</h3>
                <p><a class="contact-link" href="tel:+60123456789"><i class="fa-solid fa-phone"></i> +60 12-345 6789</a></p>
                <p><a class="contact-link" href="mailto:hoomenghui@student.mmu.edu.my"><i class="fa-solid fa-envelope"></i> hoomenghui@student.mmu.edu.my</a></p>
                <p><a class="contact-link" href="mailto:pangkanghorng@student.mmu.edu.my"><i class="fa-solid fa-envelope"></i> pangkanghorng@student.mmu.edu.my</a></p>
                <p><a class="contact-link" href="mailto:ngmengxin@student.mmu.edu.my"><i class="fa-solid fa-envelope"></i> ngmengxin@student.mmu.edu.my</a></p>
                <p><a class="contact-link" href="https://www.google.com/maps/search/?api=1&query=Multimedia+University+Melaka" target="_blank"><i class="fa-solid fa-location-dot"></i> Multimedia University, Melaka</a></p>
            </div>
        </div>

        <div class="footer-bottom">
            © 2026 KH Car Rental. All rights reserved.
        </div>
    </footer>

    <button class="back-top" type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <script>
    const avatarBtn = document.getElementById("avatarBtn");
    const profileDropdown = document.getElementById("profileDropdown");

    if(avatarBtn && profileDropdown) {
        avatarBtn.addEventListener("click", function(event) {
            event.stopPropagation();
            profileDropdown.classList.toggle("show");
        });

        document.addEventListener("click", function() {
            profileDropdown.classList.remove("show");
        });
    }

    const pickupDate = document.getElementById("pickupDate");
    const pickupTime = document.getElementById("pickupTime");
    const pickupHour = document.getElementById("pickupHour");
    const pickupMinute = document.getElementById("pickupMinute");
    const returnDate = document.getElementById("returnDate");
    const returnTime = document.getElementById("returnTime");
    const returnTimeDisplay = document.getElementById("returnTimeDisplay");
    const tripError = document.getElementById("tripError");
    const stateSearchForm = document.getElementById("stateSearchForm");
    const pickupLocation = document.getElementById("pickupLocation");
    const dropoffLocation = document.getElementById("dropoffLocation");

    function pad(num) {
        return String(num).padStart(2, "0");
    }

    function dateInputValue(dateObj) {
        return dateObj.getFullYear() + "-" + pad(dateObj.getMonth() + 1) + "-" + pad(dateObj.getDate());
    }

    function timeInputValue(dateObj) {
        const roundedMinute = Math.ceil(dateObj.getMinutes() / 5) * 5;
        if(roundedMinute >= 60) {
            dateObj.setHours(dateObj.getHours() + 1);
            dateObj.setMinutes(0);
        } else {
            dateObj.setMinutes(roundedMinute);
        }
        return pad(dateObj.getHours()) + ":" + pad(dateObj.getMinutes());
    }

    function buildPickupTimeOptions() {
        if(!pickupHour || !pickupMinute) return;

        const selectedHour = pickupHour.value;
        const selectedMinute = pickupMinute.value;

        pickupHour.innerHTML = '<option value="">Hour</option>';
        for(let hour = 0; hour < 24; hour++) {
            const value = pad(hour);
            let displayHour = hour % 12;
            if(displayHour === 0) displayHour = 12;
            const suffix = hour >= 12 ? "PM" : "AM";

            const option = document.createElement("option");
            option.value = value;
            option.textContent = pad(displayHour) + " " + suffix;
            if(value === selectedHour) option.selected = true;
            pickupHour.appendChild(option);
        }

        pickupMinute.innerHTML = '<option value="">Minute</option>';
        for(let minute = 0; minute < 60; minute += 5) {
            const value = pad(minute);
            const option = document.createElement("option");
            option.value = value;
            option.textContent = value;
            if(value === selectedMinute) option.selected = true;
            pickupMinute.appendChild(option);
        }
    }

    function syncPickupTimeValue() {
        if(!pickupTime || !pickupHour || !pickupMinute) return;

        if(pickupHour.value && pickupMinute.value) {
            pickupTime.value = pickupHour.value + ":" + pickupMinute.value;
        } else {
            pickupTime.value = "";
        }
    }

    function refreshMinimumPickupOptions() {
        if(!pickupDate || !pickupHour || !pickupMinute || !pickupTime) return;

        const minimumPickup = new Date();
        minimumPickup.setHours(minimumPickup.getHours() + 1);
        minimumPickup.setSeconds(0, 0);

        const minimumDate = dateInputValue(minimumPickup);
        const minimumHour = pad(minimumPickup.getHours());
        const minimumMinute = pad(minimumPickup.getMinutes());

        pickupDate.min = minimumDate;

        Array.from(pickupHour.options).forEach(option => {
            if(option.value === "") {
                option.disabled = false;
                return;
            }

            option.disabled = pickupDate.value === minimumDate && option.value < minimumHour;
        });

        Array.from(pickupMinute.options).forEach(option => {
            if(option.value === "") {
                option.disabled = false;
                return;
            }

            option.disabled = pickupDate.value === minimumDate &&
                pickupHour.value === minimumHour &&
                option.value < minimumMinute;
        });

        if(pickupHour.selectedOptions[0] && pickupHour.selectedOptions[0].disabled) {
            pickupHour.value = "";
            pickupMinute.value = "";
        }

        if(pickupMinute.selectedOptions[0] && pickupMinute.selectedOptions[0].disabled) {
            pickupMinute.value = "";
        }

        syncPickupTimeValue();
    }

    function addDays(dateValue, days) {
        const dateObj = new Date(dateValue + "T00:00:00");
        dateObj.setDate(dateObj.getDate() + days);
        return dateInputValue(dateObj);
    }

    function showError(message) {
        if(!tripError) return;
        tripError.textContent = message;
        tripError.classList.add("show");
    }

    function clearError() {
        if(!tripError) return;
        tripError.textContent = "";
        tripError.classList.remove("show");
    }

    function updateReturnTime() {
        if(!pickupTime || !returnTime || !returnTimeDisplay) return;

        returnTime.value = pickupTime.value;

        if(pickupTime.value) {
            const [hourRaw, minute] = pickupTime.value.split(":");
            let hour = parseInt(hourRaw, 10);
            const suffix = hour >= 12 ? "PM" : "AM";
            hour = hour % 12;
            if(hour === 0) hour = 12;
            returnTimeDisplay.innerHTML = '<i class="fa-solid fa-lock"></i><span>' + pad(hour) + ':' + minute + ' ' + suffix + ' (Fixed)</span>';
        } else {
            returnTimeDisplay.innerHTML = '<i class="fa-solid fa-lock"></i><span>Same as pickup time</span>';
        }
    }

    function setupMinDateTime() {
        if(!pickupDate || !pickupTime || !returnDate) return;

        const nowPlusOneHour = new Date();
        nowPlusOneHour.setHours(nowPlusOneHour.getHours() + 1);

        const minPickupDate = dateInputValue(nowPlusOneHour);
        pickupDate.min = minPickupDate;

        refreshMinimumPickupOptions();

        if(pickupDate.value) {
            returnDate.min = addDays(pickupDate.value, 1);
            if(returnDate.value && returnDate.value < returnDate.min) {
                returnDate.value = returnDate.min;
            }
        } else {
            returnDate.min = minPickupDate;
        }

        updateReturnTime();
    }

    if(pickupDate && pickupTime && returnDate) {
        buildPickupTimeOptions();
        setupMinDateTime();

        pickupDate.addEventListener("change", setupMinDateTime);

        if(pickupHour) {
            pickupHour.addEventListener("change", function() {
                refreshMinimumPickupOptions();
                updateReturnTime();
            });
        }

        if(pickupMinute) {
            pickupMinute.addEventListener("change", function() {
                refreshMinimumPickupOptions();
                updateReturnTime();
            });
        }

        pickupTime.addEventListener("change", function() {
            setupMinDateTime();
            updateReturnTime();
        });

        returnDate.addEventListener("change", setupMinDateTime);
    }

    if(stateSearchForm) {
        stateSearchForm.addEventListener("submit", function(event) {
            clearError();
            refreshMinimumPickupOptions();
            updateReturnTime();

            if(!pickupDate.value || !pickupTime.value || !returnDate.value) {
                event.preventDefault();
                showError("Please complete pickup date, pickup time and return date.");
                return;
            }

            const pickupDateTime = new Date(pickupDate.value + "T" + pickupTime.value + ":00");
            const returnDateTime = new Date(returnDate.value + "T" + pickupTime.value + ":00");

            const minimumPickup = new Date();
            minimumPickup.setHours(minimumPickup.getHours() + 1);

            if(pickupDateTime.getTime() < minimumPickup.getTime()) {
                event.preventDefault();
                showError("Pickup date and time must be at least 1 hour from now.");
                return;
            }

            if(returnDateTime.getTime() - pickupDateTime.getTime() < 24 * 60 * 60 * 1000) {
                event.preventDefault();
                showError("Rental period must be at least 1 day.");
                return;
            }

            returnTime.value = pickupTime.value;
        });
    }

    const locationModal = document.getElementById("locationModal");
    const locationModalClose = document.getElementById("locationModalClose");
    const locationModalBackdrop = document.getElementById("locationModalBackdrop");
    const modalMapFrame = document.getElementById("modalMapFrame");
    const modalMapLink = document.getElementById("modalMapLink");
    const modalLocationTitle = document.getElementById("modalLocationTitle");
    const modalLocationAddress = document.getElementById("modalLocationAddress");

    function openLocationModal(card) {
        if(!card || !locationModal) return;

        const locationId = card.dataset.locationId || "";
        const locationName = card.dataset.locationName || "Selected Location";
        const locationAddress = card.dataset.locationAddress || "";
        const mapEmbed = card.dataset.mapEmbed || "";
        const mapUrl = card.dataset.mapUrl || "#";

        if(pickupLocation) pickupLocation.value = locationId;
        if(dropoffLocation) dropoffLocation.value = locationId;

        if(modalLocationTitle) modalLocationTitle.textContent = locationName;
        if(modalLocationAddress) modalLocationAddress.textContent = locationAddress;
        if(modalMapFrame) modalMapFrame.src = mapEmbed;
        if(modalMapLink) modalMapLink.href = mapUrl;

        locationModal.classList.add("show");
        locationModal.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");

        setupMinDateTime();
    }

    function closeLocationModal() {
        if(!locationModal) return;

        locationModal.classList.remove("show");
        locationModal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");

        if(modalMapFrame) modalMapFrame.src = "";
    }

    document.querySelectorAll(".location-select-card").forEach(card => {
        card.addEventListener("click", function(event) {
            if(event.target.closest("a")) return;
            openLocationModal(this);
        });
    });

    document.querySelectorAll(".view-map-btn, .search-here").forEach(button => {
        button.addEventListener("click", function(event) {
            event.preventDefault();
            event.stopPropagation();
            const card = this.closest(".location-select-card");
            openLocationModal(card);
        });
    });

    if(locationModalClose) {
        locationModalClose.addEventListener("click", closeLocationModal);
    }

    if(locationModalBackdrop) {
        locationModalBackdrop.addEventListener("click", closeLocationModal);
    }

    document.addEventListener("keydown", function(event) {
        if(event.key === "Escape") {
            closeLocationModal();
        }
    });
    </script>
    </body>
    </html>
