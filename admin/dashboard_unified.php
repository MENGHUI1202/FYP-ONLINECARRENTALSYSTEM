<?php
function esc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$cars_count = (int)($conn->query("SELECT COUNT(*) AS c FROM cars WHERE COALESCE(is_deleted, 0) = 0")->fetch_assoc()['c'] ?? 0);
$available_units = (int)($conn->query("SELECT COUNT(*) AS c FROM car_units WHERE current_status = 'available'")->fetch_assoc()['c'] ?? 0);
$booked_units = (int)($conn->query("SELECT COUNT(*) AS c FROM car_units WHERE current_status = 'booked'")->fetch_assoc()['c'] ?? 0);
$users_count = (int)($conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'customer'")->fetch_assoc()['c'] ?? 0);
$revenue = (float)($conn->query("SELECT COALESCE(SUM(grand_total), 0) AS s FROM bookings WHERE booking_status IN ('approved', 'active', 'completed')")->fetch_assoc()['s'] ?? 0);
$pending_bookings = (int)($conn->query("SELECT COUNT(*) AS c FROM bookings WHERE booking_status = 'pending'")->fetch_assoc()['c'] ?? 0);
$pending_kyc_count = (int)($conn->query("SELECT COUNT(*) AS c FROM user_documents WHERE verification_status = 'Pending Verification'")->fetch_assoc()['c'] ?? 0);
$low_stock_cars = (int)($conn->query("SELECT COUNT(*) AS c FROM v_car_stock WHERE available_units = 0")->fetch_assoc()['c'] ?? 0);

$recent = $conn->query(
    "SELECT b.booking_reference, b.booking_status, b.grand_total, b.created_at, u.name AS customer_name,
        (SELECT c.car_name
         FROM booking_items bi
         INNER JOIN cars c ON c.car_id = bi.car_id
         WHERE bi.booking_id = b.booking_id
         LIMIT 1) AS car_name
     FROM bookings b
     LEFT JOIN users u ON u.user_id = b.user_id
     ORDER BY b.created_at DESC
     LIMIT 8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Fleet Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>body { font-family: "Plus Jakarta Sans", sans-serif; background: #f8fafc; }</style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include('include/sidebar.php'); ?>

    <main class="ml-64 p-10 w-full max-w-[1500px] mx-auto">
        <header class="mb-8 border-b border-slate-200 pb-6">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Unified Dashboard</h1>
            <p class="text-slate-500 mt-1 font-medium">Admin and customer data now read from the same database.</p>
        </header>

        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Cars</p><h4 class="text-3xl font-black text-slate-900 mt-1"><?= $cars_count ?></h4></div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Available Units</p><h4 class="text-3xl font-black text-emerald-500 mt-1"><?= $available_units ?></h4></div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Booked Units</p><h4 class="text-3xl font-black text-blue-500 mt-1"><?= $booked_units ?></h4></div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Customers</p><h4 class="text-3xl font-black text-slate-900 mt-1"><?= $users_count ?></h4></div>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Revenue</p><h4 class="text-2xl font-black text-emerald-600 mt-1">RM <?= number_format($revenue, 2) ?></h4></div>
            <a href="manage_bookings.php" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm block hover:border-amber-300"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending Bookings</p><h4 class="text-3xl font-black text-amber-500 mt-1"><?= $pending_bookings ?></h4></a>
            <a href="kyc_management.php" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm block hover:border-red-300"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending KYC</p><h4 class="text-3xl font-black text-red-500 mt-1"><?= $pending_kyc_count ?></h4></a>
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Out of Stock Models</p><h4 class="text-3xl font-black text-slate-900 mt-1"><?= $low_stock_cars ?></h4></div>
        </div>

        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-700"><i class="fas fa-clock text-blue-500 mr-2"></i>Recent Bookings</h2>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Reference</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Car</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php while ($row = $recent->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4 font-black text-slate-900">#<?= esc($row['booking_reference']) ?></td>
                        <td class="px-6 py-4 font-bold text-slate-700"><?= esc($row['customer_name'] ?: 'Guest') ?></td>
                        <td class="px-6 py-4 text-slate-500 font-bold"><?= esc($row['car_name'] ?: 'N/A') ?></td>
                        <td class="px-6 py-4 text-slate-500 font-black uppercase text-xs"><?= esc($row['booking_status']) ?></td>
                        <td class="px-6 py-4 text-right font-black">RM <?= number_format((float)$row['grand_total'], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
