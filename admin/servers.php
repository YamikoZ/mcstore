<?php
$pageTitle = 'จัดการเซิร์ฟเวอร์ - แอดมิน';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('admin/servers'); }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $id = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['id'])));
        if (!$id) { flash('error', 'ID ต้องเป็นตัวอักษรภาษาอังกฤษพิมพ์เล็ก'); redirect('admin/servers'); }
        $exists = $db->fetch("SELECT id FROM servers WHERE id = ?", [$id]);
        if ($exists) { flash('error', 'ID นี้มีอยู่แล้ว'); redirect('admin/servers'); }
        $db->execute(
            "INSERT INTO servers (id, name, description, icon, ip, port, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$id, trim($_POST['name']), trim($_POST['description'] ?? ''), trim($_POST['icon'] ?? ''), trim($_POST['ip'] ?? ''), (int)($_POST['port'] ?? 25565), (int)($_POST['display_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0]
        );
        auditLog(Auth::id(), 'admin_server_create', 'Created server: ' . $id);
        flash('success', 'เพิ่มเซิร์ฟเวอร์เรียบร้อย');
        redirect('admin/servers');
    }

    if ($action === 'update') {
        $id = $_POST['id'];
        $db->execute(
            "UPDATE servers SET name=?, description=?, icon=?, ip=?, port=?, display_order=?, is_active=? WHERE id=?",
            [trim($_POST['name']), trim($_POST['description'] ?? ''), trim($_POST['icon'] ?? ''), trim($_POST['ip'] ?? ''), (int)($_POST['port'] ?? 25565), (int)($_POST['display_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0, $id]
        );
        auditLog(Auth::id(), 'admin_server_update', 'Updated server: ' . $id);
        flash('success', 'อัพเดทเซิร์ฟเวอร์เรียบร้อย');
        redirect('admin/servers');
    }

    if ($action === 'delete') {
        $id = $_POST['id'];
        // Check if products or gacha exist for this server
        $prodCount = $db->count("SELECT COUNT(*) FROM products WHERE server_id = ?", [$id]);
        $gachaCount = $db->count("SELECT COUNT(*) FROM gacha_crates WHERE server_id = ?", [$id]);
        if ($prodCount > 0 || $gachaCount > 0) {
            flash('error', "ไม่สามารถลบได้ ยังมีสินค้า ({$prodCount}) หรือกาชา ({$gachaCount}) อยู่ในเซิร์ฟนี้");
            redirect('admin/servers');
        }
        $db->execute("DELETE FROM categories WHERE server_id = ?", [$id]);
        $db->execute("DELETE FROM servers WHERE id = ?", [$id]);
        auditLog(Auth::id(), 'admin_server_delete', 'Deleted server: ' . $id);
        flash('success', 'ลบเซิร์ฟเวอร์เรียบร้อย');
        redirect('admin/servers');
    }
}

$servers = $db->fetchAll("SELECT * FROM servers ORDER BY display_order, id");
$editId = $_GET['edit'] ?? '';
$editServer = $editId ? $db->fetch("SELECT * FROM servers WHERE id = ?", [$editId]) : null;
$isCreate = isset($_GET['create']);

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-server mr-2" style="color: var(--color-primary);"></i>จัดการเซิร์ฟเวอร์</h1>
            <p class="text-sm opacity-60 mt-1"><?= count($servers) ?> เซิร์ฟเวอร์</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
            <a href="?create=1" class="btn-primary px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> เพิ่มเซิร์ฟเวอร์</a>
        </div>
    </div>

    <?php if ($isCreate || $editServer): ?>
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4"><?= $editServer ? 'แก้ไขเซิร์ฟเวอร์' : 'เพิ่มเซิร์ฟเวอร์ใหม่' ?></h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editServer ? 'update' : 'create' ?>">
            <?php if ($editServer): ?><input type="hidden" name="id" value="<?= e($editServer['id']) ?>"><?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Server ID <span class="text-red-400">*</span></label>
                    <input type="text" name="id" value="<?= e($editServer['id'] ?? '') ?>" <?= $editServer ? 'readonly class="opacity-50"' : '' ?> required pattern="[a-z0-9_]+" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);" placeholder="survival">
                    <?php if (!$editServer): ?><p class="text-xs opacity-40 mt-1">ตัวอักษรภาษาอังกฤษพิมพ์เล็ก, ตัวเลข, _ เท่านั้น</p><?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อ <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="<?= e($editServer['name'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">IP</label>
                    <input type="text" name="ip" value="<?= e($editServer['ip'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Port</label>
                    <input type="number" name="port" value="<?= e($editServer['port'] ?? '25565') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ไอคอน (URL)</label>
                    <input type="text" name="icon" value="<?= e($editServer['icon'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ลำดับแสดง</label>
                    <input type="number" name="display_order" value="<?= e($editServer['display_order'] ?? '0') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);"><?= e($editServer['description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" <?= ($editServer['is_active'] ?? 1) ? 'checked' : '' ?> class="rounded">
                        <span class="text-sm">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg text-sm font-semibold"><i class="fas fa-save mr-1"></i> บันทึก</button>
                <a href="<?= url('admin/servers') ?>" class="px-6 py-2 rounded-lg text-sm border border-white/10 hover:bg-white/5">ยกเลิก</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Server List -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3">ID</th>
                    <th class="text-left px-4 py-3">ชื่อ</th>
                    <th class="text-left px-4 py-3">IP</th>
                    <th class="text-center px-4 py-3">ลำดับ</th>
                    <th class="text-center px-4 py-3">สถานะ</th>
                    <th class="text-center px-4 py-3">Poll ล่าสุด</th>
                    <th class="text-right px-4 py-3">จัดการ</th>
                </tr></thead>
                <tbody>
                <?php foreach ($servers as $s): ?>
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="px-4 py-3 font-mono text-xs"><?= e($s['id']) ?></td>
                        <td class="px-4 py-3 font-semibold"><?= e($s['name']) ?></td>
                        <td class="px-4 py-3 opacity-70"><?= e($s['ip'] ?: '-') ?>:<?= e($s['port']) ?></td>
                        <td class="px-4 py-3 text-center"><?= $s['display_order'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-0.5 rounded <?= $s['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>"><?= $s['is_active'] ? 'เปิด' : 'ปิด' ?></span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs opacity-60"><?= $s['last_poll'] ? timeAgo($s['last_poll']) : '-' ?></td>
                        <td class="px-4 py-3 text-right">
                            <a href="?edit=<?= e($s['id']) ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-edit"></i></a>
                            <form method="POST" class="inline" onsubmit="return confirm('ลบเซิร์ฟเวอร์: <?= e(addslashes($s['name'])) ?>?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e($s['id']) ?>">
                                <button type="submit" class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
