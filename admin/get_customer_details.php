<?php
include('../includes/config.php');
include('../includes/auth.php');

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

function json_error($message)
{
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function fetch_all_prepared(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function fetch_one_prepared(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $rows = fetch_all_prepared($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

function customer_file_url(?string $path): ?string
{
    $path = trim((string)$path);
    if ($path === '') {
        return null;
    }
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    $path = str_replace('\\', '/', $path);
    if (str_starts_with($path, 'assets/')) {
        return '../NEW_CAR_RENTAL_SYSTEM/' . $path;
    }
    if (str_starts_with($path, 'NEW_CAR_RENTAL_SYSTEM/')) {
        return '../' . $path;
    }
    return '../' . ltrim($path, '/');
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    json_error('Invalid customer ID.');
}

$user = fetch_one_prepared(
    $conn,
    "SELECT
        user_id, id, name, email, phone, ic_number, license_number,
        license_expiry_date, address, date_of_birth, kyc_status,
        created_at, updated_at, profile_picture
     FROM users
     WHERE user_id = ?
     LIMIT 1",
    'i',
    [$user_id]
);

if (!$user) {
    json_error('Customer not found.');
}

$documents = [];
if (db_table_exists($conn, 'user_documents')) {
    $documents = fetch_all_prepared(
        $conn,
        "SELECT document_id, user_id, document_type, file_path, verification_status, admin_note, reviewed_at, uploaded_at
         FROM user_documents
         WHERE user_id = ?
         ORDER BY uploaded_at DESC, document_id DESC",
        'i',
        [$user_id]
    );

    foreach ($documents as &$document) {
        $document['display_url'] = customer_file_url($document['file_path'] ?? '');
        $extension = strtolower(pathinfo((string)($document['file_path'] ?? ''), PATHINFO_EXTENSION));
        $document['is_image'] = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }
    unset($document);
}

$bookings = [];
if (db_table_exists($conn, 'bookings')) {
    if (db_table_exists($conn, 'booking_items') && db_table_exists($conn, 'cars')) {
        $locationSelect = "
            GROUP_CONCAT(DISTINCT bi.pickup_location SEPARATOR ', ') AS pickup_locations,
            GROUP_CONCAT(DISTINCT bi.dropoff_location SEPARATOR ', ') AS dropoff_locations";
        $locationJoin = "";

        if (db_table_exists($conn, 'rental_locations')) {
            $locationSelect = "
                GROUP_CONCAT(DISTINCT COALESCE(pl.location_name, bi.pickup_location) SEPARATOR ', ') AS pickup_locations,
                GROUP_CONCAT(DISTINCT COALESCE(dl.location_name, bi.dropoff_location) SEPARATOR ', ') AS dropoff_locations";
            $locationJoin = "
                LEFT JOIN rental_locations pl ON pl.location_id = bi.pickup_location
                LEFT JOIN rental_locations dl ON dl.location_id = bi.dropoff_location";
        }

        $bookings = fetch_all_prepared(
            $conn,
            "SELECT
                b.booking_id, b.booking_reference, b.booking_status, b.payment_status,
                b.payment_method, b.total_amount, b.grand_total, b.promo_code,
                b.voucher_discount, b.created_at, b.booking_date,
                GROUP_CONCAT(DISTINCT c.car_name SEPARATOR ', ') AS car_names,
                MIN(bi.start_datetime) AS pickup_datetime,
                MAX(bi.end_datetime) AS return_datetime,
                $locationSelect
             FROM bookings b
             LEFT JOIN booking_items bi ON bi.booking_id = b.booking_id
             LEFT JOIN cars c ON c.car_id = bi.car_id
             $locationJoin
             WHERE b.user_id = ?
             GROUP BY b.booking_id
             ORDER BY b.created_at DESC, b.booking_id DESC",
            'i',
            [$user_id]
        );
    } else {
        $bookings = fetch_all_prepared(
            $conn,
            "SELECT *
             FROM bookings
             WHERE user_id = ?
             ORDER BY created_at DESC, booking_id DESC",
            'i',
            [$user_id]
        );
    }
}

$payments = [];
if (db_table_exists($conn, 'payments')) {
    $payments = fetch_all_prepared(
        $conn,
        "SELECT p.*, b.booking_reference
         FROM payments p
         LEFT JOIN bookings b ON b.booking_id = p.booking_id
         WHERE p.user_id = ?
         ORDER BY COALESCE(p.payment_date, p.created_at) DESC, p.payment_id DESC",
        'i',
        [$user_id]
    );
}

$promo_usage = [];
if (db_table_exists($conn, 'promo_code_usage')) {
    $promo_usage = fetch_all_prepared(
        $conn,
        "SELECT pcu.*, pc.promo_code, pc.promo_name, pc.discount_percent, b.booking_reference
         FROM promo_code_usage pcu
         LEFT JOIN promo_codes pc ON pc.id = pcu.promo_id
         LEFT JOIN bookings b ON b.booking_id = pcu.booking_id
         WHERE pcu.user_id = ?
         ORDER BY COALESCE(pcu.used_at, pcu.created_at) DESC",
        'i',
        [$user_id]
    );
}

$summary = [
    'booking_count' => count($bookings),
    'total_spent' => 0,
];
foreach ($bookings as $booking) {
    $summary['total_spent'] += (float)($booking['grand_total'] ?? $booking['total_amount'] ?? 0);
}

echo json_encode([
    'ok' => true,
    'user' => $user,
    'documents' => $documents,
    'bookings' => $bookings,
    'payments' => $payments,
    'promo_usage' => $promo_usage,
    'summary' => $summary,
]);
?>
