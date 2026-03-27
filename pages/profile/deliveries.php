<?php
$pageTitle = 'การส่งของ';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$perPage    = 20;
$page       = max(1, (int)($_GET['page'] ?? 1));
$total      = (int)($db->fetch("SELECT COUNT(*) AS n FROM delivery_queue WHERE username = ?", [$user['username']])['n'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$deliveries = $db->fetchAll(
    "SELECT dq.*, s.name AS server_name FROM delivery_queue dq
     JOIN servers s ON dq.server_id = s.id
     WHERE dq.username = ? ORDER BY dq.created_at DESC LIMIT ? OFFSET ?",
    [$user['username'], $perPage, $offset]
);

$statusConfig = [
    'pending'   => ['warning', 'fa-clock',        'รอส่ง'],
    'delivered' => ['success', 'fa-check-circle', 'ส่งแล้ว'],
    'failed'    => ['danger',  'fa-times-circle', 'ล้มเหลว'],
];

// Summary counts
$pendingCount   = $db->count("SELECT COUNT(*) FROM delivery_queue WHERE username = ? AND status = 'pending'",   [$user['username']]);
$deliveredCount = $db->count("SELECT COUNT(*) FROM delivery_queue WHERE username = ? AND status = 'delivered'", [$user['username']]);
$failedCount    = $db->count("SELECT COUNT(*) FROM delivery_queue WHERE username = ? AND status = 'failed'",    [$user['username']]);

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-4xl mx-auto space-y-5">

    <!-- Header -->
    <h1 class="text-xl font-bold flex items-center gap-2">
        <i class="fas fa-truck" style="color: var(--color-primary);"></i> สถานะการส่งของ
    </h1>

    <!-- Summary pills -->
    <div class="flex flex-wrap gap-3">
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm" style="background: rgba(250,204,21,.1); border: 1px solid rgba(250,204,21,.2);">
            <i class="fas fa-clock text-yellow-400"></i>
            <span class="font-semibold text-yellow-400"><?= $pendingCount ?></span>
            <span class="opacity-60">รอส่ง</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm" style="background: rgba(74,222,128,.1); border: 1px solid rgba(74,222,128,.2);">
            <i class="fas fa-check-circle text-green-400"></i>
            <span class="font-semibold text-green-400"><?= $deliveredCount ?></span>
            <span class="opacity-60">ส่งแล้ว</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm" style="background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.2);">
            <i class="fas fa-times-circle text-red-400"></i>
            <span class="font-semibold text-red-400"><?= $failedCount ?></span>
            <span class="opacity-60">ล้มเหลว</span>
        </div>
    </div>

    <?php if (empty($deliveries)): ?>
        <div class="card p-16 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 opacity-20"
                 style="background: var(--color-surface-dark);">
                <i class="fas fa-truck"></i>
            </div>
            <p class="font-semibold opacity-50">ยังไม่มีรายการส่งของ</p>
        </div>
    <?php else: ?>

        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-white/5" style="background: var(--color-surface-dark);">
                <p class="text-sm font-semibold opacity-60"><?= $total ?> รายการทั้งหมด</p>
            </div>
            <div class="divide-y divide-white/5">
                <?php foreach ($deliveries as $d):
                    [$cls, $ico, $lbl] = $statusConfig[$d['status']] ?? ['muted', 'fa-circle', $d['status']];
                ?>
                <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-white/3 transition">
                    <span class="status-badge <?= $cls ?> shrink-0">
                        <i class="fas <?= $ico ?>"></i>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-mono truncate opacity-80"><?= e($d['command']) ?></p>
                        <p class="text-xs opacity-40 mt-0.5">
                            <i class="fas fa-server text-xs mr-1"></i><?= e($d['server_name']) ?>
                            <span class="mx-1.5 opacity-40">·</span>
                            <?= $lbl ?>
                        </p>
                    </div>
                    <span class="text-xs opacity-30 shrink-0"><?= timeAgo($d['created_at']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?= paginationHtml($page, $totalPages, url('profile/deliveries')) ?>

    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
