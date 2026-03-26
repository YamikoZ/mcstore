<?php
$pageTitle = 'เข้าสู่ระบบ';
if (Auth::check()) redirect('');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('login'); }
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        flash('error', 'กรุณากรอกข้อมูลให้ครบ');
        $_SESSION['old_input'] = ['username' => $username];
        redirect('login');
    }
    
    if (!rateLimitCheck('login', 5, 300)) {
        flash('error', 'ลองมากเกินไป กรุณารอ 5 นาที');
        redirect('login');
    }
    
    $user = Auth::login($username, $password);
    if ($user) {
        auditLog($user['id'], 'login', 'Login successful');
        flash('success', 'ยินดีต้อนรับ ' . $user['username'] . '!');
        redirect('');
    } else {
        flash('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        $_SESSION['old_input'] = ['username' => $username];
        redirect('login');
    }
}

include BASE_PATH . '/layout/header.php';
?>

<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="card w-full max-w-md p-8 animate-fade-in">
        <div class="text-center mb-8">
            <i class="fas fa-cube text-4xl mb-3" style="color: var(--color-primary);"></i>
            <h1 class="text-2xl font-bold">เข้าสู่ระบบ</h1>
            <p class="text-sm opacity-60 mt-1">ใช้ชื่อผู้ใช้เดียวกับในเกม Minecraft</p>
        </div>

        <form method="POST" action="<?= url('login') ?>" class="space-y-5">
            <?= csrf_field() ?>
            
            <div>
                <label class="block text-sm font-semibold mb-1"><i class="fas fa-user mr-1"></i> ชื่อผู้ใช้</label>
                <input type="text" name="username" value="<?= old('username') ?>" 
                       class="w-full px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none"
                       style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);"
                       placeholder="ชื่อในเกม Minecraft" required autofocus>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-1"><i class="fas fa-lock mr-1"></i> รหัสผ่าน</label>
                <input type="password" name="password" 
                       class="w-full px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none"
                       style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);"
                       placeholder="รหัสผ่าน" required>
            </div>

            <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold text-lg transition hover:opacity-90">
                <i class="fas fa-sign-in-alt mr-2"></i> เข้าสู่ระบบ
            </button>
        </form>

        <div class="text-center mt-6 text-sm">
            <span class="opacity-60">ยังไม่มีบัญชี?</span>
            <a href="<?= url('register') ?>" class="ml-1 font-semibold hover:underline" style="color: var(--color-primary);">สมัครสมาชิก</a>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
