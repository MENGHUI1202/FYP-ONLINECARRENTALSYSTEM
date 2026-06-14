<?php
include('../includes/config.php');
include('../includes/auth.php');

date_default_timezone_set("Asia/Kuala_Lumpur");
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0;
if ($admin_id == 0) { header("Location: index.php"); exit; }

$msg = ""; $error = "";

// --- 2. 处理提交 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password, avatar FROM admin WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $admin_data = $res->fetch_assoc();
        $db_password = $admin_data['password'];
        $is_password_correct = false;

        if (password_verify($current_password, $db_password)) { $is_password_correct = true; } 
        elseif ($current_password === $db_password) { $is_password_correct = true; }

        if (!$is_password_correct) {
            $error = "Error: Your current password is incorrect."; 
        } else {
            // --- 处理头像上传 ---
            $avatar_path = $admin_data['avatar']; 
            if (!empty($_FILES['profile_avatar']['name'])) {
                $target_dir = "../assets/uploads/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $file_name = time() . "_" . basename($_FILES["profile_avatar"]["name"]);
                $target_file = $target_dir . $file_name;
                if (move_uploaded_file($_FILES["profile_avatar"]["tmp_name"], $target_file)) {
                    $avatar_path = "assets/uploads/" . $file_name;
                } else {
                    $error = "Failed to upload image.";
                }
            }

            // --- 处理新密码逻辑 ---
            if (empty($error)) {
                $password_sql_part = ""; 
                $params = [$username, $avatar_path, $admin_id];
                $types = "ssi";

                if (!empty($new_password)) {
                    if (strlen($new_password) < 8) { $error = "New password is too short! (Min 8 characters)"; } 
                    elseif (!preg_match("/[A-Z]/", $new_password) || !preg_match("/[0-9]/", $new_password)) { $error = "New password needs at least 1 Uppercase letter and 1 Number."; }
                    elseif ($new_password !== $confirm_password) { $error = "New password and Confirmation do not match!"; } 
                    else {
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $password_sql_part = ", password = ?";
                        $params = [$username, $avatar_path, $new_hash, $admin_id];
                        $types = "sssi";
                    }
                } elseif ($current_password === $db_password && $current_password !== password_hash($current_password, PASSWORD_DEFAULT)) {
                    $new_hash = password_hash($current_password, PASSWORD_DEFAULT);
                    $password_sql_part = ", password = ?";
                    $params = [$username, $avatar_path, $new_hash, $admin_id];
                    $types = "sssi";
                }

                if (empty($error)) {
                    $sql = "UPDATE admin SET username = ?, avatar = ? $password_sql_part WHERE id = ?";
                    $update = $conn->prepare($sql);
                    $update->bind_param($types, ...$params);

                    if ($update->execute()) {
                        $msg = "Profile updated successfully!";
                        $_SESSION['username'] = $username;
                        if ($avatar_path != $admin_data['avatar']) { $_SESSION['avatar'] = $avatar_path; }
                    } else { $error = "Database Error: " . $conn->error; }
                }
            }
        }
    } else { $error = "Admin account not found."; }
}

// --- 3. 重新获取信息 ---
$stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$initial = strtoupper(substr($user['username'], 0, 1));
$db_img = !empty($user['avatar']) ? $user['avatar'] : ($user['profile_picture'] ?? '');
$img_path = '';
if (!empty($db_img)) {
    if (strpos($db_img, 'assets/') === 0) $img_path = '../' . $db_img;
    elseif (strpos($db_img, '../') === 0) $img_path = $db_img;
    else $img_path = '../assets/uploads/' . $db_img;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | Fleet Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script> tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }, colors: { primary: '#3b82f6' } } } } </script>
    <style> body { background: radial-gradient(circle at top right, #e0e7ff 0%, #f8fafc 40%, #f1f5f9 100%); } .glass-card { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 1); box-shadow: 0 10px 40px -10px rgba(226, 232, 240, 0.8); } </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include('include/sidebar.php'); ?>

    <main class="ml-64 p-10 w-full flex flex-col items-center">
        <div class="w-full max-w-5xl">
            <header class="mb-10">
                <h1 class="text-3xl font-extrabold tracking-tight">Security & Profile</h1>
                <p class="text-slate-500 mt-1 font-medium">Manage your session identity and access keys.</p>
            </header>

            <?php if($msg): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-2xl mb-8 flex items-center font-bold shadow-sm animate-in fade-in">
                    <i class="fas fa-check-circle mr-3 text-lg"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 flex items-center font-bold shadow-sm animate-in fade-in">
                    <i class="fas fa-exclamation-triangle mr-3 text-lg"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <div class="lg:col-span-4 flex flex-col gap-6">
                    <div class="glass-card rounded-[3rem] p-10 flex flex-col items-center text-center">
                        <div class="relative group">
                            <?php if (!empty($img_path)): ?>
                                <img src="<?php echo htmlspecialchars($img_path); ?>" class="w-40 h-40 rounded-[2.5rem] object-cover shadow-2xl border-4 border-white" id="avatar-preview" onerror="this.style.display='none'; document.getElementById('backup-avatar').style.display='flex'">
                                <div id="backup-avatar" class="hidden w-40 h-40 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-[2.5rem] items-center justify-center text-6xl font-black text-white shadow-2xl shadow-blue-500/30 border-4 border-white"><?php echo $initial; ?></div>
                            <?php else: ?>
                                <img src="" class="hidden w-40 h-40 rounded-[2.5rem] object-cover shadow-2xl border-4 border-white" id="avatar-preview">
                                <div id="backup-avatar" class="w-40 h-40 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-[2.5rem] flex items-center justify-center text-6xl font-black text-white shadow-2xl shadow-blue-500/30 border-4 border-white"><?php echo $initial; ?></div>
                            <?php endif; ?>
                            
                            <label for="avatar-upload" class="absolute -bottom-2 -right-2 w-12 h-12 bg-slate-900 text-white rounded-2xl flex items-center justify-center cursor-pointer hover:bg-primary transition-all border-4 border-white shadow-lg">
                                <i class="fas fa-camera text-sm"></i>
                            </label>
                            <input type="file" name="profile_avatar" id="avatar-upload" class="hidden" accept="image/*" onchange="previewImage(event)">
                        </div>

                        <h2 class="text-2xl font-black mt-6 text-slate-800 uppercase tracking-tighter"><?php echo htmlspecialchars($user['username']); ?></h2>
                        <span class="px-4 py-1.5 bg-emerald-50 text-emerald-600 rounded-full font-black text-[10px] uppercase tracking-widest mt-2 border border-emerald-100"><i class="fas fa-shield-check mr-1.5"></i> <?php echo ($user['role'] == 'super_admin') ? 'Super Admin' : 'Manager'; ?></span>
                        
                        <div class="w-full mt-10 space-y-3 text-left border-t border-slate-100 pt-8">
                            <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Current Session</h5>
                            <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <span class="text-xs font-bold text-slate-500"><i class="fas fa-globe mr-2"></i> IP</span>
                                <span class="font-black text-slate-800"><?php echo $_SERVER['REMOTE_ADDR']; ?></span>
                            </div>
                            <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <span class="text-xs font-bold text-slate-500"><i class="far fa-clock mr-2"></i> Time</span>
                                <span class="font-black text-slate-800"><?php echo date('h:i A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-8 glass-card rounded-[3rem] p-10 space-y-8">
                    <div>
                        <h3 class="text-lg font-black flex items-center gap-3 mb-6"><span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-xs"><i class="fas fa-user-edit"></i></span> Profile Details</h3>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Account Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-primary outline-none transition-all font-bold" required>
                        </div>
                    </div>

                    <div class="pt-8 border-t border-slate-100">
                        <h3 class="text-lg font-black flex items-center gap-3 mb-6 text-red-500"><span class="w-8 h-8 bg-red-100 text-red-500 rounded-lg flex items-center justify-center text-xs"><i class="fas fa-key"></i></span> Security Zone</h3>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-[10px] font-black text-red-400 uppercase tracking-widest mb-2 ml-1">Current Password (Required to save)</label>
                                <input type="password" name="current_password" placeholder="••••••••" class="w-full px-6 py-4 bg-red-50/50 border border-red-100 rounded-2xl focus:ring-4 focus:ring-red-500/10 focus:border-red-400 outline-none transition-all font-bold" required>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">New Password</label>
                                    <input type="password" name="new_password" placeholder="Min 8 chars, 1 uppercase, 1 num" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-primary outline-none transition-all font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Confirm New Password</label>
                                    <input type="password" name="confirm_password" placeholder="Repeat new password" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-primary outline-none transition-all font-bold">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-8 flex justify-end">
                        <button type="submit" class="px-10 py-4 bg-primary text-white rounded-2xl font-black shadow-xl shadow-blue-500/30 hover:bg-blue-700 transition-all">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('avatar-preview');
                var backup = document.getElementById('backup-avatar');
                output.src = reader.result;
                output.classList.remove('hidden');
                if(backup) backup.classList.add('hidden');
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>