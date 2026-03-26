<?php
$pageTitle = 'จัดการเติมเงิน - แอดมิน';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('admin/topups'); }

    $action = $_POST['action'] ?? '';
    $txId = (int)($_POST['tx_id'] ?? 0);

    if (in_array($action, ['approve', 'reject']) && $txId) {
        $tx = $db->fetch("SELECT * FROM topup_transactions WHERE id = ?", [$txId]);
        if (!$tx || $tx['status'] !== 'pending') { flash('error', 'ไม่พบรายการหรือดำเนินการแล้ว'); redirect('admin/topups'); }

        if ($action === 'approve') {
            $user = $db->fetch("SELECT balance FROM users WHERE username = ?", [$tx['username']]);
            if (!$user) { flash('error', 'ไม่พบผู้ใช้'); redirect('admin/topups'); }

            $newBalance = $user['balance'] + $tx['amount'];
            $db->execute("UPDATE users SET balance = ? WHERE username = ?", [$newBalance, $tx['username']]);
            $db->execute("UPDATE topup_transactions SET status = 'completed', completed_at = NOW() WHERE id = ?", [$txId]);
            $db->execute(
                "INSERT INTO wallet_ledger (username, type, amount, balance_after, reference, note) VALUES (?, 'credit', ?, ?, ?, ?)",
                [$tx['username'], $tx['amount'], $newBalance, 'topup#' . $txId, 'เติมเงินผ่าน ' . e($tx['gateway'])]
            );
            createNotification($tx['username'], 'topup', 'เติมเงินสำเร็จ', 'เติมเงิน ' . formatMoney($tx['amount']) . ' สำเร็จแล้ว');
            auditLog(Auth::id(), 'admin_topup_approve', "Topup #{$txId} amount={$tx['amount']} user={$tx['username']}");
            flash('success', 'อนุมัติเติมเงิน ' . formatMoney($tx['amount']) . ' ให้ ' . e($tx['username']) . ' สำเร็จ');
        } else {
            $db->execute("UPDATE topup_transactions SET status = 'failed', completed_at = NOW() WHERE id = ?", [$txId]);
            createNotification($tx['username'], 'topup', 'เติมเงินถูกปฏิเสธ', 'รายการเติมเงิน ' . formatMoney($tx['amount']) . ' ถูกปฏิเสธ');
            auditLog(Auth::id(), 'admin_topup_reject', "Topup #{$txId} amount={$tx['amount']} user={$tx['username']}");
            flash('success', 'ปฏิเสธรายการเรียบร้อย');
        }
        redirect('admin/topups');
    }
}

// Filters
$filterStatus = $_GET['status'] ?? 'pending';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($filterStatus) { $where = "WHERE t.status = ?"; $params[] = $filterStatus; }

$totalTx = $db->count("SELECT COUNT(*) FROM topup_transactions t {$where}", $params);
$totalPages = ceil($totalTx / $perPage);

$transactions = $db->fetchAll("SELECT t.* FROM topup_transactions t {$where} ORDER BY t.created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);

$statusLabels = ['pending'=>'รออนุมัติ','completed'=>'สำเร็จ','failed'=>'ปฏิเสธ'];
$statusColors = ['pending'=>'bg-yellow-500/20 text-yellow-400','completed'=>'bg-green-500/20 text-green-400','failed'=>'bg-red-500/20 text-red-400'];

$pendingCount = $db->count("SELECT COUNT(*) FROM topup_transactions WHERE status = 'pending'");

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-coins mr-2" style="color: var(--color-accent);"></i>จัดการเติมเงิน</h1>
            <p class="text-sm opacity-60 mt-1">
                <?php if ($pendingCount > 0): ?>
                    <span class="text-yellow-400"><i class="fas fa-exclamation-circle"></i> <?= $pendingCount ?> รายการรออนุมัติ</span>
                <?php else: ?>
                    ไม่มีรายการรออนุมัติ
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
    </div>

    <!-- Filters -->
    <div class="card p-4 mb-4">
        <div class="flex flex-wrap gap-2">
            <a href="?status=" class="px-3 py-1 rounded-lg text-sm <?= !$filterStatus ? 'font-bold' : 'opacity-60 hover:opacity-100' ?>" <?= !$filterStatus ? 'style="background-color: var(--color-primary); color: #fff;"' : '' ?>>ทั้งหมด</a>
            <?php foreach ($statusLabels as $k => $v): ?>
                <a href="?status=<?= $k ?>" class="px-3 py-1 rounded-lg text-sm <?= $filterStatus === $k ? 'font-bold' : 'opacity-60 hover:opacity-100' ?>" <?= $filterStatus === $k ? 'style="background-color: var(--color-primary); color: #fff;"' : '' ?>><?= $v ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3">#</th>
                    <th class="text-left px-4 py-3">ผู้ใช้</th>
                    <th class="text-left px-4 py-3">ช่องทาง</th>
                    <th class="text-right px-4 py-3">จำนวน</th>
                    <th class="text-center px-4 py-3">สถานะ</th>
                    <th class="text-left px-4 py-3">อ้างอิง</th>
                    <th class="text-right px-4 py-3">เวลา</th>
                    <th class="text-right px-4 py-3">จัดการ</th>
                </tr></thead>
                <tbody>
                <?php foreach ($transactions as $tx): ?>
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="px-4 py-3"><?= $tx['id'] ?></td>
                        <td class="px-4 py-3 font-semibold"><?= e($tx['username']) ?></td>
                        <td class="px-4 py-3 opacity-70"><?= e($tx['gateway']) ?></td>
                        <td class="px-4 py-3 text-right font-semibold" style="color: var(--color-accent);"><?= formatMoney($tx['amount']) ?></td>
                        <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded <?= $statusColors[$tx['status']] ?? '' ?>"><?= $statusLabels[$tx['status']] ?? $tx['status'] ?></span></td>
                        <td class="px-4 py-3 text-xs opacity-50 max-w-[200px] truncate"><?= e($tx['gateway_ref'] ?? $tx['note'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-right text-xs opacity-60"><?= timeAgo($tx['created_at']) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($tx['status'] === 'pending'): ?>
                                <form method="POST" class="inline-flex gap-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tx_id" value="<?= $tx['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="px-2 py-1 rounded text-xs bg-green-500/20 text-green-400 hover:bg-green-500/30" onclick="return confirm('อนุมัติเติมเงิน <?= formatMoney($tx['amount']) ?> ให้ <?= e($tx['username']) ?>?')"><i class="fas fa-check"></i></button>
                                    <button type="submit" name="action" value="reject" class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30" onclick="return confirm('ปฏิเสธรายการนี้?')"><i class="fas fa-times"></i></button>
                                </form>
                            <?php else: ?>
                                <span class="text-xs opacity-40"><?= $tx['completed_at'] ? timeAgo($tx['completed_at']) : '-' ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center opacity-50">ไม่พบรายการ</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-1 mt-4">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&status=<?= urlencode($filterStatus) ?>" class="px-3 py-1 rounded text-sm <?= $i === $page ? 'font-bold' : 'opacity-60 hover:opacity-100' ?>" <?= $i === $page ? 'style="background-color: var(--color-primary); color: #fff;"' : '' ?>><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
