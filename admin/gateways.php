<?php
$pageTitle = 'จัดการช่องทางชำระเงิน - แอดมิน';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('admin/gateways'); }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $id = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['id'])));
        if (!$id) { flash('error', 'ID ไม่ถูกต้อง'); redirect('admin/gateways'); }
        $exists = $db->fetch("SELECT id FROM payment_gateways WHERE id = ?", [$id]);
        if ($exists) { flash('error', 'ID นี้มีอยู่แล้ว'); redirect('admin/gateways'); }
        $db->execute(
            "INSERT INTO payment_gateways (id, name, description, icon, config_json, min_amount, max_amount, fee_percent, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$id, trim($_POST['name']), trim($_POST['description'] ?? ''), trim($_POST['icon'] ?? ''), trim($_POST['config_json'] ?? '{}'), (float)$_POST['min_amount'], (float)$_POST['max_amount'], (float)$_POST['fee_percent'], (int)($_POST['display_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0]
        );
        auditLog(Auth::id(), 'admin_gateway_create', 'Created gateway: ' . $id);
        flash('success', 'เพิ่มช่องทางชำระเงินเรียบร้อย');
        redirect('admin/gateways');
    }

    if ($action === 'update') {
        $id = $_POST['id'];
        $db->execute(
            "UPDATE payment_gateways SET name=?, description=?, icon=?, config_json=?, min_amount=?, max_amount=?, fee_percent=?, display_order=?, is_active=? WHERE id=?",
            [trim($_POST['name']), trim($_POST['description'] ?? ''), trim($_POST['icon'] ?? ''), trim($_POST['config_json'] ?? '{}'), (float)$_POST['min_amount'], (float)$_POST['max_amount'], (float)$_POST['fee_percent'], (int)($_POST['display_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0, $id]
        );
        auditLog(Auth::id(), 'admin_gateway_update', 'Updated gateway: ' . $id);
        flash('success', 'อัพเดทเรียบร้อย');
        redirect('admin/gateways');
    }

    if ($action === 'delete') {
        $id = $_POST['id'];
        $db->execute("DELETE FROM payment_gateways WHERE id = ?", [$id]);
        auditLog(Auth::id(), 'admin_gateway_delete', 'Deleted gateway: ' . $id);
        flash('success', 'ลบเรียบร้อย');
        redirect('admin/gateways');
    }
}

$gateways = $db->fetchAll("SELECT * FROM payment_gateways ORDER BY display_order");
$editId = $_GET['edit'] ?? '';
$editGateway = $editId ? $db->fetch("SELECT * FROM payment_gateways WHERE id = ?", [$editId]) : null;
$isCreate = isset($_GET['create']);

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-credit-card mr-2" style="color: var(--color-primary);"></i>ช่องทางชำระเงิน</h1>
            <p class="text-sm opacity-60 mt-1"><?= count($gateways) ?> ช่องทาง</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
            <a href="?create=1" class="btn-primary px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> เพิ่มช่องทาง</a>
        </div>
    </div>

    <?php if ($isCreate || $editGateway): ?>
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4"><?= $editGateway ? 'แก้ไข: ' . e($editGateway['name']) : 'เพิ่มช่องทางใหม่' ?></h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editGateway ? 'update' : 'create' ?>">
            <?php if ($editGateway): ?><input type="hidden" name="id" value="<?= e($editGateway['id']) ?>"><?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!$editGateway): ?>
                <div>
                    <label class="block text-sm font-medium mb-1">ID <span class="text-red-400">*</span></label>
                    <input type="text" name="id" required pattern="[a-z0-9_]+" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);" placeholder="truewallet">
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อ <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="<?= e($editGateway['name'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">คำอธิบาย</label>
                    <input type="text" name="description" value="<?= e($editGateway['description'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ไอคอน (URL)</label>
                    <input type="text" name="icon" value="<?= e($editGateway['icon'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ขั้นต่ำ</label>
                    <input type="number" name="min_amount" step="0.01" value="<?= e($editGateway['min_amount'] ?? '0') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">สูงสุด</label>
                    <input type="number" name="max_amount" step="0.01" value="<?= e($editGateway['max_amount'] ?? '99999') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ค่าธรรมเนียม (%)</label>
                    <input type="number" name="fee_percent" step="0.01" value="<?= e($editGateway['fee_percent'] ?? '0') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ลำดับ</label>
                    <input type="number" name="display_order" value="<?= e($editGateway['display_order'] ?? '0') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Config (JSON)</label>
                    <textarea name="config_json" rows="4" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm font-mono" style="background-color: var(--color-surface-dark);"><?= e($editGateway['config_json'] ?? '{}') ?></textarea>
                    <p class="text-xs opacity-40 mt-1">ตัวอย่าง: {"phone":"0812345678"}</p>
                </div>
                <div>
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= ($editGateway['is_active'] ?? 1) ? 'checked' : '' ?>> <span class="text-sm">เปิดใช้งาน</span></label>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg text-sm font-semibold"><i class="fas fa-save mr-1"></i> บันทึก</button>
                <a href="<?= url('admin/gateways') ?>" class="px-6 py-2 rounded-lg text-sm border border-white/10 hover:bg-white/5">ยกเลิก</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3">ID</th>
                    <th class="text-left px-4 py-3">ชื่อ</th>
                    <th class="text-right px-4 py-3">ขั้นต่ำ</th>
                    <th class="text-right px-4 py-3">สูงสุด</th>
                    <th class="text-center px-4 py-3">ค่าธรรมเนียม</th>
                    <th class="text-center px-4 py-3">สถานะ</th>
                    <th class="text-right px-4 py-3">จัดการ</th>
                </tr></thead>
                <tbody>
                <?php foreach ($gateways as $g): ?>
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="px-4 py-3 font-mono text-xs"><?= e($g['id']) ?></td>
                        <td class="px-4 py-3 font-semibold"><?= e($g['name']) ?></td>
                        <td class="px-4 py-3 text-right"><?= formatMoney($g['min_amount']) ?></td>
                        <td class="px-4 py-3 text-right"><?= formatMoney($g['max_amount']) ?></td>
                        <td class="px-4 py-3 text-center"><?= $g['fee_percent'] ?>%</td>
                        <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded <?= $g['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>"><?= $g['is_active'] ? 'เปิด' : 'ปิด' ?></span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="?edit=<?= e($g['id']) ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-edit"></i></a>
                            <form method="POST" class="inline" onsubmit="return confirm('ลบช่องทาง: <?= e(addslashes($g['name'])) ?>?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e($g['id']) ?>">
                                <button type="submit" class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($gateways)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center opacity-50">ยังไม่มีช่องทางชำระเงิน</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
