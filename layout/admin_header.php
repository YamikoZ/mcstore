<?php
$siteName = e(Settings::get('site_name', 'MCStore'));
$currentUser = Auth::check() ? Auth::user() : null;
$pendingTopups = Database::getInstance()->count("SELECT COUNT(*) FROM topup_transactions WHERE status = 'pending'");
$unreadContacts = Database::getInstance()->count("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");
$pendingDeliveries = Database::getInstance()->count("SELECT COUNT(*) FROM delivery_queue WHERE status = 'pending'");

$adminMenu = [
    ['icon' => 'fa-tachometer-alt', 'label' => 'แดชบอร์ด',     'url' => 'admin',            'id' => 'dashboard'],
    ['icon' => 'fa-box',           'label' => 'สินค้า',        'url' => 'admin/products',    'id' => 'products'],
    ['icon' => 'fa-server',        'label' => 'เซิร์ฟเวอร์',    'url' => 'admin/servers',     'id' => 'servers'],
    ['icon' => 'fa-dice',          'label' => 'กาชา',         'url' => 'admin/gacha',       'id' => 'gacha'],
    ['icon' => 'fa-gift',          'label' => 'รีดีมโค้ด',      'url' => 'admin/redeem',      'id' => 'redeem'],
    ['icon' => 'fa-receipt',       'label' => 'ออเดอร์',       'url' => 'admin/orders',      'id' => 'orders',   'badge' => $pendingDeliveries],
    ['icon' => 'fa-coins',         'label' => 'เติมเงิน',      'url' => 'admin/topups',      'id' => 'topups',   'badge' => $pendingTopups],
    ['icon' => 'fa-users',         'label' => 'ผู้ใช้',        'url' => 'admin/users',       'id' => 'users'],
    ['icon' => 'fa-images',        'label' => 'แบนเนอร์',      'url' => 'admin/banners',     'id' => 'banners'],
    ['icon' => 'fa-file-alt',      'label' => 'หน้าเพจ',       'url' => 'admin/pages',       'id' => 'pages'],
    ['icon' => 'fa-credit-card',   'label' => 'ช่องทางจ่าย',    'url' => 'admin/gateways',    'id' => 'gateways'],
    ['icon' => 'fa-envelope',      'label' => 'ข้อความ',       'url' => 'admin/contacts',    'id' => 'contacts', 'badge' => $unreadContacts],
    ['icon' => 'fa-cog',           'label' => 'ตั้งค่า',        'url' => 'admin/settings',    'id' => 'settings'],
    ['icon' => 'fa-key',           'label' => 'License',        'url' => 'admin/license',     'id' => 'license'],
];

// Detect current admin page
$currentAdminPage = preg_replace('/[^a-z0-9_]/', '', $param1 ?? 'dashboard');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' . $siteName : $siteName ?></title>
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

        /* Admin Sidebar */
        .admin-sidebar {
            width: 240px;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            background: rgba(5,12,30,0.95);
            border-right: 1px solid var(--color-border);
            backdrop-filter: blur(20px);
            overflow-y: auto;
            z-index: 50;
            transition: transform 0.3s;
        }
        .admin-sidebar::-webkit-scrollbar { width: 4px; }
        .admin-sidebar::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: 4px; }

        .admin-content {
            margin-left: 240px;
            min-height: 100vh;
        }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(8,20,45,0.9);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--color-border);
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 1rem;
            margin: 0.15rem 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: var(--color-text-muted, #94a3b8);
            transition: all 0.2s;
        }
        .sidebar-link:hover {
            background: rgba(56,189,248,0.08);
            color: var(--color-text);
        }
        .sidebar-link.active {
            background: rgba(56,189,248,0.15);
            color: var(--color-primary);
            font-weight: 600;
        }
        .sidebar-link .badge {
            margin-left: auto;
            background: var(--gradient-btn);
            color: #fff;
            font-size: 0.65rem;
            padding: 0.1rem 0.45rem;
            border-radius: 9999px;
            font-weight: 700;
        }

        /* Mobile sidebar */
        @media (max-width: 1023px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-content { margin-left: 0; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 45; }
            .sidebar-overlay.open { display: block; }
        }
    </style>
</head>
<body>
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <!-- Logo -->
        <div class="px-4 py-5 border-b border-white/5">
            <a href="<?= url('admin') ?>" class="flex items-center gap-2 text-lg font-bold" style="color: var(--color-primary);">
                <i class="fas fa-cube"></i>
                <span><?= $siteName ?></span>
            </a>
            <p class="text-xs mt-1" style="color: var(--color-text-muted, #94a3b8);">Admin Panel</p>
        </div>

        <!-- Menu -->
        <nav class="py-3">
            <?php foreach ($adminMenu as $item): ?>
                <a href="<?= url($item['url']) ?>" class="sidebar-link <?= $currentAdminPage === $item['id'] ? 'active' : '' ?>">
                    <i class="fas <?= $item['icon'] ?> w-5 text-center text-sm"></i>
                    <span><?= $item['label'] ?></span>
                    <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                        <span class="badge"><?= $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Bottom -->
        <div class="mt-auto px-4 py-4 border-t border-white/5">
            <a href="<?= url('') ?>" class="sidebar-link" style="margin: 0; padding-left: 0;">
                <i class="fas fa-external-link-alt w-5 text-center text-sm"></i>
                <span>กลับหน้าเว็บ</span>
            </a>
            <a href="<?= url('logout') ?>" class="sidebar-link text-red-400" style="margin: 0; padding-left: 0;">
                <i class="fas fa-sign-out-alt w-5 text-center text-sm"></i>
                <span>ออกจากระบบ</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-content">
        <!-- Top Bar -->
        <header class="admin-topbar px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden text-lg"><i class="fas fa-bars"></i></button>
                <h2 class="text-sm font-semibold opacity-80"><?= $pageTitle ?? 'Admin' ?></h2>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($currentUser): ?>
                    <img src="https://mc-heads.net/avatar/<?= e($currentUser['username']) ?>/28" class="w-7 h-7 rounded-full" alt="avatar">
                    <span class="text-sm font-medium"><?= e($currentUser['username']) ?></span>
                <?php endif; ?>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php if ($flashSuccess = flash('success')): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: <?= json_encode($flashSuccess) ?>, timer: 3000, showConfirmButton: false });
                });
            </script>
        <?php endif; ?>
        <?php if ($flashError = flash('error')): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด!', text: <?= json_encode($flashError) ?> });
                });
            </script>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="p-6">
