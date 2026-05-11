<?php
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['admin_name'] ?? 'Admin_TCF';
$avatar_letters = strtoupper(substr($admin_name, 0, 2));
?>
<style>
    /* ==========================================
       ★★★ 顶级 Toyota 智能悬浮侧边栏 ★★★
    ========================================== */
    .premium-sidebar {
        position: fixed;
        top: 0; left: 0;
        height: 100vh;
        width: 72px; /* 默认极致收缩状态 */
        background: linear-gradient(180deg, #0b1120 0%, #050810 100%);
        border-right: 1px solid rgba(255, 255, 255, 0.03);
        z-index: 1000;
        transition: width 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
        display: flex;
        flex-direction: column;
        white-space: nowrap;
        overflow: hidden;
        /* 悬浮在内容之上时的微弱阴影 */
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.3);
    }

    /* 鼠标滑过时平滑展开 */
    .premium-sidebar:hover {
        width: 250px;
        box-shadow: 15px 0 50px rgba(0, 0, 0, 0.6);
    }

    /* 极高级的 Toyota 红色氛围背景光 (Ambient Glow) */
    .premium-sidebar::after {
        content: '';
        position: absolute;
        top: -100px; left: -100px;
        width: 250px; height: 250px;
        background: radial-gradient(circle, rgba(235, 10, 30, 0.15) 0%, transparent 70%);
        z-index: -1; pointer-events: none;
    }

    /* ==================== 1. 顶部 Logo 区 ==================== */
    .sidebar-brand {
        height: 80px; min-height: 80px;
        display: flex; align-items: center; padding: 0 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        z-index: 1;
    }
    .brand-icon {
        width: 32px; min-width: 32px; 
        filter: brightness(0) invert(1) opacity(0.9);
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .premium-sidebar:hover .brand-icon { 
        transform: rotate(360deg); /* 展开时极具科技感的微旋转 */
        filter: drop-shadow(0 0 8px rgba(235, 10, 30, 0.6)) brightness(0) invert(1); 
    }
    .brand-text { 
        margin-left: 16px; display: flex; flex-direction: column; 
        opacity: 0; transform: translateX(-10px); transition: all 0.4s ease; 
    }
    .premium-sidebar:hover .brand-text { opacity: 1; transform: translateX(0); }
    .brand-title { color: #f8fafc; font-weight: 900; letter-spacing: 2.5px; font-size: 16px; line-height: 1; font-family: 'Arial Black', sans-serif;}
    .brand-subtitle { color: #eb0a1e; font-weight: 800; letter-spacing: 1.5px; font-size: 8px; text-transform: uppercase; margin-top: 4px; }

    /* ==================== 2. 核心导航菜单区 ==================== */
    .sidebar-nav { 
        flex: 1; padding: 20px 0; overflow-y: auto; overflow-x: hidden; z-index: 1; 
    }
    .sidebar-nav::-webkit-scrollbar { display: none; } /* 隐藏滚动条保持整洁 */

    .nav-section {
        font-size: 10px; font-weight: 800; color: rgba(148, 163, 184, 0.4);
        letter-spacing: 2px; text-transform: uppercase; 
        margin: 24px 0 8px 24px;
        opacity: 0; transition: opacity 0.3s;
    }
    .premium-sidebar:hover .nav-section { opacity: 1; }

    .nav-item {
        display: flex; align-items: center; height: 44px; margin: 4px 12px;
        border-radius: 8px; color: #64748b; text-decoration: none; position: relative;
        transition: all 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    
    .nav-icon { 
        width: 48px; min-width: 48px; display: flex; justify-content: center; 
        font-size: 16px; transition: all 0.3s ease; 
    }
    .nav-label { 
        font-size: 13px; font-weight: 600; opacity: 0; 
        transform: translateX(-10px); transition: all 0.4s ease; letter-spacing: 0.5px; 
    }
    .premium-sidebar:hover .nav-label { opacity: 1; transform: translateX(0); }

    /* 高级 Active 与 Hover 状态 */
    .nav-item:hover { color: #e2e8f0; background: rgba(255, 255, 255, 0.02); transform: translateY(-1px); }
    .nav-item.active { 
        color: #fff; 
        background: linear-gradient(90deg, rgba(235, 10, 30, 0.08) 0%, transparent 100%); 
    }
    .nav-item.active::before {
        content: ''; position: absolute; left: -12px; top: 20%; height: 60%; width: 3px;
        background: #eb0a1e; border-radius: 0 4px 4px 0; 
        box-shadow: 0 0 12px rgba(235, 10, 30, 0.6);
    }
    .nav-item.active .nav-icon { color: #eb0a1e; }

    /* ==================== 3. 底部信息系统 ==================== */
    .sidebar-footer { border-top: 1px solid rgba(255, 255, 255, 0.03); z-index: 1; background: rgba(0, 0, 0, 0.2); }
    
    /* System Status */
    .sys-status { 
        display: flex; align-items: center; padding: 16px 20px; 
        opacity: 0; transition: opacity 0.3s; height: 50px;
    }
    .premium-sidebar:hover .sys-status { opacity: 1; }
    .status-dot { 
        width: 6px; height: 6px; border-radius: 50%; background: #10b981; 
        box-shadow: 0 0 8px #10b981; margin-right: 12px; animation: pulse 2s infinite; 
    }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }
    .status-text { display: flex; flex-direction: column; }
    .status-title { color: #10b981; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;}
    .status-time { color: #475569; font-size: 9px; font-weight: 600; margin-top: 2px; }

    /* User Profile Card */
    .user-panel { 
        display: flex; align-items: center; padding: 12px; margin: 0 10px 16px 10px; 
        border-radius: 12px; transition: background 0.3s; position: relative;
    }
    .premium-sidebar:hover .user-panel { background: rgba(255, 255, 255, 0.03); }
    .user-avatar { 
        width: 32px; min-width: 32px; height: 32px; border-radius: 8px; 
        background: linear-gradient(135deg, #1e293b, #0f172a); border: 1px solid #334155; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 12px; font-weight: 800; color: #fff; margin-left: 4px; position: relative;
    }
    .user-online { 
        position: absolute; bottom: -2px; right: -2px; width: 8px; height: 8px; 
        background: #10b981; border: 2px solid #0b1120; border-radius: 50%; 
    }
    .user-info { margin-left: 14px; display: flex; flex-direction: column; opacity: 0; transition: opacity 0.3s; flex: 1; }
    .premium-sidebar:hover .user-info { opacity: 1; }
    .user-name { color: #f8fafc; font-size: 12px; font-weight: 800; }
    .user-role { color: #64748b; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
    
    .logout-btn { 
        color: #64748b; font-size: 14px; transition: color 0.2s; opacity: 0; 
        position: absolute; right: 15px; 
    }
    .premium-sidebar:hover .logout-btn { opacity: 1; }
    .logout-btn:hover { color: #ef4444; }
</style>

<nav class="premium-sidebar">
    <div class="sidebar-brand">
        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Toyota.svg" class="brand-icon" alt="T">
        <div class="brand-text">
            <span class="brand-title">TOYOTA</span>
            <span class="brand-subtitle">Intelligence Center</span>
        </div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section">Operations</div>
        <a href="dashboard.php" class="nav-item <?php echo $current_page=='dashboard.php'?'active':''; ?>">
            <div class="nav-icon"><i class="fas fa-th-large"></i></div>
            <span class="nav-label">Control Center</span>
        </a>
        <a href="manage_bookings.php" class="nav-item <?php echo $current_page=='manage_bookings.php'?'active':''; ?>">
            <div class="nav-icon"><i class="fas fa-project-diagram"></i></div>
            <span class="nav-label">Sales Pipeline</span>
        </a>

        <div class="nav-section">Inventory</div>
        <a href="manage_cars.php" class="nav-item <?php echo $current_page=='manage_cars.php'?'active':''; ?>">
            <div class="nav-icon"><i class="fas fa-car-side"></i></div>
            <span class="nav-label">Fleet Assets</span>
        </a>
        <a href="manage_categories.php" class="nav-item <?php echo $current_page=='manage_categories.php'?'active':''; ?>">
            <div class="nav-icon"><i class="fas fa-layer-group"></i></div>
            <span class="nav-label">Vehicle Classes</span>
        </a>

        <div class="nav-section">System Control</div>
        <a href="manage_users.php" class="nav-item <?php echo $current_page=='manage_users.php'?'active':''; ?>">
            <div class="nav-icon"><i class="fas fa-users"></i></div>
            <span class="nav-label">Client Network</span>
        </a>
        <a href="manage_admins.php" class="nav-item <?php echo $current_page=='manage_admins.php'?'active':''; ?>">
            <div class="nav-icon"><i class="fas fa-user-shield"></i></div>
            <span class="nav-label">Access Control</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo $current_page=='profile.php'?'active':''; ?>">
            <div class="nav-icon"><i class="fas fa-cog"></i></div>
            <span class="nav-label">Security Settings</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="sys-status">
            <div class="status-dot"></div>
            <div class="status-text">
                <span class="status-title">System Online</span>
                <span class="status-time">Last Sync: Just now</span>
            </div>
        </div>

        <div class="user-panel">
            <div class="user-avatar">
                <?php echo $avatar_letters; ?>
                <div class="user-online"></div>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                <span class="user-role">Dealership Mgr</span>
            </div>
            <a href="logout.php" class="logout-btn" title="Terminate Session"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
</nav>


<style>
    /* 极致毛玻璃暗黑遮罩 */
    #cmd-k-overlay {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        z-index: 9999; display: none; align-items: flex-start; justify-content: center;
        padding-top: 12vh; opacity: 0; transition: opacity 0.2s ease;
    }
    #cmd-k-overlay.active { display: flex; opacity: 1; }

    /* 指令面板主体 */
    #cmd-k-palette {
        width: 100%; max-width: 600px;
        background: #1e293b; border: 1px solid #334155;
        border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
        overflow: hidden; transform: scale(0.95); transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    #cmd-k-overlay.active #cmd-k-palette { transform: scale(1); }

    /* 超大输入框 */
    .cmd-k-header { padding: 20px 24px; border-bottom: 1px solid #334155; display: flex; align-items: center; gap: 15px; }
    .cmd-k-header i { color: #EB0A1E; font-size: 1.2rem; }
    .cmd-k-input {
        width: 100%; background: transparent; border: none; outline: none;
        color: #f8fafc; font-size: 1.2rem; font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .cmd-k-input::placeholder { color: #64748b; }

    /* 快捷指令列表 */
    .cmd-k-body { max-height: 400px; overflow-y: auto; padding: 12px; }
    .cmd-k-body::-webkit-scrollbar { width: 6px; }
    .cmd-k-body::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
    
    .cmd-group-title {
        font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase;
        letter-spacing: 1px; margin: 10px 0 5px 12px;
    }

    .cmd-item {
        display: flex; align-items: center; gap: 12px; padding: 12px 15px;
        border-radius: 10px; color: #cbd5e1; text-decoration: none; font-size: 0.95rem;
        transition: all 0.1s; cursor: pointer; border: 1px solid transparent;
    }
    .cmd-item i { width: 20px; text-align: center; color: #94a3b8; }
    
    /* 键盘选中或鼠标悬浮的状态 */
    .cmd-item:hover, .cmd-item.selected {
        background: rgba(235, 10, 30, 0.1); color: white;
        border: 1px solid rgba(235, 10, 30, 0.2);
    }
    .cmd-item:hover i, .cmd-item.selected i { color: #EB0A1E; }
    
    .cmd-badge {
        margin-left: auto; font-size: 0.7rem; background: #334155; color: #94a3b8;
        padding: 2px 6px; border-radius: 4px; font-weight: 600; font-family: monospace;
    }
</style>

<div id="cmd-k-overlay">
    <div id="cmd-k-palette">
        <div class="cmd-k-header">
            <i class="fas fa-terminal"></i>
            <input type="text" id="cmd-k-input" class="cmd-k-input" placeholder="Search orders, cars, or actions..." autocomplete="off">
            <span class="cmd-badge">ESC</span>
        </div>
        <div class="cmd-k-body" id="cmd-k-list">
            
            <div class="cmd-group-title">Navigation</div>
            <a href="dashboard.php" class="cmd-item selected"><i class="fas fa-home"></i> Go to Dashboard <span class="cmd-badge">↵</span></a>
            <a href="manage_bookings.php" class="cmd-item"><i class="fas fa-clipboard-list"></i> Manage Sales Pipeline</a>
            <a href="manage_cars.php" class="cmd-item"><i class="fas fa-car"></i> View Vehicle Inventory</a>
            
            <div class="cmd-group-title">Quick Actions</div>
            <a href="manage_cars.php?action=add" class="cmd-item"><i class="fas fa-plus-circle"></i> Add New Vehicle</a>
            <a href="manage_categories.php" class="cmd-item"><i class="fas fa-tags"></i> Manage Categories</a>
            
            <div class="cmd-group-title">System & Security</div>
            <a href="manage_users.php" class="cmd-item"><i class="fas fa-users"></i> Search Customers</a>
            <a href="profile.php" class="cmd-item"><i class="fas fa-user-shield"></i> My Profile & Security</a>
            <a href="logout.php" class="cmd-item"><i class="fas fa-sign-out-alt"></i> Secure Logout</a>
        </div>
    </div>
</div>

<script>
    const overlay = document.getElementById('cmd-k-overlay');
    const cmdInput = document.getElementById('cmd-k-input');
    const cmdList = document.getElementById('cmd-k-list');
    let cmdItems = [];
    let selectedIndex = 0;
    let searchTimeout = null;

    // 默认的静态菜单 HTML (当输入框为空时显示)
    const defaultMenuHTML = `
        <div class="cmd-group-title">Navigation</div>
        <a href="dashboard.php" class="cmd-item selected"><i class="fas fa-home"></i> Go to Dashboard <span class="cmd-badge">↵</span></a>
        <a href="manage_bookings.php" class="cmd-item"><i class="fas fa-clipboard-list"></i> Manage Sales Pipeline</a>
        <a href="manage_cars.php" class="cmd-item"><i class="fas fa-car"></i> View Vehicle Inventory</a>
        <div class="cmd-group-title">System & Security</div>
        <a href="manage_users.php" class="cmd-item"><i class="fas fa-users"></i> Manage Customers</a>
        <a href="profile.php" class="cmd-item"><i class="fas fa-user-shield"></i> My Profile</a>
    `;

    // 1. 监听全局快捷键 (Ctrl+K 或 Cmd+K)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            togglePalette();
        }
        
        if (overlay.classList.contains('active')) {
            if (e.key === 'Escape') {
                closePalette();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                moveSelection(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                moveSelection(-1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const selected = cmdItems.find(item => item.classList.contains('selected'));
                if (selected) window.location.href = selected.href;
            }
        }
    });

    // 2. 核心升级：AJAX 动态数据库查询
    cmdInput.addEventListener('input', (e) => {
        const val = e.target.value.trim();
        
        // 如果输入为空，显示默认菜单
        if (val.length === 0) {
            cmdList.innerHTML = defaultMenuHTML;
            refreshCmdItems();
            return;
        }

        // 防抖 (Debounce)
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            // 显示加载状态
            cmdList.innerHTML = `<div style="text-align:center; padding:20px; color:#64748b;"><i class="fas fa-circle-notch fa-spin me-2"></i> Searching database...</div>`;
            
            // 使用 Fetch API 呼叫后台接口
            fetch(`ajax_search.php?q=${encodeURIComponent(val)}`)
                .then(response => response.json())
                .then(data => {
                    renderResults(data);
                })
                .catch(err => {
                    cmdList.innerHTML = `<div style="color:#ef4444; padding:15px; text-align:center;">Database connection error</div>`;
                });
        }, 250); 
    });

    // 3. 渲染数据库返回的结果
    function renderResults(data) {
        cmdList.innerHTML = ''; // 清空列表
        
        if (data.length === 0) {
            cmdList.innerHTML = `<div style="text-align:center; padding:20px; color:#64748b;">No results found for "${cmdInput.value}"</div>`;
            cmdItems = [];
            return;
        }

        let currentGroup = '';
        let html = '';

        data.forEach((item, index) => {
            // 分组标题
            if (item.group !== currentGroup) {
                html += `<div class="cmd-group-title">${item.group}</div>`;
                currentGroup = item.group;
            }
            // 数据卡片
            const isSelected = index === 0 ? 'selected' : '';
            html += `
                <a href="${item.url}" class="cmd-item ${isSelected}">
                    <i class="fas ${item.icon}"></i> 
                    <div style="flex:1;">
                        <div style="font-weight:600; color:#f8fafc;">${item.title}</div>
                        <div style="font-size:0.75rem; color:#64748b;">${item.subtitle}</div>
                    </div>
                    ${index === 0 ? '<span class="cmd-badge">↵</span>' : ''}
                </a>
            `;
        });

        cmdList.innerHTML = html;
        refreshCmdItems(); // 重新绑定 DOM
    }

    // 更新内部的 Item 阵列
    function refreshCmdItems() {
        cmdItems = Array.from(document.querySelectorAll('.cmd-item'));
        selectedIndex = 0;
    }

    function togglePalette() {
        if (overlay.classList.contains('active')) {
            closePalette();
        } else {
            overlay.classList.add('active');
            cmdInput.value = ''; 
            cmdList.innerHTML = defaultMenuHTML; 
            refreshCmdItems();
            cmdInput.focus();
        }
    }

    function closePalette() {
        overlay.classList.remove('active');
        cmdInput.blur();
    }

    function moveSelection(direction) {
        if (cmdItems.length === 0) return;
        let currentVisibleIndex = cmdItems.findIndex(item => item.classList.contains('selected'));
        if (currentVisibleIndex === -1) currentVisibleIndex = 0;
        else currentVisibleIndex += direction;

        if (currentVisibleIndex >= cmdItems.length) currentVisibleIndex = 0;
        if (currentVisibleIndex < 0) currentVisibleIndex = cmdItems.length - 1;

        cmdItems.forEach((item, i) => {
            item.classList.toggle('selected', i === currentVisibleIndex);
            // 移动 "↵" 徽章
            const badge = item.querySelector('.cmd-badge');
            if(badge) badge.remove();
            if(i === currentVisibleIndex) item.insertAdjacentHTML('beforeend', '<span class="cmd-badge">↵</span>');
        });
        
        cmdItems[currentVisibleIndex].scrollIntoView({ block: 'nearest' });
    }

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closePalette();
    });
</script>

<style>
    .cmd-toast {
        position: fixed; bottom: 30px; right: 30px;
        background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(59, 130, 246, 0.3);
        padding: 16px 24px; border-radius: 16px;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5), 0 0 0 4px rgba(59,130,246,0.1);
        display: flex; align-items: center; gap: 18px;
        z-index: 99999;
        transform: translateX(150%); 
        transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .cmd-toast.show { transform: translateX(0); }
    
    .cmd-toast-icon {
        background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;
        width: 40px; height: 40px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
    }
</style>

<div id="cmd-toast" class="cmd-toast">
    <div class="cmd-toast-icon"><i class="fas fa-search"></i></div>
    <div>
        <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Smart Search Active</div>
        <div style="font-size: 1.05rem; color: #f8fafc; margin-top: 2px;">
            Filtered for: <strong id="cmd-toast-term" style="color: #60a5fa; font-weight: 800;"></strong>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const urlParams = new URLSearchParams(window.location.search);
        const searchTerm = urlParams.get('cmd_search');

        if (searchTerm) {
            document.getElementById('cmd-toast-term').innerText = searchTerm;
            
            const toast = document.getElementById('cmd-toast');
            setTimeout(() => toast.classList.add('show'), 300);
            setTimeout(() => toast.classList.remove('show'), 4000);

            const searchBoxes = [
                document.getElementById('userSearch'), 
                document.getElementById('carSearch'), 
                document.getElementById('bookingSearch'), 
                document.getElementById('catSearch') 
            ];
            
            searchBoxes.forEach(box => {
                if (box) {
                    box.value = searchTerm;
                    box.dispatchEvent(new Event('keyup'));
                }
            });
            
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
</script>