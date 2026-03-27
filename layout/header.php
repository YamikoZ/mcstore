<?php
$siteName = e(Settings::get('site_name', 'MCStore'));
$siteLogo = Settings::get('site_logo', '');
$currentUser = Auth::check() ? Auth::user() : null;
$unreadCount = $currentUser ? getUnreadNotifications($currentUser['id']) : 0;

// Feature toggles
$feat = [
    'gacha'    => Settings::get('gacha_enabled', '1') === '1',
    'topup'    => Settings::get('topup_enabled', '1') === '1',
    'contact'  => Settings::get('contact_enabled', '1') === '1',
    'redeem'   => Settings::get('redeem_enabled', '1') === '1',
    'register' => Settings::get('register_enabled', '1') === '1',
    'download' => Settings::get('download_enabled', '1') === '1',
    'shop'     => Settings::get('shop_enabled', '1') === '1',
    'welcome'  => Settings::get('welcome_enabled', '0') === '1',
];

// Build nav items — only show enabled features
$navItems = [];
$navItems[] = ['url' => '', 'icon' => 'fa-home', 'label' => 'หน้าแรก', 'always' => true];
if ($feat['shop'])     $navItems[] = ['url' => 'shop',     'icon' => 'fa-store',    'label' => 'ร้านค้า'];
if ($feat['gacha'])    $navItems[] = ['url' => 'gacha',    'icon' => 'fa-dice',     'label' => 'กาชา'];
if ($feat['topup'])    $navItems[] = ['url' => 'topup',    'icon' => 'fa-coins',    'label' => 'เติมเงิน'];
if ($feat['download']) $navItems[] = ['url' => 'download', 'icon' => 'fa-download', 'label' => 'ดาวน์โหลด'];
if ($feat['contact'])  $navItems[] = ['url' => 'contact',  'icon' => 'fa-envelope', 'label' => 'ติดต่อ'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' . $siteName : $siteName ?></title>
    <meta name="description" content="<?= e(Settings::get('site_description', '')) ?>">
    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
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
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--color-primary)',
                        secondary: 'var(--color-secondary)',
                        accent: 'var(--color-accent)',
                        surface: 'var(--color-surface)',
                        'surface-dark': 'var(--color-surface-dark)',
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: var(--color-bg); color: var(--color-text); font-family: var(--font-family); }
        .nav-link { color: var(--color-text-muted, #94a3b8); }
        .nav-link:hover { color: var(--color-primary); }
        .nav-link.active { color: var(--color-primary); font-weight: 600; }
        .btn-primary { background: var(--gradient-btn, var(--color-primary)); color: #fff; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-secondary { background: var(--color-secondary); color: #fff; }
        .card { border-radius: 0.75rem; }
        .badge-count { background: var(--gradient-btn, var(--color-accent)); }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="sticky top-0 z-50 shadow-lg" style="background-color: var(--color-nav-bg, var(--color-surface-dark));">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="<?= url('') ?>" class="flex items-center space-x-2 text-xl font-bold" style="color: var(--color-primary);">
                    <?php if ($siteLogo): ?>
                        <img src="<?= e($siteLogo) ?>" alt="<?= $siteName ?>" class="h-8 w-8 rounded object-cover">
                    <?php else: ?>
                        <i class="fas fa-cube"></i>
                    <?php endif; ?>
                    <span><?= $siteName ?></span>
                </a>

                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center gap-1">
                    <?php
                    $currentRoute = trim($_GET['route'] ?? '', '/');
                    $currentPage = explode('/', $currentRoute)[0] ?: 'home';
                    foreach ($navItems as $nav):
                        $isActive = ($nav['url'] === '' && ($currentPage === 'home' || $currentPage === ''))
                                 || ($nav['url'] !== '' && $currentPage === $nav['url']);
                    ?>
                    <a href="<?= url($nav['url']) ?>" class="nav-link px-4 py-2 rounded-lg hover:bg-white/5 font-medium transition-all text-sm <?= $isActive ? 'active' : '' ?>">
                        <i class="fas <?= $nav['icon'] ?> mr-1.5"></i><?= $nav['label'] ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Right Side -->
                <div class="flex items-center space-x-4">
                    <?php if ($currentUser): ?>
                        <!-- Notifications -->
                        <a href="<?= url('profile/notifications') ?>" class="relative nav-link transition">
                            <i class="fas fa-bell text-lg"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="absolute -top-2 -right-2 badge-count text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>

                        <!-- User Menu -->
                        <div class="relative" id="user-menu-wrapper">
                            <button id="user-menu-btn" class="flex items-center space-x-2 nav-link transition">
                                <img src="https://mc-heads.net/avatar/<?= e($currentUser['username']) ?>/32" class="w-8 h-8 rounded-full" alt="avatar">
                                <span class="hidden lg:inline"><?= e($currentUser['username']) ?></span>
                                <span class="text-sm font-semibold" style="color: var(--color-accent);"><?= formatMoney($currentUser['balance']) ?></span>
                                <i class="fas fa-chevron-down text-xs opacity-50 transition-transform" id="user-menu-arrow"></i>
                            </button>
                            <div id="user-menu-dropdown" class="absolute hidden top-full right-0 pt-2 w-56 z-50">
                                <div class="rounded-lg shadow-xl overflow-hidden" style="background-color: var(--color-surface-dark); border: 1px solid var(--color-border);">
                                    <a href="<?= url('profile') ?>" class="block px-4 py-2.5 hover:bg-white/10 transition text-sm"><i class="fas fa-user mr-2 w-5 text-center"></i> โปรไฟล์</a>
                                    <a href="<?= url('orders') ?>" class="block px-4 py-2.5 hover:bg-white/10 transition text-sm"><i class="fas fa-receipt mr-2 w-5 text-center"></i> ออเดอร์</a>
                                    <a href="<?= url('profile/wallet') ?>" class="block px-4 py-2.5 hover:bg-white/10 transition text-sm"><i class="fas fa-wallet mr-2 w-5 text-center"></i> กระเป๋าเงิน</a>
                                    <?php if ($feat['topup']): ?>
                                    <a href="<?= url('topup') ?>" class="block px-4 py-2.5 hover:bg-white/10 transition text-sm"><i class="fas fa-coins mr-2 w-5 text-center"></i> เติมเงิน</a>
                                    <?php endif; ?>
                                    <?php if (Auth::isAdmin()): ?>
                                        <div class="border-t border-white/10"></div>
                                        <a href="<?= url('admin') ?>" class="block px-4 py-2.5 hover:bg-white/10 transition text-sm text-red-400"><i class="fas fa-shield-alt mr-2 w-5 text-center"></i> แอดมิน</a>
                                    <?php endif; ?>
                                    <div class="border-t border-white/10"></div>
                                    <a href="<?= url('logout') ?>" class="block px-4 py-2.5 hover:bg-white/10 transition text-sm text-red-400"><i class="fas fa-sign-out-alt mr-2 w-5 text-center"></i> ออกจากระบบ</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-2">
                            <a href="<?= url('login') ?>" class="btn-primary px-4 py-2 rounded-lg font-semibold transition text-sm"><i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ</a>
                            <?php if ($feat['register']): ?>
                            <a href="<?= url('register') ?>" class="px-4 py-2 rounded-lg font-semibold transition text-sm border border-white/10 hover:bg-white/5">สมัคร</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-btn" class="md:hidden text-xl"><i class="fas fa-bars"></i></button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden pb-4 px-4 border-t border-white/5">
            <?php foreach ($navItems as $nav):
                $isActive = ($nav['url'] === '' && ($currentPage === 'home' || $currentPage === ''))
                         || ($nav['url'] !== '' && $currentPage === $nav['url']);
            ?>
            <a href="<?= url($nav['url']) ?>" class="block py-2.5 nav-link text-sm <?= $isActive ? 'active' : '' ?>">
                <i class="fas <?= $nav['icon'] ?> mr-2 w-5 text-center"></i> <?= $nav['label'] ?>
            </a>
            <?php endforeach; ?>

            <?php if ($currentUser): ?>
                <div class="border-t border-white/10 mt-2 pt-2">
                    <a href="<?= url('profile') ?>" class="block py-2.5 nav-link text-sm"><i class="fas fa-user mr-2 w-5 text-center"></i> โปรไฟล์</a>
                    <a href="<?= url('orders') ?>" class="block py-2.5 nav-link text-sm"><i class="fas fa-receipt mr-2 w-5 text-center"></i> ออเดอร์</a>
                    <?php if (Auth::isAdmin()): ?>
                        <a href="<?= url('admin') ?>" class="block py-2.5 text-red-400 text-sm"><i class="fas fa-shield-alt mr-2 w-5 text-center"></i> แอดมิน</a>
                    <?php endif; ?>
                    <a href="<?= url('logout') ?>" class="block py-2.5 text-red-400 text-sm"><i class="fas fa-sign-out-alt mr-2 w-5 text-center"></i> ออกจากระบบ</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

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
    <?php if ($flashWarning = flash('warning')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: <?= json_encode($flashWarning) ?> });
            });
        </script>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-1">
