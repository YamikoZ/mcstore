<?php
$pageTitle = 'ออเดอร์';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$perPage     = 10;
$page        = max(1, (int)($_GET['page'] ?? 1));
$totalOrders = (int)$db->fetch("SELECT COUNT(*) AS n FROM orders WHERE username = ?", [$user['username']])['n'];
$totalPages  = max(1, (int)ceil($totalOrders / $perPage));
$page        = min($page, $totalPages);
$offset      = ($page - 1) * $perPage;

$orders = $db->fetchAll(
    "SELECT * FROM orders WHERE username = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
    [$user['username'], $perPage, $offset]
);

include BASE_PATH . '/layout/profile_header.php';

$statusBadge = [
    'processing' => ['warning', 'fa-spinner fa-spin', 'กำลังดำเนินการ'],
    'completed'  => ['success', 'fa-check-circle', 'สำเร็จ'],
    'delivered'  => ['success', 'fa-check-circle', 'ส่งแล้ว'],
    'failed'     => ['danger',  'fa-times-circle',  'ล้มเหลว'],
    'refunded'   => ['info',    'fa-undo',           'คืนเงิน'],
];
?>

<div class="max-w-4xl mx-auto space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-receipt" style="color: var(--color-primary);"></i> ประวัติออเดอร์
            </h1>
            <p class="text-xs opacity-40 mt-0.5"><?= $totalOrders ?> ออเดอร์ทั้งหมด</p>
        </div>
        <a href="<?= url('shop') ?>" class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-store mr-1.5"></i> ร้านค้า
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card p-16 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 opacity-20" style="background: var(--color-surface-dark);">
                <i class="fas fa-receipt"></i>
            </div>
            <p class="font-semibold opacity-50 mb-4">ยังไม่มีออเดอร์</p>
            <a href="<?= url('shop') ?>" class="btn-primary px-6 py-2.5 rounded-xl font-bold inline-block text-sm">
                <i class="fas fa-store mr-2"></i> ไปเลือกสินค้า
            </a>
        </div>
    <?php else: ?>

        <div class="space-y-3">
            <?php foreach ($orders as $order):
                $items = $db->fetchAll(
                    "SELECT oi.*, p.name AS product_name, s.name AS server_name
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     JOIN servers s ON oi.server_id = s.id
                     WHERE oi.order_id = ?",
                    [$order['id']]
                );
                [$cls, $ico, $lbl] = $statusBadge[$order['status']] ?? ['muted', 'fa-circle', $order['status']];
            ?>
            <div class="card overflow-hidden">
                <!-- Order header -->
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-white/5"
                     style="background: var(--color-surface-dark);">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold"
                             style="background: var(--color-primary)22; color: var(--color-primary);">
                            #<?= (int)$order['id'] ?>
                        </div>
                        <div>
                            <p class="text-sm font-semibold">ออเดอร์ #<?= (int)$order['id'] ?></p>
                            <p class="text-xs opacity-40"><?= timeAgo($order['created_at']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-bold" style="color: var(--color-accent);"><?= formatMoney($order['total']) ?></span>
                        <span class="status-badge <?= $cls ?>">
                            <i class="fas <?= $ico ?>"></i> <?= $lbl ?>
                        </span>
                    </div>
                </div>

                <!-- Items -->
                <div class="px-5 py-3 space-y-2">
                    <?php foreach ($items as $item): ?>
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center opacity-40" style="background: var(--color-bg); font-size: .7rem;">
                                <i class="fas fa-box"></i>
                            </div>
                            <span class="font-medium"><?= e($item['product_name']) ?></span>
                            <span class="text-xs opacity-40">× <?= (int)$item['quantity'] ?></span>
                            <span class="text-xs opacity-30">(<?= e($item['server_name']) ?>)</span>
                        </div>
                        <span class="opacity-60"><?= formatMoney($item['price'] * $item['quantity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?= paginationHtml($page, $totalPages, url('orders')) ?>

    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
