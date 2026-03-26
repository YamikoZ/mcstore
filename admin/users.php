<?php
$pageTitle = 'จัดการผู้ใช้ - แอดมิน';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('admin/users'); }

    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'update_role' && $userId) {
        $role = in_array($_POST['role'], ['user', 'vip', 'admin']) ? $_POST['role'] : 'user';
        $db->execute("UPDATE users SET role = ? WHERE id = ?", [$role, $userId]);
        auditLog(Auth::id(), 'admin_user_role', "Set user #{$userId} role to {$role}");
        flash('success', 'เปลี่ยน Role เรียบร้อย');
        redirect('admin/users');
    }

    if ($action === 'ban' && $userId) {
        $reason = trim($_POST['ban_reason'] ?? '');
        $db->execute("UPDATE users SET is_banned = 1, ban_reason = ? WHERE id = ?", [$reason, $userId]);
        auditLog(Auth::id(), 'admin_user_ban', "Banned user #{$userId}: {$reason}");
        flash('success', 'แบนผู้ใช้เรียบร้อย');
        redirect('admin/users');
    }

    if ($action === 'unban' && $userId) {
        $db->execute("UPDATE users SET is_banned = 0, ban_reason = NULL WHERE id = ?", [$userId]);
        auditLog(Auth::id(), 'admin_user_unban', "Unbanned user #{$userId}");
        flash('success', 'ปลดแบนผู้ใช้เรียบร้อย');
        redirect('admin/users');
    }

    if ($action === 'adjust_balance' && $userId) {
        $amount = (float)$_POST['amount'];
        $note = trim($_POST['note'] ?? '');
        if ($amount == 0) { flash('error', 'จำนวนต้องไม่เป็น 0'); redirect('admin/users'); }

        $user = $db->fetch("SELECT username, balance FROM users WHERE id = ?", [$userId]);
        if (!$user) { flash('error', 'ไม่พบผู้ใช้'); redirect('admin/users'); }

        $newBalance = $user['balance'] + $amount;
        if ($newBalance < 0) { flash('error', 'ยอดเงินไม่พอ'); redirect('admin/users'); }

        $db->execute("UPDATE users SET balance = ? WHERE id = ?", [$newBalance, $userId]);
        $db->execute(
            "INSERT INTO wallet_ledger (username, type, amount, balance_after, reference, note) VALUES (?, ?, ?, ?, ?, ?)",
            [$user['username'], $amount > 0 ? 'credit' : 'debit', abs($amount), $newBalance, 'admin_adjust', $note ?: 'Admin adjustment']
        );
        createNotification($user['username'], 'system', 'ปรับยอดเงิน', ($amount > 0 ? '+' : '') . formatMoney($amount) . ' โดยแอดมิน');
        auditLog(Auth::id(), 'admin_user_balance', "Adjusted #{$userId} balance by {$amount}: {$note}");
        flash('success', 'ปรับยอดเงินเรียบร้อย');
        redirect('admin/users');
    }
}

// Search & pagination
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($search) {
    $where = "WHERE u.username LIKE ? OR u.email LIKE ?";
    $params = ["%{$search}%", "%{$search}%"];
}

$totalUsers = $db->count("SELECT COUNT(*) FROM users u {$where}", $params);
$totalPages = ceil($totalUsers / $perPage);

$users = $db->fetchAll("SELECT u.* FROM users u {$where} ORDER BY u.created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);

$editUserId = (int)($_GET['edit'] ?? 0);
$editUser = $editUserId ? $db->fetch("SELECT * FROM users WHERE id = ?", [$editUserId]) : null;

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-users mr-2" style="color: var(--color-primary);"></i>จัดการผู้ใช้</h1>
            <p class="text-sm opacity-60 mt-1"><?= number_format($totalUsers) ?> ผู้ใช้ทั้งหมด</p>
        </div>
        <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
    </div>

    <!-- Search -->
    <div class="card p-4 mb-4">
        <form class="flex gap-3">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="ค้นหา username หรือ email..." class="flex-1 px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
            <button type="submit" class="btn-primary px-4 py-2 rounded-lg text-sm"><i class="fas fa-search mr-1"></i> ค้นหา</button>
            <?php if ($search): ?><a href="<?= url('admin/users') ?>" class="px-4 py-2 rounded-lg text-sm text-red-400 hover:bg-red-500/10"><i class="fas fa-times mr-1"></i>ล้าง</a><?php endif; ?>
        </form>
    </div>

    <?php if ($editUser): ?>
    <!-- Edit User Panel -->
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">
            <img src="https://mc-heads.net/avatar/<?= e($editUser['username']) ?>/24" class="w-6 h-6 rounded inline mr-2">
            <?= e($editUser['username']) ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Role -->
            <form method="POST" class="p-4 rounded-lg border border-white/10">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                <label class="block text-sm font-medium mb-2">Role</label>
                <select name="role" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm mb-2" style="background-color: var(--color-surface-dark);">
                    <?php foreach (['user' => 'User', 'vip' => 'VIP', 'admin' => 'Admin'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $editUser['role'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-primary px-4 py-1 rounded text-xs w-full"><i class="fas fa-save mr-1"></i> บันทึก</button>
            </form>

            <!-- Balance Adjust -->
            <form method="POST" class="p-4 rounded-lg border border-white/10">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="adjust_balance">
                <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                <label class="block text-sm font-medium mb-2">ปรับเงิน (ปัจจุบัน: <?= formatMoney($editUser['balance']) ?>)</label>
                <input type="number" name="amount" step="0.01" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm mb-1" style="background-color: var(--color-surface-dark);" placeholder="+100 หรือ -50">
                <input type="text" name="note" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm mb-2" style="background-color: var(--color-surface-dark);" placeholder="หมายเหตุ...">
                <button type="submit" class="btn-primary px-4 py-1 rounded text-xs w-full"><i class="fas fa-coins mr-1"></i> ปรับ</button>
            </form>

            <!-- Ban/Unban -->
            <div class="p-4 rounded-lg border border-white/10">
                <?php if ($editUser['is_banned']): ?>
                    <p class="text-sm text-red-400 mb-2"><i class="fas fa-ban mr-1"></i> ถูกแบน: <?= e($editUser['ban_reason'] ?: '-') ?></p>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="unban">
                        <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                        <button type="submit" class="w-full px-4 py-1 rounded text-xs bg-green-500/20 text-green-400 hover:bg-green-500/30"><i class="fas fa-unlock mr-1"></i> ปลดแบน</button>
                    </form>
                <?php else: ?>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="ban">
                        <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                        <label class="block text-sm font-medium mb-2">แบนผู้ใช้</label>
                        <input type="text" name="ban_reason" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm mb-2" style="background-color: var(--color-surface-dark);" placeholder="เหตุผลในการแบน...">
                        <button type="submit" class="w-full px-4 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30" onclick="return confirm('แบนผู้ใช้นี้?')"><i class="fas fa-ban mr-1"></i> แบน</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3"><a href="<?= url('admin/users') ?>" class="text-sm opacity-60 hover:opacity-100"><i class="fas fa-times mr-1"></i>ปิด</a></div>
    </div>
    <?php endif; ?>

    <!-- User Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3">ผู้ใช้</th>
                    <th class="text-left px-4 py-3">อีเมล</th>
                    <th class="text-right px-4 py-3">ยอดเงิน</th>
                    <th class="text-center px-4 py-3">Role</th>
                    <th class="text-center px-4 py-3">สถานะ</th>
                    <th class="text-right px-4 py-3">สมัครเมื่อ</th>
                    <th class="text-right px-4 py-3">จัดการ</th>
                </tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <img src="https://mc-heads.net/avatar/<?= e($u['username']) ?>/24" class="w-6 h-6 rounded">
                                <span class="font-semibold"><?= e($u['username']) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 opacity-70 text-xs"><?= e($u['email'] ?: '-') ?></td>
                        <td class="px-4 py-3 text-right font-semibold" style="color: var(--color-accent);"><?= formatMoney($u['balance']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php
                            $roleColors = ['admin' => 'bg-red-500/20 text-red-400', 'vip' => 'bg-yellow-500/20 text-yellow-400', 'user' => 'bg-gray-500/20 text-gray-400'];
                            ?>
                            <span class="text-xs px-2 py-0.5 rounded <?= $roleColors[$u['role']] ?? '' ?>"><?= strtoupper($u['role']) ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($u['is_banned']): ?>
                                <span class="text-xs px-2 py-0.5 rounded bg-red-500/20 text-red-400">แบน</span>
                            <?php else: ?>
                                <span class="text-xs px-2 py-0.5 rounded bg-green-500/20 text-green-400">ปกติ</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right text-xs opacity-60"><?= $u['created_at'] ? timeAgo($u['created_at']) : '-' ?></td>
                        <td class="px-4 py-3 text-right">
                            <a href="?edit=<?= $u['id'] ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center opacity-50">ไม่พบผู้ใช้</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-1 mt-4">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1 rounded text-sm <?= $i === $page ? 'font-bold' : 'opacity-60 hover:opacity-100' ?>" <?= $i === $page ? 'style="background-color: var(--color-primary); color: #fff;"' : '' ?>><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
