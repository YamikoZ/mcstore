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

<div class="max-w-2xl mx-auto space-y-5">

    <h1 class="text-xl font-bold flex items-center gap-2">
        <i class="fas fa-user-edit" style="color: var(--color-primary);"></i> แก้ไขโปรไฟล์
    </h1>

    <!-- Avatar Card -->
    <div class="card p-5 flex items-center gap-5">
        <img src="https://mc-heads.net/avatar/<?= e($user['username']) ?>/80"
             class="w-16 h-16 rounded-2xl" style="border: 2px solid var(--color-primary); box-shadow: 0 0 16px var(--color-primary)44;" alt="avatar">
        <div>
            <p class="font-bold text-lg"><?= e($user['username']) ?></p>
            <p class="text-xs opacity-40">Minecraft skin จาก mc-heads.net</p>
        </div>
    </div>

    <!-- Form -->
    <div class="card p-6">
        <form method="POST" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold opacity-60 mb-1.5 uppercase tracking-wide">ชื่อผู้ใช้</label>
                <input type="text" value="<?= e($user['username']) ?>" disabled class="form-input">
                <p class="text-xs opacity-30 mt-1.5"><i class="fas fa-lock mr-1"></i> ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้</p>
            </div>

            <div>
                <label class="block text-xs font-semibold opacity-60 mb-1.5 uppercase tracking-wide">อีเมล</label>
                <input type="email" name="email" value="<?= e($user['email'] ?? '') ?>"
                       placeholder="your@email.com" class="form-input">
            </div>

            <button type="submit" class="btn-primary w-full py-3 rounded-xl font-bold">
                <i class="fas fa-save mr-2"></i> บันทึกการเปลี่ยนแปลง
            </button>
        </form>
    </div>

    <!-- Danger Zone -->
    <div class="card p-5" style="border-color: rgba(248,113,113,.2);">
        <h3 class="font-semibold text-sm text-red-400 mb-3 flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i> เขตอันตราย
        </h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium">เปลี่ยนรหัสผ่าน</p>
                <p class="text-xs opacity-40">ล็อกอินใหม่จะถูกกำหนดหลังเปลี่ยน</p>
            </div>
            <a href="<?= url('profile/password') ?>" class="text-sm px-4 py-2 rounded-xl font-semibold transition"
               style="background: rgba(248,113,113,.12); color: #f87171; border: 1px solid rgba(248,113,113,.2);"
               onmouseover="this.style.background='rgba(248,113,113,.22)'" onmouseout="this.style.background='rgba(248,113,113,.12)'">
                <i class="fas fa-key mr-1.5"></i> เปลี่ยน
            </a>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
