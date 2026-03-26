<?php
$pageTitle = 'การส่งของ';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$deliveries = $db->fetchAll(
    "SELECT dq.*, s.name AS server_name FROM delivery_queue dq 
     JOIN servers s ON dq.server_id = s.id 
     WHERE dq.username = ? ORDER BY dq.created_at DESC LIMIT 50",
    [$user['username']]
);

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-truck mr-2" style="color: var(--color-primary);"></i> สถานะการส่งของ</h1>

    <?php if (empty($deliveries)): ?>
        <div class="card p-12 text-center">
            <i class="fas fa-truck text-5xl mb-4 opacity-20"></i>
            <p class="opacity-60">ยังไม่มีรายการส่งของ</p>
        </div>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($deliveries as $d): ?>
                <?php
                $statusIcons = ['pending' => 'fa-clock text-yellow-400', 'delivered' => 'fa-check-circle text-green-400', 'failed' => 'fa-times-circle text-red-400'];
                $statusLabels = ['pending' => 'รอส่ง', 'delivered' => 'ส่งแล้ว', 'failed' => 'ล้มเหลว'];
                ?>
                <div class="card p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $statusIcons[$d['status']] ?? 'fa-circle' ?> text-lg"></i>
                        <div>
                            <p class="font-semibold text-sm"><?= e($d['command']) ?></p>
                            <p class="text-xs opacity-50"><?= e($d['server_name']) ?> — <?= $statusLabels[$d['status']] ?? $d['status'] ?></p>
                        </div>
                    </div>
                    <span class="text-xs opacity-40"><?= timeAgo($d['created_at']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
