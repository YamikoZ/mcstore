<?php
$pageTitle = 'โปรไฟล์';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

// Profile stats
$totalOrders = $db->count("SELECT COUNT(*) FROM orders WHERE username = ?", [$user['username']]);
$totalSpent = $db->fetch("SELECT COALESCE(SUM(total), 0) AS total FROM orders WHERE username = ? AND status != 'refunded'", [$user['username']]);
$gachaCount = $db->count("SELECT COUNT(*) FROM gacha_history WHERE username = ?", [$user['username']]);

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Profile Header -->
    <div class="card p-6 mb-6">
        <div class="flex items-center gap-6">
            <img src="https://mc-heads.net/avatar/<?= e($user['username']) ?>/80" class="w-20 h-20 rounded-xl" alt="avatar">
            <div class="flex-1">
                <h1 class="text-2xl font-bold"><?= e($user['username']) ?></h1>
                <p class="text-sm opacity-50">สมาชิกตั้งแต่ <?= e($user['created_at']) ?></p>
                <p class="text-lg font-bold mt-1" style="color: var(--color-accent);" data-balance><?= formatMoney($user['balance']) ?></p>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold" style="color: var(--color-primary);"><?= $totalOrders ?></p>
            <p class="text-xs opacity-60">ออเดอร์</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold" style="color: var(--color-accent);"><?= formatMoney($totalSpent['total'] ?? 0) ?></p>
            <p class="text-xs opacity-60">ใช้จ่ายทั้งหมด</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold" style="color: var(--color-secondary);"><?= $gachaCount ?></p>
            <p class="text-xs opacity-60">หมุนกาชา</p>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
