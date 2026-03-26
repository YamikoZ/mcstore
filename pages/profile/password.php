<?php
$pageTitle = 'เปลี่ยนรหัสผ่าน';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('profile/password'); }
    
    $current = $_POST['current_password'] ?? '';
    $newPw   = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    // Verify current password
    $authUser = Auth::findByUsername($user['username']);
    if (!$authUser || !Auth::verifyPassword($current, $authUser['password'])) {
        flash('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
        redirect('profile/password');
    }
    
    if (strlen($newPw) < 6) {
        flash('error', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
        redirect('profile/password');
    }
    
    if ($newPw !== $confirm) {
        flash('error', 'รหัสผ่านใหม่ไม่ตรงกัน');
        redirect('profile/password');
    }
    
    Auth::changePassword($user['id'], $newPw);
    auditLog($user['id'], 'password_change', 'Password changed');
    flash('success', 'เปลี่ยนรหัสผ่านสำเร็จ');
    redirect('profile/password');
}

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-key mr-2" style="color: var(--color-accent);"></i> เปลี่ยนรหัสผ่าน</h1>
    
    <div class="card p-6">
        <form method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-semibold mb-1">รหัสผ่านปัจจุบัน</label>
                <input type="password" name="current_password" required
                       class="w-full px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none"
                       style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">รหัสผ่านใหม่</label>
                <input type="password" name="new_password" required minlength="6"
                       class="w-full px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none"
                       style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ยืนยันรหัสผ่านใหม่</label>
                <input type="password" name="confirm_password" required
                       class="w-full px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none"
                       style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);">
            </div>
            <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold">
                <i class="fas fa-save mr-2"></i> เปลี่ยนรหัสผ่าน
            </button>
        </form>
    </div>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
