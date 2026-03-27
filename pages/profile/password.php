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

<div class="max-w-md mx-auto space-y-5">

    <h1 class="text-xl font-bold flex items-center gap-2">
        <i class="fas fa-key" style="color: var(--color-accent);"></i> เปลี่ยนรหัสผ่าน
    </h1>

    <div class="card p-6">
        <form method="POST" class="space-y-5" id="pw-form">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold opacity-60 mb-1.5 uppercase tracking-wide">รหัสผ่านปัจจุบัน</label>
                <div class="relative">
                    <input type="password" name="current_password" id="pw-current" required class="form-input pr-10">
                    <button type="button" onclick="togglePw('pw-current', this)" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-40 hover:opacity-80 transition">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold opacity-60 mb-1.5 uppercase tracking-wide">รหัสผ่านใหม่</label>
                <div class="relative">
                    <input type="password" name="new_password" id="pw-new" required minlength="6" class="form-input pr-10" oninput="checkStrength(this.value)">
                    <button type="button" onclick="togglePw('pw-new', this)" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-40 hover:opacity-80 transition">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
                <!-- Strength bar -->
                <div class="mt-2 h-1 rounded-full overflow-hidden" style="background: var(--color-bg);">
                    <div id="pw-strength-bar" class="h-full rounded-full transition-all" style="width:0%;"></div>
                </div>
                <p id="pw-strength-label" class="text-xs mt-1 opacity-50"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold opacity-60 mb-1.5 uppercase tracking-wide">ยืนยันรหัสผ่านใหม่</label>
                <div class="relative">
                    <input type="password" name="confirm_password" id="pw-confirm" required class="form-input pr-10">
                    <button type="button" onclick="togglePw('pw-confirm', this)" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-40 hover:opacity-80 transition">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full py-3 rounded-xl font-bold">
                <i class="fas fa-lock mr-2"></i> เปลี่ยนรหัสผ่าน
            </button>
        </form>
    </div>

    <p class="text-xs opacity-30 text-center">รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร</p>
</div>

<script>
function togglePw(id, btn) {
    const el = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (el.type === 'password') { el.type = 'text'; icon.className = 'fas fa-eye-slash text-sm'; }
    else { el.type = 'password'; icon.className = 'fas fa-eye text-sm'; }
}
function checkStrength(v) {
    const bar = document.getElementById('pw-strength-bar');
    const lbl = document.getElementById('pw-strength-label');
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const levels = [
        [0,   '#374151', ''],
        [20,  '#f87171', 'อ่อนมาก'],
        [40,  '#fb923c', 'อ่อน'],
        [60,  '#facc15', 'ปานกลาง'],
        [80,  '#4ade80', 'แข็งแกร่ง'],
        [100, '#22c55e', 'แข็งแกร่งมาก'],
    ];
    const [pct, color, text] = levels[score] ?? levels[0];
    bar.style.width = pct + '%';
    bar.style.background = color;
    lbl.textContent = text;
    lbl.style.color = color;
}
</script>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
