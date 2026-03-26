<?php
$pageTitle = 'License';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_key') {
        $key = trim($_POST['license_key'] ?? '');
        Settings::set('license_key', $key);
        License::bust();
        flash('success', 'บันทึก License Key แล้ว');
        redirect('admin/license');
    }

    if ($action === 'recheck') {
        License::bust();
        flash('success', 're-validate แล้ว กรุณารีเฟรชหน้า');
        redirect('admin/license');
    }
}

$info   = License::info();
$valid  = $info['valid'];
$domain = $info['domain'];

require BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-xl">
    <h1 class="text-xl font-bold mb-6">License</h1>

    <!-- Status Card -->
    <div class="rounded-xl p-5 mb-6 border <?= $valid ? 'border-green-500/30 bg-green-500/10' : 'border-red-500/30 bg-red-500/10' ?>">
        <div class="flex items-center gap-3 mb-3">
            <i class="fas <?= $valid ? 'fa-circle-check text-green-400' : 'fa-circle-xmark text-red-400' ?> text-2xl"></i>
            <div>
                <p class="font-semibold"><?= $valid ? 'License ใช้งานได้' : 'License ไม่ถูกต้อง' ?></p>
                <p class="text-xs opacity-60">
                    <?php if ($info['cached_at']): ?>
                        ตรวจสอบล่าสุด: <?= $info['cached_at'] ?>
                    <?php else: ?>
                        ยังไม่เคยตรวจสอบ
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="text-sm opacity-70">
            Domain ปัจจุบัน: <code class="bg-white/10 px-2 py-0.5 rounded text-xs"><?= e($domain) ?></code>
        </div>
    </div>

    <!-- License Key Form -->
    <div class="rounded-xl p-5 mb-4" style="background: var(--color-surface);">
        <h2 class="font-semibold mb-4">ใส่ License Key</h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_key">
            <div class="mb-4">
                <label class="block text-sm opacity-70 mb-1">License Key</label>
                <input type="text" name="license_key"
                       value="<?= e($info['key_raw']) ?>"
                       placeholder="XXXX-XXXX-XXXX-XXXX"
                       class="w-full px-3 py-2 rounded-lg text-sm font-mono"
                       style="background: var(--color-surface-dark, rgba(0,0,0,0.3)); border: 1px solid var(--color-border); color: var(--color-text);">
                <p class="text-xs opacity-50 mt-1">ต้องตรงกับ domain: <strong><?= e($domain) ?></strong></p>
            </div>
            <button type="submit" class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">
                <i class="fas fa-save mr-1"></i> บันทึก
            </button>
        </form>
    </div>

    <!-- Re-validate -->
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="recheck">
        <button type="submit" class="text-sm opacity-60 hover:opacity-100 transition-opacity">
            <i class="fas fa-rotate-right mr-1"></i> ตรวจสอบ License ใหม่ทันที
        </button>
    </form>

    <!-- Setup Guide -->
    <div class="mt-8 rounded-xl p-5 text-sm" style="background: var(--color-surface); opacity: 0.8;">
        <h3 class="font-semibold mb-3 opacity-100"><i class="fas fa-book mr-2"></i>วิธีตั้งค่า (สำหรับผู้พัฒนา)</h3>
        <ol class="list-decimal list-inside space-y-2 opacity-80 text-xs leading-relaxed">
            <li>สร้าง <strong>Private Gist</strong> บน GitHub ชื่อไฟล์ <code>licenses.json</code></li>
            <li>เพิ่ม key ในรูปแบบ:<br>
                <code class="bg-white/10 px-2 py-1 rounded block mt-1 leading-relaxed whitespace-pre">{"KEY-XXXX": {"domain": "<?= e($domain) ?>", "expires": "2027-01-01", "active": true}}</code>
            </li>
            <li>สร้าง Fine-grained Token (Gists → Read-only)</li>
            <li>ใส่ค่าใน <code>config/license.php</code></li>
        </ol>
    </div>
</div>

<?php require BASE_PATH . '/layout/admin_footer.php'; ?>
