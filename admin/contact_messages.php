<?php
include('../includes/config.php');
include('../includes/auth.php');
checkLogin();

require_once '../NEW_CAR_RENTAL_SYSTEM/mail_config.php';

function cm_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$conn->query("
    CREATE TABLE IF NOT EXISTS contact_messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(150) NOT NULL,
        support_category VARCHAR(80) NOT NULL DEFAULT 'General Enquiry',
        subject VARCHAR(180) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'new',
        admin_reply TEXT NULL,
        replied_at DATETIME NULL,
        replied_by_admin_id INT NULL,
        reply_error TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

foreach ([
    "admin_reply TEXT NULL AFTER status",
    "replied_at DATETIME NULL AFTER admin_reply",
    "replied_by_admin_id INT NULL AFTER replied_at",
    "reply_error TEXT NULL AFTER replied_by_admin_id",
] as $columnSql) {
    [$columnName] = explode(' ', $columnSql, 2);
    if (!db_column_exists($conn, 'contact_messages', $columnName)) {
        $conn->query("ALTER TABLE contact_messages ADD COLUMN $columnSql");
    }
}

$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message_id'])) {
    $messageId = (int)$_POST['reply_message_id'];
    $replyBody = trim($_POST['reply_body'] ?? '');

    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE message_id = ? LIMIT 1");
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $message = $stmt->get_result()->fetch_assoc();

    if (!$message) {
        $error = 'Contact message not found.';
    } elseif ($replyBody === '') {
        $error = 'Reply message cannot be empty.';
    } else {
        try {
            $mail = createMailer();
            $mail->addAddress($message['email'], $message['name']);
            $mail->Subject = 'Re: ' . $message['subject'];

            $safeName = cm_e($message['name']);
            $safeOriginal = nl2br(cm_e($message['message']));
            $safeReply = nl2br(cm_e($replyBody));

            $mail->Body = "
                <div style='font-family:Arial,sans-serif;max-width:640px;margin:auto;padding:24px;border:1px solid #dce5f2;border-radius:18px;color:#0f172a;'>
                    <h2 style='color:#1266f1;margin-top:0;'>NO1 Car Rental Support</h2>
                    <p>Hello <strong>{$safeName}</strong>,</p>
                    <p>Thank you for contacting NO1 Car Rental. Our reply is below:</p>
                    <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin:18px 0;'>{$safeReply}</div>
                    <p style='font-size:13px;color:#64748b;margin-bottom:6px;'>Your original message:</p>
                    <div style='background:#fff;border-left:4px solid #1266f1;padding:12px 16px;color:#475569;'>{$safeOriginal}</div>
                    <br>
                    <p>Regards,<br>NO1 Car Rental Team</p>
                </div>
            ";
            $mail->AltBody = "Hello {$message['name']},\n\n{$replyBody}\n\nRegards,\NO1 Car Rental Team";
            $mail->send();

           // 安全获取当前管理员的 ID (兼容新旧表结构)
            $adminId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
            $stmt = $conn->prepare("
                UPDATE contact_messages
                SET status = 'replied',
                    admin_reply = ?,
                    replied_at = NOW(),
                    replied_by_admin_id = NULLIF(?, 0),
                    reply_error = NULL
                WHERE message_id = ?
            ");
            $stmt->bind_param('sii', $replyBody, $adminId, $messageId);
            $stmt->execute();
            admin_audit_log($conn, 'CONTACT_REPLIED', "Replied to {$message['email']} about \"{$message['subject']}\".", 'contact_message', $messageId);
            $notice = 'Reply email sent successfully.';
        } catch (Throwable $ex) {
            $mailError = $ex->getMessage();
            $stmt = $conn->prepare("
                UPDATE contact_messages
                SET status = 'reply_failed',
                    admin_reply = ?,
                    reply_error = ?
                WHERE message_id = ?
            ");
            $stmt->bind_param('ssi', $replyBody, $mailError, $messageId);
            $stmt->execute();
            admin_audit_log($conn, 'CONTACT_REPLY_FAILED', "Reply to {$message['email']} failed: {$mailError}", 'contact_message', $messageId);
            $error = 'Email failed to send: ' . $mailError;
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'new') {
    $where = "WHERE status = 'new'";
} elseif ($filter === 'replied') {
    $where = "WHERE status = 'replied'";
} elseif ($filter === 'failed') {
    $where = "WHERE status = 'reply_failed'";
}

$messages = $conn->query("SELECT * FROM contact_messages $where ORDER BY created_at DESC");
$newCount = (int)($conn->query("SELECT COUNT(*) AS c FROM contact_messages WHERE status = 'new'")->fetch_assoc()['c'] ?? 0);
$repliedCount = (int)($conn->query("SELECT COUNT(*) AS c FROM contact_messages WHERE status = 'replied'")->fetch_assoc()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Messages | Fleet Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }, colors: { primary: '#3b82f6' } } } }
    </script>
    <style>
        body { background: #f8fafc; }
        .glass-card { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05), 0 2px 4px -1px rgba(0,0,0,.03); }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include('include/sidebar.php'); ?>

    <main class="ml-64 p-10 w-full max-w-[1500px] mx-auto">
        <header class="mb-8 flex justify-between items-end border-b border-slate-200 pb-6">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Contact Messages</h1>
                <p class="text-slate-500 mt-1 font-medium">Review customer contact form submissions and reply by email.</p>
            </div>
            <div class="px-4 py-2 bg-blue-50 border border-blue-100 text-blue-700 rounded-full text-[10px] font-black uppercase tracking-widest">
                <?= $newCount ?> New
            </div>
        </header>

        <?php if ($notice): ?>
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold"><i class="fas fa-check-circle mr-2"></i><?= cm_e($notice) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 font-bold"><i class="fas fa-exclamation-circle mr-2"></i><?= cm_e($error) ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-3 gap-4 mb-8">
            <a href="?filter=all" class="glass-card rounded-2xl p-5 <?= $filter === 'all' ? 'ring-2 ring-blue-400' : '' ?>"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">All Messages</p><h4 class="text-3xl font-black text-slate-900 mt-1"><?= $messages ? $messages->num_rows : 0 ?></h4></a>
            <a href="?filter=new" class="glass-card rounded-2xl p-5 <?= $filter === 'new' ? 'ring-2 ring-blue-400' : '' ?>"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">New</p><h4 class="text-3xl font-black text-amber-500 mt-1"><?= $newCount ?></h4></a>
            <a href="?filter=replied" class="glass-card rounded-2xl p-5 <?= $filter === 'replied' ? 'ring-2 ring-blue-400' : '' ?>"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Replied</p><h4 class="text-3xl font-black text-emerald-500 mt-1"><?= $repliedCount ?></h4></a>
        </div>

        <div class="space-y-5">
            <?php if ($messages && $messages->num_rows > 0): ?>
                <?php while ($row = $messages->fetch_assoc()): ?>
                    <?php
                    $status = strtolower($row['status']);
                    $badgeClass = $status === 'replied' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : ($status === 'reply_failed' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-amber-50 text-amber-600 border-amber-100');
                    ?>
                    <article class="glass-card rounded-2xl overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex justify-between gap-6">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="font-black text-slate-900 text-lg"><?= cm_e($row['subject']) ?></h3>
                                    <span class="px-3 py-1 rounded-lg border text-[9px] font-black uppercase tracking-widest <?= $badgeClass ?>"><?= cm_e($row['status']) ?></span>
                                </div>
                                <div class="text-xs font-bold text-slate-500">
                                    <i class="fas fa-user mr-1"></i><?= cm_e($row['name']) ?>
                                    <span class="mx-2 text-slate-300">|</span>
                                    <i class="fas fa-envelope mr-1"></i><?= cm_e($row['email']) ?>
                                    <span class="mx-2 text-slate-300">|</span>
                                    <i class="fas fa-tag mr-1"></i><?= cm_e($row['support_category']) ?>
                                </div>
                            </div>
                            <div class="text-right text-xs font-bold text-slate-400"><?= cm_e(date('d M Y, H:i', strtotime($row['created_at']))) ?></div>
                        </div>
                        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Customer Message</p>
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 text-sm font-semibold text-slate-700 whitespace-pre-wrap"><?= cm_e($row['message']) ?></div>
                                <?php if (!empty($row['admin_reply'])): ?>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-5 mb-2">Last Reply</p>
                                    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-sm font-semibold text-emerald-800 whitespace-pre-wrap"><?= cm_e($row['admin_reply']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($row['reply_error'])): ?>
                                    <div class="mt-4 bg-red-50 border border-red-100 rounded-2xl p-4 text-xs font-bold text-red-700"><?= cm_e($row['reply_error']) ?></div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" class="flex flex-col">
                                <input type="hidden" name="reply_message_id" value="<?= (int)$row['message_id'] ?>">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Email Reply</label>
                                <textarea name="reply_body" required class="min-h-[190px] resize-y bg-white border border-slate-200 rounded-2xl p-4 text-sm font-semibold text-slate-700 outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50" placeholder="Type your email reply here..."></textarea>
                                <button type="submit" onclick="return confirm('Send this reply email to <?= cm_e($row['email']) ?>?')" class="mt-4 self-end px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-500/20 transition-all">
                                    <i class="fas fa-paper-plane mr-2"></i>Send Email Reply
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="glass-card rounded-2xl p-14 text-center text-slate-400 font-bold">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>No contact messages found.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
