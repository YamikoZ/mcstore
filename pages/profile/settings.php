<?php
$pageTitle = 'ตั้งค่า';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$userSettings = $db->fetch("SELECT setting_value FROM user_settings WHERE username = ? AND setting_key = 'notifications_enabled'", [$user['username']]);
$notifValue = $userSettings['setting_value'] ?? '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('profile/settings'); }
    
    $notifEnabled = isset($_POST['notifications_enabled']) ? '1' : '0';
    
    if ($userSettings) {
        $db->execute("UPDATE user_settings SET setting_value = ? WHERE username = ? AND setting_key = 'notifications_enabled'", [$notifEnabled, $user['username']]);
    } else {
        $db->execute("INSERT INTO user_settings (username, setting_key, setting_value) VALUES (?, 'notifications_enabled', ?)", [$user['username'], $notifEnabled]);
    }
    
    flash('success', 'บันทึกตั้งค่าแล้ว');
    redirect('profile/settings');
}

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-cog mr-2" style="color: var(--color-secondary);"></i> ตั้งค่า</h1>

    <div class="card p-6">
        <form method="POST" class="space-y-4">
            <?= csrf_field() ?>
            
            <div class="flex items-center justify-between py-3 border-b border-white/5">
                <div>
                    <p class="font-semibold">แจ้งเตือน</p>
                    <p class="text-xs opacity-50">รับการแจ้งเตือนเมื่อมีสิ่งใหม่</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="notifications_enabled" class="sr-only peer" 
                           <?= $notifValue === '1' ? 'checked' : '' ?>>
                    <div class="w-11 h-6 rounded-full peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:rounded-full after:h-5 after:w-5 after:transition-all"
                         style="background: var(--color-bg); --tw-peer-checked-bg: var(--color-primary);"
                         ></div>
                </label>
            </div>

            <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold">
                <i class="fas fa-save mr-2"></i> บันทึก
            </button>
        </form>
    </div>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
