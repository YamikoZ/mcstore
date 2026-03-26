<?php
$pageTitle = 'License';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_key') {
        $key = trim($_POST['license_key'] ?? '');
        Settings::set('license_key', $key);
        License::bust();
        flash('success', 'บันทึก License Key แล้ว กำลังตรวจสอบ...');
        redirect('admin/license');
    }

    if ($action === 'recheck') {
        License::bust();
        flash('success', 'ล้าง cache แล้ว กรุณารีเฟรชหน้าอีกครั้ง');
        redirect('admin/license');
    }
}

$info   = License::info();
$valid  = $info['valid'];
$domain = $info['domain'];
$status = $info['status']; // active / invalid / not_set

require BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-3xl mx-auto space-y-4">

    <!-- ═══ Header ═══ -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">License</h1>
            <p class="text-xs opacity-50 mt-0.5">จัดการสิทธิ์การใช้งาน MCStore</p>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="recheck">
            <button type="submit"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium opacity-60 hover:opacity-100 transition-all"
                    style="background: var(--color-surface); border: 1px solid var(--color-border);">
                <i class="fas fa-rotate-right"></i> ตรวจสอบใหม่
            </button>
        </form>
    </div>

    <!-- ═══ Status Banner ═══ -->
    <?php if ($status === 'active'): ?>
    <div class="rounded-xl p-5 flex items-center gap-4"
         style="background: linear-gradient(135deg, rgba(34,197,94,0.15), rgba(16,185,129,0.08)); border: 1px solid rgba(34,197,94,0.3);">
        <div class="w-14 h-14 rounded-full bg-green-500/20 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-shield-halved text-green-400 text-2xl"></i>
        </div>
        <div>
            <p class="text-green-400 font-bold text-lg leading-tight">License ใช้งานได้</p>
            <p class="text-sm opacity-60 mt-0.5">ระบบได้รับการยืนยันแล้ว สามารถใช้งานได้เต็มรูปแบบ</p>
        </div>
        <div class="ml-auto text-right hidden sm:block">
            <p class="text-xs opacity-40">ตรวจสอบล่าสุด</p>
            <p class="text-xs font-medium opacity-70"><?= $info['cached_at'] ?? '-' ?></p>
        </div>
    </div>

    <?php elseif ($status === 'invalid'): ?>
    <div class="rounded-xl p-5 flex items-center gap-4"
         style="background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(220,38,38,0.08)); border: 1px solid rgba(239,68,68,0.3);">
        <div class="w-14 h-14 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-shield-xmark text-red-400 text-2xl"></i>
        </div>
        <div>
            <p class="text-red-400 font-bold text-lg leading-tight">License ไม่ถูกต้อง</p>
            <p class="text-sm opacity-60 mt-0.5">Key ผิด, หมดอายุ, หรือ domain ไม่ตรง — กรุณาตรวจสอบ</p>
        </div>
    </div>

    <?php else: ?>
    <div class="rounded-xl p-5 flex items-center gap-4"
         style="background: linear-gradient(135deg, rgba(234,179,8,0.15), rgba(202,138,4,0.08)); border: 1px solid rgba(234,179,8,0.3);">
        <div class="w-14 h-14 rounded-full bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-shield-exclamation text-yellow-400 text-2xl"></i>
        </div>
        <div>
            <p class="text-yellow-400 font-bold text-lg leading-tight">ยังไม่ได้ใส่ License Key</p>
            <p class="text-sm opacity-60 mt-0.5">กรุณาใส่ Key เพื่อเปิดใช้งานระบบ</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Info Cards ═══ -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div class="card p-4">
            <p class="text-xs opacity-40 mb-1"><i class="fas fa-globe mr-1"></i>Domain</p>
            <p class="font-mono text-sm font-semibold truncate"><?= e($domain) ?></p>
            <p class="text-xs opacity-40 mt-1">domain ที่ผูกกับ key</p>
        </div>
        <div class="card p-4">
            <p class="text-xs opacity-40 mb-1"><i class="fas fa-key mr-1"></i>License Key</p>
            <p class="font-mono text-sm font-semibold truncate">
                <?= $info['key'] ?: '<span class="opacity-30 font-sans font-normal text-xs">ไม่มี</span>' ?>
            </p>
            <p class="text-xs opacity-40 mt-1">key ที่ใช้งานอยู่</p>
        </div>
        <div class="card p-4 col-span-2 sm:col-span-1">
            <p class="text-xs opacity-40 mb-1"><i class="fas fa-circle-dot mr-1"></i>สถานะ</p>
            <?php
                $badge = match($status) {
                    'active'  => ['text' => 'Active',   'class' => 'text-green-400 bg-green-500/15'],
                    'invalid' => ['text' => 'Invalid',  'class' => 'text-red-400 bg-red-500/15'],
                    default   => ['text' => 'Not Set',  'class' => 'text-yellow-400 bg-yellow-500/15'],
                };
            ?>
            <span class="inline-block text-xs font-bold px-2 py-0.5 rounded-full <?= $badge['class'] ?>">
                <?= $badge['text'] ?>
            </span>
            <p class="text-xs opacity-40 mt-1">สถานะปัจจุบัน</p>
        </div>
    </div>

    <!-- ═══ Change Key ═══ -->
    <div class="card p-5">
        <button onclick="document.getElementById('key-form').classList.toggle('hidden')"
                class="w-full flex items-center justify-between group">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center">
                    <i class="fas fa-pen text-xs opacity-60"></i>
                </div>
                <div class="text-left">
                    <p class="text-sm font-semibold">เปลี่ยน License Key</p>
                    <p class="text-xs opacity-40">ใช้เมื่อได้รับ key ใหม่หรือ key หมดอายุ</p>
                </div>
            </div>
            <i class="fas fa-chevron-down text-xs opacity-40 group-hover:opacity-80 transition-all"></i>
        </button>

        <div id="key-form" class="hidden mt-4 pt-4" style="border-top: 1px solid var(--color-border);">
            <form method="POST" class="flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_key">
                <input type="text" name="license_key"
                       value="<?= e($info['key_raw']) ?>"
                       placeholder="XXXX-XXXX-XXXX-XXXX"
                       class="flex-1 px-3 py-2 rounded-lg text-sm font-mono"
                       style="background: rgba(0,0,0,0.3); border: 1px solid var(--color-border); color: var(--color-text);">
                <button type="submit" class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap">
                    <i class="fas fa-save mr-1"></i> บันทึก
                </button>
            </form>
            <p class="text-xs opacity-40 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                Key จะถูกผูกกับ domain <strong><?= e($domain) ?></strong> โดยอัตโนมัติเมื่อใช้ครั้งแรก
            </p>
        </div>
    </div>

    <!-- ═══ Get License ═══ -->
    <div class="card p-5" style="border: 1px solid rgba(99,102,241,0.25);">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background: rgba(99,102,241,0.15);">
                <i class="fas fa-cart-shopping text-indigo-400"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold mb-0.5">ต้องการ License Key?</p>
                <p class="text-xs opacity-50 mb-3 leading-relaxed">
                    ติดต่อผู้พัฒนาพร้อมแจ้ง domain ของคุณ<br>
                    Key จะถูกสร้างและผูกกับ domain นั้นโดยอัตโนมัติ
                </p>
                <div class="flex flex-wrap gap-2">
                    <a href="https://github.com/YamikoZ/mcstore/issues/new?title=<?= urlencode('ขอ License Key — ' . $domain) ?>&body=<?= urlencode("**Domain:** `{$domain}`\n\n**ชื่อเว็บ / ร้านค้า:**\n\n**ช่องทางติดต่อ:**") ?>&labels=license" target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                       style="background: var(--color-surface-dark, rgba(0,0,0,0.3)); border: 1px solid var(--color-border);">
                        <i class="fab fa-github"></i> เปิด Issue ขอ Key
                    </a>
                    <button id="copy-btn"
                            onclick="navigator.clipboard.writeText('<?= e($domain) ?>').then(()=>{
                                const b=document.getElementById('copy-btn');
                                b.innerHTML='<i class=\'fas fa-check mr-1.5\'></i>คัดลอกแล้ว!';
                                b.style.color='#4ade80';
                                setTimeout(()=>{b.innerHTML='<i class=\'fas fa-copy mr-1.5\'></i>คัดลอก Domain';b.style.color=''},2000)
                            })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                            style="background: var(--color-surface-dark, rgba(0,0,0,0.3)); border: 1px solid var(--color-border);">
                        <i class="fas fa-copy mr-1.5"></i>คัดลอก Domain
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ FAQ ═══ -->
    <div class="card p-5">
        <p class="text-sm font-semibold mb-3 opacity-70"><i class="fas fa-circle-question mr-2"></i>คำถามที่พบบ่อย</p>
        <div class="space-y-3 text-xs opacity-60 leading-relaxed">
            <div class="flex gap-2">
                <i class="fas fa-chevron-right mt-0.5 flex-shrink-0 text-indigo-400"></i>
                <p><strong class="opacity-100">Key ผูกกับ domain ไหน?</strong><br>
                   Key จะถูกล็อก domain ครั้งแรกที่ activate — ใช้ซ้ำบน domain อื่นไม่ได้</p>
            </div>
            <div class="flex gap-2">
                <i class="fas fa-chevron-right mt-0.5 flex-shrink-0 text-indigo-400"></i>
                <p><strong class="opacity-100">License ตรวจสอบทุกเมื่อไหร่?</strong><br>
                   Cache 24 ชั่วโมง — ถ้า server ล่มจะมี grace period 7 วัน</p>
            </div>
            <div class="flex gap-2">
                <i class="fas fa-chevron-right mt-0.5 flex-shrink-0 text-indigo-400"></i>
                <p><strong class="opacity-100">ต้องทำอะไรเมื่อย้าย domain?</strong><br>
                   ติดต่อผู้พัฒนาเพื่อขอ key ใหม่สำหรับ domain ใหม่</p>
            </div>
        </div>
    </div>

</div>

<?php require BASE_PATH . '/layout/admin_footer.php'; ?>
