<?php
$pageTitle = 'แก้ไขโปรไฟล์';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('profile/edit'); }
    
    $email = trim($_POST['email'] ?? '');
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'อีเมลไม่ถูกต้อง');
        redirect('profile/edit');
    }
    
    $db->execute("UPDATE users SET email = ? WHERE id = ?", [$email, $user['id']]);
    auditLog($user['id'], 'profile_update', 'Updated email');
    flash('success', 'บันทึกโปรไฟล์แล้ว');
    redirect('profile/edit');
}

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-user-edit mr-2" style="color: var(--color-primary);"></i> แก้ไขโปรไฟล์</h1>
    
    <div class="card p-6">
        <form method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-semibold mb-1">ชื่อผู้ใช้</label>
                <input type="text" value="<?= e($user['username']) ?>" disabled
                       class="w-full px-4 py-3 rounded-lg border-0 opacity-50 cursor-not-allowed"
                       style="background: var(--color-bg); color: var(--color-text);">
                <p class="text-xs opacity-40 mt-1">ชื่อผู้ใช้ไม่สามารถเปลี่ยนได้</p>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">อีเมล</label>
                <input type="email" name="email" value="<?= e($user['email']) ?>"
                       class="w-full px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none"
                       style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);">
            </div>
            <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold">
                <i class="fas fa-save mr-2"></i> บันทึก
            </button>
        </form>
    </div>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
