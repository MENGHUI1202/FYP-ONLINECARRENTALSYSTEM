<?php
$admin_id = current_admin_id();

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_document_url(string $path): string
{
    if (str_starts_with($path, 'assets/uploads/documents/')) {
        return '../NEW_CAR_RENTAL_SYSTEM/' . $path;
    }
    return '../' . ltrim($path, '/');
}

function refresh_kyc_status(mysqli $conn, int $user_id): void
{
    $required = ['IC Photo', 'Driving License Photo'];
    $latest_statuses = [];

    foreach ($required as $type) {
        $stmt = $conn->prepare(
            "SELECT verification_status
             FROM user_documents
             WHERE user_id = ? AND document_type = ? AND TRIM(COALESCE(file_path, '')) <> ''
             ORDER BY uploaded_at DESC, document_id DESC
             LIMIT 1"
        );
        $stmt->bind_param('is', $user_id, $type);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $latest_statuses[$type] = $row['verification_status'] ?? 'Not Uploaded';
        $stmt->close();
    }

    $status = 'Verified';
    foreach ($latest_statuses as $doc_status) {
        if ($doc_status === 'Rejected') {
            $status = 'Rejected';
            break;
        }
        if ($doc_status === 'Not Uploaded') {
            $status = 'Unverified';
            continue;
        }
        if ($doc_status !== 'Verified' && $status !== 'Unverified') {
            $status = 'Pending';
        }
    }

    $stmt = $conn->prepare("UPDATE users SET kyc_status = ? WHERE user_id = ?");
    $stmt->bind_param('si', $status, $user_id);
    $stmt->execute();
}

function review_document(mysqli $conn, int $document_id, int $admin_id, string $status, string $note): void
{
    $routine = $conn->prepare(
        "SELECT COUNT(*) AS c
         FROM information_schema.ROUTINES
         WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = 'sp_admin_review_user_document'"
    );
    $routine->execute();
    $has_procedure = ((int)($routine->get_result()->fetch_assoc()['c'] ?? 0)) > 0;

    if ($has_procedure) {
        $stmt = $conn->prepare("CALL sp_admin_review_user_document(?, ?, ?, ?)");
        $stmt->bind_param('iiss', $document_id, $admin_id, $status, $note);
        $stmt->execute();
        return;
    }

    $stmt = $conn->prepare("SELECT user_id FROM user_documents WHERE document_id = ? LIMIT 1");
    $stmt->bind_param('i', $document_id);
    $stmt->execute();
    $user_id = (int)($stmt->get_result()->fetch_assoc()['user_id'] ?? 0);
    if ($user_id <= 0) {
        throw new RuntimeException('Document not found.');
    }

    $stmt = $conn->prepare(
        "UPDATE user_documents
         SET verification_status = ?, admin_note = ?, reviewed_by_admin_id = NULLIF(?, 0), reviewed_at = NOW()
         WHERE document_id = ?"
    );
    $stmt->bind_param('ssii', $status, $note, $admin_id, $document_id);
    $stmt->execute();
    refresh_kyc_status($conn, $user_id);
}

if (isset($_GET['action'], $_GET['id'])) {
    $document_id = (int)$_GET['id'];
    $action = strtolower(trim($_GET['action']));
    $status = $action === 'approve' ? 'Verified' : ($action === 'reject' ? 'Rejected' : '');
    $note = trim($_POST['admin_note'] ?? '');

    if ($document_id > 0 && $status !== '') {
        try {
            review_document($conn, $document_id, $admin_id, $status, $note);
            header('Location: kyc_management.php?msg=updated');
            exit;
        } catch (Throwable $ex) {
            $error = $ex->getMessage();
        }
    }
}

$pending_kyc = $conn->query(
    "SELECT
        ud.document_id,
        ud.user_id,
        ud.document_type,
        ud.file_path,
        ud.verification_status,
        ud.uploaded_at,
        u.name,
        u.email,
        u.phone,
        u.kyc_status
     FROM user_documents ud
     INNER JOIN users u ON u.user_id = ud.user_id
     WHERE ud.verification_status = 'Pending Verification'
     ORDER BY ud.uploaded_at ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KYC Verification Center | Fleet Command</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: "Plus Jakarta Sans", sans-serif; }</style>
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
                <p class="text-slate-500 text-sm font-medium">Review customer identity documents from the unified customer database.</p>
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 font-bold"><?= e($error) ?></div>
        <?php elseif (isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold">KYC document reviewed and user status synchronized.</div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest"><i class="fas fa-user-clock text-amber-500 mr-2"></i> Pending Documents</h3>
                <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-full"><?= $pending_kyc->num_rows ?> Requests</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4">Document</th>
                            <th class="px-6 py-4">Uploaded</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php if ($pending_kyc->num_rows > 0): ?>
                        <?php while ($row = $pending_kyc->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 text-base"><?= e($row['name']) ?></div>
                                    <div class="text-xs text-slate-400 mt-0.5">User #<?= e($row['user_id']) ?> / <?= e($row['kyc_status']) ?></div>
                                    <div class="text-xs text-slate-500 mt-2"><i class="fas fa-envelope text-slate-400 mr-1.5"></i><?= e($row['email']) ?></div>
                                    <div class="text-xs text-slate-500 mt-1"><i class="fas fa-phone text-slate-400 mr-1.5"></i><?= e($row['phone']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-black text-slate-700"><?= e($row['document_type']) ?></div>
                                    <a href="<?= e(admin_document_url($row['file_path'])) ?>" target="_blank" class="inline-flex items-center gap-2 mt-2 px-3 py-1.5 bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600 border border-slate-200 text-slate-600 text-xs font-bold rounded transition-colors">
                                        <i class="fas fa-file-image"></i> View File
                                    </a>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-500"><?= e(date('d M Y, H:i', strtotime($row['uploaded_at']))) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="?action=approve&id=<?= (int)$row['document_id'] ?>" onclick="return confirm('Approve this document?')" class="w-9 h-9 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-500 hover:text-white transition-colors border border-emerald-200 shadow-sm" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <form method="POST" action="?action=reject&id=<?= (int)$row['document_id'] ?>" class="flex gap-2">
                                            <input name="admin_note" class="w-44 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-xs font-bold" placeholder="Reject reason">
                                            <button onclick="return confirm('Reject this document?')" class="w-9 h-9 flex items-center justify-center bg-red-50 text-red-600 rounded-lg hover:bg-red-500 hover:text-white transition-colors border border-red-200 shadow-sm" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center text-slate-400 font-medium">
                                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl mx-auto mb-3 border border-emerald-100 shadow-sm"><i class="fas fa-check"></i></div>
                                <h4 class="text-slate-700 font-bold text-sm">All Clear!</h4>
                                <p class="text-xs text-slate-400 mt-1">No pending identity documents currently.</p>
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
