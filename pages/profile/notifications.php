<?php
$pageTitle = 'แจ้งเตือน';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

// Mark all as read if requested
if (isset($_GET['read_all'])) {
    $db->execute("UPDATE notifications SET is_read = 1 WHERE username = ? AND is_read = 0", [$user['username']]);
    redirect('profile/notifications');
}

$notifications = $db->fetchAll(
    "SELECT * FROM notifications WHERE username = ? ORDER BY created_at DESC LIMIT 50",
    [$user['username']]
);

// Mark displayed as read
$db->execute("UPDATE notifications SET is_read = 1 WHERE username = ? AND is_read = 0", [$user['username']]);

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold"><i class="fas fa-bell mr-2" style="color: var(--color-accent);"></i> แจ้งเตือน</h1>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="card p-12 text-center">
            <i class="fas fa-bell text-5xl mb-4 opacity-20"></i>
            <p class="opacity-60">ไม่มีการแจ้งเตือน</p>
        </div>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($notifications as $n): ?>
                <?php
                $icons = ['info' => 'fa-info-circle text-blue-400', 'success' => 'fa-check-circle text-green-400', 'warning' => 'fa-exclamation-circle text-yellow-400', 'error' => 'fa-times-circle text-red-400'];
                ?>
                <<?= $n['link'] ? 'a href="' . url($n['link']) . '"' : 'div' ?> class="card p-4 flex items-start gap-3 <?= !$n['is_read'] ? 'border-l-4' : '' ?> <?= $n['link'] ? 'hover:bg-white/5 transition' : '' ?>"
                    style="<?= !$n['is_read'] ? 'border-color: var(--color-primary);' : '' ?>">
                    <i class="fas <?= $icons[$n['type']] ?? 'fa-bell text-gray-400' ?> text-lg mt-1"></i>
                    <div class="flex-1">
                        <p class="font-bold text-sm"><?= e($n['title']) ?></p>
                        <p class="text-sm opacity-70"><?= e($n['message']) ?></p>
                        <p class="text-xs opacity-40 mt-1"><?= timeAgo($n['created_at']) ?></p>
                    </div>
                </<?= $n['link'] ? 'a' : 'div' ?>>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
