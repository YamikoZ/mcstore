<?php
$pageTitle = 'จัดการออเดอร์ - แอดมิน';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('admin/orders'); }

    $action = $_POST['action'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);

    if ($action === 'update_status' && $orderId) {
        $status = $_POST['status'];
        $allowed = ['pending', 'paid', 'delivered', 'cancelled', 'refunded'];
        if (!in_array($status, $allowed)) { flash('error', 'สถานะไม่ถูกต้อง'); redirect('admin/orders'); }

        $order = $db->fetch("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) { flash('error', 'ไม่พบออเดอร์'); redirect('admin/orders'); }

        // Handle refund
        if ($status === 'refunded' && $order['status'] !== 'refunded') {
            $user = $db->fetch("SELECT balance FROM users WHERE username = ?", [$order['username']]);
            if ($user) {
                $newBalance = $user['balance'] + $order['total'];
                $db->execute("UPDATE users SET balance = ? WHERE username = ?", [$newBalance, $order['username']]);
                $db->execute(
                    "INSERT INTO wallet_ledger (username, type, amount, balance_after, reference, note) VALUES (?, 'credit', ?, ?, ?, ?)",
                    [$order['username'], $order['total'], $newBalance, 'refund#' . $orderId, 'คืนเงินออเดอร์ #' . $orderId]
                );
                createNotification($order['username'], 'order', 'คืนเงิน', 'คืนเงินออเดอร์ #' . $orderId . ' จำนวน ' . formatMoney($order['total']));
            }
        }

        $db->execute("UPDATE orders SET status = ? WHERE id = ?", [$status, $orderId]);
        auditLog(Auth::id(), 'admin_order_status', "Order #{$orderId} -> {$status}");
        flash('success', 'อัพเดทสถานะเรียบร้อย');
        redirect('admin/orders');
    }
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($filterStatus) { $where = "WHERE o.status = ?"; $params[] = $filterStatus; }

$totalOrders = $db->count("SELECT COUNT(*) FROM orders o {$where}", $params);
$totalPages = ceil($totalOrders / $perPage);

$orders = $db->fetchAll("SELECT o.* FROM orders o {$where} ORDER BY o.created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);

$viewOrder = (int)($_GET['view'] ?? 0);
$orderDetail = null;
$orderItems = [];
$orderDeliveries = [];
if ($viewOrder) {
    $orderDetail = $db->fetch("SELECT * FROM orders WHERE id = ?", [$viewOrder]);
    $orderItems = $db->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$viewOrder]);
    $orderDeliveries = $db->fetchAll("SELECT * FROM delivery_queue WHERE order_id = ? ORDER BY id", [$viewOrder]);
}

$statusLabels = ['pending'=>'รอดำเนินการ','paid'=>'ชำระแล้ว','delivered'=>'ส่งแล้ว','cancelled'=>'ยกเลิก','refunded'=>'คืนเงิน'];
$statusColors = ['pending'=>'bg-yellow-500/20 text-yellow-400','paid'=>'bg-blue-500/20 text-blue-400','delivered'=>'bg-green-500/20 text-green-400','cancelled'=>'bg-red-500/20 text-red-400','refunded'=>'bg-gray-500/20 text-gray-400'];

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-receipt mr-2" style="color: var(--color-primary);"></i>จัดการออเดอร์</h1>
            <p class="text-sm opacity-60 mt-1"><?= number_format($totalOrders) ?> ออเดอร์</p>
        </div>
        <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
    </div>

    <!-- Filters -->
    <div class="card p-4 mb-4">
        <div class="flex flex-wrap gap-2">
            <a href="<?= url('admin/orders') ?>" class="px-3 py-1 rounded-lg text-sm <?= !$filterStatus ? 'font-bold' : 'opacity-60 hover:opacity-100' ?>" <?= !$filterStatus ? 'style="background-color: var(--color-primary); color: #fff;"' : '' ?>>ทั้งหมด</a>
            <?php foreach ($statusLabels as $k => $v): ?>
                <a href="?status=<?= $k ?>" class="px-3 py-1 rounded-lg text-sm <?= $filterStatus === $k ? 'font-bold' : 'opacity-60 hover:opacity-100' ?>" <?= $filterStatus === $k ? 'style="background-color: var(--color-primary); color: #fff;"' : '' ?>><?= $v ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($orderDetail): ?>
    <!-- Order Detail -->
    <div class="card p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">ออเดอร์ #<?= $orderDetail['id'] ?></h2>
            <a href="<?= url('admin/orders') ?>" class="text-sm opacity-60 hover:opacity-100"><i class="fas fa-times"></i></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div><p class="text-xs opacity-50">ผู้ซื้อ</p><p class="font-semibold"><?= e($orderDetail['username']) ?></p></div>
            <div><p class="text-xs opacity-50">ยอดรวม</p><p class="font-semibold" style="color: var(--color-accent);"><?= formatMoney($orderDetail['total']) ?></p></div>
            <div><p class="text-xs opacity-50">สถานะ</p><span class="text-xs px-2 py-0.5 rounded <?= $statusColors[$orderDetail['status']] ?? '' ?>"><?= $statusLabels[$orderDetail['status']] ?? $orderDetail['status'] ?></span></div>
            <div><p class="text-xs opacity-50">เวลา</p><p class="text-sm"><?= $orderDetail['created_at'] ?></p></div>
        </div>

        <!-- Change Status -->
        <form method="POST" class="flex items-center gap-2 mb-4 p-3 rounded-lg border border-white/10">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?= $orderDetail['id'] ?>">
            <label class="text-sm">เปลี่ยนสถานะ:</label>
            <select name="status" class="px-3 py-1 rounded border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                <?php foreach ($statusLabels as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $orderDetail['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary px-4 py-1 rounded text-sm" onclick="return confirm('เปลี่ยนสถานะออเดอร์?')"><i class="fas fa-save mr-1"></i> บันทึก</button>
        </form>

        <!-- Items -->
        <h3 class="font-semibold mb-2">รายการสินค้า</h3>
        <table class="w-full text-sm mb-4">
            <thead><tr class="border-b border-white/10 opacity-60">
                <th class="text-left py-2">สินค้า</th>
                <th class="text-left py-2">เซิร์ฟเวอร์</th>
                <th class="text-center py-2">จำนวน</th>
                <th class="text-right py-2">ราคา</th>
            </tr></thead>
            <tbody>
            <?php foreach ($orderItems as $item): ?>
                <tr class="border-b border-white/5">
                    <td class="py-2"><?= e($item['name']) ?></td>
                    <td class="py-2 opacity-70"><?= e($item['server_id']) ?></td>
                    <td class="py-2 text-center"><?= $item['quantity'] ?></td>
                    <td class="py-2 text-right"><?= formatMoney($item['price'] * $item['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Deliveries -->
        <?php if ($orderDeliveries): ?>
        <h3 class="font-semibold mb-2">คิวส่งของ</h3>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-white/10 opacity-60">
                <th class="text-left py-2">คำสั่ง</th>
                <th class="text-center py-2">เซิร์ฟเวอร์</th>
                <th class="text-center py-2">สถานะ</th>
                <th class="text-right py-2">เวลา</th>
            </tr></thead>
            <tbody>
            <?php foreach ($orderDeliveries as $d): ?>
                <tr class="border-b border-white/5">
                    <td class="py-2 font-mono text-xs"><?= e($d['command']) ?></td>
                    <td class="py-2 text-center text-xs"><?= e($d['server_id']) ?></td>
                    <td class="py-2 text-center">
                        <?php 
                        $dColors = ['pending'=>'text-yellow-400','processing'=>'text-blue-400','delivered'=>'text-green-400','failed'=>'text-red-400'];
                        ?>
                        <span class="text-xs <?= $dColors[$d['status']] ?? '' ?>"><?= e($d['status']) ?></span>
                    </td>
                    <td class="py-2 text-right text-xs opacity-60"><?= $d['delivered_at'] ?: '-' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Order List -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3">#</th>
                    <th class="text-left px-4 py-3">ผู้ซื้อ</th>
                    <th class="text-right px-4 py-3">ยอดรวม</th>
                    <th class="text-center px-4 py-3">สถานะ</th>
                    <th class="text-right px-4 py-3">เวลา</th>
                    <th class="text-right px-4 py-3">จัดการ</th>
                </tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="px-4 py-3"><?= $o['id'] ?></td>
                        <td class="px-4 py-3 font-semibold"><?= e($o['username']) ?></td>
                        <td class="px-4 py-3 text-right" style="color: var(--color-accent);"><?= formatMoney($o['total']) ?></td>
                        <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded <?= $statusColors[$o['status']] ?? '' ?>"><?= $statusLabels[$o['status']] ?? $o['status'] ?></span></td>
                        <td class="px-4 py-3 text-right text-xs opacity-60"><?= timeAgo($o['created_at']) ?></td>
                        <td class="px-4 py-3 text-right">
                            <a href="?view=<?= $o['id'] ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center opacity-50">ไม่พบออเดอร์</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-1 mt-4">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?><?= $filterStatus ? '&status=' . urlencode($filterStatus) : '' ?>" class="px-3 py-1 rounded text-sm <?= $i === $page ? 'font-bold' : 'opacity-60 hover:opacity-100' ?>" <?= $i === $page ? 'style="background-color: var(--color-primary); color: #fff;"' : '' ?>><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
