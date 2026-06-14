<?php
include('../includes/config.php');
include('../includes/auth.php');
if(!isset($_SESSION['admin_logged_in'])) { header("Location: index.php"); exit; }

// --- 1. 逻辑处理 ---
// A. 删除用户
if (isset($_GET['delete'])) {
    if (($_SESSION['role'] ?? '') !== 'super_admin') {
        die("Access Denied: You do not have permission to delete users.");
    }
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_users.php?msg=deleted"); exit;
}

// B. 编辑用户
if (isset($_POST['edit_user'])) {
    if (($_SESSION['role'] ?? '') !== 'super_admin') {
        die("Access Denied: You do not have permission to edit users.");
    }
    $id = intval($_POST['user_id']);
    $name = $_POST['fullname']; 
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    // ★★★ 修复1：数据库字段名为 name，不是 fullname ★★★
    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE user_id=?");
    $stmt->bind_param("sssi", $name, $email, $phone, $id);
    
    if ($stmt->execute()) {
        header("Location: manage_users.php?msg=updated"); exit;
    } else {
        die("Error updating user: " . $conn->error);
    }
}

// --- 2. 顶部统计数据 ---
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$new_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetch_assoc()['c'];
$active_rentals = $conn->query("SELECT COUNT(DISTINCT user_id) as c FROM bookings WHERE booking_status IN ('approved','active')")->fetch_assoc()['c'];

// --- 3. 核心查询 ---
$sql = "SELECT u.*, u.user_id AS customer_id,
        COUNT(b.booking_id) as booking_count, 
        COALESCE(SUM(b.grand_total), 0) as total_spent,
        GROUP_CONCAT(DISTINCT b.booking_id SEPARATOR ' ') as booking_ids,
        GROUP_CONCAT(DISTINCT b.booking_reference SEPARATOR ' ') as booking_refs,
        GROUP_CONCAT(DISTINCT b.promo_code SEPARATOR ' ') as used_promo_codes
        FROM users u 
        LEFT JOIN bookings b ON u.user_id = b.user_id 
        GROUP BY u.user_id 
        ORDER BY u.user_id DESC";
$users = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customers | Fleet Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }, colors: { primary: '#3b82f6' } } } }
    </script>
    <style>
        body { background: radial-gradient(circle at top right, #e0e7ff 0%, #f8fafc 40%, #f1f5f9 100%); }
        .glass-card { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 1); box-shadow: 0 10px 40px -10px rgba(226, 232, 240, 0.8); }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include('include/sidebar.php'); ?>
    
    <main class="ml-64 p-10 w-full">
        <header class="mb-10">
            <h1 class="text-3xl font-extrabold tracking-tight">Customer Insights</h1>
            <p class="text-slate-500 mt-1 font-medium">Manage registered users and track their lifetime value.</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="glass-card p-6 rounded-3xl flex items-center gap-5 border-t-4 border-blue-500">
                <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl shadow-sm"><i class="fas fa-users"></i></div>
                <div><h4 class="text-3xl font-black text-slate-800"><?php echo $total_users; ?></h4><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Total Customers</p></div>
            </div>
            <div class="glass-card p-6 rounded-3xl flex items-center gap-5 border-t-4 border-emerald-500">
                <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl shadow-sm"><i class="fas fa-user-plus"></i></div>
                <div><h4 class="text-3xl font-black text-slate-800"><?php echo $new_users; ?></h4><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">New This Month</p></div>
            </div>
            <div class="glass-card p-6 rounded-3xl flex items-center gap-5 border-t-4 border-amber-500">
                <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-2xl shadow-sm"><i class="fas fa-car-side"></i></div>
                <div><h4 class="text-3xl font-black text-slate-800"><?php echo $active_rentals; ?></h4><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Active Rentals</p></div>
            </div>
        </div>

        <div class="glass-card rounded-[2.5rem] overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white/30">
                <h3 class="font-black text-xl text-slate-800">User Directory</h3>
                <div class="relative w-72">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="userSearch" placeholder="Search IC, license, email, phone, order..." class="w-full pl-11 pr-5 py-2.5 bg-white border border-slate-100 focus:border-primary rounded-xl outline-none transition-all font-bold text-sm shadow-sm">
                </div>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-left border-separate border-spacing-y-2" id="userTable">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Customer Profile</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Contact Info</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Lifetime Value</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $users->fetch_assoc()): ?>
                        <?php
                            $editPayload = [
                                'user_id' => (int)$row['customer_id'],
                                'name' => $row['name'] ?? '',
                                'email' => $row['email'] ?? '',
                                'phone' => $row['phone'] ?? ''
                            ];
                            $searchText = implode(' ', [
                                $row['customer_id'] ?? '',
                                $row['name'] ?? '',
                                $row['email'] ?? '',
                                $row['phone'] ?? '',
                                $row['ic_number'] ?? '',
                                $row['license_number'] ?? '',
                                $row['license_expiry_date'] ?? '',
                                $row['kyc_status'] ?? '',
                                $row['address'] ?? '',
                                $row['booking_ids'] ?? '',
                                $row['booking_refs'] ?? '',
                                $row['used_promo_codes'] ?? ''
                            ]);
                        ?>
                        <tr class="bg-white/60 hover:bg-white transition-all rounded-2xl group" data-search="<?php echo htmlspecialchars(strtolower($searchText), ENT_QUOTES); ?>">
                            <td class="px-6 py-5 rounded-l-2xl">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-slate-100 text-slate-500 rounded-full flex items-center justify-center font-black text-lg border-2 border-white shadow-sm">
                                        <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-800 text-lg leading-tight"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">UID: #<?php echo str_pad($row['customer_id'], 4, '0', STR_PAD_LEFT); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-sm font-bold text-slate-600 space-y-1">
                                <div><i class="fas fa-envelope text-slate-300 w-5"></i> <?php echo htmlspecialchars($row['email']); ?></div>
                                <div><i class="fas fa-phone text-slate-300 w-5"></i> <?php echo $row['phone'] ? htmlspecialchars($row['phone']) : '<span class="italic opacity-50">No phone</span>'; ?></div>
                                <div><i class="fas fa-calendar-check text-slate-300 w-5"></i> License Expiry: <?php echo !empty($row['license_expiry_date']) ? htmlspecialchars(date('d M Y', strtotime($row['license_expiry_date']))) : '<span class="italic opacity-50">Not set</span>'; ?></div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="font-black text-slate-800 text-lg">RM <?php echo number_format($row['total_spent']); ?></div>
                                <div class="text-xs font-bold text-slate-400 mt-1"><?php echo $row['booking_count']; ?> Bookings</div>
                            </td>
                            <td class="px-6 py-5">
                                <?php if($row['booking_count'] > 5): ?>
                                    <span class="px-3 py-1.5 bg-gradient-to-r from-amber-200 to-yellow-400 text-amber-900 border border-amber-300 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-sm"><i class="fas fa-crown mr-1"></i> VIP</span>
                                <?php elseif($row['booking_count'] > 0): ?>
                                    <span class="px-3 py-1.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-lg text-[10px] font-black uppercase tracking-widest">Regular</span>
                                <?php else: ?>
                                    <span class="px-3 py-1.5 bg-slate-100 text-slate-500 rounded-lg text-[10px] font-black uppercase tracking-widest">New</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 text-right rounded-r-2xl">
                                <div class="flex justify-end gap-2">
                                    <button onclick="openCustomerDetails(<?php echo (int)$row['customer_id']; ?>)" class="w-10 h-10 bg-white text-slate-600 hover:bg-slate-800 hover:text-white rounded-xl transition-all shadow-sm border border-slate-100" title="View Details"><i class="fas fa-eye"></i></button>
                                    <?php if(($_SESSION['role'] ?? '') == 'super_admin'): ?>
                                        <button onclick='openEditModal(<?php echo json_encode($editPayload); ?>)' class="w-10 h-10 bg-slate-100 text-slate-600 hover:bg-primary hover:text-white rounded-xl transition-all shadow-sm"><i class="fas fa-pen"></i></button>
                                        <a href="?delete=<?php echo $row['customer_id']; ?>" onclick="return confirm('Are you sure? All their bookings will be affected.')" class="w-10 h-10 flex items-center justify-center bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-xl transition-all shadow-sm"><i class="fas fa-trash-alt"></i></a>
                                    <?php else: ?>
                                        <button disabled class="px-4 h-10 bg-slate-50 text-slate-300 rounded-xl cursor-not-allowed text-xs font-bold"><i class="fas fa-lock mr-2"></i> Read Only</button>
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

    <div id="customerDetailModal" class="fixed inset-0 z-[110] hidden flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="glass-card w-full max-w-6xl rounded-[2rem] shadow-2xl relative max-h-[92vh] overflow-hidden flex flex-col">
            <div class="p-7 border-b border-slate-100 flex items-start justify-between bg-white/50">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Customer Details</p>
                    <h3 class="text-2xl font-black text-slate-800">Complete Customer Record</h3>
                </div>
                <button onclick="closeDetailModal()" class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-500 hover:bg-slate-800 hover:text-white transition-all"><i class="fas fa-times"></i></button>
            </div>
            <div id="customerDetailBody" class="p-7 overflow-y-auto bg-slate-50/40"></div>
        </div>
    </div>

    <div id="userModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="glass-card w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl relative">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-black text-slate-800">Edit Customer</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-800 text-2xl transition-colors"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" class="space-y-5">
                <input type="hidden" name="user_id" id="user_id">
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Full Name</label>
                    <input type="text" name="fullname" id="fullname" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-primary outline-none font-bold" required>
                </div>
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Email Address</label>
                    <input type="email" name="email" id="email" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-primary outline-none font-bold" required>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Phone Number</label>
                    <input type="text" name="phone" id="phone" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:border-primary outline-none font-bold" placeholder="e.g. 012-3456789">
                </div>
                
                <div class="pt-4 mt-6 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-2xl font-bold hover:bg-slate-200 transition-all">Cancel</button>
                    <button type="submit" name="edit_user" class="px-8 py-3 bg-primary text-white rounded-2xl font-black shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-all">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const detailModal = document.getElementById('customerDetailModal');
        const detailBody = document.getElementById('customerDetailBody');

        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));

        const fmt = (value) => {
            if (!value) return '<span class="text-slate-300 italic">Not set</span>';
            return escapeHtml(value);
        };

        const money = (value) => {
            const amount = Number(value || 0);
            return `RM ${amount.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        };

        const badge = (value) => {
            const text = String(value || 'Unknown');
            const lower = text.toLowerCase();
            let cls = 'bg-slate-100 text-slate-500 border-slate-200';
            if (lower.includes('approved') || lower.includes('success') || lower.includes('paid') || lower.includes('active') || lower.includes('verified')) cls = 'bg-emerald-50 text-emerald-600 border-emerald-100';
            if (lower.includes('pending') || lower.includes('waiting')) cls = 'bg-amber-50 text-amber-600 border-amber-100';
            if (lower.includes('reject') || lower.includes('fail') || lower.includes('cancel')) cls = 'bg-red-50 text-red-600 border-red-100';
            return `<span class="px-3 py-1.5 rounded-lg border ${cls} text-[10px] font-black uppercase tracking-widest">${escapeHtml(text)}</span>`;
        };

        const infoItem = (label, value, icon) => `
            <div class="bg-white/80 border border-slate-100 rounded-2xl p-4">
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2"><i class="${icon} mr-2"></i>${escapeHtml(label)}</div>
                <div class="text-sm font-extrabold text-slate-700 break-words">${fmt(value)}</div>
            </div>
        `;

        function openEditModal(user) {
            document.getElementById('user_id').value = user.user_id;
            document.getElementById('fullname').value = user.name; 
            document.getElementById('email').value = user.email;
            document.getElementById('phone').value = user.phone ? user.phone : '';
            modal.classList.remove('hidden');
        }
        function closeModal() { modal.classList.add('hidden'); }
        function closeDetailModal() { detailModal.classList.add('hidden'); }

        function renderDocuments(documents) {
            if (!documents.length) {
                return '<div class="bg-white/80 border border-dashed border-slate-200 rounded-2xl p-8 text-center text-sm font-bold text-slate-400">No KYC documents uploaded yet.</div>';
            }
            return `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">${documents.map(doc => {
                const isImage = !!doc.is_image;
                const preview = doc.display_url
                    ? (isImage
                        ? `<a href="${escapeHtml(doc.display_url)}" target="_blank" class="block h-52 rounded-2xl overflow-hidden bg-slate-100 border border-slate-100"><img src="${escapeHtml(doc.display_url)}" class="w-full h-full object-contain bg-white" alt="KYC document"></a>`
                        : `<a href="${escapeHtml(doc.display_url)}" target="_blank" class="h-52 rounded-2xl bg-slate-100 border border-slate-100 flex flex-col items-center justify-center text-slate-500 hover:bg-slate-200 transition-all"><i class="fas fa-file-pdf text-4xl mb-3"></i><span class="text-xs font-black uppercase tracking-widest">Open Document</span></a>`)
                    : '<div class="h-52 rounded-2xl bg-slate-100 border border-slate-100 flex items-center justify-center text-slate-300 font-bold">Missing file path</div>';
                return `
                    <div class="bg-white/80 border border-slate-100 rounded-3xl p-4">
                        ${preview}
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <div>
                                <div class="font-black text-slate-800">${fmt(doc.document_type)}</div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Uploaded: ${fmt(doc.uploaded_at)}</div>
                            </div>
                            ${badge(doc.verification_status)}
                        </div>
                        ${doc.admin_note ? `<div class="mt-3 p-3 bg-slate-50 rounded-2xl text-xs font-bold text-slate-500">${escapeHtml(doc.admin_note)}</div>` : ''}
                    </div>
                `;
            }).join('')}</div>`;
        }

        function renderBookings(bookings) {
            if (!bookings.length) {
                return '<div class="bg-white/80 border border-dashed border-slate-200 rounded-2xl p-8 text-center text-sm font-bold text-slate-400">No orders yet.</div>';
            }
            return `
                <div class="overflow-x-auto bg-white/80 border border-slate-100 rounded-3xl">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <th class="p-4">Order</th>
                                <th class="p-4">Vehicle & Date</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${bookings.map(booking => `
                                <tr class="border-b border-slate-50 last:border-0">
                                    <td class="p-4 align-top">
                                        <div class="font-black text-slate-800">${fmt(booking.booking_reference || ('#' + booking.booking_id))}</div>
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">ID: #${escapeHtml(booking.booking_id)}</div>
                                        ${booking.promo_code ? `<div class="mt-2 text-[10px] font-black text-blue-600 uppercase tracking-widest"><i class="fas fa-ticket-alt mr-1"></i>${escapeHtml(booking.promo_code)}</div>` : ''}
                                    </td>
                                    <td class="p-4 align-top">
                                        <div class="font-extrabold text-slate-700">${fmt(booking.car_names)}</div>
                                        <div class="text-xs font-bold text-slate-400 mt-1">${fmt(booking.pickup_datetime)} - ${fmt(booking.return_datetime)}</div>
                                        <div class="text-xs font-bold text-slate-400 mt-1">${fmt(booking.pickup_locations)} to ${fmt(booking.dropoff_locations)}</div>
                                    </td>
                                    <td class="p-4 align-top">
                                        <div class="flex flex-col gap-2 items-start">
                                            ${badge(booking.booking_status)}
                                            ${badge(booking.payment_status)}
                                        </div>
                                    </td>
                                    <td class="p-4 align-top text-right font-black text-slate-800">${money(booking.grand_total || booking.total_amount)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function renderPayments(payments) {
            if (!payments.length) return '<div class="text-sm font-bold text-slate-400">No payment records.</div>';
            return payments.map(payment => `
                <div class="flex items-center justify-between gap-4 py-3 border-b border-slate-100 last:border-0">
                    <div>
                        <div class="font-black text-slate-700">${fmt(payment.transaction_reference || payment.booking_reference || ('Payment #' + payment.payment_id))}</div>
                        <div class="text-xs font-bold text-slate-400 mt-1">${fmt(payment.payment_method)} · ${fmt(payment.payment_date || payment.created_at)}</div>
                    </div>
                    <div class="text-right">
                        ${badge(payment.payment_status)}
                        <div class="mt-2 font-black text-slate-800">${money(payment.amount)}</div>
                    </div>
                </div>
            `).join('');
        }

        function renderPromoUsage(usages) {
            if (!usages.length) return '<div class="text-sm font-bold text-slate-400">No promo usage records.</div>';
            return usages.map(item => `
                <div class="flex items-center justify-between gap-4 py-3 border-b border-slate-100 last:border-0">
                    <div>
                        <div class="font-black text-slate-700">${fmt(item.promo_code || item.promo_name)}</div>
                        <div class="text-xs font-bold text-slate-400 mt-1">${fmt(item.booking_reference || ('Booking #' + item.booking_id))}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-black text-blue-600">${fmt(item.discount_percent)}%</div>
                        <div class="text-xs font-bold text-slate-400 mt-1">${fmt(item.used_at || item.created_at)}</div>
                    </div>
                </div>
            `).join('');
        }

        async function openCustomerDetails(userId) {
            detailBody.innerHTML = '<div class="py-20 text-center text-slate-400 font-black"><i class="fas fa-circle-notch fa-spin mr-2"></i>Loading customer record...</div>';
            detailModal.classList.remove('hidden');

            try {
                const response = await fetch(`get_customer_details.php?id=${userId}`);
                const data = await response.json();
                if (!data.ok) throw new Error(data.error || 'Unable to load customer details.');

                const user = data.user || {};
                const initial = (user.name || '?').trim().charAt(0).toUpperCase();
                detailBody.innerHTML = `
                    <div class="space-y-6">
                        <div class="bg-white/80 border border-slate-100 rounded-3xl p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                            <div class="flex items-center gap-5">
                                <div class="w-16 h-16 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-2xl font-black shadow-lg">${escapeHtml(initial)}</div>
                                <div>
                                    <h4 class="text-2xl font-black text-slate-800">${fmt(user.name)}</h4>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">UID: #${String(user.user_id || '').padStart(4, '0')} · Registered: ${fmt(user.created_at)}</div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                ${badge(user.kyc_status || 'KYC Unknown')}
                                <span class="px-3 py-1.5 rounded-lg border border-blue-100 bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-widest">${escapeHtml(data.summary.booking_count || 0)} Orders</span>
                                <span class="px-3 py-1.5 rounded-lg border border-slate-100 bg-slate-50 text-slate-600 text-[10px] font-black uppercase tracking-widest">${money(data.summary.total_spent)}</span>
                            </div>
                        </div>

                        <section>
                            <h5 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-3">Registered Information</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                                ${infoItem('Email', user.email, 'fas fa-envelope')}
                                ${infoItem('Phone', user.phone, 'fas fa-phone')}
                                ${infoItem('IC Number', user.ic_number, 'fas fa-id-card')}
                                ${infoItem('License Number', user.license_number, 'fas fa-id-badge')}
                                ${infoItem('License Expiry', user.license_expiry_date, 'fas fa-calendar-check')}
                                ${infoItem('Date of Birth', user.date_of_birth, 'fas fa-cake-candles')}
                                ${infoItem('Created At', user.created_at, 'fas fa-clock')}
                                ${infoItem('Updated At', user.updated_at, 'fas fa-rotate')}
                            </div>
                            <div class="mt-4">
                                ${infoItem('Address', user.address, 'fas fa-location-dot')}
                            </div>
                        </section>

                        <section>
                            <h5 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-3">KYC / IC / License Documents</h5>
                            ${renderDocuments(data.documents || [])}
                        </section>

                        <section>
                            <h5 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-3">Order History</h5>
                            ${renderBookings(data.bookings || [])}
                        </section>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <section class="bg-white/80 border border-slate-100 rounded-3xl p-5">
                                <h5 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-3">Payments</h5>
                                ${renderPayments(data.payments || [])}
                            </section>
                            <section class="bg-white/80 border border-slate-100 rounded-3xl p-5">
                                <h5 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-3">Promo Usage</h5>
                                ${renderPromoUsage(data.promo_usage || [])}
                            </section>
                        </div>
                    </div>
                `;
            } catch (error) {
                detailBody.innerHTML = `<div class="bg-red-50 border border-red-100 text-red-600 rounded-2xl p-6 font-bold">${escapeHtml(error.message)}</div>`;
            }
        }

        window.onclick = function(e) {
            if(e.target == modal) closeModal();
            if(e.target == detailModal) closeDetailModal();
        }

        document.getElementById('userSearch').addEventListener('keyup', function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll('#userTable tbody tr').forEach(row => {
                const haystack = (row.dataset.search || row.innerText).toLowerCase();
                row.style.display = haystack.includes(val) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
