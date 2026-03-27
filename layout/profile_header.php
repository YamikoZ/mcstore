<?php
$siteName = e(Settings::get('site_name', 'MCStore'));
$currentUser = Auth::check() ? Auth::user() : null;
$unreadCount = $currentUser ? getUnreadNotifications($currentUser['id']) : 0;

// Feature toggles
$featTopup = Settings::get('topup_enabled', '1') === '1';
$featGacha = Settings::get('gacha_enabled', '1') === '1';
$featShop  = Settings::get('shop_enabled', '1') === '1';

// Build profile menu
$profileMenu = [
    ['icon' => 'fa-user',       'label' => 'ภาพรวม',        'url' => 'profile',               'id' => 'overview',       'show' => true],
    ['icon' => 'fa-receipt',    'label' => 'ออเดอร์',        'url' => 'profile/orders',         'id' => 'orders',        'show' => true],
    ['icon' => 'fa-wallet',     'label' => 'กระเป๋าเงิน',     'url' => 'profile/wallet',         'id' => 'wallet',        'show' => true],
    ['icon' => 'fa-coins',      'label' => 'เติมเงิน',        'url' => 'topup',                  'id' => 'topup',         'show' => $featTopup],
    ['icon' => 'fa-dice',       'label' => 'ประวัติกาชา',     'url' => 'profile/gacha',          'id' => 'gacha',         'show' => $featGacha],
    ['icon' => 'fa-truck',      'label' => 'การส่งของ',       'url' => 'profile/deliveries',     'id' => 'deliveries',    'show' => true],
    ['icon' => 'fa-bell',       'label' => 'แจ้งเตือน',       'url' => 'profile/notifications',  'id' => 'notifications', 'show' => true, 'badge' => $unreadCount],
    ['icon' => 'fa-user-edit',  'label' => 'แก้ไขโปรไฟล์',   'url' => 'profile/edit',           'id' => 'edit',          'show' => true],
    ['icon' => 'fa-key',        'label' => 'เปลี่ยนรหัสผ่าน', 'url' => 'profile/password',       'id' => 'password',      'show' => true],
    ['icon' => 'fa-cog',        'label' => 'ตั้งค่า',          'url' => 'profile/settings',       'id' => 'settings',      'show' => true],
];

$currentProfilePage = $param1 ?? 'overview';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' . $siteName : $siteName ?></title>
    <meta name="base-url" content="<?= url('') ?>">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <?php if ($favicon = Settings::get('site_favicon')): ?>
        <link rel="icon" href="<?= e($favicon) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/theme.php') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: {
                primary: 'var(--color-primary)', secondary: 'var(--color-secondary)',
                accent: 'var(--color-accent)', surface: 'var(--color-surface)',
                'surface-dark': 'var(--color-surface-dark)',
            }}}
        }
    </script>
    <style>
        * { box-sizing: border-box; }
        body { background-color: var(--color-bg); color: var(--color-text); font-family: var(--font-family); margin: 0; }
        .btn-primary { background: var(--gradient-btn, var(--color-primary)); color: #fff; transition: opacity .2s, transform .1s; }
        .btn-primary:hover { opacity: .88; transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }
        .card { background: var(--color-surface); border-radius: 1rem; border: 1px solid var(--color-border); }

        /* ───── Sidebar ───── */
        .profile-sidebar {
            width: 264px;
            position: fixed; top: 0; left: 0; bottom: 0;
            background: var(--color-surface-dark, #0a1628);
            border-right: 1px solid var(--color-border);
            overflow-y: auto; z-index: 50;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
            display: flex; flex-direction: column;
        }
        .profile-sidebar::-webkit-scrollbar { width: 3px; }
        .profile-sidebar::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: 3px; }

        /* User card in sidebar */
        .sidebar-user-card {
            position: relative; overflow: hidden;
            padding: 1.5rem 1.25rem 1.25rem;
            border-bottom: 1px solid var(--color-border);
        }
        .sidebar-user-card::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, var(--color-primary, #38bdf8)22, transparent 60%);
            pointer-events: none;
        }
        .sidebar-avatar {
            position: relative; display: inline-block;
        }
        .sidebar-avatar img {
            width: 56px; height: 56px;
            border-radius: .75rem;
            border: 2px solid var(--color-primary);
            box-shadow: 0 0 16px var(--color-primary)55;
        }
        .sidebar-avatar-status {
            position: absolute; bottom: -3px; right: -3px;
            width: 14px; height: 14px; border-radius: 50%;
            background: #4ade80; border: 2px solid var(--color-surface-dark);
        }

        /* Nav items */
        .sidebar-section-label {
            font-size: .65rem; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; opacity: .4;
            padding: .9rem 1.25rem .3rem;
        }
        .sidebar-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .55rem 1rem; margin: .1rem .75rem;
            border-radius: .625rem; font-size: .875rem;
            color: var(--color-text-muted, #94a3b8);
            transition: all .18s;
            cursor: pointer; text-decoration: none;
        }
        .sidebar-link .link-icon {
            width: 2rem; height: 2rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: .5rem; font-size: .8rem; flex-shrink: 0;
            background: rgba(255,255,255,.05);
            transition: all .18s;
        }
        .sidebar-link:hover { color: var(--color-text); background: rgba(255,255,255,.05); }
        .sidebar-link:hover .link-icon { background: rgba(56,189,248,.15); color: var(--color-primary); }
        .sidebar-link.active {
            color: #fff;
            background: linear-gradient(90deg, var(--color-primary)22, transparent);
        }
        .sidebar-link.active .link-icon {
            background: var(--color-primary);
            color: #fff;
            box-shadow: 0 0 12px var(--color-primary)88;
        }
        .sidebar-link.active::before {
            content: ''; position: absolute; left: 0;
            width: 3px; height: 2rem; border-radius: 0 2px 2px 0;
            background: var(--color-primary);
        }
        .sidebar-link { position: relative; }
        .link-badge {
            margin-left: auto; background: var(--color-primary);
            color: #fff; font-size: .6rem; font-weight: 700;
            min-width: 1.1rem; height: 1.1rem; border-radius: 9999px;
            display: flex; align-items: center; justify-content: center; padding: 0 .25rem;
        }

        /* Bottom links */
        .sidebar-bottom { padding: .75rem; border-top: 1px solid var(--color-border); }
        .sidebar-bottom-link {
            display: flex; align-items: center; gap: .625rem;
            padding: .45rem .75rem; border-radius: .5rem;
            font-size: .8rem; color: var(--color-text-muted, #94a3b8);
            transition: all .15s; text-decoration: none;
        }
        .sidebar-bottom-link:hover { background: rgba(255,255,255,.06); color: var(--color-text); }
        .sidebar-bottom-link.danger:hover { background: rgba(239,68,68,.1); color: #f87171; }

        /* Content */
        .profile-content { margin-left: 264px; min-height: 100vh; }

        /* Topbar */
        .profile-topbar {
            position: sticky; top: 0; z-index: 40;
            background: rgba(8,20,45,.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--color-border);
        }
        .topbar-breadcrumb { font-size: .75rem; opacity: .45; margin-bottom: .1rem; }

        /* Mobile */
        @media (max-width: 1023px) {
            .profile-sidebar { transform: translateX(-100%); }
            .profile-sidebar.open { transform: translateX(0); box-shadow: 4px 0 24px rgba(0,0,0,.5); }
            .profile-content { margin-left: 0; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 45; backdrop-filter: blur(2px); }
            .sidebar-overlay.open { display: block; }
        }

        /* Cards */
        .stat-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 1rem; padding: 1.25rem; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: -20px; right: -20px; width: 70px; height: 70px; border-radius: 50%; opacity: .08; }
        .stat-card.primary::before { background: var(--color-primary); }
        .stat-card.accent::before { background: var(--color-accent); }
        .stat-card.secondary::before { background: var(--color-secondary); }
        .stat-icon { width: 2.5rem; height: 2.5rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; font-size: 1rem; }

        /* Input */
        .form-input {
            width: 100%; padding: .75rem 1rem;
            background: var(--color-bg); color: var(--color-text);
            border: 1px solid var(--color-border); border-radius: .75rem;
            outline: none; transition: border-color .2s, box-shadow .2s; font-size: .875rem;
        }
        .form-input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px var(--color-primary)22; }
        .form-input:disabled { opacity: .45; cursor: not-allowed; }

        /* Status badge */
        .status-badge {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .2rem .65rem; border-radius: 9999px; font-size: .72rem; font-weight: 600;
        }
        .status-badge.success { background: rgba(74,222,128,.12); color: #4ade80; }
        .status-badge.warning { background: rgba(250,204,21,.12); color: #facc15; }
        .status-badge.danger  { background: rgba(248,113,113,.12); color: #f87171; }
        .status-badge.info    { background: rgba(96,165,250,.12);  color: #60a5fa; }
        .status-badge.muted   { background: rgba(148,163,184,.1);  color: #94a3b8; }

        /* Rarity glows */
        .rarity-common    { border-color: #9CA3AF55; }
        .rarity-uncommon  { border-color: #34D39944; box-shadow: 0 0 8px #34D39920; }
        .rarity-rare      { border-color: #60A5FA55; box-shadow: 0 0 10px #60A5FA25; }
        .rarity-epic      { border-color: #A78BFA66; box-shadow: 0 0 12px #A78BFA30; }
        .rarity-legendary { border-color: #FBBF2488; box-shadow: 0 0 16px #FBBF2440; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- ───── Sidebar ───── -->
    <aside class="profile-sidebar" id="profile-sidebar">
        <!-- User Card -->
        <div class="sidebar-user-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="sidebar-avatar">
                    <img src="https://mc-heads.net/avatar/<?= e($currentUser['username']) ?>/80" alt="avatar">
                    <span class="sidebar-avatar-status"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold truncate text-sm"><?= e($currentUser['username']) ?></p>
                    <p class="text-xs opacity-50 truncate"><?= e($currentUser['email'] ?? 'ไม่มีอีเมล') ?></p>
                    <p class="font-bold mt-0.5" style="color: var(--color-accent); font-size: .95rem;" data-balance>
                        <?= formatMoney($currentUser['balance']) ?>
                    </p>
                </div>
            </div>
            <?php if ($featTopup): ?>
            <a href="<?= url('topup') ?>" class="btn-primary flex items-center justify-center gap-2 w-full py-2 rounded-xl text-sm font-semibold">
                <i class="fas fa-plus text-xs"></i> เติมเงิน
            </a>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 py-2">
            <p class="sidebar-section-label">เมนูหลัก</p>
            <?php foreach ($profileMenu as $item):
                if (!$item['show']) continue;
                $isActive = $currentProfilePage === $item['id'];
            ?>
                <a href="<?= url($item['url']) ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                    <span class="link-icon"><i class="fas <?= $item['icon'] ?>"></i></span>
                    <span><?= $item['label'] ?></span>
                    <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                        <span class="link-badge"><?= $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Bottom Links -->
        <div class="sidebar-bottom">
            <a href="<?= url('') ?>" class="sidebar-bottom-link">
                <i class="fas fa-home w-4 text-center text-xs"></i> กลับหน้าแรก
            </a>
            <?php if ($featShop): ?>
            <a href="<?= url('shop') ?>" class="sidebar-bottom-link">
                <i class="fas fa-store w-4 text-center text-xs"></i> ร้านค้า
            </a>
            <?php endif; ?>
            <?php if (Auth::isAdmin()): ?>
            <a href="<?= url('admin') ?>" class="sidebar-bottom-link" style="color: #fbbf24;">
                <i class="fas fa-shield-alt w-4 text-center text-xs"></i> แผงแอดมิน
            </a>
            <?php endif; ?>
            <a href="<?= url('logout') ?>" class="sidebar-bottom-link danger">
                <i class="fas fa-sign-out-alt w-4 text-center text-xs"></i> ออกจากระบบ
            </a>
        </div>
    </aside>

    <!-- ───── Main Content ───── -->
    <div class="profile-content">
        <!-- Top Bar -->
        <header class="profile-topbar px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-bars text-sm"></i>
                </button>
                <div>
                    <p class="topbar-breadcrumb">MCStore / โปรไฟล์</p>
                    <h2 class="text-sm font-semibold leading-tight"><?= $pageTitle ?? 'โปรไฟล์' ?></h2>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= url('profile/notifications') ?>" class="relative w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 transition opacity-70 hover:opacity-100">
                    <i class="fas fa-bell text-sm"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="absolute -top-1 -right-1 w-4 h-4 flex items-center justify-center text-white rounded-full"
                              style="background: var(--color-primary); font-size: .55rem; font-weight: 700;"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
                <img src="https://mc-heads.net/avatar/<?= e($currentUser['username']) ?>/32" class="w-8 h-8 rounded-lg" alt="avatar">
            </div>
        </header>

        <!-- Flash Messages -->
        <?php if ($flashSuccess = flash('success')): ?>
            <script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: <?= json_encode($flashSuccess) ?>, timer: 3000, showConfirmButton: false, background: '#0f1e3a', color: '#e2e8f0' }));</script>
        <?php endif; ?>
        <?php if ($flashError = flash('error')): ?>
            <script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon: 'error', title: 'ผิดพลาด!', text: <?= json_encode($flashError) ?>, background: '#0f1e3a', color: '#e2e8f0' }));</script>
        <?php endif; ?>
        <?php if ($flashWarning = flash('warning')): ?>
            <script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: <?= json_encode($flashWarning) ?>, background: '#0f1e3a', color: '#e2e8f0' }));</script>
        <?php endif; ?>
        <?php if ($flashInfo = flash('info')): ?>
            <script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon: 'info', title: 'ข้อมูล', text: <?= json_encode($flashInfo) ?>, timer: 4000, showConfirmButton: false, background: '#0f1e3a', color: '#e2e8f0' }));</script>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="p-6">
