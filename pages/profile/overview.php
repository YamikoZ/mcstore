<?php
$pageTitle = 'ภาพรวม';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$totalOrders  = $db->count("SELECT COUNT(*) FROM orders WHERE username = ?", [$user['username']]);
$totalSpent   = (float)($db->fetch("SELECT COALESCE(SUM(total),0) AS t FROM orders WHERE username = ? AND status != 'refunded'", [$user['username']])['t'] ?? 0);
$gachaCount   = $db->count("SELECT COUNT(*) FROM gacha_history WHERE username = ?", [$user['username']]);
$pendingDeliveries = $db->count("SELECT COUNT(*) FROM delivery_queue WHERE username = ? AND status = 'pending'", [$user['username']]);

$recentOrders = $db->fetchAll(
    "SELECT * FROM orders WHERE username = ? ORDER BY created_at DESC LIMIT 5",
    [$user['username']]
);

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Hero Card -->
    <div class="relative overflow-hidden rounded-2xl p-6" style="background: linear-gradient(135deg, var(--color-surface) 0%, var(--color-surface-dark) 100%); border: 1px solid var(--color-border);">
        <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 20% 50%, var(--color-primary) 0%, transparent 50%), radial-gradient(circle at 80% 20%, var(--color-secondary) 0%, transparent 40%);"></div>
        <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <div class="relative">
                <img src="https://mc-heads.net/body/<?= e($user['username']) ?>/80" alt="skin"
                     class="h-24 w-auto rounded-xl" style="image-rendering: pixelated;">
            </div>
            <div class="flex-1">
                <p class="text-xs opacity-50 mb-1">สวัสดี,</p>
                <h1 class="text-2xl font-bold mb-1"><?= e($user['username']) ?></h1>
                <p class="text-xs opacity-40">สมาชิกตั้งแต่ <?= date('d M Y', strtotime($user['created_at'])) ?></p>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold" style="color: var(--color-accent);" data-balance><?= formatMoney($user['balance']) ?></span>
                    <span class="text-xs opacity-50">ยอดเงินคงเหลือ</span>
                </div>
            </div>
            <?php if (Settings::get('topup_enabled', '1') === '1'): ?>
            <a href="<?= url('topup') ?>" class="btn-primary px-5 py-2.5 rounded-xl font-semibold text-sm shrink-0">
                <i class="fas fa-plus mr-2"></i>เติมเงิน
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card primary">
            <div class="stat-icon mb-3" style="background: var(--color-primary)22; color: var(--color-primary);">
                <i class="fas fa-receipt"></i>
            </div>
            <p class="text-2xl font-bold"><?= $totalOrders ?></p>
            <p class="text-xs opacity-50 mt-0.5">ออเดอร์ทั้งหมด</p>
        </div>
        <div class="stat-card accent">
            <div class="stat-icon mb-3" style="background: var(--color-accent)22; color: var(--color-accent);">
                <i class="fas fa-coins"></i>
            </div>
            <p class="text-2xl font-bold"><?= formatMoney($totalSpent) ?></p>
            <p class="text-xs opacity-50 mt-0.5">ใช้จ่ายรวม</p>
        </div>
        <div class="stat-card secondary">
            <div class="stat-icon mb-3" style="background: var(--color-secondary)22; color: var(--color-secondary);">
                <i class="fas fa-dice"></i>
            </div>
            <p class="text-2xl font-bold"><?= $gachaCount ?></p>
            <p class="text-xs opacity-50 mt-0.5">หมุนกาชา</p>
        </div>
        <div class="stat-card" style="border-color: #fbbf2433;">
            <div class="stat-icon mb-3" style="background: #fbbf2420; color: #fbbf24;">
                <i class="fas fa-truck"></i>
            </div>
            <p class="text-2xl font-bold"><?= $pendingDeliveries ?></p>
            <p class="text-xs opacity-50 mt-0.5">รอส่งของ</p>
        </div>
    </div>

    <!-- Recent Orders -->
    <?php if (!empty($recentOrders)): ?>
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold flex items-center gap-2">
                <i class="fas fa-clock-rotate-left text-sm" style="color: var(--color-primary);"></i>
                ออเดอร์ล่าสุด
            </h3>
            <a href="<?= url('profile/orders') ?>" class="text-xs opacity-50 hover:opacity-100 transition">ดูทั้งหมด →</a>
        </div>
        <div class="space-y-2">
            <?php
            $statusBadge = [
                'processing' => ['warning', 'fa-spinner', 'กำลังดำเนินการ'],
                'completed'  => ['success', 'fa-check',  'สำเร็จ'],
                'delivered'  => ['success', 'fa-check-circle', 'ส่งแล้ว'],
                'failed'     => ['danger',  'fa-times',  'ล้มเหลว'],
                'refunded'   => ['info',    'fa-undo',   'คืนเงิน'],
            ];
            foreach ($recentOrders as $o):
                [$cls, $ico, $lbl] = $statusBadge[$o['status']] ?? ['muted', 'fa-circle', $o['status']];
            ?>
            <div class="flex items-center justify-between py-2.5 border-b border-white/5 last:border-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs" style="background: var(--color-bg);">
                        <i class="fas fa-box opacity-40"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold">ออเดอร์ #<?= (int)$o['id'] ?></p>
                        <p class="text-xs opacity-40"><?= timeAgo($o['created_at']) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-bold text-sm" style="color: var(--color-accent);"><?= formatMoney($o['total']) ?></span>
                    <span class="status-badge <?= $cls ?>"><i class="fas <?= $ico ?> text-xs"></i> <?= $lbl ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <?php $quickLinks = [
            ['url' => 'shop',              'icon' => 'fa-store',     'label' => 'ร้านค้า',       'color' => 'var(--color-primary)'],
            ['url' => 'profile/wallet',    'icon' => 'fa-wallet',    'label' => 'กระเป๋าเงิน',  'color' => 'var(--color-accent)'],
            ['url' => 'profile/orders',    'icon' => 'fa-receipt',   'label' => 'ออเดอร์',      'color' => 'var(--color-secondary)'],
            ['url' => 'profile/deliveries','icon' => 'fa-truck',     'label' => 'การส่งของ',   'color' => '#fbbf24'],
            ['url' => 'profile/edit',      'icon' => 'fa-user-edit', 'label' => 'แก้ไขโปรไฟล์','color' => '#94a3b8'],
            ['url' => 'topup',             'icon' => 'fa-coins',     'label' => 'เติมเงิน',     'color' => '#4ade80'],
        ]; ?>
        <?php foreach ($quickLinks as $ql): ?>
        <a href="<?= url($ql['url']) ?>" class="card p-4 flex items-center gap-3 hover:border-white/20 transition group">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm flex-shrink-0 transition group-hover:scale-110"
                 style="background: <?= $ql['color'] ?>22; color: <?= $ql['color'] ?>;">
                <i class="fas <?= $ql['icon'] ?>"></i>
            </div>
            <span class="text-sm font-medium"><?= $ql['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>

</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
