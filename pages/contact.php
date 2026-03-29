<?php
$pageTitle = 'ติดต่อเรา';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('contact'); }
    
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        flash('error', 'กรุณากรอกข้อมูลให้ครบ');
        $_SESSION['old_input'] = compact('name', 'email', 'subject', 'message');
        redirect('contact');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'อีเมลไม่ถูกต้อง');
        redirect('contact');
    }
    
    if (!rateLimitCheck('contact', 3, 300)) {
        flash('error', 'ส่งข้อความมากเกินไป กรุณารอ 5 นาที');
        redirect('contact');
    }
    
    $db->execute(
        "INSERT INTO contact_messages (username, name, email, subject, message) VALUES (?, ?, ?, ?, ?)",
        [Auth::check() ? Auth::user()['username'] : null, $name, $email, $subject, $message]
    );

    sendWebhook('system', '📩 ข้อความจากลูกค้า',
        "**{$subject}**\n{$message}",
        0xE67E22,
        [
            ['name' => 'ชื่อ',  'value' => $name,  'inline' => true],
            ['name' => 'อีเมล', 'value' => $email, 'inline' => true],
        ]
    );
    flash('success', 'ส่งข้อความสำเร็จ! เราจะตอบกลับโดยเร็ว');
    redirect('contact');
}

include BASE_PATH . '/layout/header.php';
?>

<?php
$serverIp = Settings::get('server_ip', 'play.mcsakura.com');
$discord = Settings::get('social_discord', '');
$facebook = Settings::get('social_facebook', '');
$youtube = Settings::get('social_youtube', '');
$siteName = Settings::get('site_name', 'MC Sakura');
?>

<div class="max-w-5xl mx-auto px-4 py-8">
    <!-- Hero -->
    <div class="text-center mb-10">
        <h1 class="text-3xl md:text-4xl font-bold mb-3 gradient-text">ติดต่อเรา</h1>
        <p class="opacity-70 max-w-lg mx-auto">มีคำถาม ปัญหา หรือข้อเสนอแนะ? ติดต่อทีมงาน <?= e($siteName) ?> ได้ตลอด เรายินดีช่วยเหลือ!</p>
    </div>

    <!-- Quick Contact Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <?php if ($discord): ?>
        <a href="<?= e($discord) ?>" target="_blank" class="card card-hover p-5 text-center group">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition" style="background: rgba(88,101,242,0.15);">
                <i class="fab fa-discord text-2xl" style="color: #5865F2;"></i>
            </div>
            <p class="font-bold text-sm mb-1">Discord</p>
            <p class="text-xs opacity-50">ช่องทางหลัก ตอบไวที่สุด</p>
        </a>
        <?php endif; ?>

        <?php if ($facebook): ?>
        <a href="<?= e($facebook) ?>" target="_blank" class="card card-hover p-5 text-center group">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition" style="background: rgba(24,119,242,0.15);">
                <i class="fab fa-facebook text-2xl" style="color: #1877F2;"></i>
            </div>
            <p class="font-bold text-sm mb-1">Facebook</p>
            <p class="text-xs opacity-50">แฟนเพจ & ข่าวสาร</p>
        </a>
        <?php endif; ?>

        <?php if ($youtube): ?>
        <a href="<?= e($youtube) ?>" target="_blank" class="card card-hover p-5 text-center group">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition" style="background: rgba(255,0,0,0.12);">
                <i class="fab fa-youtube text-2xl" style="color: #FF0000;"></i>
            </div>
            <p class="font-bold text-sm mb-1">YouTube</p>
            <p class="text-xs opacity-50">วิดีโอ & สอนเล่น</p>
        </a>
        <?php endif; ?>

        <div class="card card-hover p-5 text-center group cursor-pointer" onclick="App.copyText('<?= e($serverIp) ?>')">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition" style="background: rgba(14,165,233,0.15);">
                <i class="fas fa-server text-2xl" style="color: var(--color-primary);"></i>
            </div>
            <p class="font-bold text-sm mb-1"><?= e($serverIp) ?></p>
            <p class="text-xs opacity-50">คลิกเพื่อคัดลอก IP</p>
        </div>

        <a href="<?= url('topup') ?>" class="card card-hover p-5 text-center group">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition" style="background: rgba(255,107,0,0.15);">
                <i class="fas fa-wallet text-2xl" style="color: #FF6B00;"></i>
            </div>
            <p class="font-bold text-sm mb-1">เติมเงิน</p>
            <p class="text-xs opacity-50">ซองอั่งเปา TrueWallet</p>
        </a>

        <a href="<?= url('shop') ?>" class="card card-hover p-5 text-center group">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition" style="background: rgba(16,185,129,0.15);">
                <i class="fas fa-store text-2xl" style="color: #10b981;"></i>
            </div>
            <p class="font-bold text-sm mb-1">ร้านค้า</p>
            <p class="text-xs opacity-50">เลือกซื้อไอเทมในเกม</p>
        </a>

        <a href="<?= url('gacha') ?>" class="card card-hover p-5 text-center group">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition" style="background: rgba(245,158,11,0.15);">
                <i class="fas fa-dice text-2xl" style="color: #f59e0b;"></i>
            </div>
            <p class="font-bold text-sm mb-1">กาชา</p>
            <p class="text-xs opacity-50">ลุ้นรับไอเทมหายาก</p>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- Contact Form -->
        <div class="lg:col-span-3 card p-6">
            <h3 class="font-bold text-lg mb-1"><i class="fas fa-paper-plane mr-2" style="color: var(--color-primary);"></i>ส่งข้อความถึงเรา</h3>
            <p class="text-sm opacity-50 mb-5">กรอกข้อมูลด้านล่าง เราจะตอบกลับโดยเร็วที่สุด</p>
            <form method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">ชื่อ <span class="text-red-400">*</span></label>
                        <input type="text" name="name" value="<?= old('name', Auth::check() ? Auth::user()['username'] : '') ?>" required
                               class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:outline-none"
                               placeholder="ชื่อผู้ใช้หรือชื่อจริง">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">อีเมล <span class="text-red-400">*</span></label>
                        <input type="email" name="email" value="<?= old('email') ?>" required
                               class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:outline-none"
                               placeholder="email@example.com">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">หัวข้อ <span class="text-red-400">*</span></label>
                    <select name="subject" required class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:outline-none">
                        <option value="" disabled <?= old('subject') ? '' : 'selected' ?>>-- เลือกหัวข้อ --</option>
                        <option value="สอบถามทั่วไป" <?= old('subject') === 'สอบถามทั่วไป' ? 'selected' : '' ?>>สอบถามทั่วไป</option>
                        <option value="ปัญหาการเติมเงิน" <?= old('subject') === 'ปัญหาการเติมเงิน' ? 'selected' : '' ?>>ปัญหาการเติมเงิน</option>
                        <option value="ไม่ได้รับไอเทม" <?= old('subject') === 'ไม่ได้รับไอเทม' ? 'selected' : '' ?>>ไม่ได้รับไอเทม</option>
                        <option value="แจ้งบัค/ปัญหา" <?= old('subject') === 'แจ้งบัค/ปัญหา' ? 'selected' : '' ?>>แจ้งบัค/ปัญหา</option>
                        <option value="ขอคืนเงิน" <?= old('subject') === 'ขอคืนเงิน' ? 'selected' : '' ?>>ขอคืนเงิน</option>
                        <option value="เสนอแนะ/ร้องเรียน" <?= old('subject') === 'เสนอแนะ/ร้องเรียน' ? 'selected' : '' ?>>เสนอแนะ/ร้องเรียน</option>
                        <option value="อื่นๆ" <?= old('subject') === 'อื่นๆ' ? 'selected' : '' ?>>อื่นๆ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ข้อความ <span class="text-red-400">*</span></label>
                    <textarea name="message" rows="5" required
                              class="w-full px-4 py-3 rounded-lg focus:ring-2 focus:outline-none resize-none"
                              placeholder="อธิบายรายละเอียดให้ชัดเจน เพื่อให้เราช่วยเหลือได้รวดเร็ว..."><?= old('message') ?></textarea>
                </div>
                <button type="submit" class="btn-primary w-full py-3 rounded-lg font-bold text-lg">
                    <i class="fas fa-paper-plane mr-2"></i> ส่งข้อความ
                </button>
            </form>
        </div>

        <!-- Sidebar Info -->
        <div class="lg:col-span-2 space-y-4">
            <!-- FAQ -->
            <div class="card p-5">
                <h3 class="font-bold mb-3"><i class="fas fa-question-circle mr-2" style="color: var(--color-accent);"></i>คำถามที่พบบ่อย</h3>
                <div class="space-y-3">
                    <details class="group">
                        <summary class="cursor-pointer text-sm font-medium flex items-center justify-between py-2 border-b border-white/5">
                            เติมเงินแล้วไม่เข้าทำไง?
                            <i class="fas fa-chevron-down text-xs opacity-40 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <p class="text-sm opacity-70 pt-2 pb-1">ตรวจสอบว่าลิงก์ซองอั่งเปาถูกต้อง หากยังไม่ได้รับเงิน กรุณาแจ้งทีมงานผ่านฟอร์มด้านซ้ายหรือ Discord พร้อมแนบหลักฐาน</p>
                    </details>
                    <details class="group">
                        <summary class="cursor-pointer text-sm font-medium flex items-center justify-between py-2 border-b border-white/5">
                            ซื้อไอเทมแล้วไม่ได้รับ?
                            <i class="fas fa-chevron-down text-xs opacity-40 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <p class="text-sm opacity-70 pt-2 pb-1">ต้องออนไลน์ในเซิร์ฟเวอร์ที่ซื้อถึงจะได้รับของ ลองเข้าเกมใหม่อีกครั้ง หากยังไม่ได้กรุณาติดต่อทีมงาน</p>
                    </details>
                    <details class="group">
                        <summary class="cursor-pointer text-sm font-medium flex items-center justify-between py-2 border-b border-white/5">
                            ขอคืนเงินได้ไหม?
                            <i class="fas fa-chevron-down text-xs opacity-40 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <p class="text-sm opacity-70 pt-2 pb-1">สามารถขอคืนได้ภายใน 24 ชั่วโมงหลังซื้อ หากยังไม่ได้ใช้ไอเทม กรุณาแจ้งพร้อมเลขออเดอร์</p>
                    </details>
                    <details class="group">
                        <summary class="cursor-pointer text-sm font-medium flex items-center justify-between py-2">
                            เปลี่ยนชื่อในเกมได้ไหม?
                            <i class="fas fa-chevron-down text-xs opacity-40 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <p class="text-sm opacity-70 pt-2 pb-1">ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้หลังสมัครแล้ว เพราะข้อมูลผูกกับบัญชีเกม หากมีปัญหากรุณาติดต่อแอดมิน</p>
                    </details>
                </div>
            </div>

            <!-- Response Time -->
            <div class="card p-5">
                <h3 class="font-bold mb-3"><i class="fas fa-clock mr-2" style="color: var(--color-primary);"></i>เวลาตอบกลับ</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fab fa-discord" style="color: #5865F2;"></i>
                            <span>Discord</span>
                        </div>
                        <span class="text-green-400 font-semibold">~ 5–30 นาที</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fab fa-facebook" style="color: #1877F2;"></i>
                            <span>Facebook</span>
                        </div>
                        <span class="text-yellow-400 font-semibold">~ 1–3 ชั่วโมง</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-envelope" style="color: var(--color-primary);"></i>
                            <span>ฟอร์มติดต่อ</span>
                        </div>
                        <span class="text-yellow-400 font-semibold">~ 1–24 ชั่วโมง</span>
                    </div>
                </div>
                <p class="text-xs opacity-40 mt-3">* เวลาโดยประมาณ อาจนานกว่านี้ในช่วงวันหยุดหรือเวลาดึก</p>
            </div>

            <!-- Tips -->
            <div class="card p-5">
                <h3 class="font-bold mb-3"><i class="fas fa-lightbulb mr-2" style="color: var(--color-accent);"></i>เคล็ดลับแจ้งปัญหา</h3>
                <ul class="text-sm opacity-70 space-y-2">
                    <li class="flex items-start gap-2"><i class="fas fa-check text-green-400 mt-0.5 text-xs"></i> บอกชื่อผู้ใช้ในเกมให้ชัดเจน</li>
                    <li class="flex items-start gap-2"><i class="fas fa-check text-green-400 mt-0.5 text-xs"></i> แนบเลขออเดอร์ / หลักฐานการเติมเงิน</li>
                    <li class="flex items-start gap-2"><i class="fas fa-check text-green-400 mt-0.5 text-xs"></i> บอกเซิร์ฟเวอร์ที่เกิดปัญหา</li>
                    <li class="flex items-start gap-2"><i class="fas fa-check text-green-400 mt-0.5 text-xs"></i> อธิบายขั้นตอนที่เจอบัคให้ละเอียด</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
