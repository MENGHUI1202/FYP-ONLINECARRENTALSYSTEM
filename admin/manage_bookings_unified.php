<?php
$admin_id = current_admin_id();

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function booking_badge(string $status): string
{
    $status = strtolower(trim($status));
    $classes = [
        'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
        'approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'active' => 'bg-blue-100 text-blue-700 border-blue-200',
        'completed' => 'bg-slate-100 text-slate-700 border-slate-200',
        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
        'rejected' => 'bg-red-100 text-red-700 border-red-200',
    ];
    $labels = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
    ];
    $class = $classes[$status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
    return '<span class="px-3 py-1 rounded-lg border text-[10px] font-black uppercase tracking-widest ' . $class . '">' . h($labels[$status] ?? $status) . '</span>';
}

function review_booking(mysqli $conn, int $booking_id, int $admin_id, string $status, string $note): void
{
    $routine = $conn->prepare(
        "SELECT COUNT(*) AS c
         FROM information_schema.ROUTINES
         WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = 'sp_admin_review_booking'"
    );
    $routine->execute();
    $has_procedure = ((int)($routine->get_result()->fetch_assoc()['c'] ?? 0)) > 0;

    if ($has_procedure) {
        $stmt = $conn->prepare("CALL sp_admin_review_booking(?, ?, ?, ?)");
        $stmt->bind_param('iiss', $booking_id, $admin_id, $status, $note);
        $stmt->execute();
        return;
    }

    $conn->begin_transaction();
    try {
        if (in_array($status, ['approved', 'active'], true)) {
            $stmt = $conn->prepare(
                "UPDATE car_units cu
                 INNER JOIN booking_items bi ON bi.unit_id = cu.unit_id
                 SET cu.current_status = 'booked', cu.reserved_booking_id = ?
                 WHERE bi.booking_id = ? AND cu.current_status = 'available'"
            );
            $stmt->bind_param('ii', $booking_id, $booking_id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare(
                "UPDATE car_units cu
                 INNER JOIN booking_items bi ON bi.unit_id = cu.unit_id
                 SET cu.current_status = 'available', cu.reserved_booking_id = NULL
                 WHERE bi.booking_id = ? AND cu.reserved_booking_id = ?"
            );
            $stmt->bind_param('ii', $booking_id, $booking_id);
            $stmt->execute();
        }

        $stmt = $conn->prepare(
            "UPDATE bookings
             SET booking_status = ?,
                 admin_note = ?,
                 reviewed_by_admin_id = NULLIF(?, 0),
                 reviewed_at = NOW(),
                 approved_at = IF(? IN ('approved', 'active') AND approved_at IS NULL, NOW(), approved_at),
                 rejected_at = IF(? = 'rejected', NOW(), rejected_at),
                 cancelled_at = IF(? = 'cancelled', NOW(), cancelled_at)
             WHERE booking_id = ?"
        );
        $stmt->bind_param('ssisssi', $status, $note, $admin_id, $status, $status, $status, $booking_id);
        $stmt->execute();
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

if (isset($_GET['action'], $_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    $requested = strtolower(trim($_GET['action']));
    $map = [
        'approve' => 'approved',
        'approved' => 'approved',
        'active' => 'active',
        'complete' => 'completed',
        'completed' => 'completed',
        'cancel' => 'cancelled',
        'cancelled' => 'cancelled',
        'reject' => 'rejected',
        'rejected' => 'rejected',
    ];
    $status = $map[$requested] ?? '';
    $note = trim($_POST['admin_notes'] ?? '');

    if ($booking_id > 0 && $status !== '') {
        try {
            review_booking($conn, $booking_id, $admin_id, $status, $note);
            header('Location: manage_bookings.php?msg=updated');
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$stats = [
    'pending' => 0,
    'approved' => 0,
    'active' => 0,
    'completed' => 0,
];
$stats_res = $conn->query("SELECT booking_status, COUNT(*) AS c FROM bookings GROUP BY booking_status");
while ($row = $stats_res->fetch_assoc()) {
    $stats[strtolower($row['booking_status'])] = (int)$row['c'];
}

$bookings = $conn->query(
    "SELECT
        b.booking_id,
        b.booking_reference,
        b.booking_status,
        b.payment_status,
        b.payment_method,
        b.grand_total,
        b.total_amount,
        b.admin_note,
        b.created_at,
        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,
        u.license_number,
        u.kyc_status
     FROM bookings b
     LEFT JOIN users u ON u.user_id = b.user_id
     ORDER BY b.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Operations | Fleet Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Plus Jakarta Sans", sans-serif; background: #f8fafc; }
        .card { background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 8px 20px rgba(15, 23, 42, .04); }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include('include/sidebar.php'); ?>

    <main class="ml-64 p-10 w-full max-w-[1600px] mx-auto">
        <header class="mb-8 flex justify-between items-end border-b border-slate-200 pb-6">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Operations Control</h1>
                <p class="text-slate-500 mt-1 font-medium">Unified customer bookings with admin approval and fleet-unit sync.</p>
            </div>
            <a href="../NEW_CAR_RENTAL_SYSTEM/homepage.php" target="_blank" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest">
                <i class="fas fa-arrow-up-right-from-square mr-2"></i>Customer Site
            </a>
        </header>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 font-bold"><?= h($error) ?></div>
        <?php elseif (isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold">Booking updated and inventory synchronized.</div>
        <?php endif; ?>

        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="card rounded-2xl p-5"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending</p><h4 class="text-3xl font-black text-amber-500 mt-1"><?= $stats['pending'] ?></h4></div>
            <div class="card rounded-2xl p-5"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Approved</p><h4 class="text-3xl font-black text-emerald-500 mt-1"><?= $stats['approved'] ?></h4></div>
            <div class="card rounded-2xl p-5"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active</p><h4 class="text-3xl font-black text-blue-500 mt-1"><?= $stats['active'] ?></h4></div>
            <div class="card rounded-2xl p-5"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Completed</p><h4 class="text-3xl font-black text-slate-700 mt-1"><?= $stats['completed'] ?></h4></div>
        </div>

        <div class="card rounded-2xl overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                <h2 class="font-black text-slate-800 uppercase tracking-widest text-xs"><i class="fas fa-calendar-check text-blue-500 mr-2"></i>Bookings</h2>
                <input id="bookingSearch" class="w-80 px-4 py-2 border border-slate-200 rounded-xl text-sm font-bold outline-none focus:border-blue-400" placeholder="Search booking, customer, email...">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm data-table">
                    <thead class="bg-slate-50 text-[10px] text-slate-400 uppercase tracking-widest">
                        <tr>
                            <th class="px-6 py-4">Booking</th>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4">Vehicle Units</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <?php
                        $booking_id = (int)$booking['booking_id'];
                        $items_stmt = $conn->prepare(
                            "SELECT bi.*, c.car_name, c.main_image, br.brand_name, cu.plate_number, cu.current_status AS unit_status
                             FROM booking_items bi
                             LEFT JOIN cars c ON c.car_id = bi.car_id
                             LEFT JOIN brands br ON br.brand_id = c.brand_id
                             LEFT JOIN car_units cu ON cu.unit_id = bi.unit_id
                             WHERE bi.booking_id = ?"
                        );
                        $items_stmt->bind_param('i', $booking_id);
                        $items_stmt->execute();
                        $items = $items_stmt->get_result();
                        $status = strtolower((string)$booking['booking_status']);
                        ?>
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-6 py-5 align-top">
                                <div class="font-black text-slate-900">#<?= h($booking['booking_reference']) ?></div>
                                <div class="text-xs text-slate-400 font-bold mt-1"><?= h(date('d M Y, H:i', strtotime($booking['created_at']))) ?></div>
                                <?php if (!empty($booking['admin_note'])): ?>
                                    <div class="mt-2 text-xs text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2"><?= h($booking['admin_note']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 align-top">
                                <div class="font-black text-slate-800"><?= h($booking['customer_name'] ?: 'Guest') ?></div>
                                <div class="text-xs text-slate-500 font-bold mt-1"><?= h($booking['customer_email']) ?></div>
                                <div class="text-xs text-slate-400 font-bold mt-1"><?= h($booking['customer_phone']) ?></div>
                                <div class="text-[10px] font-black text-slate-400 uppercase mt-2">KYC: <?= h($booking['kyc_status'] ?: 'Unverified') ?></div>
                            </td>
                            <td class="px-6 py-5 align-top">
                                <div class="space-y-3">
                                <?php while ($item = $items->fetch_assoc()): ?>
                                    <div class="flex gap-3 items-center">
                                        <img src="../<?= h($item['main_image'] ?: 'assets/img/FYP CARRENTAL BG.jpeg') ?>" class="w-14 h-12 rounded-xl object-cover border border-slate-200">
                                        <div>
                                            <div class="font-black text-slate-800"><?= h(trim(($item['brand_name'] ?? '') . ' ' . ($item['car_name'] ?? ''))) ?></div>
                                            <div class="text-[10px] text-slate-400 font-bold uppercase">
                                                Unit <?= h($item['unit_id'] ?: 'N/A') ?><?= $item['plate_number'] ? ' / ' . h($item['plate_number']) : '' ?> / <?= h($item['unit_status'] ?: 'unassigned') ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-bold"><?= h($item['start_datetime']) ?> to <?= h($item['end_datetime']) ?></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                </div>
                            </td>
                            <td class="px-6 py-5 align-top font-black text-slate-900">RM <?= number_format((float)$booking['grand_total'], 2) ?></td>
                            <td class="px-6 py-5 align-top text-center"><?= booking_badge($status) ?></td>
                            <td class="px-6 py-5 align-top text-right">
                                <div class="flex flex-col gap-2 items-end">
                                    <?php if ($status === 'pending'): ?>
                                        <a href="?action=approve&id=<?= $booking_id ?>" onclick="return confirm('Approve this booking and reserve the selected unit?')" class="px-4 py-2 bg-emerald-500 text-white rounded-xl text-xs font-black uppercase tracking-widest">Approve</a>
                                        <form method="POST" action="?action=reject&id=<?= $booking_id ?>" class="flex gap-2">
                                            <input name="admin_notes" class="w-48 px-3 py-2 bg-red-50 border border-red-200 rounded-xl text-xs font-bold" placeholder="Reject reason">
                                            <button class="px-4 py-2 bg-red-500 text-white rounded-xl text-xs font-black uppercase tracking-widest" onclick="return confirm('Reject this booking?')">Reject</button>
                                        </form>
                                    <?php elseif ($status === 'approved'): ?>
                                        <a href="?action=active&id=<?= $booking_id ?>" onclick="return confirm('Confirm key handover?')" class="px-4 py-2 bg-blue-500 text-white rounded-xl text-xs font-black uppercase tracking-widest">Handover</a>
                                        <a href="?action=cancel&id=<?= $booking_id ?>" onclick="return confirm('Cancel and release the car unit?')" class="px-4 py-2 bg-white border border-red-200 text-red-600 rounded-xl text-xs font-black uppercase tracking-widest">Cancel</a>
                                    <?php elseif ($status === 'active'): ?>
                                        <a href="?action=complete&id=<?= $booking_id ?>" onclick="return confirm('Mark returned and release the car unit?')" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest">Complete</a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 font-bold">No action</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('bookingSearch').addEventListener('keyup', function () {
            const value = this.value.toLowerCase();
            document.querySelectorAll('.data-table tbody tr').forEach((row) => {
                row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
