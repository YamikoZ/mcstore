<?php
$pageTitle = 'แจ้งเตือน';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

if (isset($_GET['read_all'])) {
    $db->execute("UPDATE notifications SET is_read = 1 WHERE username = ? AND is_read = 0", [$user['username']]);
    redirect('profile/notifications');
}

$perPage    = 20;
$page       = max(1, (int)($_GET['page'] ?? 1));
$total      = (int)($db->fetch("SELECT COUNT(*) AS n FROM notifications WHERE username = ?", [$user['username']])['n'] ?? 0);
$unread     = (int)($db->fetch("SELECT COUNT(*) AS n FROM notifications WHERE username = ? AND is_read = 0", [$user['username']])['n'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$notifications = $db->fetchAll(
    "SELECT * FROM notifications WHERE username = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
    [$user['username'], $perPage, $offset]
);

// Mark displayed as read
$db->execute("UPDATE notifications SET is_read = 1 WHERE username = ? AND is_read = 0", [$user['username']]);

$typeConfig = [
    'info'    => ['fa-info-circle',        '#60A5FA', 'rgba(96,165,250,.12)'],
    'success' => ['fa-check-circle',       '#4ade80', 'rgba(74,222,128,.12)'],
    'warning' => ['fa-exclamation-circle', '#facc15', 'rgba(250,204,21,.12)'],
    'error'   => ['fa-times-circle',       '#f87171', 'rgba(248,113,113,.12)'],
];

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-3xl mx-auto space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-bell" style="color: var(--color-accent);"></i> แจ้งเตือน
                <?php if ($unread > 0): ?>
                    <span class="text-sm px-2 py-0.5 rounded-full font-bold" style="background: var(--color-primary); color: #fff;"><?= $unread ?></span>
                <?php endif; ?>
            </h1>
            <p class="text-xs opacity-40 mt-0.5"><?= $total ?> รายการทั้งหมด</p>
        </div>
        <?php if ($unread > 0): ?>
        <a href="<?= url('profile/notifications?read_all=1') ?>"
           class="text-xs px-3 py-1.5 rounded-lg transition opacity-60 hover:opacity-100"
           style="background: var(--color-surface-dark);">
            <i class="fas fa-check-double mr-1"></i> อ่านทั้งหมด
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="card p-16 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 opacity-20"
                 style="background: var(--color-surface-dark);">
                <i class="fas fa-bell-slash"></i>
            </div>
            <p class="font-semibold opacity-50">ไม่มีการแจ้งเตือน</p>
        </div>
    <?php else: ?>
        <div class="card overflow-hidden">
            <div class="divide-y divide-white/5">
                <?php foreach ($notifications as $n):
                    [$ico, $color, $bg] = $typeConfig[$n['type']] ?? $typeConfig['info'];
                    $tag   = $n['link'] ? 'a' : 'div';
                    $attrs = $n['link'] ? 'href="' . url($n['link']) . '" ' : '';
                ?>
                <<?= $tag ?> <?= $attrs ?>class="flex items-start gap-4 px-5 py-4 transition hover:bg-white/3 relative <?= $n['link'] ? 'cursor-pointer' : '' ?>">
                    <?php if (!$n['is_read']): ?>
                        <span class="absolute left-0 top-0 bottom-0 w-0.5" style="background: var(--color-primary);"></span>
                    <?php endif; ?>
                    <!-- Icon -->
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-sm mt-0.5"
                         style="background: <?= $bg ?>; color: <?= $color ?>;">
                        <i class="fas <?= $ico ?>"></i>
                    </div>
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold <?= !$n['is_read'] ? '' : 'opacity-70' ?>"><?= e($n['title']) ?></p>
                        <p class="text-sm opacity-60 mt-0.5 leading-relaxed"><?= e($n['message']) ?></p>
                        <p class="text-xs opacity-30 mt-1"><?= timeAgo($n['created_at']) ?></p>
                    </div>
                    <?php if (!$n['is_read']): ?>
                        <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background: var(--color-primary);"></span>
                    <?php endif; ?>
                </<?= $tag ?>>
                <?php endforeach; ?>
            </div>
        </div>

        <?= paginationHtml($page, $totalPages, url('profile/notifications')) ?>

    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
