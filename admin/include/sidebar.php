<?php
// 获取网站名字并提取缩写
$site_name = defined('SITE_NAME') ? SITE_NAME : 'Car Rental';
$words = explode(" ", $site_name);
$initials = "";
foreach ($words as $w) { $initials .= strtoupper($w[0]); if(strlen($initials)>=2) break; }
// 获取当前页面名称用于高亮
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-64 h-screen fixed left-0 top-0 bg-white/70 backdrop-blur-xl border-r border-white flex flex-col p-6 z-50 shadow-[10px_0_30px_rgba(226,232,240,0.6)]">
    
    <div class="flex items-center gap-3 mb-10 shrink-0">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30 font-bold text-xl">
            <?php echo $initials; ?>
        </div>
        <div class="font-extrabold text-[1.15rem] tracking-tight text-slate-800">
            <?php 
                $first_word = $words[0];
                $rest_words = substr($site_name, strlen($first_word));
                echo $first_word;
            ?><span class="text-blue-600"><?php echo $rest_words; ?></span>
        </div>
    </div>

    <nav class="flex flex-col gap-1.5 flex-1 overflow-y-auto" style="scrollbar-width: none;">
        
        <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-2 mb-2 ml-4">Overview</div>
        
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold transition-all <?php echo $current_page=='dashboard.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1'; ?>">
            <i class="fas fa-chart-pie w-5 text-center text-lg"></i> Dashboard
        </a>
        
        <a href="manage_bookings.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold transition-all <?php echo $current_page=='manage_bookings.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1'; ?>">
            <i class="fas fa-calendar-check w-5 text-center text-lg"></i> Bookings
        </a>

        <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-6 mb-2 ml-4">Fleet Control</div>

        <a href="live_tracking.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 font-bold hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all mb-1 group">
    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 group-hover:text-blue-600 group-hover:bg-blue-100 transition-all">
        <i class="fas fa-map-marked-alt"></i>
    </div>
    <span>Live GPS</span>
    <span class="ml-auto w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
</a>
        
        <a href="manage_cars.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold transition-all <?php echo $current_page=='manage_cars.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1'; ?>">
            <i class="fas fa-car-side w-5 text-center text-lg"></i> Vehicles
        </a>
        
        <a href="manage_categories.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold transition-all <?php echo $current_page=='manage_categories.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1'; ?>">
            <i class="fas fa-tags w-5 text-center text-lg"></i> Categories
        </a>

        <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mt-6 mb-2 ml-4">System & Users</div>
        
        <a href="manage_users.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold transition-all <?php echo $current_page=='manage_users.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1'; ?>">
            <i class="fas fa-user-friends w-5 text-center text-lg"></i> Customers
        </a>

        <a href="contact_messages.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold transition-all <?php echo $current_page=='contact_messages.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1'; ?>">
            <i class="fas fa-envelope-open-text w-5 text-center text-lg"></i> Contact Messages
        </a>

        <a href="manage_promocodes.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold transition-all <?php echo $current_page=='manage_promocodes.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1'; ?>">
            <i class="fas fa-ticket-alt w-5 text-center text-lg"></i> Promo Codes
        </a>
        
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
        <a href="manage_admins.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold transition-all <?php echo $current_page=='manage_admins.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1'; ?>">
            <i class="fas fa-shield-alt w-5 text-center text-lg"></i> Admins
        </a>
        <?php endif; ?>
        
        <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold transition-all <?php echo $current_page=='profile.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1'; ?>">
            <i class="fas fa-user-circle w-5 text-center text-lg"></i> My Profile
        </a>
    </nav>

    <a href="logout.php" class="mt-auto shrink-0 flex items-center gap-3 px-4 py-3 text-red-500 font-semibold rounded-2xl bg-red-50/50 border border-red-100 hover:bg-red-500 hover:text-white transition-all hover:-translate-y-1 shadow-sm">
        <i class="fas fa-power-off w-5 text-center text-lg"></i> Sign Out
    </a>
</aside>
