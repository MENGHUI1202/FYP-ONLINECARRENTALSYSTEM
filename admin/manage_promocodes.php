<?php
include('../includes/config.php');
include('../includes/auth.php');
if(!isset($_SESSION['admin_logged_in'])) { header("Location: index.php"); exit; }

function pc_e($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

function pc_ensure_schema(mysqli $conn): void
{
    if (!db_table_exists($conn, 'promo_codes')) {
        $conn->query("
            CREATE TABLE promo_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                promo_name VARCHAR(180) NOT NULL,
                promo_code VARCHAR(50) NOT NULL UNIQUE,
                discount_percent INT NOT NULL DEFAULT 0,
                description TEXT NULL,
                show_on_homepage TINYINT(1) NOT NULL DEFAULT 0,
                status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                disabled_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    if (!db_column_exists($conn, 'promo_codes', 'disabled_at')) {
        $conn->query("ALTER TABLE promo_codes ADD COLUMN disabled_at DATETIME NULL AFTER status");
    }
    if (!db_column_exists($conn, 'promo_codes', 'updated_at')) {
        $conn->query("ALTER TABLE promo_codes ADD COLUMN updated_at DATETIME NULL AFTER created_at");
    }
    if (!db_column_exists($conn, 'promo_codes', 'deleted_at')) {
        $conn->query("ALTER TABLE promo_codes ADD COLUMN deleted_at DATETIME NULL AFTER updated_at");
    }

    if (!db_table_exists($conn, 'promo_code_assignments')) {
        $conn->query("
            CREATE TABLE promo_code_assignments (
                assignment_id INT AUTO_INCREMENT PRIMARY KEY,
                promo_id INT NOT NULL,
                user_id INT NOT NULL,
                status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_promo_user (promo_id, user_id),
                KEY idx_user_id (user_id),
                CONSTRAINT fk_promo_assignment_promo FOREIGN KEY (promo_id) REFERENCES promo_codes(id) ON DELETE CASCADE,
                CONSTRAINT fk_promo_assignment_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}

function pc_code($value): string
{
    return strtoupper(preg_replace('/[^A-Z0-9_-]/', '', (string)$value));
}

pc_ensure_schema($conn);

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'super_admin') {
        die('Access Denied: You do not have permission to modify promo codes.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save_promo') {
        $promoId = (int)($_POST['promo_id'] ?? 0);
        $promoName = trim($_POST['promo_name'] ?? '');
        $promoCode = pc_code($_POST['promo_code'] ?? '');
        $discount = max(1, min(100, (int)($_POST['discount_percent'] ?? 0)));
        $description = trim($_POST['description'] ?? '');
        $showOnHomepage = isset($_POST['show_on_homepage']) ? 1 : 0;
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $assignedUserId = (int)($_POST['assigned_user_id'] ?? 0);

        if ($promoName === '') {
            $error = 'Promo name is required.';
        } elseif ($promoCode === '') {
            $error = 'Promo code is required.';
        } else {
            $disabledAtSql = $status === 'inactive' ? 'IFNULL(disabled_at, NOW())' : 'NULL';

            if ($promoId > 0) {
                $stmt = $conn->prepare("
                    UPDATE promo_codes
                    SET promo_name=?, promo_code=?, discount_percent=?, description=?, show_on_homepage=?, status=?, disabled_at=$disabledAtSql, updated_at=NOW()
                    WHERE id=?
                ");
                $stmt->bind_param('ssisisi', $promoName, $promoCode, $discount, $description, $showOnHomepage, $status, $promoId);
                $ok = $stmt->execute();
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO promo_codes (promo_name, promo_code, discount_percent, description, show_on_homepage, status, disabled_at, updated_at)
                    VALUES (?,?,?,?,?,?, " . ($status === 'inactive' ? 'NOW()' : 'NULL') . ", NOW())
                ");
                $stmt->bind_param('ssisis', $promoName, $promoCode, $discount, $description, $showOnHomepage, $status);
                $ok = $stmt->execute();
                $promoId = $ok ? (int)$conn->insert_id : 0;
            }

            if (!empty($ok) && $promoId > 0) {
                $conn->query("DELETE FROM promo_code_assignments WHERE promo_id=" . (int)$promoId);
                if ($assignedUserId > 0) {
                    $assign = $conn->prepare("INSERT INTO promo_code_assignments (promo_id, user_id, status) VALUES (?, ?, 'active')");
                    $assign->bind_param('ii', $promoId, $assignedUserId);
                    $assign->execute();
                    $assign->close();
                }
                header('Location: manage_promocodes.php?msg=saved');
                exit;
            }

            $error = 'Promo code could not be saved. Please check if the code already exists.';
        }
    }

    if ($action === 'toggle_status') {
        $promoId = (int)($_POST['promo_id'] ?? 0);
        $newStatus = ($_POST['new_status'] ?? '') === 'active' ? 'active' : 'inactive';
        $stmt = $conn->prepare("UPDATE promo_codes SET status=?, disabled_at=" . ($newStatus === 'inactive' ? 'NOW()' : 'NULL') . ", updated_at=NOW() WHERE id=? AND deleted_at IS NULL");
        $stmt->bind_param('si', $newStatus, $promoId);
        $stmt->execute();
        header('Location: manage_promocodes.php?msg=status');
        exit;
    }

    if ($action === 'delete_promo') {
        $promoId = (int)($_POST['promo_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE promo_codes SET status='inactive', disabled_at=IFNULL(disabled_at,NOW()), deleted_at=NOW(), updated_at=NOW() WHERE id=?");
        $stmt->bind_param('i', $promoId);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE promo_code_assignments SET status='inactive' WHERE promo_id=?");
        $stmt->bind_param('i', $promoId);
        $stmt->execute();

        header('Location: manage_promocodes.php?msg=deleted');
        exit;
    }
}

$total = (int)($conn->query("SELECT COUNT(*) AS c FROM promo_codes WHERE deleted_at IS NULL")->fetch_assoc()['c'] ?? 0);
$active = (int)($conn->query("SELECT COUNT(*) AS c FROM promo_codes WHERE status='active' AND deleted_at IS NULL")->fetch_assoc()['c'] ?? 0);
$disabled = (int)($conn->query("SELECT COUNT(*) AS c FROM promo_codes WHERE status='inactive' AND deleted_at IS NULL")->fetch_assoc()['c'] ?? 0);
$assigned = (int)($conn->query("SELECT COUNT(DISTINCT promo_id) AS c FROM promo_code_assignments WHERE status='active'")->fetch_assoc()['c'] ?? 0);

$users = $conn->query("SELECT user_id, name, email, phone FROM users WHERE role='customer' OR role IS NULL ORDER BY name ASC");
$promos = $conn->query("
    SELECT pc.*, u.user_id AS assigned_user_id, u.name AS assigned_name, u.email AS assigned_email,
           COALESCE(usage_stats.used_count, 0) AS used_count
    FROM promo_codes pc
    LEFT JOIN promo_code_assignments pca ON pca.promo_id=pc.id AND pca.status='active'
    LEFT JOIN users u ON u.user_id=pca.user_id
    LEFT JOIN (
        SELECT promo_id, COUNT(*) AS used_count
        FROM promo_code_usage
        GROUP BY promo_id
    ) usage_stats ON usage_stats.promo_id=pc.id
    WHERE pc.deleted_at IS NULL
    ORDER BY pc.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promo Codes | Fleet Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['"Plus Jakarta Sans"','sans-serif']},colors:{primary:'#3b82f6'}}}}</script>
    <style>
        body{background:radial-gradient(circle at top right,#e0e7ff 0%,#f8fafc 40%,#f1f5f9 100%)}
        .glass-card{background:rgba(255,255,255,.75);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,1);box-shadow:0 10px 40px -10px rgba(226,232,240,.8)}
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include('include/sidebar.php'); ?>

    <main class="ml-64 p-10 w-full">
        <header class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Promo Code Control</h1>
                <p class="text-slate-500 mt-1 font-medium">Create public vouchers or assign private compensation codes to one customer.</p>
            </div>
            <?php if(($_SESSION['role'] ?? '') === 'super_admin'): ?>
                <button onclick="openPromoModal()" class="px-6 py-3 bg-slate-900 text-white rounded-xl font-bold shadow-xl shadow-slate-900/20 hover:bg-slate-800 transition-all flex items-center gap-2 uppercase tracking-widest text-xs"><i class="fas fa-plus"></i> Add Promo</button>
            <?php endif; ?>
        </header>

        <?php if($error): ?><div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-100 text-red-600 font-bold"><?= pc_e($error) ?></div><?php endif; ?>
        <?php if(isset($_GET['msg'])): ?><div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600 font-bold">Promo code updated successfully.</div><?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
            <div class="glass-card p-5 rounded-3xl border-t-4 border-blue-500"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Promo</p><h4 class="text-3xl font-black mt-1"><?= $total ?></h4></div>
            <div class="glass-card p-5 rounded-3xl border-t-4 border-emerald-500"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active</p><h4 class="text-3xl font-black text-emerald-500 mt-1"><?= $active ?></h4></div>
            <div class="glass-card p-5 rounded-3xl border-t-4 border-slate-400"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Disabled</p><h4 class="text-3xl font-black text-slate-500 mt-1"><?= $disabled ?></h4></div>
            <div class="glass-card p-5 rounded-3xl border-t-4 border-amber-500"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Assigned</p><h4 class="text-3xl font-black text-amber-500 mt-1"><?= $assigned ?></h4></div>
        </div>

        <div class="glass-card rounded-[2rem] overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white/30">
                <h3 class="font-black text-xl">Promo Directory</h3>
                <div class="relative w-80">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input id="promoSearch" class="w-full pl-11 pr-5 py-2.5 bg-white border border-slate-100 rounded-xl outline-none font-bold text-sm shadow-sm" placeholder="Search code, customer, status...">
                </div>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-left border-separate border-spacing-y-2" id="promoTable">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Promo</th>
                            <th class="px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Discount</th>
                            <th class="px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Visibility</th>
                            <th class="px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-5 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($row=$promos->fetch_assoc()): ?>
                        <?php
                            $payload=[
                                'id'=>(int)$row['id'],
                                'promo_name'=>$row['promo_name'] ?? '',
                                'promo_code'=>$row['promo_code'] ?? '',
                                'discount_percent'=>(int)($row['discount_percent'] ?? 0),
                                'description'=>$row['description'] ?? '',
                                'show_on_homepage'=>(int)($row['show_on_homepage'] ?? 0),
                                'status'=>$row['status'] ?? 'active',
                                'assigned_user_id'=>(int)($row['assigned_user_id'] ?? 0),
                            ];
                            $search=strtolower(implode(' ', [$row['promo_name'],$row['promo_code'],$row['status'],$row['assigned_name'],$row['assigned_email'],$row['disabled_at']]));
                        ?>
                        <tr class="bg-white/65 hover:bg-white transition-all" data-search="<?= pc_e($search) ?>">
                            <td class="px-5 py-5 rounded-l-2xl">
                                <div class="font-black text-slate-800 text-lg"><?= pc_e($row['promo_code']) ?></div>
                                <div class="text-xs font-bold text-slate-500 mt-1"><?= pc_e($row['promo_name']) ?></div>
                                <div class="text-[10px] font-bold text-slate-400 mt-2"><?= pc_e($row['description']) ?></div>
                            </td>
                            <td class="px-5 py-5">
                                <span class="text-2xl font-black text-blue-600"><?= (int)$row['discount_percent'] ?>%</span>
                                <button type="button" onclick="openUsageModal(<?= (int)$row['id'] ?>, '<?= pc_e($row['promo_code']) ?>')" class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 bg-white text-slate-500 hover:bg-blue-50 hover:text-blue-600 border border-slate-100 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">
                                    <i class="fas fa-users"></i> <?= (int)$row['used_count'] ?> Used
                                </button>
                            </td>
                            <td class="px-5 py-5">
                                <?php if(!empty($row['assigned_user_id'])): ?>
                                    <span class="px-3 py-1.5 bg-amber-50 text-amber-600 border border-amber-100 rounded-lg text-[10px] font-black uppercase tracking-widest">Private</span>
                                    <div class="text-xs font-bold text-slate-500 mt-2"><?= pc_e($row['assigned_name']) ?></div>
                                    <div class="text-[10px] font-bold text-slate-400"><?= pc_e($row['assigned_email']) ?></div>
                                <?php else: ?>
                                    <span class="px-3 py-1.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-lg text-[10px] font-black uppercase tracking-widest">Public</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-5">
                                <?php if(($row['status'] ?? '') === 'active'): ?>
                                    <span class="px-3 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-lg text-[10px] font-black uppercase tracking-widest">Active</span>
                                <?php else: ?>
                                    <span class="px-3 py-1.5 bg-slate-100 text-slate-500 border border-slate-200 rounded-lg text-[10px] font-black uppercase tracking-widest">Disabled</span>
                                    <div class="text-[10px] font-bold text-slate-400 mt-2">Disabled: <?= pc_e($row['disabled_at'] ? date('d M Y, H:i', strtotime($row['disabled_at'])) : '-') ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-5 text-right rounded-r-2xl">
                                <?php if(($_SESSION['role'] ?? '') === 'super_admin'): ?>
                                    <div class="flex justify-end gap-2">
                                        <button onclick='openPromoModal(<?= json_encode($payload) ?>)' class="w-10 h-10 bg-slate-100 text-slate-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all"><i class="fas fa-pen"></i></button>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="promo_id" value="<?= (int)$row['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= ($row['status'] ?? '') === 'active' ? 'inactive' : 'active' ?>">
                                            <button class="w-10 h-10 <?= ($row['status'] ?? '') === 'active' ? 'bg-emerald-50 text-emerald-600 hover:bg-red-500 hover:text-white' : 'bg-red-50 text-red-500 hover:bg-emerald-500 hover:text-white' ?> rounded-xl transition-all" title="<?= ($row['status'] ?? '') === 'active' ? 'Enabled - click to disable' : 'Disabled - click to enable' ?>"><i class="fas <?= ($row['status'] ?? '') === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i></button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Delete this promo code from active management? Usage history will be kept.');">
                                            <input type="hidden" name="action" value="delete_promo">
                                            <input type="hidden" name="promo_id" value="<?= (int)$row['id'] ?>">
                                            <button class="w-10 h-10 bg-slate-100 text-slate-500 hover:bg-red-600 hover:text-white rounded-xl transition-all" title="Delete Promo"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs font-bold text-slate-300">Read Only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="promoModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-2xl rounded-3xl p-8 shadow-2xl border border-slate-100">
            <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                <h3 id="promoModalTitle" class="text-2xl font-black text-slate-800">Add Promo Code</h3>
                <button onclick="closePromoModal()" class="text-slate-400 hover:text-red-500 text-2xl"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="action" value="save_promo">
                <input type="hidden" name="promo_id" id="promo_id">
                <div class="grid grid-cols-2 gap-5">
                    <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Promo Name</label><input name="promo_name" id="promo_name" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none font-bold"></div>
                    <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Promo Code</label><input name="promo_code" id="promo_code" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none font-black uppercase"></div>
                </div>
                <div class="grid grid-cols-2 gap-5">
                    <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Discount Percent</label><input type="number" min="1" max="100" name="discount_percent" id="discount_percent" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none font-bold"></div>
                    <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Status</label><select name="status" id="status" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none font-bold"><option value="active">Active</option><option value="inactive">Disabled</option></select></div>
                </div>
                <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Assign To One Customer</label><select name="assigned_user_id" id="assigned_user_id" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none font-bold"><option value="0">Public promo - all customers can use</option><?php if($users): while($u=$users->fetch_assoc()): ?><option value="<?= (int)$u['user_id'] ?>"><?= pc_e($u['name']) ?> · <?= pc_e($u['email']) ?> · <?= pc_e($u['phone']) ?></option><?php endwhile; endif; ?></select><p class="text-[10px] font-bold text-slate-400 mt-2">Choose a customer only for compensation / private promo. Public promo stays visible to all logged-in customers.</p></div>
                <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Description</label><textarea name="description" id="description" rows="3" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none font-medium"></textarea></div>
                <label class="flex items-center gap-3 text-sm font-black text-slate-600"><input type="checkbox" name="show_on_homepage" id="show_on_homepage" class="w-4 h-4 accent-blue-600"> Show on homepage / voucher list</label>
                <div class="pt-5 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closePromoModal()" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-xl font-black text-xs uppercase tracking-widest">Cancel</button>
                    <button class="px-8 py-3 bg-slate-900 text-white rounded-xl font-black text-xs uppercase tracking-widest">Save Promo</button>
                </div>
            </form>
        </div>
    </div>

    <div id="usageModal" class="fixed inset-0 z-[120] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-4xl rounded-3xl p-8 shadow-2xl border border-slate-100 max-h-[86vh] flex flex-col">
            <div class="flex justify-between items-start mb-6 border-b border-slate-100 pb-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Promo Usage</p>
                    <h3 id="usageModalTitle" class="text-2xl font-black text-slate-800">Used Customers</h3>
                </div>
                <button onclick="closeUsageModal()" class="text-slate-400 hover:text-red-500 text-2xl"><i class="fas fa-times"></i></button>
            </div>
            <div id="usageModalBody" class="overflow-y-auto flex-1"></div>
        </div>
    </div>

    <script>
        const promoModal=document.getElementById('promoModal');
        const usageModal=document.getElementById('usageModal');
        const usageModalBody=document.getElementById('usageModalBody');
        const escapeHtml=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
        function openPromoModal(promo=null){
            document.getElementById('promoModalTitle').innerText=promo?'Edit Promo Code':'Add Promo Code';
            document.getElementById('promo_id').value=promo?.id || '';
            document.getElementById('promo_name').value=promo?.promo_name || '';
            document.getElementById('promo_code').value=promo?.promo_code || '';
            document.getElementById('discount_percent').value=promo?.discount_percent || 10;
            document.getElementById('description').value=promo?.description || '';
            document.getElementById('status').value=promo?.status || 'active';
            document.getElementById('assigned_user_id').value=promo?.assigned_user_id || 0;
            document.getElementById('show_on_homepage').checked=(promo?.show_on_homepage || 0)==1;
            promoModal.classList.remove('hidden');
        }
        function closePromoModal(){ promoModal.classList.add('hidden'); }
        async function openUsageModal(promoId,promoCode){
            document.getElementById('usageModalTitle').innerText=`${promoCode} Used Customers`;
            usageModalBody.innerHTML='<div class="py-16 text-center text-slate-400 font-black"><i class="fas fa-circle-notch fa-spin mr-2"></i>Loading usage records...</div>';
            usageModal.classList.remove('hidden');
            try{
                const response=await fetch(`get_promo_usage.php?promo_id=${promoId}`);
                const data=await response.json();
                if(!data.ok) throw new Error(data.error || 'Unable to load usage.');
                if(!data.rows.length){
                    usageModalBody.innerHTML='<div class="p-10 bg-slate-50 border border-dashed border-slate-200 rounded-2xl text-center text-slate-400 font-bold">No customer has used this promo yet.</div>';
                    return;
                }
                usageModalBody.innerHTML=`
                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                <tr><th class="p-4">Customer</th><th class="p-4">Booking</th><th class="p-4">Used At</th><th class="p-4 text-right">Amount</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                ${data.rows.map(row=>`
                                    <tr class="bg-white">
                                        <td class="p-4"><div class="font-black text-slate-800">${escapeHtml(row.name || '-')}</div><div class="text-xs font-bold text-slate-400">${escapeHtml(row.email || '')}</div><div class="text-xs font-bold text-slate-400">${escapeHtml(row.phone || '')}</div></td>
                                        <td class="p-4"><div class="font-black text-blue-600">${escapeHtml(row.booking_reference || ('#'+row.booking_id))}</div><div class="text-xs font-bold text-slate-400">${escapeHtml(row.booking_status || '')}</div></td>
                                        <td class="p-4 font-bold text-slate-500">${escapeHtml(row.used_at || row.created_at || '-')}</td>
                                        <td class="p-4 text-right font-black text-slate-800">RM ${Number(row.grand_total || 0).toLocaleString('en-MY',{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }catch(error){
                usageModalBody.innerHTML=`<div class="p-6 bg-red-50 border border-red-100 text-red-600 rounded-2xl font-bold">${escapeHtml(error.message)}</div>`;
            }
        }
        function closeUsageModal(){ usageModal.classList.add('hidden'); }
        document.getElementById('promoSearch').addEventListener('input',function(){
            const value=this.value.toLowerCase();
            document.querySelectorAll('#promoTable tbody tr').forEach(row=>{
                row.style.display=(row.dataset.search || row.innerText).toLowerCase().includes(value)?'':'none';
            });
        });
        window.addEventListener('click',e=>{ if(e.target===promoModal) closePromoModal(); if(e.target===usageModal) closeUsageModal(); });
    </script>
</body>
</html>
