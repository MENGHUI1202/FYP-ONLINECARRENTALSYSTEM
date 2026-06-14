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
        $stmt->bind_param("ss", $table, $column);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row["total"] ?? 0) > 0;
    }

    function firstColumn($conn, $table, $columns, $fallback = null) {
        foreach ($columns as $column) {
            if (columnExists($conn, $table, $column)) return $column;
        }
        return $fallback;
    }


    function getNavCartCount($conn) {
        $sessionCount = 0;
        if (!empty($_SESSION["cart"]) && is_array($_SESSION["cart"])) {
            $sessionCount = count($_SESSION["cart"]);
        }

        if (empty($_SESSION["user_id"]) || !tableExists($conn, "cart_items")) {
            return $sessionCount;
        }

        $stmt = $conn->prepare("\n        SELECT COUNT(*) AS total\n        FROM cart_items\n        WHERE user_id = ?\n        AND LOWER(COALESCE(status, 'active')) NOT IN ('removed','checked_out')\n    ");

        if (!$stmt) {
            return $sessionCount;
        }

        $userId = (int)$_SESSION["user_id"];
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row["total"] ?? 0);
    }

    function fetchRows($conn, $sql) {
        $result = $conn->query($sql);
        if (!$result) return [];
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    function getCarImages($conn, $carId, $fallbackImage = "") {
        $images = [];

        if (tableExists($conn, "car_images")) {
            $carImageCarCol = firstColumn($conn, "car_images", ["car_id"], "car_id");
            $carImageUrlCol = firstColumn($conn, "car_images", ["image_url", "image", "image_path"], "image_url");
            $carImageSortCol = firstColumn($conn, "car_images", ["sort_order"], null);

            $orderBy = $carImageSortCol ? "ORDER BY $carImageSortCol ASC" : "ORDER BY image_id ASC";
            $stmt = $conn->prepare("SELECT $carImageUrlCol AS image_url FROM car_images WHERE $carImageCarCol = ? $orderBy LIMIT 5");
            $stmt->bind_param("i", $carId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if (!empty($row["image_url"])) {
                    $images[] = $row["image_url"];
                }
            }

            $stmt->close();
        }

        if (empty($images) && !empty($fallbackImage)) {
            $images[] = $fallbackImage;
        }

        return $images;
    }


    function resolveCarImageSrc($imagePath, $carName = "Car Image") {
        $imagePath = trim((string)$imagePath);

        if ($imagePath !== "" && preg_match('/^https?:\/\//i', $imagePath)) {
            return $imagePath;
        }

        if ($imagePath !== "") {
            $localPath = __DIR__ . "/" . ltrim($imagePath, "/");
            if (is_file($localPath)) {
                return $imagePath;
            }
        }

        $safeName = htmlspecialchars((string)$carName, ENT_QUOTES, "UTF-8");
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="520" viewBox="0 0 900 520">'
            . '<defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#eaf7ff"/><stop offset="1" stop-color="#f8fcff"/></linearGradient></defs>'
            . '<rect width="900" height="520" fill="url(#g)"/>'
            . '<rect x="58" y="64" width="784" height="392" rx="34" fill="#ffffff" opacity="0.72" stroke="#b8e4ff"/>'
            . '<path d="M198 318h475c32 0 58-24 58-54v-20c0-14-10-27-24-31l-96-28c-31-45-68-68-112-68H356c-54 0-94 24-128 70l-70 24c-17 6-29 22-29 40v13c0 30 26 54 69 54Z" fill="#1fa2df" opacity="0.92"/>'
            . '<circle cx="271" cy="323" r="45" fill="#10233d"/><circle cx="271" cy="323" r="20" fill="#d8f2ff"/>'
            . '<circle cx="624" cy="323" r="45" fill="#10233d"/><circle cx="624" cy="323" r="20" fill="#d8f2ff"/>'
            . '<path d="M336 188h159c36 0 67 18 93 53H280c19-35 37-53 56-53Z" fill="#d8f2ff" opacity="0.9"/>'
            . '<text x="450" y="92" text-anchor="middle" font-family="Segoe UI, Arial" font-size="28" font-weight="800" fill="#10233d">' . $safeName . '</text>'
            . '<text x="450" y="430" text-anchor="middle" font-family="Segoe UI, Arial" font-size="18" font-weight="700" fill="#6e8297">Upload real vehicle photos to replace this placeholder</text>'
            . '</svg>';

        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }

    $user = null;
    if (!empty($_SESSION["user_id"]) && tableExists($conn, "users")) {
        $userIdCol = firstColumn($conn, "users", ["user_id", "id"], "user_id");
        $stmt = $conn->prepare("SELECT * FROM users WHERE $userIdCol = ? LIMIT 1");
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    $navCartCount = getNavCartCount($conn);

    $fixedCategories = ["Sedan", "SUV", "MPV", "Hatchback", "Pickup", "Luxury", "Sport", "Coupe", "EV"];
    $fixedBrands = ["Mazda", "Toyota", "Honda", "Perodua", "Proton", "Nissan", "Mercedes", "BMW", "Lexus", "Volkswagen"];

    $states = [];
    $locations = [];
    $cars = [];
    $categories = $fixedCategories;
    $brands = $fixedBrands;

    if (tableExists($conn, "rental_states")) {
        $stateIdCol = firstColumn($conn, "rental_states", ["state_id", "id"], "state_id");
        $stateNameCol = firstColumn($conn, "rental_states", ["state_name", "name"], "state_name");
        $states = fetchRows($conn, "SELECT $stateIdCol AS state_id, $stateNameCol AS state_name FROM rental_states ORDER BY $stateNameCol ASC");
    }

    if (!$states) {
        $states = [
            ["state_id" => 1, "state_name" => "Johor"],
            ["state_id" => 2, "state_name" => "Melaka"],
            ["state_id" => 3, "state_name" => "Kuala Lumpur"]
        ];
    }

    if (tableExists($conn, "rental_locations")) {
        $locationIdCol = firstColumn($conn, "rental_locations", ["location_id", "id"], "location_id");
        $locationNameCol = firstColumn($conn, "rental_locations", ["location_name", "name"], "location_name");
        $locationStateCol = firstColumn($conn, "rental_locations", ["state_id"], "state_id");
        $locations = fetchRows($conn, "SELECT $locationIdCol AS location_id, $locationNameCol AS location_name, $locationStateCol AS state_id FROM rental_locations ORDER BY $locationStateCol ASC, $locationNameCol ASC");
    }

    if (!$locations) {
        $locations = [
            ["location_id" => 1, "location_name" => "JB Sentral", "state_id" => 1],
            ["location_id" => 2, "location_name" => "Johor Bahru City Centre", "state_id" => 1],
            ["location_id" => 3, "location_name" => "Larkin Sentral", "state_id" => 1],
            ["location_id" => 4, "location_name" => "Melaka Sentral", "state_id" => 2],
            ["location_id" => 5, "location_name" => "MMU Melaka", "state_id" => 2],
            ["location_id" => 6, "location_name" => "Ayer Keroh", "state_id" => 2],
            ["location_id" => 7, "location_name" => "KL Sentral", "state_id" => 3],
            ["location_id" => 8, "location_name" => "Bukit Bintang", "state_id" => 3],
            ["location_id" => 9, "location_name" => "TBS Kuala Lumpur", "state_id" => 3]
        ];
    }

    if (tableExists($conn, "cars")) {
        $carIdCol = firstColumn($conn, "cars", ["car_id", "id"], "car_id");
        $carNameCol = firstColumn($conn, "cars", ["car_name", "name"], "car_name");
        $priceCol = firstColumn($conn, "cars", ["price_per_day", "daily_rate", "price"], null);
        $imageCol = firstColumn($conn, "cars", ["image", "main_image", "car_image"], null);
        $brandCol = firstColumn($conn, "cars", ["brand", "brand_name"], null);
        $brandIdCol = firstColumn($conn, "cars", ["brand_id"], null);
        $categoryCol = firstColumn($conn, "cars", ["type", "category", "category_name"], null);
        $categoryIdCol = firstColumn($conn, "cars", ["category_id"], null);
        $yearCol = firstColumn($conn, "cars", ["year", "car_year"], null);
        $seatsCol = firstColumn($conn, "cars", ["seats"], null);
        $transmissionCol = firstColumn($conn, "cars", ["transmission"], null);
        $fuelCol = firstColumn($conn, "cars", ["fuel_type"], null);
        $engineCol = firstColumn($conn, "cars", ["engine", "engine_capacity"], null);
        $horsepowerCol = firstColumn($conn, "cars", ["horsepower", "hp"], null);
        $drivetrainCol = firstColumn($conn, "cars", ["drivetrain"], null);
        $tagCol = firstColumn($conn, "cars", ["car_tag", "tag", "badge"], null);
        $statusCol = firstColumn($conn, "cars", ["status", "availability"], null);
        $descCol = null;

        $select = [
            "c.$carIdCol AS car_id",
            "c.$carNameCol AS car_name",
            ($priceCol ? "c.$priceCol" : "0") . " AS price_per_day",
            ($imageCol ? "c.$imageCol" : "''") . " AS image",
            ($yearCol ? "c.$yearCol" : "''") . " AS car_year",
            ($seatsCol ? "c.$seatsCol" : "5") . " AS seats",
            ($transmissionCol ? "c.$transmissionCol" : "'Automatic'") . " AS transmission",
            ($fuelCol ? "c.$fuelCol" : "'Petrol'") . " AS fuel_type",
            ($engineCol ? "c.$engineCol" : "'-'") . " AS engine",
            ($horsepowerCol ? "c.$horsepowerCol" : "0") . " AS horsepower",
            ($drivetrainCol ? "c.$drivetrainCol" : "'FWD'") . " AS drivetrain",
            ($tagCol ? "c.$tagCol" : "''") . " AS car_tag",
            "'' AS description"
        ];

        $join = "";

        if ($brandIdCol && tableExists($conn, "brands")) {
            $brandPk = firstColumn($conn, "brands", ["brand_id", "id"], "id");
            $brandNameCol = firstColumn($conn, "brands", ["brand_name", "name"], "brand_name");
            $select[] = "COALESCE(b.$brandNameCol, '-') AS brand";
            $join .= " LEFT JOIN brands b ON b.$brandPk = c.$brandIdCol ";
        } elseif ($brandIdCol && tableExists($conn, "car_brands")) {
            $brandPk = firstColumn($conn, "car_brands", ["brand_id", "id"], "brand_id");
            $brandNameCol = firstColumn($conn, "car_brands", ["brand_name", "name"], "brand_name");
            $select[] = "COALESCE(b.$brandNameCol, '-') AS brand";
            $join .= " LEFT JOIN car_brands b ON b.$brandPk = c.$brandIdCol ";
        } elseif ($brandCol) {
            $select[] = "c.$brandCol AS brand";
        } else {
            $select[] = "'-' AS brand";
        }

        if ($categoryIdCol && tableExists($conn, "categories")) {
            $categoryPk = firstColumn($conn, "categories", ["category_id", "id"], "category_id");
            $categoryNameCol = firstColumn($conn, "categories", ["category_name", "name"], "category_name");
            $select[] = "COALESCE(cat.$categoryNameCol, 'Others') AS category_name";
            $join .= " LEFT JOIN categories cat ON cat.$categoryPk = c.$categoryIdCol ";
        } elseif ($categoryIdCol && tableExists($conn, "vehicle_categories")) {
            $categoryPk = firstColumn($conn, "vehicle_categories", ["category_id", "id"], "category_id");
            $categoryNameCol = firstColumn($conn, "vehicle_categories", ["category_name", "name"], "category_name");
            $select[] = "COALESCE(cat.$categoryNameCol, 'Others') AS category_name";
            $join .= " LEFT JOIN vehicle_categories cat ON cat.$categoryPk = c.$categoryIdCol ";
        } elseif ($categoryCol) {
            $select[] = "c.$categoryCol AS category_name";
        } else {
            $select[] = "'Others' AS category_name";
        }

        $where = "1=1";
        if ($statusCol) {
            $where .= " AND (LOWER(c.$statusCol) IN ('active','available') OR c.$statusCol = 1)";
        }

        $cars = fetchRows($conn, "SELECT " . implode(", ", $select) . " FROM cars c $join WHERE $where");

        $carColorMap = [];
        if (tableExists($conn, "car_units") && columnExists($conn, "car_units", "color")) {
            $colorRows = fetchRows($conn, "
                SELECT car_id, MIN(NULLIF(TRIM(color), '')) AS car_color
                FROM car_units
                GROUP BY car_id
            ");
            foreach ($colorRows as $colorRow) {
                if (!empty($colorRow["car_color"])) {
                    $carColorMap[(int)$colorRow["car_id"]] = $colorRow["car_color"];
                }
            }
        }

        foreach ($cars as &$carColorItem) {
            $carColorItem["car_color"] = $carColorMap[(int)($carColorItem["car_id"] ?? 0)] ?? "Not specified";
        }
        unset($carColorItem);

        foreach ($cars as $car) {
            if (!empty($car["category_name"]) && !in_array($car["category_name"], $categories, true)) $categories[] = $car["category_name"];
            if (!empty($car["brand"]) && !in_array($car["brand"], $brands, true)) $brands[] = $car["brand"];
        }
    }

    $selectedCategory = trim($_GET["category"] ?? "All");
    $selectedBrand = trim($_GET["brand"] ?? "All");
    $keyword = trim($_GET["keyword"] ?? "");
    $minPrice = trim($_GET["min_price"] ?? "");
    $maxPrice = trim($_GET["max_price"] ?? "");
    $sort = trim($_GET["sort"] ?? "default");
    $seats = trim($_GET["seats"] ?? "All");
    $transmission = trim($_GET["transmission"] ?? "All");
    $fuel = trim($_GET["fuel"] ?? "All");
    $selectedStateFilter = trim($_GET["car_state"] ?? "All");


    $carStateMap = [];
    if (tableExists($conn, "car_units")) {
        $unitCarColForMap = firstColumn($conn, "car_units", ["car_id"], "car_id");
        $unitStateColForMap = firstColumn($conn, "car_units", ["state_id"], null);
        $unitStatusColForMap = firstColumn($conn, "car_units", ["current_status", "status"], null);

        if ($unitStateColForMap) {
            $whereUnitMap = $unitStatusColForMap ? "WHERE LOWER(COALESCE($unitStatusColForMap, 'available')) NOT IN ('maintenance','inactive')" : "";
            $stateRows = fetchRows($conn, "
                SELECT $unitCarColForMap AS car_id, GROUP_CONCAT(DISTINCT $unitStateColForMap) AS state_ids
                FROM car_units
                $whereUnitMap
                GROUP BY $unitCarColForMap
            ");

            foreach ($stateRows as $stateRow) {
                $carStateMap[(int)$stateRow["car_id"]] = array_filter(array_map("intval", explode(",", (string)$stateRow["state_ids"])));
            }
        }
    }

    $filteredCars = array_values(array_filter($cars, function($car) use ($selectedCategory, $selectedBrand, $keyword, $minPrice, $maxPrice, $seats, $transmission, $fuel, $selectedStateFilter, $carStateMap) {
        $name = strtolower($car["car_name"] ?? "");
        $brand = strtolower($car["brand"] ?? "");
        $category = strtolower($car["category_name"] ?? "");
        $price = (float)($car["price_per_day"] ?? 0);

        if ($selectedCategory !== "" && $selectedCategory !== "All" && strtolower($selectedCategory) !== $category) return false;
        if ($selectedBrand !== "" && $selectedBrand !== "All" && strtolower($selectedBrand) !== $brand) return false;
        if ($selectedStateFilter !== "" && $selectedStateFilter !== "All") {
            $carStates = $carStateMap[(int)($car["car_id"] ?? 0)] ?? [];
            if (!in_array((int)$selectedStateFilter, $carStates, true)) return false;
        }

        if ($keyword !== "") {
            $key = strtolower($keyword);
            if (!str_contains($name, $key) && !str_contains($brand, $key) && !str_contains($category, $key)) return false;
        }

        if ($minPrice !== "" && $price < (float)$minPrice) return false;
        if ($maxPrice !== "" && $price > (float)$maxPrice) return false;
        if ($seats !== "All" && (int)$car["seats"] < (int)$seats) return false;
        if ($transmission !== "All" && strtolower($car["transmission"]) !== strtolower($transmission)) return false;
        if ($fuel !== "All" && strtolower($car["fuel_type"]) !== strtolower($fuel)) return false;

        return true;
    }));

    usort($filteredCars, function($a, $b) use ($sort) {
        if ($sort === "price_low") return (float)$a["price_per_day"] <=> (float)$b["price_per_day"];
        if ($sort === "price_high") return (float)$b["price_per_day"] <=> (float)$a["price_per_day"];
        if ($sort === "seats") return (int)$b["seats"] <=> (int)$a["seats"];
        if ($sort === "horsepower") return (int)$b["horsepower"] <=> (int)$a["horsepower"];
        if ($sort === "newest") return (int)$b["car_year"] <=> (int)$a["car_year"];
        return strcmp($a["car_name"], $b["car_name"]);
    });

    function catalogueUrl($updates = []) {
        $query = array_merge($_GET, $updates);
        return "catalogue.php?" . http_build_query($query) . "#catalogueResults";
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Catalogue | KH Car Rental</title>
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

    /* ===== Hero ===== */
    .hero{
        width:min(1280px,100%);
        margin:14px auto 0;
        padding:0 22px;
    }
    .hero-card{
        min-height:168px;
        padding:22px 28px;
        border-radius:26px;
        display:grid;
        grid-template-columns:1.45fr .9fr;
        gap:22px;
        align-items:center;
        background:
            radial-gradient(circle at 100% 0%,rgba(184,228,255,.20),transparent 30%),
            linear-gradient(135deg,rgba(255,255,255,.98),rgba(248,253,255,.94));
        border:1px solid rgba(184,228,255,.82);
        box-shadow:var(--shadow);
    }
    .pill{
        display:inline-flex;
        align-items:center;
        gap:8px;
        width:fit-content;
        padding:6px 11px;
        border-radius:999px;
        background:rgba(40,168,234,.12);
        color:var(--sky600);
        border:1px solid rgba(40,168,234,.22);
        font-size:10.5px;
        font-weight:950;
        letter-spacing:.8px;
        text-transform:uppercase;
        margin-bottom:10px;
    }
    .hero h1{
        font-size:clamp(36px,3.8vw,54px);
        line-height:1;
        letter-spacing:-1.9px;
        font-weight:950;
        margin-bottom:10px;
    }
    .hero p{
        max-width:660px;
        color:var(--muted);
        font-size:14px;
        line-height:1.48;
        font-weight:650;
    }
    .hero-badges{
        display:flex;
        flex-wrap:wrap;
        gap:9px;
        margin-top:16px;
    }
    .hero-badge{
        min-height:38px;
        display:inline-flex;
        align-items:center;
        gap:10px;
        padding:0 13px;
        border-radius:14px;
        background:rgba(255,255,255,.82);
        border:1px solid var(--border);
        box-shadow:var(--soft);
        font-size:12.5px;
        font-weight:950;
    }
    .hero-badge i{color:var(--sky600)}

    /* ===== Trip search ===== */
    .trip-card{
        padding:16px 18px;
        border-radius:22px;
        background:
            radial-gradient(circle at 100% 0%,rgba(184,228,255,.20),transparent 30%),
            linear-gradient(135deg,rgba(255,255,255,.98),rgba(248,253,255,.94));
        border:1px solid rgba(184,228,255,.82);
        box-shadow:0 22px 50px rgba(40,168,234,.12);
    }
    .trip-card.search-closed{
        min-height:88px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:18px;
    }
    .trip-card.search-closed .trip-form{display:none}
    .trip-card.search-open{display:block}
    .trip-card.search-open .trip-form{display:grid}
    .trip-top{
        width:100%;
        margin:0;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
    }
    .trip-top h2{
        font-size:20px;
        line-height:1.05;
        letter-spacing:-.35px;
        font-weight:950;
    }
    .trip-top p{
        color:var(--muted);
        font-size:11.5px;
        line-height:1.55;
        margin-top:5px;
        font-weight:700;
    }
    .modify-btn{
        min-width:100px;
        min-height:36px;
        padding:0 13px;
        border:1px solid var(--border);
        border-radius:13px;
        background:var(--sky100);
        color:var(--sky600);
        font-size:12px;
        font-weight:950;
        cursor:pointer;
        white-space:nowrap;
        flex:0 0 auto;
    }
    .trip-form{
        margin-top:12px;
        grid-template-columns:1fr 1fr;
        gap:8px;
    }
    .full{grid-column:1/-1}
    label{
        display:block;
        font-size:9.5px;
        font-weight:950;
        letter-spacing:.55px;
        text-transform:uppercase;
        line-height:1;
        margin-bottom:4px;
    }
    .input{
        width:100%;
        height:34px;
        min-height:34px;
        border:2px solid #e2f2ff;
        background:rgba(255,255,255,.94);
        color:var(--dark);
        border-radius:11px;
        padding:6px 10px;
        outline:none;
        font-size:12px;
        font-weight:750;
        transition:.24s;
    }
    .input:focus{
        border-color:var(--sky500);
        box-shadow:0 0 0 .2rem rgba(40,168,234,.13);
    }
    .input.error{border-color:#ff4d4f!important;background:#fff5f5!important}
    .inline-error{
        display:none;
        grid-column:1/-1;
        color:#d63031;
        background:#fff5f5;
        border:1px solid rgba(255,77,79,.2);
        border-radius:14px;
        padding:10px 12px;
        font-size:12.5px;
        font-weight:800;
    }
    .inline-error.show{display:block}

    /* ===== Buttons ===== */
    .btn,
    .category-tab,
    .filter-toggle,
    .modify-btn,
    .start-btn,
    .login-btn,
    .car-card{
        position:relative;
        overflow:hidden;
    }
    .btn{
        min-height:36px;
        border:0;
        border-radius:12px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        padding:0 10px;
        font-size:12px;
        font-weight:950;
        cursor:pointer;
        transition:.24s;
    }
    .btn-blue{
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
    }
    .btn-white{
        background:#fff;
        color:var(--sky600);
        border:1px solid var(--border);
    }
    .btn-orange{
        width:100%;
        color:#fff;
        background:linear-gradient(135deg,#ff9a4a,#ff7a1a 48%,#f15f12);
        box-shadow:0 18px 34px rgba(255,122,26,.26);
    }
    .btn::before,
    .category-tab::before,
    .filter-toggle::before,
    .modify-btn::before,
    .start-btn::before,
    .login-btn::before{
        content:"";
        position:absolute;
        top:0;
        left:-120%;
        width:55%;
        height:100%;
        background:linear-gradient(120deg,transparent,rgba(255,255,255,.42),transparent);
        transform:skewX(-22deg);
        transition:left .58s ease;
        pointer-events:none;
    }
    .btn:hover::before,
    .category-tab:hover::before,
    .filter-toggle:hover::before,
    .modify-btn:hover::before,
    .start-btn:hover::before,
    .login-btn:hover::before{left:130%}
    .btn:hover,
    .category-tab:hover,
    .filter-toggle:hover,
    .modify-btn:hover,
    .start-btn:hover,
    .login-btn:hover{
        transform:translateY(-3px);
        box-shadow:var(--soft);
    }

    /* ===== Main layout ===== */
    .main{
        width:min(1280px,100%);
        margin:14px auto 52px;
        padding:0 22px;
    }
    .catalogue-control-layout{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:16px;
        align-items:start;
        margin-bottom:12px;
    }
    .category-panel,
    .tools-panel{
        height:auto;
        min-height:0;
        padding:13px 18px;
        border-radius:22px;
        background:
            radial-gradient(circle at 100% 0%,rgba(184,228,255,.20),transparent 30%),
            linear-gradient(135deg,rgba(255,255,255,.98),rgba(248,253,255,.94));
        border:1px solid rgba(184,228,255,.82);
        box-shadow:0 16px 44px rgba(39,137,199,.10);
    }
    .category-title{margin-bottom:8px}
    .category-header-row{
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:14px;
        margin-top:8px;
    }
    .category-header-row .pill{margin-bottom:8px}
    .category-header-row h2{
        font-size:21px;
        line-height:1.05;
        margin:0 0 4px;
        font-weight:950;
    }
    .category-header-row p{
        color:var(--muted);
        font-size:12px;
        line-height:1.2;
        font-weight:650;
    }
    .category-header-row .category-all-tab{
        display:inline-flex;
        width:118px;
        height:36px;
        min-height:36px;
        flex:0 0 118px;
        border-radius:13px;
        font-size:12px;
        padding:0 12px;
        justify-content:center;
        align-items:center;
        gap:7px;
    }
    .category-tabs{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:8px;
        align-content:start;
    }
    .category-tabs a[href="catalogue.php#catalogueResults"],
    .category-tabs .category-all-tab{display:none!important}
    .category-tab{
        height:36px;
        min-height:36px;
        border-radius:13px;
        padding:0 10px;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:6px;
        background:rgba(255,255,255,.84);
        border:1px solid var(--border);
        color:#24415f;
        font-size:12px;
        font-weight:950;
        transition:.24s;
        box-shadow:0 10px 24px rgba(40,168,234,.05);
    }
    .category-tab:hover,
    .category-tab.active{
        color:#fff;
        background:linear-gradient(135deg,var(--sky500),var(--sky600));
        box-shadow:0 18px 36px rgba(40,168,234,.22);
    }

    /* ===== Filter panel ===== */
    .tools-form{
        display:grid;
        grid-template-columns:1fr 1fr;
        grid-template-areas:
            "keyword keyword"
            "brand sort"
            "min max"
            "actions actions"
            "advanced advanced";
        gap:7px 10px;
        align-content:start;
    }
    .tools-form > div:nth-of-type(1){grid-area:keyword}
    .tools-form > div:nth-of-type(2){grid-area:brand}
    .tools-form > div:nth-of-type(3){grid-area:min}
    .tools-form > div:nth-of-type(4){grid-area:max}
    .tools-form > div:nth-of-type(5){grid-area:sort}
    .filter-actions-row{
        grid-area:actions;
        display:grid;
        grid-template-columns:1fr 1fr 1fr;
        gap:9px;
    }
    .filter-actions-row .btn,
    .filter-actions-row .filter-toggle{
        width:100%;
        height:36px;
        min-height:36px;
    }
    .filter-toggle{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:10px;
        color:var(--sky600);
        background:linear-gradient(135deg,#fff,var(--sky100));
        border:1px solid var(--border);
        border-radius:12px;
        font-size:12px;
        font-weight:950;
        cursor:pointer;
        transition:.24s;
    }
    .advanced-filter{
        grid-area:advanced;
        display:none;
        padding:9px;
        margin-top:0;
        border-radius:14px;
        background:linear-gradient(135deg,rgba(255,255,255,.95),rgba(234,247,255,.72));
        border:1px solid var(--border);
    }
    .advanced-filter.show{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }

    /* ===== Results and cards ===== */
    .result-head{
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:18px;
        margin:12px 0 8px;
    }
    .result-head h2{
        font-size:26px;
        line-height:1.1;
        font-weight:950;
        letter-spacing:-.8px;
    }
    .result-head p{
        color:var(--muted);
        font-size:13px;
        line-height:1.4;
        font-weight:650;
    }
    .count-tag{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:8px 12px;
        border-radius:999px;
        color:var(--sky600);
        background:var(--sky100);
        font-size:12.5px;
        font-weight:950;
        white-space:nowrap;
    }
    .car-grid{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:16px;
    }
    .car-card{
        border-radius:24px;
        background:
            radial-gradient(circle at 100% 0%,rgba(40,168,234,.08),transparent 28%),
            linear-gradient(145deg,rgba(255,255,255,.98),rgba(246,252,255,.92));
        border:1px solid rgba(184,228,255,.98);
        box-shadow:0 18px 46px rgba(29,109,164,.12);
        transition:.28s cubic-bezier(.2,.8,.2,1);
    }
    .car-card::before{
        content:"";
        position:absolute;
        inset:0;
        background:linear-gradient(130deg,transparent 0%,rgba(255,255,255,.55) 42%,transparent 58%);
        transform:translateX(-130%);
        transition:transform .75s ease;
        z-index:1;
        pointer-events:none;
    }
    .car-card:hover::before{transform:translateX(130%)}
    .car-card:hover{
        transform:translateY(-9px);
        border-color:rgba(40,168,234,.42);
        box-shadow:0 28px 70px rgba(29,109,164,.20);
    }
    .car-media{
        position:relative;
        height:172px;
        display:grid;
        place-items:center;
        overflow:hidden;
        background:
            radial-gradient(circle at 78% 22%,rgba(40,168,234,.18),transparent 34%),
            linear-gradient(135deg,#edf9ff,#ffffff);
        z-index:2;
    }
    .car-media::after{
        content:"";
        position:absolute;
        inset:0;
        background:linear-gradient(180deg,rgba(16,35,61,.12) 0%,transparent 42%,rgba(16,35,61,.18) 100%);
        z-index:1;
        pointer-events:none;
    }
    .no-image{
        width:86px;
        height:86px;
        border-radius:30px;
        display:grid;
        place-items:center;
        color:var(--sky600);
        background:linear-gradient(135deg,#d8f2ff,#fff);
        border:1px solid var(--border);
        font-size:36px;
    }
    .car-body{
        position:relative;
        z-index:2;
        padding:16px;
    }
    .car-title{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        margin-bottom:10px;
    }
    .car-title h3{
        font-size:20px;
        line-height:1.15;
        font-weight:950;
    }
    .brand-tag{
        padding:6px 9px;
        border-radius:999px;
        background:var(--sky100);
        color:var(--sky600);
        font-size:11px;
        font-weight:950;
        white-space:nowrap;
    }
    .spec-grid{
        display:grid;
        grid-template-columns:repeat(2,1fr);
        gap:7px;
        margin:10px 0;
    }
    .spec{
        display:flex;
        align-items:center;
        gap:9px;
        min-height:33px;
        padding:7px 9px;
        border-radius:12px;
        background:linear-gradient(135deg,rgba(234,247,255,.92),rgba(255,255,255,.82));
        border:1px solid rgba(216,236,251,.68);
        color:#2b4969;
        font-size:11.5px;
        font-weight:850;
    }
    .spec i{color:var(--sky600)}
    .colour-spec{
        grid-column:1/-1;
        justify-content:flex-start;
        min-height:40px;
        padding:10px 13px;
        border-radius:16px;
        background:
            linear-gradient(135deg, rgba(40,168,234,.12), rgba(255,255,255,.92)),
            radial-gradient(circle at 96% 15%, rgba(255,122,26,.12), transparent 34%);
        border:1px solid rgba(40,168,234,.26);
        box-shadow:inset 0 1px 0 rgba(255,255,255,.78), 0 10px 22px rgba(40,168,234,.08);
        color:#17304f;
        font-weight:950;
    }
    .colour-spec i{
        width:24px;
        height:24px;
        display:inline-grid;
        place-items:center;
        border-radius:50%;
        background:linear-gradient(135deg,#d8f2ff,#ffffff);
        color:var(--sky600);
        box-shadow:0 7px 16px rgba(40,168,234,.13);
    }
    .desc{
        color:var(--muted);
        font-size:12px;
        line-height:1.45;
        font-weight:650;
        min-height:34px;
    }
    .actions{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:8px;
        margin-top:12px;
    }
    .actions .btn{
        min-height:40px;
        border-radius:13px;
        font-size:12px;
    }
    .actions .btn-white:hover{
        color:var(--sky600);
        border-color:rgba(40,168,234,.38);
        background:linear-gradient(135deg,#ffffff,var(--sky100));
    }
    .car-tags{
        position:absolute;
        left:16px;
        top:16px;
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        z-index:6;
    }
    .tag{
        padding:6px 9px;
        border-radius:999px;
        background:rgba(255,255,255,.92);
        color:var(--sky600);
        border:1px solid var(--border);
        font-size:10.5px;
        font-weight:950;
    }
    .tag.orange{
        color:var(--orange2);
        border-color:rgba(255,122,26,.28);
        background:rgba(255,247,239,.95);
    }
    .price-chip{
        position:absolute;
        right:16px;
        bottom:16px;
        z-index:6;
        padding:9px 12px;
        border-radius:15px;
        color:#fff;
        background:linear-gradient(135deg,#ff9a4a,#f15f12);
        box-shadow:0 16px 34px rgba(255,122,26,.30);
        font-size:12.5px;
        font-weight:950;
    }
    .empty{
        padding:38px;
        border-radius:28px;
        text-align:center;
        background:rgba(255,255,255,.78);
        border:1px dashed var(--border);
        color:var(--muted);
        font-weight:650;
    }

    /* ===== Image carousel ===== */
    .car-carousel{
        position:absolute;
        inset:0;
        width:100%;
        height:100%;
        z-index:0;
        overflow:hidden;
        background:linear-gradient(135deg,#edf9ff,#ffffff);
    }
    .carousel-track{
        position:relative;
        width:100%;
        height:100%;
    }
    .carousel-img{
        position:absolute;
        inset:0;
        width:100%;
        height:100%;
        object-fit:cover;
        opacity:0;
        transform:scale(1.04);
        transition:opacity .42s ease,transform .55s ease;
    }
    .carousel-img.active{
        opacity:1;
        transform:scale(1);
    }
    .car-card:hover .carousel-img.active{transform:scale(1.045)}
    .carousel-btn{
        position:absolute;
        top:50%;
        transform:translateY(-50%);
        width:36px;
        height:36px;
        border:1px solid rgba(255,255,255,.72);
        border-radius:50%;
        background:rgba(255,255,255,.86);
        color:var(--sky600);
        display:grid;
        place-items:center;
        cursor:pointer;
        z-index:5;
        opacity:0;
        transition:.25s ease;
        box-shadow:0 10px 24px rgba(16,35,61,.16);
        backdrop-filter:blur(12px);
    }
    .carousel-prev{left:14px}
    .carousel-next{right:14px}
    .car-card:hover .carousel-btn{opacity:1}
    .carousel-btn:hover{
        background:var(--sky600);
        color:#fff;
        transform:translateY(-50%) scale(1.08);
    }
    .carousel-dots{
        position:absolute;
        left:50%;
        bottom:12px;
        transform:translateX(-50%);
        display:flex;
        gap:6px;
        z-index:5;
        padding:6px 8px;
        border-radius:999px;
        background:rgba(16,35,61,.28);
        backdrop-filter:blur(10px);
    }
    .carousel-dot{
        width:7px;
        height:7px;
        border-radius:999px;
        border:0;
        background:rgba(255,255,255,.62);
        cursor:pointer;
        transition:.22s ease;
    }
    .carousel-dot.active{
        width:18px;
        background:#fff;
    }

    /* ===== Modal ===== */
    .modal{
        position:fixed;
        inset:0;
        z-index:999;
        display:none;
        place-items:center;
        background:rgba(13,31,55,.42);
        backdrop-filter:blur(10px);
        padding:18px;
    }
    .modal.show{display:grid}
    .modal-card{
        width:min(620px,100%);
        border-radius:32px;
        background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(244,251,255,.94));
        border:1px solid rgba(184,228,255,.95);
        box-shadow:0 34px 90px rgba(23,48,79,.24);
        overflow:hidden;
    }
    .modal-head{
        padding:24px 26px 16px;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:16px;
    }
    .modal-head h2{
        font-size:28px;
        font-weight:950;
        letter-spacing:-.8px;
        margin-bottom:6px;
    }
    .modal-head p{color:var(--muted);font-weight:650}
    .close{
        width:40px;
        height:40px;
        border:0;
        border-radius:14px;
        cursor:pointer;
        color:var(--sky600);
        background:var(--sky100);
    }
    .modal-body{padding:0 26px 26px}
    .modal-grid{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:12px;
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


    /* ===== Check Availability Modal Result ===== */
    .availability-result{
        display:none;
        grid-column:1/-1;
        border-radius:22px;
        padding:18px;
        border:1px solid var(--border);
        background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(234,247,255,.68));
    }
    .availability-result.show{display:block}
    .availability-result.success{
        border-color:rgba(20,184,116,.28);
        background:linear-gradient(135deg,#f0fff8,#ffffff);
    }
    .availability-result.danger{
        border-color:rgba(244,67,54,.24);
        background:linear-gradient(135deg,#fff4f2,#ffffff);
    }
    .availability-status{
        display:flex;
        align-items:flex-start;
        gap:12px;
        margin-bottom:14px;
    }
    .availability-status .status-icon{
        width:42px;
        height:42px;
        border-radius:16px;
        display:grid;
        place-items:center;
        flex:0 0 auto;
        color:#ffffff;
        background:linear-gradient(135deg,#17b26a,#079455);
    }
    .availability-result.danger .status-icon{
        background:linear-gradient(135deg,#ff6b5f,#d92d20);
    }
    .availability-status h3{
        font-size:20px;
        font-weight:950;
        line-height:1.15;
        margin-bottom:4px;
    }
    .availability-status p{
        color:var(--muted);
        font-size:13px;
        line-height:1.5;
        font-weight:650;
    }
    .availability-detail-grid{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:10px;
        margin:12px 0 14px;
    }
    .availability-detail{
        border-radius:15px;
        padding:10px 12px;
        background:rgba(255,255,255,.84);
        border:1px solid rgba(216,236,251,.82);
    }
    .availability-detail span{
        display:block;
        color:var(--muted);
        font-size:10px;
        font-weight:950;
        letter-spacing:.55px;
        text-transform:uppercase;
        margin-bottom:3px;
    }
    .availability-detail strong{
        display:block;
        color:var(--dark);
        font-size:13px;
        font-weight:950;
    }
    .availability-detail.wide{grid-column:1 / -1;}
    .availability-detail small{
        display:block;
        margin-top:4px;
        color:var(--muted);
        font-size:10px;
        font-weight:850;
    }
    .compact-result-grid .availability-detail.wide strong{white-space:normal;}
    .availability-actions{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:10px;
    }
    .availability-actions.three{
        grid-template-columns:1fr 1fr 1fr;
    }
    @media(max-width:760px){
        .availability-detail-grid,
        .availability-actions,
        .availability-actions.three{
            grid-template-columns:1fr;
        }
    }


    /* ===== Homepage-matching search time controls ===== */
    .time-combo{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:10px;
    }
    .time-combo .input{
        width:100%;
    }
    .fixed-time-display{
        width:100%;
        min-height:34px;
        height:34px;
        border:2px solid #e2f2ff;
        background:rgba(255,255,255,.94);
        color:var(--dark);
        border-radius:11px;
        padding:6px 10px;
        display:flex;
        align-items:center;
        gap:9px;
        font-size:12px;
        font-weight:850;
    }
    .fixed-time-display i{
        color:var(--sky600);
    }
    .search-open .trip-form{
        grid-template-columns:1fr 1fr;
    }
    .applied-filter-row{
        width:100%;
        margin:0 0 14px;
        display:flex;
        align-items:center;
        flex-wrap:wrap;
        gap:8px;
    }
    .applied-filter-title,
    .filter-chip{
        min-height:32px;
        display:inline-flex;
        align-items:center;
        gap:7px;
        padding:0 12px;
        border-radius:999px;
        font-size:11px;
        font-weight:950;
        border:1px solid var(--border);
        background:rgba(255,255,255,.86);
        color:var(--sky600);
    }
    .applied-filter-title{
        background:var(--sky100);
    }
    .modal-card{
        width:min(760px,calc(100% - 32px))!important;
    }
    .modal-grid{
        grid-template-columns:1fr 1fr;
    }
    .availability-result.show{
        display:block;
    }
    .availability-result{
        margin-top:8px;
    }
    .availability-actions.three{
        grid-template-columns:1fr 1fr 1fr;
    }
    @media(max-width:760px){
        .modal-grid,
        .search-open .trip-form{
            grid-template-columns:1fr;
        }
        .availability-actions.three{
            grid-template-columns:1fr;
        }
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



    /* ===== ONLY FIX: Make Check Availability result modal smaller ===== */
    #availabilityModal .modal-card{
        width:min(600px,calc(100% - 28px))!important;
        border-radius:24px!important;
    }
    #availabilityModal .modal-head{
        padding:16px 20px 10px!important;
    }
    #availabilityModal .modal-head h2{
        font-size:22px!important;
        margin-bottom:3px!important;
    }
    #availabilityModal .modal-head p{
        font-size:12px!important;
        line-height:1.3!important;
    }
    #availabilityModal .modal-body{
        padding:0 20px 18px!important;
    }
    #availabilityModal .modal-grid{
        gap:8px 10px!important;
    }
    #availabilityModal .input,
    #availabilityModal .fixed-time-display{
        height:30px!important;
        min-height:30px!important;
        border-radius:10px!important;
        padding:5px 9px!important;
        font-size:11.5px!important;
    }
    #availabilityModal label{
        font-size:8.8px!important;
        margin-bottom:3px!important;
    }
    #availabilityModal .time-combo{
        gap:7px!important;
    }
    #availabilityModal .inline-error{
        padding:7px 10px!important;
        font-size:11px!important;
    }
    #availabilityModal .availability-result{
        margin-top:4px!important;
        padding:10px!important;
        border-radius:16px!important;
    }
    #availabilityModal .availability-status{
        gap:9px!important;
        margin-bottom:8px!important;
    }
    #availabilityModal .availability-status .status-icon{
        width:32px!important;
        height:32px!important;
        border-radius:12px!important;
        font-size:13px!important;
    }
    #availabilityModal .availability-status h3{
        font-size:16px!important;
        margin-bottom:2px!important;
    }
    #availabilityModal .availability-status p{
        font-size:11px!important;
        line-height:1.3!important;
    }
    #availabilityModal .availability-detail-grid{
        gap:6px!important;
        margin:7px 0 9px!important;
    }
    #availabilityModal .availability-detail{
        border-radius:11px!important;
        padding:6px 8px!important;
    }
    #availabilityModal .availability-detail span{
        font-size:8.5px!important;
        margin-bottom:2px!important;
    }
    #availabilityModal .availability-detail strong{
        font-size:11.5px!important;
        line-height:1.22!important;
    }
    #availabilityModal .availability-detail small{
        font-size:8.8px!important;
        margin-top:2px!important;
    }
    #availabilityModal .availability-actions,
    #availabilityModal .availability-actions.three{
        gap:7px!important;
    }
    #availabilityModal .availability-actions .btn,
    #availabilityModal #checkNowBtn{
        min-height:32px!important;
        height:32px!important;
        border-radius:10px!important;
        font-size:11px!important;
    }
    @media(min-width:761px){
        #availabilityModal:has(#availabilityResult.show) .modal-card{
            transform:scale(.88)!important;
        }
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
                <li><a href="catalogue.php" class="active"><i class="fa-solid fa-car"></i> CATALOGUE</a></li>
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

    <section class="hero">
        <div class="hero-card">
            <div>
                <span class="pill"><i class="fa-solid fa-layer-group"></i> Catalogue</span>
                <h1>Browse Our Rental Cars</h1>
                <p>Browse all car models owned by KH Car Rental. To confirm whether a car is available for your trip, use Check Availability or search by date and location.</p>
                <div class="hero-badges">
                    <span class="hero-badge"><i class="fa-solid fa-car-side"></i> All Car Models</span>
                    <span class="hero-badge"><i class="fa-solid fa-tags"></i> Clear Daily Price</span>
                    <span class="hero-badge"><i class="fa-solid fa-calendar-check"></i> Check Availability</span>
                </div>
            </div>

            <div class="trip-card search-closed" id="tripCard">
                <div class="trip-top">
                    <div>
                        <h2>Find available cars for your trip</h2>
                        <p>Pickup State • Location • Date & Time</p>
                    </div>
                    <button class="modify-btn" type="button" id="modifyTripBtn">Modify Search</button>
                </div>

                <form class="trip-form" method="GET" action="available_cars.php" id="availableSearchForm">
                    <div class="full">
                        <label>Pickup State</label>
                        <select class="input" name="state" id="pickupState" required>
                            <option value="">Select State</option>
                            <?php foreach($states as $state): ?>
                                <option value="<?= e($state["state_id"]) ?>"><?= e($state["state_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Pickup Location</label>
                        <select class="input" name="pickup_location" id="pickupLocation" required>
                            <option value="">Select Pickup Location</option>
                            <?php foreach($locations as $location): ?>
                                <option value="<?= e($location["location_id"]) ?>" data-state="<?= e($location["state_id"]) ?>"><?= e($location["location_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Drop-off Location</label>
                        <select class="input" name="dropoff_location" id="dropoffLocation" required>
                            <option value="">Select Drop-off Location</option>
                            <?php foreach($locations as $location): ?>
                                <option value="<?= e($location["location_id"]) ?>" data-state="<?= e($location["state_id"]) ?>"><?= e($location["location_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="optional-field"><label>Pickup Date</label><input class="input" type="date" name="pickup_date" id="pickupDate" required></div>
                    <div class="optional-field">
                        <label>Pickup Time</label>
                        <div class="time-combo" id="pickupTimeGroup">
                            <select class="input time-part" id="pickupHour" required><option value="">Hour</option></select>
                            <select class="input time-part" id="pickupMinute" required><option value="">Minute</option></select>
                        </div>
                        <input type="hidden" name="pickup_time" id="pickupTime" required>
                    </div>
                    <div class="optional-field"><label>Return Date</label><input class="input" type="date" name="return_date" id="returnDate" required></div>
                    <div class="optional-field">
                        <label>Return Time</label>
                        <div class="fixed-time-display" id="returnTimeDisplay"><i class="fa-solid fa-lock"></i><span>Same as pickup time</span></div>
                        <input type="hidden" name="return_time" id="returnTime" required>
                    </div>

                    <div class="inline-error" id="tripError"></div>

                    <div class="full">
                        <button class="btn btn-orange" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search Available Cars</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <main class="main" id="catalogueResults">
        <div class="catalogue-control-layout">
        <section class="category-panel">
                <div class="category-title">
                    <div class="category-header-row">
                        <div>
                            <span class="pill"><i class="fa-solid fa-table-cells-large"></i> CATEGORY FILTER</span>
                            <h2>Car Type</h2>
                            <p>Choose one category or keep All vehicles.</p>
                        </div>

                        <a class="category-tab category-all-tab <?= ($selectedCategory === "" || $selectedCategory === "All") ? "active" : "" ?>" href="catalogue.php#catalogueResults">
                            <i class="fa-solid fa-table-cells-large"></i> All
                        </a>
                    </div>
                </div>

                <div class="category-tabs">
                    <?php foreach($categories as $cat): ?>
                        <?php
                            $catName = is_array($cat) ? ($cat["category_name"] ?? "") : $cat;
                            if (trim((string)$catName) === "" || strtolower((string)$catName) === "all") {
                                continue;
                            }
                            $isActive = strtolower((string)$selectedCategory) === strtolower((string)$catName);
                        ?>
                        <a class="category-tab <?= $isActive ? 'active' : '' ?>" href="catalogue.php?<?= http_build_query(array_merge($_GET, ['category' => (string)$catName])) ?>#catalogueResults">
                            <i class="fa-solid fa-car-side"></i> <?= e($catName) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="tools-panel">
            <form class="tools-form" method="GET" action="catalogue.php#catalogueResults" id="filterForm">
                <input type="hidden" name="category" value="<?= e($selectedCategory) ?>">

                <div>
                    <label>Search Model / Brand</label>
                    <input class="input" type="text" name="keyword" value="<?= e($keyword) ?>" placeholder="Vios, CX-5, BMW, Alza">
                </div>

                <div>
                    <label>Brand</label>
                    <select class="input" name="brand">
                        <option value="All">All Brands</option>
                        <?php foreach($brands as $brand): ?>
                            <option value="<?= e($brand) ?>" <?= strtolower($selectedBrand) === strtolower($brand) ? "selected" : "" ?>><?= e($brand) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Min Price</label>
                    <input class="input" type="number" name="min_price" id="minPrice" min="0" value="<?= e($minPrice) ?>" placeholder="RM">
                </div>

                <div>
                    <label>Max Price</label>
                    <input class="input" type="number" name="max_price" id="maxPrice" min="<?= e($minPrice !== '' ? $minPrice : 0) ?>" value="<?= e($maxPrice) ?>" placeholder="RM">
                </div>

                <div>
                    <label>Sort By</label>
                    <select class="input" name="sort">
                        <option value="default" <?= $sort === "default" ? "selected" : "" ?>>Name A-Z</option>
                        <option value="price_low" <?= $sort === "price_low" ? "selected" : "" ?>>Price Low to High</option>
                        <option value="price_high" <?= $sort === "price_high" ? "selected" : "" ?>>Price High to Low</option>
                        <option value="newest" <?= $sort === "newest" ? "selected" : "" ?>>Newest Cars</option>
                        <option value="seats" <?= $sort === "seats" ? "selected" : "" ?>>Most Seats</option>
                        <option value="horsepower" <?= $sort === "horsepower" ? "selected" : "" ?>>Highest Horsepower</option>
                    </select>
                </div>

                <div class="filter-actions-row">
                    <button class="btn btn-blue" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
                    <a class="btn btn-white" href="catalogue.php#catalogueResults"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                    <button class="filter-toggle" type="button" id="advancedToggle"><i class="fa-solid fa-sliders"></i><span>More Filters</span></button>
                </div>

                <div class="advanced-filter" id="advancedFilter">

                    <div>
                        <label>State</label>
                        <select class="input" name="car_state">
                            <option value="All">Any State</option>
                            <?php foreach($states as $state): ?>
                                <option value="<?= e($state["state_id"]) ?>" <?= (string)$selectedStateFilter === (string)$state["state_id"] ? "selected" : "" ?>>
                                    <?= e($state["state_name"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Seats</label>
                        <select class="input" name="seats">
                            <option value="All">Any Seats</option>
                            <option value="4" <?= $seats === "4" ? "selected" : "" ?>>4+ Seats</option>
                            <option value="5" <?= $seats === "5" ? "selected" : "" ?>>5+ Seats</option>
                            <option value="7" <?= $seats === "7" ? "selected" : "" ?>>7+ Seats</option>
                        </select>
                    </div>

                    <div>
                        <label>Transmission</label>
                        <select class="input" name="transmission">
                            <option value="All">Any Transmission</option>
                            <option value="Automatic" <?= $transmission === "Automatic" ? "selected" : "" ?>>Automatic</option>
                            <option value="Manual" <?= $transmission === "Manual" ? "selected" : "" ?>>Manual</option>
                        </select>
                    </div>

                    <div>
                        <label>Fuel Type</label>
                        <select class="input" name="fuel">
                            <option value="All">Any Fuel</option>
                            <option value="Petrol" <?= $fuel === "Petrol" ? "selected" : "" ?>>Petrol</option>
                            <option value="Hybrid" <?= $fuel === "Hybrid" ? "selected" : "" ?>>Hybrid</option>
                            <option value="Diesel" <?= $fuel === "Diesel" ? "selected" : "" ?>>Diesel</option>
                            <option value="EV" <?= $fuel === "EV" ? "selected" : "" ?>>EV</option>
                        </select>
                    </div>
                </div>
            </form>
        </section>
        </div>

        <?php
            $currentFilterChips = [];

            if ($selectedStateFilter !== "" && $selectedStateFilter !== "All") {
                $stateFilterName = "Selected State";
                foreach ($states as $state) {
                    if ((string)$state["state_id"] === (string)$selectedStateFilter) {
                        $stateFilterName = $state["state_name"];
                        break;
                    }
                }
                $currentFilterChips[] = "State: " . $stateFilterName;
            } else {
                $currentFilterChips[] = "State: All States";
            }

            $currentFilterChips[] = "Category: " . (($selectedCategory === "" || $selectedCategory === "All") ? "All Categories" : $selectedCategory);
            $currentFilterChips[] = "Brand: " . (($selectedBrand === "" || $selectedBrand === "All") ? "All Brands" : $selectedBrand);

            if ($keyword !== "") $currentFilterChips[] = "Search: " . $keyword;
            if ($minPrice !== "") $currentFilterChips[] = "Min Price: RM " . $minPrice;
            if ($maxPrice !== "") $currentFilterChips[] = "Max Price: RM " . $maxPrice;
            if ($seats !== "" && $seats !== "All") $currentFilterChips[] = "Seats: " . $seats . "+";
            if ($transmission !== "" && $transmission !== "All") $currentFilterChips[] = "Transmission: " . $transmission;
            if ($fuel !== "" && $fuel !== "All") $currentFilterChips[] = "Fuel: " . $fuel;

            $sortLabels = [
                "default" => "Name A-Z",
                "price_low" => "Price Low to High",
                "price_high" => "Price High to Low",
                "newest" => "Newest Cars",
                "seats" => "Most Seats",
                "horsepower" => "Highest Horsepower"
            ];
            $currentFilterChips[] = "Sort: " . ($sortLabels[$sort] ?? "Name A-Z");
        ?>
        <div class="applied-filter-row">
            <span class="applied-filter-title"><i class="fa-solid fa-filter"></i> Current Filters</span>
            <?php foreach($currentFilterChips as $chip): ?>
                <span class="filter-chip"><?= e($chip) ?></span>
            <?php endforeach; ?>
        </div>


        <section>
            <div class="result-head">
                <div>
                    <span class="pill"><i class="fa-solid fa-car"></i> <?= e($selectedCategory === "" ? "All" : $selectedCategory) ?></span>
                    <h2><?= e($selectedCategory === "" || $selectedCategory === "All" ? "All Rental Cars" : $selectedCategory . " Cars") ?></h2>
                    <p>Browse all matching car models. Click Check Availability to verify selected date and location.</p>
                </div>
                <span class="count-tag"><i class="fa-solid fa-layer-group"></i> <?= count($filteredCars) ?> Models</span>
            </div>

            <?php if(empty($filteredCars)): ?>
                <div class="empty">
                    <h2>No cars found for this filter.</h2>
                    <p>Try another brand, category or price range.</p>
                    <br>
                    <a class="btn btn-blue" href="catalogue.php#catalogueResults"><i class="fa-solid fa-car-side"></i> View All Cars</a>
                </div>
            <?php else: ?>
                <div class="car-grid">
                    <?php foreach($filteredCars as $car): ?>
                        <?php
                            $image = trim($car["image"] ?? "");
                            $tag = trim($car["car_tag"] ?? "");
                        ?>
                        <article class="car-card">
                            <div class="car-media">
                                <div class="car-tags">
                                    <span class="tag"><?= e($car["category_name"]) ?></span>
                                    <?php if($tag): ?><span class="tag orange"><?= e($tag) ?></span><?php endif; ?>
                                </div>

                                <?php
                                    $carouselImages = getCarImages($conn, (int)$car["car_id"], $image);
                                    $carouselId = "carCarousel_" . (int)$car["car_id"];
                                ?>

                                <?php if(!empty($carouselImages)): ?>
                                    <div class="car-carousel" id="<?= e($carouselId) ?>">
                                        <div class="carousel-track">
                                            <?php foreach($carouselImages as $index => $carImage): ?>
                                                <img class="carousel-img <?= $index === 0 ? 'active' : '' ?>"
                                                    src="<?= e(resolveCarImageSrc($carImage, $car["car_name"])) ?>"
                                                    alt="<?= e($car["car_name"]) ?> image <?= $index + 1 ?>">
                                            <?php endforeach; ?>
                                        </div>

                                        <?php if(count($carouselImages) > 1): ?>
                                            <button class="carousel-btn carousel-prev" type="button" aria-label="Previous image">
                                                <i class="fa-solid fa-chevron-left"></i>
                                            </button>
                                            <button class="carousel-btn carousel-next" type="button" aria-label="Next image">
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </button>

                                            <div class="carousel-dots">
                                                <?php foreach($carouselImages as $index => $carImage): ?>
                                                    <button class="carousel-dot <?= $index === 0 ? 'active' : '' ?>" type="button" data-index="<?= $index ?>" aria-label="Go to image <?= $index + 1 ?>"></button>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-image"><i class="fa-solid fa-car-side"></i></div>
                                <?php endif; ?>

                                <span class="price-chip">RM <?= e(number_format((float)$car["price_per_day"], 2)) ?> / day</span>
                            </div>

                            <div class="car-body">
                                <div class="car-title">
                                    <h3><?= e($car["car_name"]) ?></h3>
                                    <span class="brand-tag"><?= e($car["brand"]) ?></span>
                                </div>

                                <div class="spec-grid">
                                    <div class="spec"><i class="fa-solid fa-users"></i> <?= e($car["seats"]) ?> Seats</div>
                                    <div class="spec"><i class="fa-solid fa-gears"></i> <?= e($car["transmission"]) ?></div>
                                    <div class="spec"><i class="fa-solid fa-gas-pump"></i> <?= e($car["fuel_type"]) ?></div>
                                    <div class="spec"><i class="fa-solid fa-screwdriver-wrench"></i> <?= e($car["engine"]) ?></div>
                                    <div class="spec"><i class="fa-solid fa-gauge-high"></i> <?= e($car["horsepower"]) ?> hp</div>
                                    <div class="spec"><i class="fa-solid fa-road"></i> <?= e($car["drivetrain"]) ?></div>
                                    <div class="spec colour-spec"><i class="fa-solid fa-palette"></i> <?= e($car["car_color"] ?? "Not specified") ?> Colour</div>
                                </div>

                                <div class="actions">
                                    <a class="btn btn-white" href="car_details.php?car_id=<?= e($car["car_id"]) ?>">
                                        <i class="fa-solid fa-circle-info"></i> View Details
                                    </a>
                                    <button class="btn btn-blue check-btn"
                                            type="button"
                                            data-car-id="<?= e($car["car_id"]) ?>"
                                            data-car-name="<?= e($car["car_name"]) ?>">
                                        <i class="fa-solid fa-calendar-check"></i> Check Availability
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <div class="modal" id="availabilityModal">
        <div class="modal-card">
            <div class="modal-head">
                <div>
                    <span class="pill"><i class="fa-solid fa-calendar-check"></i> Check Selected Vehicle</span>
                    <h2>Check Availability</h2>
                    <p id="modalCarName">Check availability for selected vehicle</p>
                </div>
                <button class="close" type="button" id="closeModal"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="modal-body">
                <form class="modal-grid" method="POST" action="check_availability.php" id="availabilityForm">
                    <input type="hidden" name="car_id" id="modalCarId">

                    <div class="full">
                        <label>Pickup State</label>
                        <select class="input" name="state" id="modalState" required>
                            <option value="">Select State</option>
                            <?php foreach($states as $state): ?>
                                <option value="<?= e($state["state_id"]) ?>"><?= e($state["state_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Pickup Location</label>
                        <select class="input" name="pickup_location" id="modalPickup" required>
                            <option value="">Select Pickup Location</option>
                            <?php foreach($locations as $location): ?>
                                <option value="<?= e($location["location_id"]) ?>" data-state="<?= e($location["state_id"]) ?>"><?= e($location["location_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Drop-off Location</label>
                        <select class="input" name="dropoff_location" id="modalDropoff" required>
                            <option value="">Select Drop-off Location</option>
                            <?php foreach($locations as $location): ?>
                                <option value="<?= e($location["location_id"]) ?>" data-state="<?= e($location["state_id"]) ?>"><?= e($location["location_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div><label>Pickup Date</label><input class="input" type="date" name="pickup_date" id="modalPickupDate" required></div>
                    <div>
                        <label>Pickup Time</label>
                        <div class="time-combo" id="modalPickupTimeGroup">
                            <select class="input time-part" id="modalPickupHour" required><option value="">Hour</option></select>
                            <select class="input time-part" id="modalPickupMinute" required><option value="">Minute</option></select>
                        </div>
                        <input type="hidden" name="pickup_time" id="modalPickupTime" required>
                    </div>
                    <div><label>Return Date</label><input class="input" type="date" name="return_date" id="modalReturnDate" required></div>
                    <div>
                        <label>Return Time</label>
                        <div class="fixed-time-display" id="modalReturnTimeDisplay"><i class="fa-solid fa-lock"></i><span>Same as pickup time</span></div>
                        <input type="hidden" name="return_time" id="modalReturnTime" required>
                    </div>

                    <div class="inline-error" id="modalError"></div>

                    <div class="availability-result full" id="availabilityResult"></div>

                    <div class="full">
                        <button class="btn btn-orange" type="submit" id="checkNowBtn"><i class="fa-solid fa-magnifying-glass"></i> Check Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
    function filterLocations(stateSelect, pickupSelect, dropoffSelect){
        const stateId = stateSelect.value;

        [pickupSelect, dropoffSelect].forEach(select=>{
            [...select.options].forEach(option=>{
                if(!option.value) return;
                option.hidden = stateId && option.dataset.state !== stateId;
            });

            if(select.selectedOptions[0] && select.selectedOptions[0].hidden){
                select.value = "";
            }
        });
    }

    function showError(errorBox, message){
        if(!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.add("show");
    }

    function clearError(errorBox){
        if(!errorBox) return;
        errorBox.textContent = "";
        errorBox.classList.remove("show");
    }


    function formatHourLabel(hour){
        const displayHour = hour % 12 === 0 ? 12 : hour % 12;
        const ampm = hour >= 12 ? "PM" : "AM";
        return `${String(displayHour).padStart(2, "0")} ${ampm}`;
    }

    function formatFixedTimeLabel(timeValue){
        if(!timeValue || !timeValue.includes(":")) return "Same as pickup time";
        const [hourText, minuteText] = timeValue.split(":");
        const hour = Number(hourText);
        const displayHour = hour % 12 === 0 ? 12 : hour % 12;
        const ampm = hour >= 12 ? "PM" : "AM";
        return `${String(displayHour).padStart(2, "0")}:${minuteText} ${ampm} (Fixed)`;
    }

    function buildTimeOptions(hourSelect, minuteSelect){
        if(hourSelect && hourSelect.options.length <= 1){
            for(let hour = 0; hour < 24; hour++){
                const value = String(hour).padStart(2, "0");
                hourSelect.add(new Option(formatHourLabel(hour), value));
            }
        }
        if(minuteSelect && minuteSelect.options.length <= 1){
            for(let minute = 0; minute < 60; minute += 5){
                const value = String(minute).padStart(2, "0");
                minuteSelect.add(new Option(value, value));
            }
        }
    }

    function setupTimeCombo(config){
        const hourSelect = document.getElementById(config.hour);
        const minuteSelect = document.getElementById(config.minute);
        const pickupHidden = document.getElementById(config.pickupHidden);
        const returnHidden = document.getElementById(config.returnHidden);
        const display = document.getElementById(config.display);

        if(!hourSelect || !minuteSelect || !pickupHidden || !returnHidden) return;

        buildTimeOptions(hourSelect, minuteSelect);

        function sync(){
            if(hourSelect.value !== "" && minuteSelect.value !== ""){
                const selectedTime = `${hourSelect.value}:${minuteSelect.value}`;
                pickupHidden.value = selectedTime;
                returnHidden.value = selectedTime;
                if(display) display.querySelector("span").textContent = formatFixedTimeLabel(selectedTime);
            }else{
                pickupHidden.value = "";
                returnHidden.value = "";
                if(display) display.querySelector("span").textContent = "Same as pickup time";
            }
            pickupHidden.dispatchEvent(new Event("change"));
            returnHidden.dispatchEvent(new Event("change"));
        }

        hourSelect.addEventListener("change", sync);
        minuteSelect.addEventListener("change", sync);
        sync();
    }


    function localDateValue(date = new Date()){
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    function minimumPickupDateTime(){
        const minimum = new Date();
        minimum.setHours(minimum.getHours() + 1);
        minimum.setSeconds(0, 0);
        return minimum;
    }

    function setupTripValidation(config){
        const pickupDate = document.getElementById(config.pickupDate);
        const pickupTime = document.getElementById(config.pickupTime);
        const returnDate = document.getElementById(config.returnDate);
        const returnTime = document.getElementById(config.returnTime);
        const form = document.getElementById(config.form);
        const errorBox = document.getElementById(config.error);

        if(!form || !pickupDate || !pickupTime || !returnDate || !returnTime) return;

        const today = localDateValue();
        pickupDate.min = today;
        returnDate.min = today;

        const pickupHour = document.getElementById(config.pickupTime.replace("Time", "Hour"));
        const pickupMinute = document.getElementById(config.pickupTime.replace("Time", "Minute"));

        function refreshMinimumPickupOptions(){
            const minimum = minimumPickupDateTime();
            const minimumDate = localDateValue(minimum);
            const minimumHour = String(minimum.getHours()).padStart(2, "0");
            const minimumMinute = String(minimum.getMinutes()).padStart(2, "0");

            pickupDate.min = minimumDate;

            if(!pickupHour || !pickupMinute) return;

            Array.from(pickupHour.options).forEach(option => {
                if(option.value === ""){
                    option.disabled = false;
                    return;
                }

                option.disabled = pickupDate.value === minimumDate && option.value < minimumHour;
            });

            Array.from(pickupMinute.options).forEach(option => {
                if(option.value === ""){
                    option.disabled = false;
                    return;
                }

                option.disabled = pickupDate.value === minimumDate &&
                    pickupHour.value === minimumHour &&
                    option.value < minimumMinute;
            });

            if(pickupHour.selectedOptions[0] && pickupHour.selectedOptions[0].disabled){
                pickupHour.value = "";
                pickupMinute.value = "";
                pickupTime.value = "";
                returnTime.value = "";
            }

            if(pickupMinute.selectedOptions[0] && pickupMinute.selectedOptions[0].disabled){
                pickupMinute.value = "";
                pickupTime.value = "";
                returnTime.value = "";
            }
        }

        function syncReturnTime(){
            returnTime.value = pickupTime.value || "";
            refreshMinimumPickupOptions();
        }

        function addOneDay(dateValue){
            const d = new Date(`${dateValue}T00:00:00`);
            d.setDate(d.getDate() + 1);
            return localDateValue(d);
        }

        function updateReturnMin(){
            if(pickupDate.value){
                const minReturn = addOneDay(pickupDate.value);
                returnDate.min = minReturn;
                if(returnDate.value && returnDate.value < minReturn){
                    returnDate.value = "";
                }
            }
        }

        function validate(){
            clearError(errorBox);
            [pickupDate,pickupTime,returnDate,returnTime].forEach(input=>input.classList.remove("error"));

            syncReturnTime();

            const todayValue = localDateValue();
            if(pickupDate.value && pickupDate.value < todayValue){
                pickupDate.value = "";
                pickupDate.classList.add("error");
                showError(errorBox, "Pickup date cannot be earlier than today.");
                return false;
            }

            if(returnDate.value && returnDate.value < todayValue){
                returnDate.value = "";
                returnDate.classList.add("error");
                showError(errorBox, "Return date cannot be earlier than today.");
                return false;
            }

            updateReturnMin();

            if(!pickupDate.value || !pickupTime.value || !returnDate.value || !returnTime.value){
                return true;
            }

            const pickup = new Date(`${pickupDate.value}T${pickupTime.value}`);
            const returned = new Date(`${returnDate.value}T${returnTime.value}`);

            if(pickup < minimumPickupDateTime()){
                pickupDate.classList.add("error");
                pickupTime.classList.add("error");
                showError(errorBox, "Pickup time must be at least 1 hour from now.");
                return false;
            }

            if(returned <= pickup){
                returnDate.value = "";
                returnDate.classList.add("error");
                showError(errorBox, "Return date must be later than pickup date. Invalid date is not allowed.");
                return false;
            }

            return true;
        }

        pickupDate.addEventListener("change", () => {
            refreshMinimumPickupOptions();
            validate();
        });

        if(pickupHour){
            pickupHour.addEventListener("change", () => {
                refreshMinimumPickupOptions();
                validate();
            });
        }

        if(pickupMinute){
            pickupMinute.addEventListener("change", () => {
                refreshMinimumPickupOptions();
                validate();
            });
        }

        pickupTime.addEventListener("change", () => {
            refreshMinimumPickupOptions();
            validate();
        });

        returnDate.addEventListener("change", validate);
        returnTime.addEventListener("change", validate);

        refreshMinimumPickupOptions();

        form.addEventListener("submit", event=>{
            refreshMinimumPickupOptions();
            if(!validate()){
                event.preventDefault();
            }
        });
    }

    const avatarBtn = document.getElementById("avatarBtn");
    const dropdown = document.getElementById("profileDropdown");

    if(avatarBtn && dropdown){
        avatarBtn.addEventListener("click", event=>{
            event.stopPropagation();
            dropdown.classList.toggle("show");
        });

        document.addEventListener("click", ()=>{
            dropdown.classList.remove("show");
        });
    }

    const modifyTripBtn = document.getElementById("modifyTripBtn");
    const availableSearchForm = document.getElementById("availableSearchForm");

    const tripCard = document.getElementById("tripCard");

    modifyTripBtn?.addEventListener("click", ()=>{
        const isClosed = tripCard.classList.contains("search-closed");

        tripCard.classList.toggle("search-closed", !isClosed);
        tripCard.classList.toggle("search-open", isClosed);

        modifyTripBtn.textContent = isClosed ? "Hide Search" : "Modify Search";
    });

    const pickupState = document.getElementById("pickupState");
    const pickupLocation = document.getElementById("pickupLocation");
    const dropoffLocation = document.getElementById("dropoffLocation");

    pickupState?.addEventListener("change", ()=>{
        filterLocations(pickupState, pickupLocation, dropoffLocation);
    });

    setupTimeCombo({
        hour:"pickupHour",
        minute:"pickupMinute",
        pickupHidden:"pickupTime",
        returnHidden:"returnTime",
        display:"returnTimeDisplay"
    });

    setupTripValidation({
        form:"availableSearchForm",
        pickupDate:"pickupDate",
        pickupTime:"pickupTime",
        returnDate:"returnDate",
        returnTime:"returnTime",
        error:"tripError"
    });

    const advancedToggle = document.getElementById("advancedToggle");
    const advancedFilter = document.getElementById("advancedFilter");

    advancedToggle?.addEventListener("click", ()=>{
        advancedFilter.classList.toggle("show");
        advancedToggle.innerHTML = advancedFilter.classList.contains("show")
            ? '<i class="fa-solid fa-xmark"></i><span>Hide Filters</span>'
            : '<i class="fa-solid fa-sliders"></i><span>More Filters</span>';
    });

    const minPrice = document.getElementById("minPrice");
    const maxPrice = document.getElementById("maxPrice");
    const filterForm = document.getElementById("filterForm");

    function syncPrice(){
        const min = parseFloat(minPrice.value || 0);
        maxPrice.min = min;

        if(maxPrice.value !== "" && parseFloat(maxPrice.value) < min){
            maxPrice.value = "";
        }
    }

    minPrice?.addEventListener("input", syncPrice);
    maxPrice?.addEventListener("input", syncPrice);

    filterForm?.addEventListener("submit", event=>{
        syncPrice();
        if(minPrice.value !== "" && maxPrice.value !== "" && parseFloat(maxPrice.value) < parseFloat(minPrice.value)){
            event.preventDefault();
            alert("Max price cannot be lower than Min price.");
        }
    });

    const modal = document.getElementById("availabilityModal");
    const closeModal = document.getElementById("closeModal");
    const modalCarName = document.getElementById("modalCarName");
    const modalCarId = document.getElementById("modalCarId");
    const modalState = document.getElementById("modalState");
    const modalPickup = document.getElementById("modalPickup");
    const modalDropoff = document.getElementById("modalDropoff");

    document.querySelectorAll(".check-btn").forEach(button=>{
        button.addEventListener("click", ()=>{
            modalCarName.textContent = "Check availability for " + button.dataset.carName;
            modalCarId.value = button.dataset.carId;
            modal.classList.add("show");
        });
    });

    closeModal?.addEventListener("click", ()=>modal.classList.remove("show"));

    modal?.addEventListener("click", event=>{
        if(event.target === modal) modal.classList.remove("show");
    });

    modalState?.addEventListener("change", ()=>{
        filterLocations(modalState, modalPickup, modalDropoff);
    });

    setupTimeCombo({
        hour:"modalPickupHour",
        minute:"modalPickupMinute",
        pickupHidden:"modalPickupTime",
        returnHidden:"modalReturnTime",
        display:"modalReturnTimeDisplay"
    });

    setupTripValidation({
        form:"availabilityForm",
        pickupDate:"modalPickupDate",
        pickupTime:"modalPickupTime",
        returnDate:"modalReturnDate",
        returnTime:"modalReturnTime",
        error:"modalError"
    });

    if(window.location.hash === "#catalogueResults"){
        setTimeout(()=>{
            document.getElementById("catalogueResults")?.scrollIntoView({block:"start"});
        },80);
    }

    document.querySelectorAll(".car-carousel").forEach(carousel => {
        const images = [...carousel.querySelectorAll(".carousel-img")];
        const dots = [...carousel.querySelectorAll(".carousel-dot")];
        const prev = carousel.querySelector(".carousel-prev");
        const next = carousel.querySelector(".carousel-next");
        let current = 0;

        if (images.length <= 1) return;

        function showImage(index) {
            current = (index + images.length) % images.length;

            images.forEach((img, i) => {
                img.classList.toggle("active", i === current);
            });

            dots.forEach((dot, i) => {
                dot.classList.toggle("active", i === current);
            });
        }

        prev?.addEventListener("click", event => {
            event.preventDefault();
            event.stopPropagation();
            showImage(current - 1);
        });

        next?.addEventListener("click", event => {
            event.preventDefault();
            event.stopPropagation();
            showImage(current + 1);
        });

        dots.forEach(dot => {
            dot.addEventListener("click", event => {
                event.preventDefault();
                event.stopPropagation();
                showImage(parseInt(dot.dataset.index, 10));
            });
        });
    });


    /* ===== AJAX Check Availability In Catalogue Modal ===== */
    const availabilityResult = document.getElementById("availabilityResult");
    const availabilityFormAjax = document.getElementById("availabilityForm");
    const checkNowBtn = document.getElementById("checkNowBtn");

    function resetAvailabilityResult(){
        if(!availabilityResult) return;
        availabilityResult.className = "availability-result full";
        availabilityResult.innerHTML = "";
    }

    function formatMoney(value){
        const amount = Number(value || 0);
        return "RM " + amount.toLocaleString("en-MY", {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    function forceModalTimeSync(){
        const hour = document.getElementById("modalPickupHour");
        const minute = document.getElementById("modalPickupMinute");
        const pickupHidden = document.getElementById("modalPickupTime");
        const returnHidden = document.getElementById("modalReturnTime");
        const display = document.getElementById("modalReturnTimeDisplay");
        if(hour && minute && pickupHidden && returnHidden && hour.value !== "" && minute.value !== ""){
            const selectedTime = `${hour.value}:${minute.value}`;
            pickupHidden.value = selectedTime;
            returnHidden.value = selectedTime;
            if(display) display.querySelector("span").textContent = formatFixedTimeLabel(selectedTime);
        }
    }

    function buildAvailabilityQuery(extra = {}){
        if(!availabilityFormAjax) return "";
        forceModalTimeSync();
        const params = new URLSearchParams(new FormData(availabilityFormAjax));

        if(!Object.prototype.hasOwnProperty.call(extra, "car_id")){
            params.delete("car_id");
        }

        Object.entries(extra).forEach(([key,value])=>{
            if(value !== undefined && value !== null && value !== "") params.set(key, value);
        });

        return params.toString();
    }

    function renderAvailableResult(data){
        const cartQ = buildAvailabilityQuery({car_id:data.car_id});
        const detailQ = buildAvailabilityQuery({car_id:data.car_id});
        availabilityResult.className = "availability-result full show success";
        availabilityResult.innerHTML = `
            <div class="availability-status">
                <div class="status-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <h3>Available for your trip</h3>
                    <p><strong>${data.car_name}</strong> is available for your selected date and location.</p>
                </div>
            </div>

            <div class="availability-detail-grid compact-result-grid">
                <div class="availability-detail"><span>Pickup</span><strong>${data.pickup_location_name || "-"}</strong></div>
                <div class="availability-detail"><span>Drop-off</span><strong>${data.dropoff_location_name || "-"}</strong></div>
                <div class="availability-detail wide"><span>Rental Period & Days</span><strong>${data.pickup_label} → ${data.return_label} ; ${data.rental_days} day(s)</strong></div>
                <div class="availability-detail"><span>Price Per Day</span><strong>${formatMoney(data.price_per_day)}</strong></div>
                <div class="availability-detail"><span>Estimated Rental Total</span><strong>${formatMoney(data.estimated_total)}</strong><small>Car rental price only</small></div>
            </div>

            <div class="availability-actions three">
                <a class="btn btn-blue" href="add_to_cart.php?${cartQ}">
                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                </a>
                <a class="btn btn-white" href="car_details.php?${detailQ}">
                    <i class="fa-solid fa-circle-info"></i> View Details
                </a>
                <a class="btn btn-white" href="available_cars.php?${buildAvailabilityQuery()}">
                    <i class="fa-solid fa-layer-group"></i> View Cars for This Trip
                </a>
            </div>
        `;
    }

    function renderUnavailableResult(data){
        const allQ = buildAvailabilityQuery();
        const similarQ = buildAvailabilityQuery({category:data.category_name || ""});

        availabilityResult.className = "availability-result full show danger";
        availabilityResult.innerHTML = `
            <div class="availability-status">
                <div class="status-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                <div>
                    <h3>Not Available</h3>
                    <p><strong>${data.car_name || "This car"}</strong> is not available for your selected date and location. It may already be booked or unavailable in the selected state during your rental period.</p>
                </div>
            </div>

            <div class="availability-actions three">
                <button class="btn btn-white" type="button" id="chooseAnotherDateBtn">
                    <i class="fa-solid fa-calendar-days"></i> Choose Another Date
                </button>
                <a class="btn btn-blue" href="available_cars.php?${similarQ}">
                    <i class="fa-solid fa-car-side"></i> Search Similar Cars
                </a>
                <a class="btn btn-white" href="available_cars.php?${allQ}">
                    <i class="fa-solid fa-layer-group"></i> View Cars for This Trip
                </a>
            </div>
        `;

        const chooseBtn = document.getElementById("chooseAnotherDateBtn");
        if(chooseBtn){
            chooseBtn.addEventListener("click", ()=>{
                resetAvailabilityResult();
                const firstInput = document.getElementById("modalPickupDate");
                if(firstInput) firstInput.focus();
            });
        }
    }

    if(availabilityFormAjax){
        availabilityFormAjax.addEventListener("submit", async (event)=>{
            event.preventDefault();

            if(typeof clearError === "function") clearError(document.getElementById("modalError"));
            resetAvailabilityResult();

            forceModalTimeSync();
            const pickupDate = document.getElementById("modalPickupDate");
            const pickupTime = document.getElementById("modalPickupTime");
            const returnDate = document.getElementById("modalReturnDate");
            const returnTime = document.getElementById("modalReturnTime");

            if(pickupTime && returnTime){
                returnTime.value = pickupTime.value || returnTime.value;
            }

            if(pickupDate && pickupTime && returnDate && returnTime && pickupDate.value && pickupTime.value && returnDate.value && returnTime.value){
                const todayValue = localDateValue();
                if(pickupDate.value < todayValue){
                    pickupDate.classList.add("error");
                    showError(document.getElementById("modalError"), "Pickup date cannot be earlier than today.");
                    return;
                }
                const minReturnDate = (()=>{ const d = new Date(`${pickupDate.value}T00:00:00`); d.setDate(d.getDate() + 1); return localDateValue(d); })();
                if(returnDate.value < minReturnDate){
                    returnDate.classList.add("error");
                    showError(document.getElementById("modalError"), "Return date must be at least the next day. Past dates are not allowed.");
                    return;
                }
                const pickup = new Date(`${pickupDate.value}T${pickupTime.value}`);
                const returned = new Date(`${returnDate.value}T${returnTime.value}`);

                if(pickup < minimumPickupDateTime()){
                    pickupDate.classList.add("error");
                    pickupTime.classList.add("error");
                    if(typeof showError === "function"){
                        showError(document.getElementById("modalError"), "Pickup time must be at least 1 hour from now.");
                    }
                    return;
                }

                if(returned <= pickup){
                    if(typeof showError === "function"){
                        showError(document.getElementById("modalError"), "Return date/time must be later than pickup date/time.");
                    }
                    return;
                }
            }

            const originalText = checkNowBtn ? checkNowBtn.innerHTML : "";
            if(checkNowBtn){
                checkNowBtn.disabled = true;
                checkNowBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Checking...`;
            }

            try{
                forceModalTimeSync();
                const submitData = new FormData(availabilityFormAjax);
                submitData.set("pickup_time", document.getElementById("modalPickupTime")?.value || "");
                submitData.set("return_time", document.getElementById("modalReturnTime")?.value || document.getElementById("modalPickupTime")?.value || "");

                const response = await fetch("check_availability.php", {
                    method:"POST",
                    body:submitData,
                    headers:{"X-Requested-With":"XMLHttpRequest"}
                });

                const data = await response.json();

                if(!data.ok){
                    if(typeof showError === "function"){
                        showError(document.getElementById("modalError"), data.message || "Unable to check availability.");
                    }
                    return;
                }

                if(data.available){
                    renderAvailableResult(data);
                }else{
                    renderUnavailableResult(data);
                }
            }catch(error){
                if(typeof showError === "function"){
                    showError(document.getElementById("modalError"), "System error. Please try again.");
                }
            }finally{
                if(checkNowBtn){
                    checkNowBtn.disabled = false;
                    checkNowBtn.innerHTML = originalText;
                }
            }
        });
    }

    document.querySelectorAll(".check-btn").forEach(btn=>{
        btn.addEventListener("click", ()=>{
            resetAvailabilityResult();
        });
    });

    </script>
    </body>
    </html>
