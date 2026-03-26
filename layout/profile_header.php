<?php
$siteName = e(Settings::get('site_name', 'MCStore'));
$currentUser = Auth::check() ? Auth::user() : null;
$unreadCount = $currentUser ? getUnreadNotifications($currentUser['id']) : 0;

// Feature toggles
$featTopup = Settings::get('topup_enabled', '1') === '1';
$featGacha = Settings::get('gacha_enabled', '1') === '1';
$featShop  = Settings::get('shop_enabled', '1') === '1';

// Build profile menu — only show enabled features
$profileMenu = [
    ['icon' => 'fa-user',       'label' => 'ภาพรวม',        'url' => 'profile',               'id' => 'overview',       'show' => true],
    ['icon' => 'fa-receipt',    'label' => 'ออเดอร์',        'url' => 'profile/orders',         'id' => 'orders',        'show' => true],
    ['icon' => 'fa-wallet',     'label' => 'กระเป๋าเงิน',     'url' => 'profile/wallet',         'id' => 'wallet',        'show' => true],
    ['icon' => 'fa-coins',      'label' => 'เติมเงิน',       'url' => 'topup',                  'id' => 'topup',         'show' => $featTopup],
    ['icon' => 'fa-dice',       'label' => 'ประวัติกาชา',     'url' => 'profile/gacha',          'id' => 'gacha',         'show' => $featGacha],
    ['icon' => 'fa-truck',      'label' => 'การส่งของ',      'url' => 'profile/deliveries',     'id' => 'deliveries',    'show' => true],
    ['icon' => 'fa-bell',       'label' => 'แจ้งเตือน',      'url' => 'profile/notifications',  'id' => 'notifications', 'show' => true, 'badge' => $unreadCount],
    ['icon' => 'fa-user-edit',  'label' => 'แก้ไขโปรไฟล์',    'url' => 'profile/edit',           'id' => 'edit',          'show' => true],
    ['icon' => 'fa-key',        'label' => 'เปลี่ยนรหัสผ่าน',  'url' => 'profile/password',       'id' => 'password',      'show' => true],
    ['icon' => 'fa-cog',        'label' => 'ตั้งค่า',         'url' => 'profile/settings',       'id' => 'settings',      'show' => true],
];

// Detect current profile page
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
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        body { background-color: var(--color-bg); color: var(--color-text); font-family: var(--font-family); margin: 0; }
        .btn-primary { background: var(--gradient-btn, var(--color-primary)); color: #fff; }
        .btn-primary:hover { opacity: 0.9; }
        .card { border-radius: 0.75rem; }

        /* Profile Sidebar */
        .profile-sidebar {
            width: 260px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            background: rgba(5,12,30,0.95);
            border-right: 1px solid var(--color-border);
            backdrop-filter: blur(20px);
            overflow-y: auto;
            z-index: 50;
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
        }
        .profile-sidebar::-webkit-scrollbar { width: 4px; }
        .profile-sidebar::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: 4px; }

        .profile-content { margin-left: 260px; min-height: 100vh; }

        .profile-topbar {
            position: sticky; top: 0; z-index: 40;
            background: rgba(8,20,45,0.9);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--color-border);
        }

        .sidebar-link {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.6rem 1rem; margin: 0.15rem 0.75rem;
            border-radius: 0.5rem; font-size: 0.875rem;
            color: var(--color-text-muted, #94a3b8);
            transition: all 0.2s;
        }
        .sidebar-link:hover { background: rgba(56,189,248,0.08); color: var(--color-text); }
        .sidebar-link.active { background: rgba(56,189,248,0.15); color: var(--color-primary); font-weight: 600; }
        .sidebar-link .badge {
            margin-left: auto;
            background: var(--gradient-btn);
            color: #fff; font-size: 0.65rem;
            padding: 0.1rem 0.45rem; border-radius: 9999px; font-weight: 700;
        }

        @media (max-width: 1023px) {
            .profile-sidebar { transform: translateX(-100%); }
            .profile-sidebar.open { transform: translateX(0); }
            .profile-content { margin-left: 0; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 45; }
            .sidebar-overlay.open { display: block; }
        }
    </style>
</head>
<body>
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="profile-sidebar" id="profile-sidebar">
        <!-- User Info -->
        <div class="px-5 py-5 border-b border-white/5">
            <div class="flex items-center gap-3 mb-3">
                <img src="https://mc-heads.net/avatar/<?= e($currentUser['username']) ?>/48" class="w-12 h-12 rounded-xl" alt="avatar">
                <div class="min-w-0">
                    <p class="font-bold truncate"><?= e($currentUser['username']) ?></p>
                    <p class="text-lg font-bold" style="color: var(--color-accent);" data-balance><?= formatMoney($currentUser['balance']) ?></p>
                </div>
            </div>
            <?php if ($featTopup): ?>
            <a href="<?= url('topup') ?>" class="btn-primary w-full py-2 rounded-lg text-sm font-semibold text-center block">
                <i class="fas fa-plus mr-1"></i> เติมเงิน
            </a>
            <?php endif; ?>
        </div>

        <!-- Menu -->
        <nav class="py-3 flex-1">
            <?php foreach ($profileMenu as $item):
                if (!$item['show']) continue;
            ?>
                <a href="<?= url($item['url']) ?>" class="sidebar-link <?= $currentProfilePage === $item['id'] ? 'active' : '' ?>">
                    <i class="fas <?= $item['icon'] ?> w-5 text-center text-sm"></i>
                    <span><?= $item['label'] ?></span>
                    <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                        <span class="badge"><?= $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Bottom -->
        <div class="px-4 py-4 border-t border-white/5">
            <a href="<?= url('') ?>" class="sidebar-link" style="margin: 0; padding-left: 0;">
                <i class="fas fa-home w-5 text-center text-sm"></i>
                <span>กลับหน้าแรก</span>
            </a>
            <?php if ($featShop): ?>
            <a href="<?= url('shop') ?>" class="sidebar-link" style="margin: 0; padding-left: 0;">
                <i class="fas fa-store w-5 text-center text-sm"></i>
                <span>ร้านค้า</span>
            </a>
            <?php endif; ?>
            <?php if (Auth::isAdmin()): ?>
            <a href="<?= url('admin') ?>" class="sidebar-link text-yellow-400" style="margin: 0; padding-left: 0;">
                <i class="fas fa-shield-alt w-5 text-center text-sm"></i>
                <span>แอดมิน</span>
            </a>
            <?php endif; ?>
            <a href="<?= url('logout') ?>" class="sidebar-link text-red-400" style="margin: 0; padding-left: 0;">
                <i class="fas fa-sign-out-alt w-5 text-center text-sm"></i>
                <span>ออกจากระบบ</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="profile-content">
        <!-- Top Bar -->
        <header class="profile-topbar px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden text-lg"><i class="fas fa-bars"></i></button>
                <h2 class="text-sm font-semibold opacity-80"><?= $pageTitle ?? 'โปรไฟล์' ?></h2>
            </div>
            <div class="flex items-center gap-4">
                <a href="<?= url('cart') ?>" class="opacity-60 hover:opacity-100 transition"><i class="fas fa-shopping-cart"></i></a>
                <a href="<?= url('profile/notifications') ?>" class="relative opacity-60 hover:opacity-100 transition">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="absolute -top-2 -right-2 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" style="background: var(--gradient-btn); font-size: 0.6rem;"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php if ($flashSuccess = flash('success')): ?>
            <script>document.addEventListener('DOMContentLoaded', () => { Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: <?= json_encode($flashSuccess) ?>, timer: 3000, showConfirmButton: false }); });</script>
        <?php endif; ?>
        <?php if ($flashError = flash('error')): ?>
            <script>document.addEventListener('DOMContentLoaded', () => { Swal.fire({ icon: 'error', title: 'ผิดพลาด!', text: <?= json_encode($flashError) ?> }); });</script>
        <?php endif; ?>
        <?php if ($flashWarning = flash('warning')): ?>
            <script>document.addEventListener('DOMContentLoaded', () => { Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: <?= json_encode($flashWarning) ?> }); });</script>
        <?php endif; ?>
        <?php if ($flashInfo = flash('info')): ?>
            <script>document.addEventListener('DOMContentLoaded', () => { Swal.fire({ icon: 'info', title: 'ข้อมูล', text: <?= json_encode($flashInfo) ?>, timer: 4000, showConfirmButton: false }); });</script>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="p-6">
