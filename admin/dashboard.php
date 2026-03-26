<?php
$pageTitle = 'แดชบอร์ด - แอดมิน';
$db = Database::getInstance();

// Stats
$totalUsers = $db->count("SELECT COUNT(*) FROM users");
$totalOrders = $db->count("SELECT COUNT(*) FROM orders");
$totalRevenue = $db->fetch("SELECT COALESCE(SUM(total),0) as total FROM orders WHERE status IN ('paid','delivered')")['total'];
$totalTopup = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM topup_transactions WHERE status = 'completed'")['total'];
$pendingDeliveries = $db->count("SELECT COUNT(*) FROM delivery_queue WHERE status = 'pending'");
$pendingTopups = $db->count("SELECT COUNT(*) FROM topup_transactions WHERE status = 'pending'");
$unreadContacts = $db->count("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");
$totalGachaSpins = $db->count("SELECT COUNT(*) FROM gacha_history");

// Today stats
$todayOrders = $db->count("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
$todayRevenue = $db->fetch("SELECT COALESCE(SUM(total),0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('paid','delivered')")['total'];
$todayTopup = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM topup_transactions WHERE DATE(created_at) = CURDATE() AND status = 'completed'")['total'];
$todayRegistrations = $db->count("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");

// Recent orders
$recentOrders = $db->fetchAll("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");

// Recent topups
$recentTopups = $db->fetchAll("SELECT * FROM topup_transactions ORDER BY created_at DESC LIMIT 10");

// Recent audit log
$recentAudit = $db->fetchAll("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 15");

// Server online counts
$serverStats = $db->fetchAll("SELECT id, name, last_poll FROM servers WHERE is_active = 1 ORDER BY display_order");

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-7xl mx-auto">

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-60">ผู้ใช้ทั้งหมด</p>
                    <p class="text-2xl font-bold"><?= number_format($totalUsers) ?></p>
                    <p class="text-xs mt-1" style="color: var(--color-accent);">+<?= $todayRegistrations ?> วันนี้</p>
                </div>
                <div class="text-3xl opacity-30"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-60">ออเดอร์ทั้งหมด</p>
                    <p class="text-2xl font-bold"><?= number_format($totalOrders) ?></p>
                    <p class="text-xs mt-1" style="color: var(--color-accent);">+<?= $todayOrders ?> วันนี้</p>
                </div>
                <div class="text-3xl opacity-30"><i class="fas fa-shopping-bag"></i></div>
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-60">รายได้รวม</p>
                    <p class="text-2xl font-bold"><?= formatMoney($totalRevenue) ?></p>
                    <p class="text-xs mt-1" style="color: var(--color-accent);">+<?= formatMoney($todayRevenue) ?> วันนี้</p>
                </div>
                <div class="text-3xl opacity-30"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-60">เติมเงินรวม</p>
                    <p class="text-2xl font-bold"><?= formatMoney($totalTopup) ?></p>
                    <p class="text-xs mt-1" style="color: var(--color-accent);">+<?= formatMoney($todayTopup) ?> วันนี้</p>
                </div>
                <div class="text-3xl opacity-30"><i class="fas fa-coins"></i></div>
            </div>
        </div>
    </div>

    <!-- Alert Badges -->
    <div class="flex flex-wrap gap-3 mb-6">
        <?php if ($pendingDeliveries > 0): ?>
            <a href="<?= url('admin/orders') ?>" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold bg-yellow-500/20 text-yellow-400 hover:bg-yellow-500/30 transition">
                <i class="fas fa-truck"></i> <?= $pendingDeliveries ?> รอส่งของ
            </a>
        <?php endif; ?>
        <?php if ($pendingTopups > 0): ?>
            <a href="<?= url('admin/topups') ?>" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 transition">
                <i class="fas fa-clock"></i> <?= $pendingTopups ?> รออนุมัติเติมเงิน
            </a>
        <?php endif; ?>
        <?php if ($unreadContacts > 0): ?>
            <a href="<?= url('admin/contacts') ?>" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold bg-red-500/20 text-red-400 hover:bg-red-500/30 transition">
                <i class="fas fa-envelope"></i> <?= $unreadContacts ?> ข้อความใหม่
            </a>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Orders -->
        <div class="card p-5">
            <h3 class="text-lg font-semibold mb-4"><i class="fas fa-receipt mr-2" style="color: var(--color-primary);"></i>ออเดอร์ล่าสุด</h3>
            <?php if (empty($recentOrders)): ?>
                <p class="opacity-50 text-sm">ยังไม่มีออเดอร์</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="opacity-60 border-b border-white/10">
                            <th class="text-left pb-2">#</th>
                            <th class="text-left pb-2">ผู้ใช้</th>
                            <th class="text-right pb-2">ยอด</th>
                            <th class="text-center pb-2">สถานะ</th>
                            <th class="text-right pb-2">เวลา</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                            <tr class="border-b border-white/5">
                                <td class="py-2"><?= $o['id'] ?></td>
                                <td class="py-2"><?= e($o['username']) ?></td>
                                <td class="py-2 text-right"><?= formatMoney($o['total']) ?></td>
                                <td class="py-2 text-center">
                                    <?php
                                    $statusMap = ['pending'=>'bg-yellow-500/20 text-yellow-400','paid'=>'bg-blue-500/20 text-blue-400','delivered'=>'bg-green-500/20 text-green-400','cancelled'=>'bg-red-500/20 text-red-400','refunded'=>'bg-gray-500/20 text-gray-400'];
                                    $cls = $statusMap[$o['status']] ?? 'bg-gray-500/20 text-gray-400';
                                    ?>
                                    <span class="px-2 py-0.5 rounded text-xs <?= $cls ?>"><?= e($o['status']) ?></span>
                                </td>
                                <td class="py-2 text-right text-xs opacity-60"><?= timeAgo($o['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Topups -->
        <div class="card p-5">
            <h3 class="text-lg font-semibold mb-4"><i class="fas fa-coins mr-2" style="color: var(--color-accent);"></i>เติมเงินล่าสุด</h3>
            <?php if (empty($recentTopups)): ?>
                <p class="opacity-50 text-sm">ยังไม่มีรายการเติมเงิน</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="opacity-60 border-b border-white/10">
                            <th class="text-left pb-2">#</th>
                            <th class="text-left pb-2">ผู้ใช้</th>
                            <th class="text-right pb-2">จำนวน</th>
                            <th class="text-center pb-2">ช่องทาง</th>
                            <th class="text-center pb-2">สถานะ</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($recentTopups as $t): ?>
                            <tr class="border-b border-white/5">
                                <td class="py-2"><?= $t['id'] ?></td>
                                <td class="py-2"><?= e($t['username']) ?></td>
                                <td class="py-2 text-right"><?= formatMoney($t['amount']) ?></td>
                                <td class="py-2 text-center text-xs"><?= e($t['gateway']) ?></td>
                                <td class="py-2 text-center">
                                    <?php
                                    $tStatus = ['pending'=>'bg-yellow-500/20 text-yellow-400','completed'=>'bg-green-500/20 text-green-400','failed'=>'bg-red-500/20 text-red-400','cancelled'=>'bg-gray-500/20 text-gray-400'];
                                    $cls = $tStatus[$t['status']] ?? 'bg-gray-500/20 text-gray-400';
                                    ?>
                                    <span class="px-2 py-0.5 rounded text-xs <?= $cls ?>"><?= e($t['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Audit Log -->
        <div class="card p-5 lg:col-span-2">
            <h3 class="text-lg font-semibold mb-4"><i class="fas fa-history mr-2" style="color: var(--color-secondary);"></i>กิจกรรมล่าสุด</h3>
            <?php if (empty($recentAudit)): ?>
                <p class="opacity-50 text-sm">ยังไม่มีกิจกรรม</p>
            <?php else: ?>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php foreach ($recentAudit as $log): ?>
                        <div class="flex items-start gap-3 p-2 rounded hover:bg-white/5">
                            <i class="fas fa-circle text-xs mt-1.5 opacity-30"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm">
                                    <span class="font-semibold"><?= e($log['username'] ?? 'System') ?></span>
                                    <span class="opacity-70"> — <?= e($log['action']) ?></span>
                                    <?php if ($log['detail']): ?>
                                        <span class="opacity-50 text-xs"> <?= e(mb_substr($log['detail'], 0, 100)) ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs opacity-40"><?= timeAgo($log['created_at']) ?> • IP: <?= e($log['ip'] ?? '-') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
