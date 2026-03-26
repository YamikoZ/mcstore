<?php
$pageTitle = 'จัดการสินค้า - แอดมิน';
$db = Database::getInstance();
$servers = $db->fetchAll("SELECT * FROM servers WHERE is_active = 1 ORDER BY display_order");
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY server_id, display_order");

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash('error', 'CSRF token ไม่ถูกต้อง');
        redirect('admin/products');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $db->execute(
            "INSERT INTO products (server_id, category_id, name, description, image, price, original_price, command, stock, is_featured, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $_POST['server_id'],
                $_POST['category_id'] ?: null,
                trim($_POST['name']),
                trim($_POST['description'] ?? ''),
                trim($_POST['image'] ?? ''),
                (float)$_POST['price'],
                $_POST['original_price'] ? (float)$_POST['original_price'] : null,
                trim($_POST['command']),
                (int)$_POST['stock'],
                isset($_POST['is_featured']) ? 1 : 0,
                isset($_POST['is_active']) ? 1 : 0,
                (int)($_POST['display_order'] ?? 0),
            ]
        );
        auditLog(Auth::id(), 'admin_product_create', 'Created product: ' . $_POST['name']);
        flash('success', 'เพิ่มสินค้าเรียบร้อย');
        redirect('admin/products');
    }

    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $db->execute(
            "UPDATE products SET server_id=?, category_id=?, name=?, description=?, image=?, price=?, original_price=?, command=?, stock=?, is_featured=?, is_active=?, display_order=? WHERE id=?",
            [
                $_POST['server_id'],
                $_POST['category_id'] ?: null,
                trim($_POST['name']),
                trim($_POST['description'] ?? ''),
                trim($_POST['image'] ?? ''),
                (float)$_POST['price'],
                $_POST['original_price'] ? (float)$_POST['original_price'] : null,
                trim($_POST['command']),
                (int)$_POST['stock'],
                isset($_POST['is_featured']) ? 1 : 0,
                isset($_POST['is_active']) ? 1 : 0,
                (int)($_POST['display_order'] ?? 0),
                $id
            ]
        );
        auditLog(Auth::id(), 'admin_product_update', 'Updated product #' . $id);
        flash('success', 'อัพเดทสินค้าเรียบร้อย');
        redirect('admin/products');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $product = $db->fetch("SELECT name FROM products WHERE id = ?", [$id]);
        $db->execute("DELETE FROM products WHERE id = ?", [$id]);
        auditLog(Auth::id(), 'admin_product_delete', 'Deleted product: ' . ($product['name'] ?? $id));
        flash('success', 'ลบสินค้าเรียบร้อย');
        redirect('admin/products');
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $db->execute("UPDATE products SET is_active = NOT is_active WHERE id = ?", [$id]);
        flash('success', 'เปลี่ยนสถานะเรียบร้อย');
        redirect('admin/products');
    }
}

// Filters
$filterServer = $_GET['server'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$editId = (int)($_GET['edit'] ?? 0);
$isCreate = isset($_GET['create']);

// Build query
$where = [];
$params = [];
if ($filterServer) { $where[] = "p.server_id = ?"; $params[] = $filterServer; }
if ($filterCategory) { $where[] = "p.category_id = ?"; $params[] = (int)$filterCategory; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$products = $db->fetchAll("SELECT p.*, s.name as server_name, c.name as category_name FROM products p LEFT JOIN servers s ON p.server_id = s.id LEFT JOIN categories c ON p.category_id = c.id {$whereSQL} ORDER BY p.server_id, p.display_order, p.id DESC", $params);

$editProduct = null;
if ($editId) {
    $editProduct = $db->fetch("SELECT * FROM products WHERE id = ?", [$editId]);
}

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-box mr-2" style="color: var(--color-primary);"></i>จัดการสินค้า</h1>
            <p class="text-sm opacity-60 mt-1">สินค้าทั้งหมด <?= count($products) ?> รายการ</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
            <a href="?create=1" class="btn-primary px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> เพิ่มสินค้า</a>
        </div>
    </div>

    <?php if ($isCreate || $editProduct): ?>
    <!-- Create/Edit Form -->
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">
            <i class="fas <?= $editProduct ? 'fa-edit' : 'fa-plus' ?> mr-2" style="color: var(--color-primary);"></i>
            <?= $editProduct ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' ?>
        </h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'create' ?>">
            <?php if ($editProduct): ?><input type="hidden" name="id" value="<?= $editProduct['id'] ?>"><?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อสินค้า <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="<?= e($editProduct['name'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">เซิร์ฟเวอร์ <span class="text-red-400">*</span></label>
                    <select name="server_id" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                        <option value="">-- เลือก --</option>
                        <?php foreach ($servers as $s): ?>
                            <option value="<?= e($s['id']) ?>" <?= ($editProduct['server_id'] ?? '') === $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">หมวดหมู่</label>
                    <select name="category_id" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                        <option value="">-- ไม่ระบุ --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($editProduct['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>[<?= e($cat['server_id']) ?>] <?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ราคา <span class="text-red-400">*</span></label>
                    <input type="number" name="price" step="0.01" min="0" value="<?= e($editProduct['price'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ราคาเดิม (ถ้ามีส่วนลด)</label>
                    <input type="number" name="original_price" step="0.01" min="0" value="<?= e($editProduct['original_price'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">สต็อก (-1 = ไม่จำกัด)</label>
                    <input type="number" name="stock" value="<?= e($editProduct['stock'] ?? '-1') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ลำดับแสดง</label>
                    <input type="number" name="display_order" value="<?= e($editProduct['display_order'] ?? '0') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">รูปภาพ (URL)</label>
                    <input type="text" name="image" value="<?= e($editProduct['image'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);" placeholder="https://...">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">คำสั่ง (Command) <span class="text-red-400">*</span></label>
                    <input type="text" name="command" value="<?= e($editProduct['command'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm font-mono" style="background-color: var(--color-surface-dark);" placeholder="give {player} diamond_sword 1">
                    <p class="text-xs opacity-40 mt-1">ใช้ {player} แทนชื่อผู้เล่น, {amount} แทนจำนวน</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);"><?= e($editProduct['description'] ?? '') ?></textarea>
                </div>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" <?= ($editProduct['is_active'] ?? 1) ? 'checked' : '' ?> class="rounded">
                        <span class="text-sm">เปิดใช้งาน</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" <?= ($editProduct['is_featured'] ?? 0) ? 'checked' : '' ?> class="rounded">
                        <span class="text-sm">สินค้าแนะนำ</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg font-semibold text-sm">
                    <i class="fas fa-save mr-1"></i> <?= $editProduct ? 'อัพเดท' : 'เพิ่มสินค้า' ?>
                </button>
                <a href="<?= url('admin/products') ?>" class="px-6 py-2 rounded-lg text-sm border border-white/10 hover:bg-white/5 transition">ยกเลิก</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card p-4 mb-4">
        <form class="flex flex-wrap gap-3 items-center">
            <select name="server" onchange="this.form.submit()" class="px-3 py-2 rounded-lg text-sm border border-white/10" style="background-color: var(--color-surface-dark);">
                <option value="">ทุกเซิร์ฟเวอร์</option>
                <?php foreach ($servers as $s): ?>
                    <option value="<?= e($s['id']) ?>" <?= $filterServer === $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="category" onchange="this.form.submit()" class="px-3 py-2 rounded-lg text-sm border border-white/10" style="background-color: var(--color-surface-dark);">
                <option value="">ทุกหมวด</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>>[<?= e($cat['server_id']) ?>] <?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($filterServer || $filterCategory): ?>
                <a href="<?= url('admin/products') ?>" class="text-sm text-red-400 hover:underline"><i class="fas fa-times mr-1"></i>ล้างฟิลเตอร์</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Product List -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="text-left px-4 py-3">ID</th>
                        <th class="text-left px-4 py-3">สินค้า</th>
                        <th class="text-left px-4 py-3">เซิร์ฟเวอร์</th>
                        <th class="text-left px-4 py-3">หมวด</th>
                        <th class="text-right px-4 py-3">ราคา</th>
                        <th class="text-center px-4 py-3">สต็อก</th>
                        <th class="text-center px-4 py-3">สถานะ</th>
                        <th class="text-right px-4 py-3">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="8" class="px-4 py-8 text-center opacity-50">ไม่พบสินค้า</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr class="border-b border-white/5 hover:bg-white/5">
                                <td class="px-4 py-3 opacity-60"><?= $p['id'] ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <?php if ($p['image']): ?>
                                            <img src="<?= e($p['image']) ?>" class="w-10 h-10 rounded object-cover" onerror="this.style.display='none'">
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded bg-white/10 flex items-center justify-center"><i class="fas fa-box opacity-30"></i></div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-semibold"><?= e($p['name']) ?></p>
                                            <?php if ($p['is_featured']): ?><span class="text-xs px-1.5 py-0.5 rounded bg-yellow-500/20 text-yellow-400">แนะนำ</span><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><?= e($p['server_name'] ?? $p['server_id']) ?></td>
                                <td class="px-4 py-3 opacity-70"><?= e($p['category_name'] ?? '-') ?></td>
                                <td class="px-4 py-3 text-right">
                                    <?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?>
                                        <span class="line-through text-xs opacity-40"><?= formatMoney($p['original_price']) ?></span><br>
                                    <?php endif; ?>
                                    <span class="font-semibold" style="color: var(--color-accent);"><?= formatMoney($p['price']) ?></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?= $p['stock'] == -1 ? '<span class="text-green-400 text-xs">∞</span>' : $p['stock'] ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="text-xs px-2 py-0.5 rounded <?= $p['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>">
                                            <?= $p['is_active'] ? 'เปิด' : 'ปิด' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <a href="?edit=<?= $p['id'] ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-edit"></i></a>
                                        <form method="POST" class="inline" onsubmit="return confirm('ลบสินค้า: <?= e(addslashes($p['name'])) ?>?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
