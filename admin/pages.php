<?php
$pageTitle = 'จัดการหน้าเพจ - แอดมิน';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('admin/pages'); }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_POST['slug'])));
        if (!$slug) { flash('error', 'Slug ไม่ถูกต้อง'); redirect('admin/pages'); }
        $exists = $db->fetch("SELECT id FROM pages WHERE slug = ?", [$slug]);
        if ($exists) { flash('error', 'Slug นี้มีอยู่แล้ว'); redirect('admin/pages'); }
        $db->execute(
            "INSERT INTO pages (slug, title, content, is_active) VALUES (?, ?, ?, ?)",
            [$slug, trim($_POST['title']), $_POST['content'] ?? '', isset($_POST['is_active']) ? 1 : 0]
        );
        auditLog(Auth::id(), 'admin_page_create', 'Created page: ' . $slug);
        flash('success', 'สร้างหน้าเพจเรียบร้อย');
        redirect('admin/pages');
    }

    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $db->execute(
            "UPDATE pages SET title=?, content=?, is_active=? WHERE id=?",
            [trim($_POST['title']), $_POST['content'] ?? '', isset($_POST['is_active']) ? 1 : 0, $id]
        );
        auditLog(Auth::id(), 'admin_page_update', 'Updated page #' . $id);
        flash('success', 'อัพเดทเรียบร้อย');
        redirect('admin/pages');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->execute("DELETE FROM pages WHERE id = ?", [$id]);
        auditLog(Auth::id(), 'admin_page_delete', 'Deleted page #' . $id);
        flash('success', 'ลบเรียบร้อย');
        redirect('admin/pages');
    }
}

$pages = $db->fetchAll("SELECT * FROM pages ORDER BY id");
$editId = (int)($_GET['edit'] ?? 0);
$editPage = $editId ? $db->fetch("SELECT * FROM pages WHERE id = ?", [$editId]) : null;
$isCreate = isset($_GET['create']);

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-file-alt mr-2" style="color: var(--color-primary);"></i>จัดการหน้าเพจ</h1>
            <p class="text-sm opacity-60 mt-1"><?= count($pages) ?> หน้า</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
            <a href="?create=1" class="btn-primary px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> สร้างหน้าใหม่</a>
        </div>
    </div>

    <?php if ($isCreate || $editPage): ?>
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4"><?= $editPage ? 'แก้ไขหน้า: ' . e($editPage['slug']) : 'สร้างหน้าเพจใหม่' ?></h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editPage ? 'update' : 'create' ?>">
            <?php if ($editPage): ?><input type="hidden" name="id" value="<?= $editPage['id'] ?>"><?php endif; ?>
            <div class="space-y-4">
                <?php if (!$editPage): ?>
                <div>
                    <label class="block text-sm font-medium mb-1">Slug (URL) <span class="text-red-400">*</span></label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm opacity-50"><?= url('page/') ?></span>
                        <input type="text" name="slug" required pattern="[a-z0-9-]+" class="flex-1 px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);" placeholder="my-page">
                    </div>
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อหน้า <span class="text-red-400">*</span></label>
                    <input type="text" name="title" value="<?= e($editPage['title'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">เนื้อหา (HTML)</label>
                    <textarea name="content" rows="15" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm font-mono" style="background-color: var(--color-surface-dark);"><?= e($editPage['content'] ?? '') ?></textarea>
                    <p class="text-xs opacity-40 mt-1">รองรับ HTML เช่น &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;ol&gt; ฯลฯ</p>
                </div>
                <div>
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= ($editPage['is_active'] ?? 1) ? 'checked' : '' ?>> <span class="text-sm">เปิดใช้งาน</span></label>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg text-sm font-semibold"><i class="fas fa-save mr-1"></i> บันทึก</button>
                <a href="<?= url('admin/pages') ?>" class="px-6 py-2 rounded-lg text-sm border border-white/10 hover:bg-white/5">ยกเลิก</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3">Slug</th>
                    <th class="text-left px-4 py-3">ชื่อ</th>
                    <th class="text-center px-4 py-3">สถานะ</th>
                    <th class="text-right px-4 py-3">จัดการ</th>
                </tr></thead>
                <tbody>
                <?php foreach ($pages as $p): ?>
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="px-4 py-3 font-mono text-xs"><?= e($p['slug']) ?></td>
                        <td class="px-4 py-3 font-semibold"><?= e($p['title']) ?></td>
                        <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded <?= $p['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>"><?= $p['is_active'] ? 'เปิด' : 'ปิด' ?></span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url('page/' . e($p['slug'])) ?>" target="_blank" class="px-2 py-1 rounded text-xs bg-gray-500/20 text-gray-400 hover:bg-gray-500/30"><i class="fas fa-eye"></i></a>
                            <a href="?edit=<?= $p['id'] ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-edit"></i></a>
                            <form method="POST" class="inline" onsubmit="return confirm('ลบหน้า: <?= e($p['slug']) ?>?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pages)): ?>
                    <tr><td colspan="4" class="px-4 py-8 text-center opacity-50">ยังไม่มีหน้าเพจ</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
