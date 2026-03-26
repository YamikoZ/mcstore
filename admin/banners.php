<?php
$pageTitle = 'จัดการแบนเนอร์ - แอดมิน';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('admin/banners'); }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $db->execute(
            "INSERT INTO banners (title, image, link, display_order, is_active, starts_at, ends_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [trim($_POST['title'] ?? ''), trim($_POST['image']), trim($_POST['link'] ?? ''), (int)($_POST['display_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0, $_POST['starts_at'] ?: null, $_POST['ends_at'] ?: null]
        );
        auditLog(Auth::id(), 'admin_banner_create', 'Created banner');
        flash('success', 'เพิ่มแบนเนอร์เรียบร้อย');
        redirect('admin/banners');
    }

    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $db->execute(
            "UPDATE banners SET title=?, image=?, link=?, display_order=?, is_active=?, starts_at=?, ends_at=? WHERE id=?",
            [trim($_POST['title'] ?? ''), trim($_POST['image']), trim($_POST['link'] ?? ''), (int)($_POST['display_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0, $_POST['starts_at'] ?: null, $_POST['ends_at'] ?: null, $id]
        );
        auditLog(Auth::id(), 'admin_banner_update', 'Updated banner #' . $id);
        flash('success', 'อัพเดทเรียบร้อย');
        redirect('admin/banners');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->execute("DELETE FROM banners WHERE id = ?", [$id]);
        auditLog(Auth::id(), 'admin_banner_delete', 'Deleted banner #' . $id);
        flash('success', 'ลบเรียบร้อย');
        redirect('admin/banners');
    }
}

$banners = $db->fetchAll("SELECT * FROM banners ORDER BY display_order, id DESC");
$editId = (int)($_GET['edit'] ?? 0);
$editBanner = $editId ? $db->fetch("SELECT * FROM banners WHERE id = ?", [$editId]) : null;
$isCreate = isset($_GET['create']);

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-images mr-2" style="color: var(--color-primary);"></i>จัดการแบนเนอร์</h1>
            <p class="text-sm opacity-60 mt-1"><?= count($banners) ?> แบนเนอร์</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
            <a href="?create=1" class="btn-primary px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> เพิ่มแบนเนอร์</a>
        </div>
    </div>

    <?php if ($isCreate || $editBanner): ?>
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4"><?= $editBanner ? 'แก้ไขแบนเนอร์' : 'เพิ่มแบนเนอร์ใหม่' ?></h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editBanner ? 'update' : 'create' ?>">
            <?php if ($editBanner): ?><input type="hidden" name="id" value="<?= $editBanner['id'] ?>"><?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อ</label>
                    <input type="text" name="title" value="<?= e($editBanner['title'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">รูปภาพ (URL) <span class="text-red-400">*</span></label>
                    <input type="text" name="image" value="<?= e($editBanner['image'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);" placeholder="https://...">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ลิงก์ (เมื่อคลิก)</label>
                    <input type="text" name="link" value="<?= e($editBanner['link'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ลำดับ</label>
                    <input type="number" name="display_order" value="<?= e($editBanner['display_order'] ?? '0') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">เริ่มแสดง</label>
                    <input type="datetime-local" name="starts_at" value="<?= $editBanner['starts_at'] ? date('Y-m-d\TH:i', strtotime($editBanner['starts_at'])) : '' ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">หยุดแสดง</label>
                    <input type="datetime-local" name="ends_at" value="<?= $editBanner['ends_at'] ? date('Y-m-d\TH:i', strtotime($editBanner['ends_at'])) : '' ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="flex items-center gap-2 mt-4"><input type="checkbox" name="is_active" value="1" <?= ($editBanner['is_active'] ?? 1) ? 'checked' : '' ?>> <span class="text-sm">เปิดใช้งาน</span></label>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg text-sm font-semibold"><i class="fas fa-save mr-1"></i> บันทึก</button>
                <a href="<?= url('admin/banners') ?>" class="px-6 py-2 rounded-lg text-sm border border-white/10 hover:bg-white/5">ยกเลิก</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($banners as $b): ?>
            <div class="card overflow-hidden">
                <div class="aspect-[3/1] bg-white/5">
                    <img src="<?= e($b['image']) ?>" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<div class=\'flex items-center justify-center h-full opacity-30\'><i class=\'fas fa-image text-3xl\'></i></div>'">
                </div>
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <p class="font-semibold"><?= e($b['title'] ?: 'ไม่มีชื่อ') ?></p>
                        <p class="text-xs opacity-50">ลำดับ: <?= $b['display_order'] ?> • <span class="<?= $b['is_active'] ? 'text-green-400' : 'text-red-400' ?>"><?= $b['is_active'] ? 'เปิด' : 'ปิด' ?></span></p>
                    </div>
                    <div class="flex gap-1">
                        <a href="?edit=<?= $b['id'] ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-edit"></i></a>
                        <form method="POST" class="inline" onsubmit="return confirm('ลบแบนเนอร์นี้?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($banners)): ?>
            <div class="md:col-span-2 card p-8 text-center opacity-50">ยังไม่มีแบนเนอร์</div>
        <?php endif; ?>
    </div>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
