<?php
$pageTitle = 'สมัครสมาชิก';
if (Auth::check()) redirect('');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('register'); }
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    $email    = trim($_POST['email'] ?? '');
    
    $errors = [];
    if (empty($username) || strlen($username) < 3 || strlen($username) > 16) {
        $errors[] = 'ชื่อผู้ใช้ต้องมี 3-16 ตัวอักษร';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'ชื่อผู้ใช้ใช้ได้เฉพาะ a-z, 0-9, _';
    }
    if (strlen($password) < 6) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    }
    if ($password !== $confirm) {
        $errors[] = 'รหัสผ่านไม่ตรงกัน';
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'อีเมลไม่ถูกต้อง';
    }
    
    // Check existing user (ทั้ง authme และ users table)
    if (empty($errors)) {
        $db = Database::getInstance();
        if (Auth::findByUsername($username) ||
            $db->fetch("SELECT id FROM users WHERE LOWER(username) = LOWER(?)", [$username])) {
            $errors[] = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว';
        }
    }
    
    if (!rateLimitCheck('register', 10, 600)) {
        $errors[] = 'สมัครมากเกินไป กรุณารอ 10 นาที';
    }
    
    if (!empty($errors)) {
        flash('error', implode('<br>', $errors));
        $_SESSION['old_input'] = ['username' => $username, 'email' => $email];
        redirect('register');
    }
    
    $userId = Auth::register($username, $password, $email);
    Auth::login($username, $password);
    auditLog($userId, 'register', 'New user registered');
    flash('success', 'สมัครสมาชิกสำเร็จ! ยินดีต้อนรับ!');
    redirect('');
}

include BASE_PATH . '/layout/header.php';
?>

<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="card w-full max-w-md p-8 animate-fade-in">
        <div class="text-center mb-8">
            <i class="fas fa-user-plus text-4xl mb-3" style="color: var(--color-primary);"></i>
            <h1 class="text-2xl font-bold">สมัครสมาชิก</h1>
            <p class="text-sm opacity-60 mt-1">สร้างบัญชีเพื่อซื้อสินค้าในเซิร์ฟเวอร์</p>
        </div>

        <form method="POST" action="<?= url('register') ?>" class="space-y-5">
            <?= csrf_field() ?>
            
            <div>
                <label class="block text-sm font-semibold mb-1"><i class="fas fa-user mr-1"></i> ชื่อผู้ใช้ (ชื่อในเกม)</label>
                <input type="text" name="username" value="<?= old('username') ?>" 
                       class="w-full px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none"
                       style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);"
                       placeholder="เช่น Steve_TH" required autofocus maxlength="16" pattern="[a-zA-Z0-9_]+">
                <p class="text-xs opacity-50 mt-1">3-16 ตัวอักษร (a-z, 0-9, _)</p>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-1"><i class="fas fa-envelope mr-1"></i> อีเมล (ไม่บังคับ)</label>
                <input type="email" name="email" value="<?= old('email') ?>"
                       class="w-full px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none"
                       style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);"
                       placeholder="email@example.com">
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-1"><i class="fas fa-lock mr-1"></i> รหัสผ่าน</label>
                <input type="password" name="password" 
                       class="w-full px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none"
                       style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);"
                       placeholder="อย่างน้อย 6 ตัวอักษร" required minlength="6">
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-1"><i class="fas fa-lock mr-1"></i> ยืนยันรหัสผ่าน</label>
                <input type="password" name="password_confirm" 
                       class="w-full px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none"
                       style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);"
                       placeholder="กรอกรหัสผ่านอีกครั้ง" required>
            </div>

            <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold text-lg transition hover:opacity-90">
                <i class="fas fa-user-plus mr-2"></i> สมัครสมาชิก
            </button>
        </form>

        <div class="text-center mt-6 text-sm">
            <span class="opacity-60">มีบัญชีแล้ว?</span>
            <a href="<?= url('login') ?>" class="ml-1 font-semibold hover:underline" style="color: var(--color-primary);">เข้าสู่ระบบ</a>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
