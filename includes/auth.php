<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 核心修复：检查管理员登录状态
// 不再直接引用可能不存在的 $_SESSION['user_id']
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// 提示：这个文件被 include 后会自动运行上面的检查
// 这样你就不需要在每个页面手动写 checkLogin(); 了
?>