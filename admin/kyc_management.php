<?php
// admin/kyc_management.php
include('../includes/config.php');
include('../includes/auth.php');
checkLogin();

if (db_table_exists($conn, 'user_documents')) {
    require __DIR__ . '/kyc_management_unified.php';
    exit;
}

// 处理管理员的点击审核动作
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $conn->query("UPDATE users SET kyc_status = 'Verified' WHERE id = $user_id");
        $msg = "Approveed";
    } elseif ($action === 'reject') {
        $conn->query("UPDATE users SET kyc_status = 'Rejected' WHERE id = $user_id");
        $msg = "Rejected";
    }
    if(isset($msg)) {
        echo "<script>alert('User KYC status updated to: $msg'); window.location.href='kyc_management.php';</script>";
        exit;
    }
}

// 只拉取待审核 (Pending) 的用户
$pending_kyc = $conn->query("SELECT id, name, email, phone, ic_front_image, driving_license_image, created_at FROM users WHERE kyc_status = 'Pending' ORDER BY created_at ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KYC Verification Center | Fleet Command</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex">

    <?php include('include/sidebar.php'); ?>
    
    <main class="ml-64 p-8 w-full max-w-[1400px] mx-auto">
        <header class="flex justify-between items-start pb-6 border-b border-slate-200 mb-8">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-black shadow-lg shadow-indigo-500/30"><i class="fas fa-id-card"></i></div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">KYC Verification Center</h1>
                </div>
                <p class="text-slate-500 text-sm font-medium">Review and verify submitted customer identity documents.</p>
            </div>
        </header>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest"><i class="fas fa-user-clock text-amber-500 mr-2"></i> Pending Queue</h3>
                <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-full"><?php echo $pending_kyc->num_rows; ?> Requests</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4">Contact Info</th>
                            <th class="px-6 py-4">Verification Documents</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if($pending_kyc->num_rows > 0): ?>
                            <?php while($row = $pending_kyc->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 text-base"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <div class="text-xs text-slate-400 mt-0.5">ID: #<?php echo $row['id']; ?></div>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-600">
                                    <div><i class="fas fa-envelope text-slate-400 mr-1.5"></i><?php echo htmlspecialchars($row['email']); ?></div>
                                    <div class="mt-1"><i class="fas fa-phone text-slate-400 mr-1.5"></i><?php echo htmlspecialchars($row['phone']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="../uploads/kyc/<?php echo $row['ic_front_image'] ?: 'dummy.jpg'; ?>" target="_blank" class="px-3 py-1.5 bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600 border border-slate-200 text-slate-600 text-xs font-bold rounded transition-colors flex items-center gap-1.5">
                                            <i class="fas fa-address-card text-slate-400 group-hover:text-indigo-500"></i> View IC Front
                                        </a>
                                        <a href="../uploads/kyc/<?php echo $row['driving_license_image'] ?: 'dummy.jpg'; ?>" target="_blank" class="px-3 py-1.5 bg-slate-50 hover:bg-teal-50 hover:text-teal-600 border border-slate-200 text-slate-600 text-xs font-bold rounded transition-colors flex items-center gap-1.5">
                                            <i class="fas fa-car text-slate-400 group-hover:text-teal-500"></i> View Driver License
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="?action=approve&id=<?php echo $row['id']; ?>" class="w-8 h-8 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-500 hover:text-white transition-colors border border-emerald-200 shadow-sm" title="Approve Identity">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="?action=reject&id=<?php echo $row['id']; ?>" onclick="return confirm('Reject this customer KYC application?');" class="w-8 h-8 flex items-center justify-center bg-red-50 text-red-600 rounded-lg hover:bg-red-500 hover:text-white transition-colors border border-red-200 shadow-sm" title="Reject / Flag">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center text-slate-400 font-medium">
                                    <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl mx-auto mb-3 border border-emerald-100 shadow-sm"><i class="fas fa-check"></i></div>
                                    <h4 class="text-slate-700 font-bold text-sm">All Clear!</h4>
                                    <p class="text-xs text-slate-400 mt-1">No pending identity verification tasks currently.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
