<?php
$pageTitle = 'จัดการรีดีมโค้ด - แอดมิน';
$db = Database::getInstance();
$servers = $db->fetchAll("SELECT * FROM servers WHERE is_active = 1 ORDER BY display_order");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('admin/redeem'); }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $code = strtoupper(trim($_POST['code']));
        $exists = $db->fetch("SELECT id FROM redeem_codes WHERE code = ?", [$code]);
        if ($exists) { flash('error', 'โค้ดนี้มีอยู่แล้ว'); redirect('admin/redeem'); }
        $db->execute(
            "INSERT INTO redeem_codes (code, reward_type, reward_value, server_id, max_uses, per_user_limit, expires_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$code, $_POST['reward_type'], trim($_POST['reward_value']), $_POST['server_id'] ?: null, (int)$_POST['max_uses'], (int)$_POST['per_user_limit'], $_POST['expires_at'] ?: null, isset($_POST['is_active']) ? 1 : 0]
        );
        auditLog(Auth::id(), 'admin_redeem_create', 'Created code: ' . $code);
        flash('success', 'สร้างรีดีมโค้ดเรียบร้อย');
        redirect('admin/redeem');
    }

    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $db->execute(
            "UPDATE redeem_codes SET reward_type=?, reward_value=?, server_id=?, max_uses=?, per_user_limit=?, expires_at=?, is_active=? WHERE id=?",
            [$_POST['reward_type'], trim($_POST['reward_value']), $_POST['server_id'] ?: null, (int)$_POST['max_uses'], (int)$_POST['per_user_limit'], $_POST['expires_at'] ?: null, isset($_POST['is_active']) ? 1 : 0, $id]
        );
        auditLog(Auth::id(), 'admin_redeem_update', 'Updated code #' . $id);
        flash('success', 'อัพเดทเรียบร้อย');
        redirect('admin/redeem');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->execute("DELETE FROM redeem_codes WHERE id = ?", [$id]);
        auditLog(Auth::id(), 'admin_redeem_delete', 'Deleted code #' . $id);
        flash('success', 'ลบเรียบร้อย');
        redirect('admin/redeem');
    }

    if ($action === 'generate') {
        $prefix = strtoupper(trim($_POST['prefix'] ?? ''));
        $count = min((int)$_POST['count'], 100);
        $rewardType = $_POST['reward_type'];
        $rewardValue = trim($_POST['reward_value']);
        $maxUses = (int)$_POST['max_uses'];
        $expiresAt = $_POST['expires_at'] ?: null;

        $generated = 0;
        for ($i = 0; $i < $count; $i++) {
            $code = $prefix . strtoupper(bin2hex(random_bytes(4)));
            $exists = $db->fetch("SELECT id FROM redeem_codes WHERE code = ?", [$code]);
            if (!$exists) {
                $db->execute(
                    "INSERT INTO redeem_codes (code, reward_type, reward_value, max_uses, per_user_limit, expires_at, is_active) VALUES (?, ?, ?, ?, 1, ?, 1)",
                    [$code, $rewardType, $rewardValue, $maxUses, $expiresAt]
                );
                $generated++;
            }
        }
        auditLog(Auth::id(), 'admin_redeem_generate', "Generated {$generated} codes");
        flash('success', "สร้างโค้ดสำเร็จ {$generated} โค้ด");
        redirect('admin/redeem');
    }
}

$codes = $db->fetchAll("SELECT rc.*, (SELECT COUNT(*) FROM redeem_usage WHERE code_id = rc.id) as actual_uses FROM redeem_codes rc ORDER BY rc.created_at DESC LIMIT 100");

$editId = (int)($_GET['edit'] ?? 0);
$editCode = $editId ? $db->fetch("SELECT * FROM redeem_codes WHERE id = ?", [$editId]) : null;
$isCreate = isset($_GET['create']);
$isGenerate = isset($_GET['generate']);

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-gift mr-2" style="color: var(--color-accent);"></i>จัดการรีดีมโค้ด</h1>
            <p class="text-sm opacity-60 mt-1"><?= count($codes) ?> โค้ด (แสดง 100 ล่าสุด)</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
            <a href="?generate=1" class="btn-secondary px-4 py-2 rounded-lg text-sm"><i class="fas fa-magic mr-1"></i> สร้างอัตโนมัติ</a>
            <a href="?create=1" class="btn-primary px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> เพิ่มโค้ด</a>
        </div>
    </div>

    <?php if ($isGenerate): ?>
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4"><i class="fas fa-magic mr-2" style="color: var(--color-secondary);"></i>สร้างโค้ดอัตโนมัติ</h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="generate">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Prefix</label>
                    <input type="text" name="prefix" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);" placeholder="GIFT-">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">จำนวน <span class="text-red-400">*</span></label>
                    <input type="number" name="count" min="1" max="100" value="10" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ประเภทรางวัล</label>
                    <select name="reward_type" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                        <option value="balance">เงิน (Balance)</option>
                        <option value="item">ไอเทม (Command)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ค่ารางวัล <span class="text-red-400">*</span></label>
                    <input type="text" name="reward_value" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);" placeholder="100 หรือ give {player} diamond 1">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ใช้ได้สูงสุด (ต่อโค้ด)</label>
                    <input type="number" name="max_uses" min="1" value="1" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">หมดอายุ</label>
                    <input type="datetime-local" name="expires_at" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-secondary px-6 py-2 rounded-lg text-sm font-semibold"><i class="fas fa-magic mr-1"></i> สร้าง</button>
                <a href="<?= url('admin/redeem') ?>" class="px-6 py-2 rounded-lg text-sm border border-white/10 hover:bg-white/5">ยกเลิก</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($isCreate || $editCode): ?>
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4"><?= $editCode ? 'แก้ไขโค้ด' : 'เพิ่มรีดีมโค้ด' ?></h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editCode ? 'update' : 'create' ?>">
            <?php if ($editCode): ?><input type="hidden" name="id" value="<?= $editCode['id'] ?>"><?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!$editCode): ?>
                <div>
                    <label class="block text-sm font-medium mb-1">โค้ด <span class="text-red-400">*</span></label>
                    <input type="text" name="code" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm font-mono uppercase" style="background-color: var(--color-surface-dark);" placeholder="WELCOME2024">
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium mb-1">ประเภทรางวัล</label>
                    <select name="reward_type" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                        <option value="balance" <?= ($editCode['reward_type'] ?? '') === 'balance' ? 'selected' : '' ?>>เงิน (Balance)</option>
                        <option value="item" <?= ($editCode['reward_type'] ?? '') === 'item' ? 'selected' : '' ?>>ไอเทม (Command)</option>
                        <option value="gacha" <?= ($editCode['reward_type'] ?? '') === 'gacha' ? 'selected' : '' ?>>กาชา</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ค่ารางวัล <span class="text-red-400">*</span></label>
                    <input type="text" name="reward_value" value="<?= e($editCode['reward_value'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);" placeholder="100 (เงิน) หรือ give {player} diamond 1">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">เซิร์ฟเวอร์ (ถ้าเป็น item)</label>
                    <select name="server_id" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                        <option value="">-- ไม่ระบุ --</option>
                        <?php foreach ($servers as $s): ?>
                            <option value="<?= e($s['id']) ?>" <?= ($editCode['server_id'] ?? '') === $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ใช้ได้สูงสุด (รวม)</label>
                    <input type="number" name="max_uses" min="1" value="<?= e($editCode['max_uses'] ?? '1') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">จำกัดต่อคน</label>
                    <input type="number" name="per_user_limit" min="1" value="<?= e($editCode['per_user_limit'] ?? '1') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">หมดอายุ</label>
                    <input type="datetime-local" name="expires_at" value="<?= $editCode['expires_at'] ? date('Y-m-d\TH:i', strtotime($editCode['expires_at'])) : '' ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="flex items-center gap-2 mt-6"><input type="checkbox" name="is_active" value="1" <?= ($editCode['is_active'] ?? 1) ? 'checked' : '' ?>> <span class="text-sm">เปิดใช้งาน</span></label>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg text-sm font-semibold"><i class="fas fa-save mr-1"></i> บันทึก</button>
                <a href="<?= url('admin/redeem') ?>" class="px-6 py-2 rounded-lg text-sm border border-white/10 hover:bg-white/5">ยกเลิก</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Code List -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3">โค้ด</th>
                    <th class="text-center px-4 py-3">ประเภท</th>
                    <th class="text-left px-4 py-3">รางวัล</th>
                    <th class="text-center px-4 py-3">ใช้แล้ว</th>
                    <th class="text-center px-4 py-3">หมดอายุ</th>
                    <th class="text-center px-4 py-3">สถานะ</th>
                    <th class="text-right px-4 py-3">จัดการ</th>
                </tr></thead>
                <tbody>
                <?php foreach ($codes as $c): ?>
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="px-4 py-3 font-mono font-semibold"><?= e($c['code']) ?></td>
                        <td class="px-4 py-3 text-center text-xs">
                            <?php
                            $typeLabels = ['balance' => 'เงิน', 'item' => 'ไอเทม', 'gacha' => 'กาชา'];
                            echo $typeLabels[$c['reward_type']] ?? $c['reward_type'];
                            ?>
                        </td>
                        <td class="px-4 py-3 text-xs opacity-70"><?= e(mb_substr($c['reward_value'], 0, 40)) ?></td>
                        <td class="px-4 py-3 text-center"><?= $c['actual_uses'] ?>/<?= $c['max_uses'] ?></td>
                        <td class="px-4 py-3 text-center text-xs opacity-60"><?= $c['expires_at'] ? date('d/m/Y', strtotime($c['expires_at'])) : '-' ?></td>
                        <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded <?= $c['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>"><?= $c['is_active'] ? 'เปิด' : 'ปิด' ?></span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="?edit=<?= $c['id'] ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-edit"></i></a>
                            <form method="POST" class="inline" onsubmit="return confirm('ลบโค้ด: <?= e($c['code']) ?>?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($codes)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center opacity-50">ยังไม่มีรีดีมโค้ด</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
