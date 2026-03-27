<?php
$pageTitle = 'ดาวน์โหลดเกม';
$serverIp = Settings::get('server_ip', 'play.example.com');
$serverVersion = Settings::get('server_version', '1.21.x');

include BASE_PATH . '/layout/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-10">

    <div class="text-center mb-10">
        <i class="fas fa-download text-4xl mb-3" style="color: var(--color-primary);"></i>
        <h1 class="text-3xl font-bold mb-2">ดาวน์โหลด Minecraft</h1>
        <p class="opacity-60">เวอร์ชันที่รองรับ: <strong style="color: var(--color-accent);"><?= e($serverVersion) ?></strong> &nbsp;|&nbsp; IP: <strong style="color: var(--color-primary);"><?= e($serverIp) ?></strong></p>
    </div>

    <!-- Launchers -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">

        <!-- Official -->
        <div class="card p-6 text-center">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background: rgba(74,222,128,0.15);">
                <i class="fas fa-cube text-2xl" style="color: #4ade80;"></i>
            </div>
            <h3 class="font-bold text-lg mb-1">Java Edition</h3>
            <p class="text-xs opacity-50 mb-4">Official — ต้องซื้อเกม</p>
            <a href="https://www.minecraft.net/en-us/download" target="_blank" rel="noopener"
               class="btn-primary block py-2.5 rounded-lg font-semibold text-sm">
                <i class="fas fa-download mr-1"></i> ดาวน์โหลด
            </a>
        </div>

        <!-- TLauncher -->
        <div class="card p-6 text-center">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background: rgba(59,130,246,0.15);">
                <i class="fas fa-rocket text-2xl" style="color: #3b82f6;"></i>
            </div>
            <h3 class="font-bold text-lg mb-1">TLauncher</h3>
            <p class="text-xs opacity-50 mb-4">ฟรี — Windows / Mac / Linux</p>
            <a href="https://tlauncher.org/en/" target="_blank" rel="noopener"
               class="btn-primary block py-2.5 rounded-lg font-semibold text-sm">
                <i class="fas fa-download mr-1"></i> ดาวน์โหลด
            </a>
        </div>

        <!-- Pojav (Mobile) -->
        <div class="card p-6 text-center">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background: rgba(168,85,247,0.15);">
                <i class="fas fa-mobile-alt text-2xl" style="color: #a855f7;"></i>
            </div>
            <h3 class="font-bold text-lg mb-1">Pojav Launcher</h3>
            <p class="text-xs opacity-50 mb-4">เล่น Java บนมือถือ Android</p>
            <a href="https://pojavlauncherteam.github.io/" target="_blank" rel="noopener"
               class="btn-primary block py-2.5 rounded-lg font-semibold text-sm">
                <i class="fas fa-download mr-1"></i> ดาวน์โหลด
            </a>
        </div>

    </div>

    <!-- How to connect -->
    <div class="card p-6 mb-6">
        <h2 class="font-bold text-lg mb-5"><i class="fas fa-plug mr-2" style="color: var(--color-primary);"></i> วิธีเข้าเซิร์ฟเวอร์</h2>
        <div class="space-y-3">
            <?php
            $steps = [
                'เปิด Minecraft แล้วกด <strong>Multiplayer</strong>',
                'กด <strong>Add Server</strong> หรือ <strong>Direct Connect</strong>',
                'กรอก Server Address: <strong style="color:var(--color-accent);">' . e($serverIp) . '</strong>',
                'กด <strong>Join Server</strong> แล้วสนุกได้เลย!',
            ];
            foreach ($steps as $i => $step):
            ?>
            <div class="flex items-center gap-4 p-3 rounded-xl" style="background: rgba(255,255,255,0.03);">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm flex-shrink-0" style="background: var(--color-primary); color: #fff;"><?= $i + 1 ?></div>
                <p class="text-sm"><?= $step ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <button onclick="navigator.clipboard.writeText('<?= e($serverIp) ?>').then(()=>Swal.fire({icon:'success',title:'คัดลอกแล้ว!',text:'<?= e($serverIp) ?>',timer:1500,showConfirmButton:false}))"
                class="btn-primary mt-5 px-6 py-2.5 rounded-lg font-semibold text-sm">
            <i class="fas fa-copy mr-2"></i> คัดลอก IP: <?= e($serverIp) ?>
        </button>
    </div>

    <!-- CTA -->
    <div class="card p-6 text-center">
        <p class="opacity-60 text-sm mb-4">เข้าเซิร์ฟได้แล้ว? มาซื้อไอเทมหรือยศที่ร้านค้าได้เลย!</p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="<?= url('shop') ?>" class="btn-primary px-6 py-2.5 rounded-lg font-semibold text-sm">
                <i class="fas fa-store mr-1"></i> ร้านค้า
            </a>
            <?php if ($discord = Settings::get('discord_invite')): ?>
            <a href="<?= e($discord) ?>" target="_blank" rel="noopener"
               class="px-6 py-2.5 rounded-lg font-semibold text-sm border border-white/10 hover:bg-white/5">
                <i class="fab fa-discord mr-1"></i> Discord
            </a>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
