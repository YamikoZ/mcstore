<?php
$pageTitle = 'ออเดอร์';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$orders = $db->fetchAll(
    "SELECT * FROM orders WHERE username = ? ORDER BY created_at DESC",
    [$user['username']]
);

include BASE_PATH . '/layout/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-receipt mr-2" style="color: var(--color-primary);"></i> ประวัติออเดอร์</h1>

    <?php if (empty($orders)): ?>
        <div class="card p-12 text-center">
            <i class="fas fa-receipt text-5xl mb-4 opacity-20"></i>
            <p class="text-lg opacity-60 mb-4">ยังไม่มีออเดอร์</p>
            <a href="<?= url('shop') ?>" class="btn-primary px-6 py-3 rounded-lg font-bold inline-block">
                <i class="fas fa-store mr-2"></i> ไปเลือกสินค้า
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
                <?php
                $items = $db->fetchAll(
                    "SELECT oi.*, p.name AS product_name, s.name AS server_name 
                     FROM order_items oi 
                     JOIN products p ON oi.product_id = p.id 
                     JOIN servers s ON oi.server_id = s.id 
                     WHERE oi.order_id = ?",
                    [$order['id']]
                );
                $statusColors = ['processing' => 'text-yellow-400', 'completed' => 'text-green-400', 'delivered' => 'text-green-400', 'failed' => 'text-red-400', 'refunded' => 'text-red-400'];
                $statusLabels = ['processing' => 'กำลังดำเนินการ', 'completed' => 'สำเร็จ', 'delivered' => 'ส่งแล้ว', 'failed' => 'ล้มเหลว', 'refunded' => 'คืนเงิน'];
                ?>
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="font-bold">ออเดอร์ #<?= (int)$order['id'] ?></span>
                            <span class="text-sm opacity-50 ml-2"><?= timeAgo($order['created_at']) ?></span>
                        </div>
                        <span class="<?= $statusColors[$order['status']] ?? '' ?> font-semibold text-sm">
                            <?= $statusLabels[$order['status']] ?? $order['status'] ?>
                        </span>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($items as $item): ?>
                            <div class="flex justify-between text-sm py-1 border-b border-white/5">
                                <span><?= e($item['product_name']) ?> <span class="opacity-40">(<?= e($item['server_name']) ?>) x<?= (int)$item['quantity'] ?></span></span>
                                <span><?= formatMoney($item['price'] * $item['quantity']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex justify-between mt-3 pt-2 border-t border-white/10">
                        <span class="font-bold">รวม</span>
                        <span class="font-bold" style="color: var(--color-accent);"><?= formatMoney($order['total']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
