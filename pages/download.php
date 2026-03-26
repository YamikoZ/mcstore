<?php
$pageTitle = 'ดาวน์โหลดเกม';
$siteName = Settings::get('site_name', 'MCStore');
$serverIp = Settings::get('server_ip', 'play.example.com');
$serverVersion = Settings::get('server_version', '1.20.x');

include BASE_PATH . '/layout/header.php';
?>

<style>
/* Hero */
.dl-hero {
    position: relative; padding: 60px 0 80px; text-align: center; overflow: hidden;
    background: linear-gradient(160deg, rgba(10,22,40,0.95), rgba(15,30,60,0.9));
}
.dl-hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse at 40% 60%, rgba(56,189,248,0.08), transparent 60%),
                radial-gradient(ellipse at 70% 30%, rgba(14,165,233,0.06), transparent 50%);
}
.dl-hero-content { position: relative; z-index: 2; }

/* Download cards */
.dl-card {
    border-radius: 20px; overflow: hidden; transition: all 0.3s;
    background: var(--gradient-card); border: 1px solid var(--color-border);
    position: relative;
}
.dl-card:hover { transform: translateY(-6px); box-shadow: 0 16px 48px rgba(0,0,0,0.3), 0 0 30px rgba(56,189,248,0.08); }
.dl-card-header {
    padding: 32px 24px; text-align: center; position: relative; overflow: hidden;
}
.dl-card-header::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(to bottom, transparent 50%, rgba(10,22,40,0.5));
}
.dl-card-body { padding: 24px; }
.dl-card-icon {
    width: 80px; height: 80px; border-radius: 20px; margin: 0 auto 16px;
    display: flex; align-items: center; justify-content: center;
    position: relative; z-index: 1;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}
.dl-btn {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    width: 100%; padding: 14px; border-radius: 14px; font-weight: 700;
    font-size: 0.95rem; transition: all 0.3s; color: #fff; border: none; cursor: pointer;
}
.dl-btn:hover { transform: translateY(-2px); }
.dl-btn-primary { background: var(--gradient-btn); box-shadow: 0 4px 15px rgba(14,165,233,0.3); }
.dl-btn-primary:hover { box-shadow: 0 6px 25px rgba(14,165,233,0.5); }
.dl-btn-green { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 15px rgba(16,185,129,0.3); }
.dl-btn-green:hover { box-shadow: 0 6px 25px rgba(16,185,129,0.5); }
.dl-btn-orange { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 15px rgba(245,158,11,0.3); }
.dl-btn-orange:hover { box-shadow: 0 6px 25px rgba(245,158,11,0.5); }
.dl-btn-outline {
    background: transparent; border: 1px solid var(--color-border);
    color: var(--color-text); box-shadow: none;
}
.dl-btn-outline:hover { background: rgba(56,189,248,0.06); }

/* Badge */
.dl-badge {
    display: inline-block; padding: 4px 12px; border-radius: 999px;
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
}

/* Requirements */
.req-table { width: 100%; font-size: 0.875rem; }
.req-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
.req-table tr:last-child td { border-bottom: none; }
.req-table td:first-child { opacity: 0.5; width: 35%; white-space: nowrap; }

/* Steps */
.setup-step {
    display: flex; gap: 16px; padding: 20px; border-radius: 14px;
    background: rgba(255,255,255,0.02); border: 1px solid var(--color-border);
    transition: all 0.3s;
}
.setup-step:hover { background: rgba(56,189,248,0.04); }
.step-num-badge {
    width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1rem; color: #fff;
}

/* FAQ */
.faq-item {
    border-radius: 12px; border: 1px solid var(--color-border); overflow: hidden;
    transition: border-color 0.3s;
}
.faq-item:hover { border-color: rgba(56,189,248,0.3); }
.faq-q {
    padding: 16px 20px; cursor: pointer; display: flex; align-items: center;
    justify-content: space-between; font-weight: 600; font-size: 0.9rem;
    transition: background 0.2s;
}
.faq-q:hover { background: rgba(56,189,248,0.04); }
.faq-q i { transition: transform 0.3s; font-size: 0.8rem; opacity: 0.4; }
.faq-item.open .faq-q i { transform: rotate(180deg); }
.faq-a {
    max-height: 0; overflow: hidden; transition: max-height 0.4s ease, padding 0.3s;
    padding: 0 20px; font-size: 0.85rem; opacity: 0.7; line-height: 1.6;
}
.faq-item.open .faq-a { max-height: 300px; padding: 0 20px 16px; }

/* Responsive */
@media (max-width: 640px) {
    .dl-hero { padding: 40px 0 60px; }
    .dl-card-icon { width: 64px; height: 64px; border-radius: 16px; }
    .dl-card-header { padding: 24px 16px; }
    .dl-card-body { padding: 16px; }
}
</style>

<!-- ═══ Hero ═══ -->
<div class="dl-hero">
    <div class="dl-hero-content max-w-3xl mx-auto px-4">
        <div class="mb-4">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5" class="mx-auto" style="filter: drop-shadow(0 0 20px rgba(56,189,248,0.4));">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold gradient-text mb-3">ดาวน์โหลด Minecraft</h1>
        <p class="opacity-60 max-w-lg mx-auto">ดาวน์โหลดตัวเกม Minecraft เพื่อเข้าเล่นเซิร์ฟเวอร์ <strong style="color: var(--color-primary);"><?= e($serverIp) ?></strong></p>
        <div class="flex flex-wrap justify-center gap-3 mt-5">
            <span class="dl-badge" style="background: rgba(56,189,248,0.15); color: var(--color-primary);">
                <i class="fas fa-gamepad mr-1"></i> เวอร์ชัน <?= e($serverVersion) ?>
            </span>
            <span class="dl-badge" style="background: rgba(52,211,153,0.15); color: #34d399;">
                <i class="fas fa-check-circle mr-1"></i> Java & Bedrock
            </span>
        </div>
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 py-10">

    <!-- ═══ Download Cards ═══ -->
    <section class="mb-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Java Edition (Official) -->
            <div class="dl-card animate-fade-in">
                <div class="dl-card-header" style="background: linear-gradient(135deg, #2d7a30, #1a5c1e);">
                    <div class="dl-card-icon" style="background: linear-gradient(135deg, #4ade80, #22c55e);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="3" width="18" height="18" rx="3" fill="#1a5c1e"/>
                            <rect x="5" y="5" width="6" height="6" fill="#4ade80"/>
                            <rect x="13" y="5" width="6" height="6" fill="#2d7a30"/>
                            <rect x="5" y="13" width="6" height="6" fill="#2d7a30"/>
                            <rect x="13" y="13" width="6" height="6" fill="#4ade80"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white relative z-[1]">Java Edition</h3>
                    <p class="text-xs text-white/60 relative z-[1]">ตัวเกมเวอร์ชัน Official</p>
                </div>
                <div class="dl-card-body">
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>เวอร์ชันเต็ม อัปเดตตลอด</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>รองรับ Mods / Shaders</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>PC (Windows / Mac / Linux)</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-info-circle text-blue-400 w-4"></i> <span class="opacity-60">ต้องซื้อเกมจาก Mojang</span></div>
                    </div>
                    <a href="https://www.minecraft.net/en-us/download" target="_blank" rel="noopener" class="dl-btn dl-btn-green">
                        <i class="fas fa-download"></i> ดาวน์โหลด Official
                    </a>
                </div>
            </div>

            <!-- TLauncher -->
            <div class="dl-card animate-fade-in" style="animation-delay: 0.1s;">
                <div class="dl-card-header" style="background: linear-gradient(135deg, #1e40af, #1d4ed8);">
                    <div class="dl-card-icon" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                        <i class="fas fa-rocket text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white relative z-[1]">TLauncher</h3>
                    <p class="text-xs text-white/60 relative z-[1]">Launcher ฟรี สำหรับ PC</p>
                </div>
                <div class="dl-card-body">
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>ฟรี ไม่ต้องซื้อเกม</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>เลือกเวอร์ชันได้เอง</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>รองรับ Mods / OptiFine</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>Windows / Mac / Linux</span></div>
                    </div>
                    <a href="https://tlauncher.org/en/" target="_blank" rel="noopener" class="dl-btn dl-btn-primary">
                        <i class="fas fa-download"></i> ดาวน์โหลด TLauncher
                    </a>
                </div>
            </div>

            <!-- Pojav Launcher (Mobile) -->
            <div class="dl-card animate-fade-in" style="animation-delay: 0.2s;">
                <div class="dl-card-header" style="background: linear-gradient(135deg, #9333ea, #7c3aed);">
                    <div class="dl-card-icon" style="background: linear-gradient(135deg, #a855f7, #c084fc);">
                        <i class="fas fa-mobile-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white relative z-[1]">Pojav Launcher</h3>
                    <p class="text-xs text-white/60 relative z-[1]">เล่น Java Edition บนมือถือ</p>
                </div>
                <div class="dl-card-body">
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>เล่น Java บน Android/iOS</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>รองรับ Mods เหมือน PC</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>เข้าเซิร์ฟ Java ได้</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-exclamation-triangle text-yellow-400 w-4"></i> <span class="opacity-60">ต้องการ RAM 3GB+</span></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="https://pojavlauncherteam.github.io/" target="_blank" rel="noopener" class="dl-btn dl-btn-primary text-sm" style="padding: 10px;">
                            <i class="fab fa-android"></i> Android
                        </a>
                        <a href="https://pojavlauncherteam.github.io/" target="_blank" rel="noopener" class="dl-btn dl-btn-outline text-sm" style="padding: 10px;">
                            <i class="fab fa-apple"></i> iOS
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bedrock Edition -->
            <div class="dl-card animate-fade-in" style="animation-delay: 0.3s;">
                <div class="dl-card-header" style="background: linear-gradient(135deg, #b45309, #d97706);">
                    <div class="dl-card-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="3" width="18" height="18" rx="3" fill="#b45309"/>
                            <rect x="6" y="6" width="5" height="5" fill="#fbbf24"/>
                            <rect x="13" y="6" width="5" height="5" fill="#d97706"/>
                            <rect x="6" y="13" width="5" height="5" fill="#d97706"/>
                            <rect x="13" y="13" width="5" height="5" fill="#fbbf24"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white relative z-[1]">Bedrock Edition</h3>
                    <p class="text-xs text-white/60 relative z-[1]">สำหรับมือถือ / Windows 10+</p>
                </div>
                <div class="dl-card-body">
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>Cross-play ทุกแพลตฟอร์ม</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>Android / iOS / Windows</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>Xbox / PlayStation / Switch</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-info-circle text-blue-400 w-4"></i> <span class="opacity-60">ต้องซื้อจาก Store</span></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="https://play.google.com/store/apps/details?id=com.mojang.minecraftpe" target="_blank" rel="noopener" class="dl-btn dl-btn-orange text-sm" style="padding: 10px;">
                            <i class="fab fa-google-play"></i> Play Store
                        </a>
                        <a href="https://apps.apple.com/app/minecraft/id479516143" target="_blank" rel="noopener" class="dl-btn dl-btn-outline text-sm" style="padding: 10px;">
                            <i class="fab fa-app-store"></i> App Store
                        </a>
                    </div>
                </div>
            </div>

            <!-- Salwyrr Launcher -->
            <div class="dl-card animate-fade-in" style="animation-delay: 0.4s;">
                <div class="dl-card-header" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                    <div class="dl-card-icon" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                        <i class="fas fa-fire text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white relative z-[1]">Salwyrr Launcher</h3>
                    <p class="text-xs text-white/60 relative z-[1]">Launcher ฟรี FPS สูง</p>
                </div>
                <div class="dl-card-body">
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>ฟรี + FPS Boost ในตัว</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>เลือกเวอร์ชันได้</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>Built-in OptiFine</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>Windows เท่านั้น</span></div>
                    </div>
                    <a href="https://www.salwyrr.com/" target="_blank" rel="noopener" class="dl-btn dl-btn-primary" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                        <i class="fas fa-download"></i> ดาวน์โหลด Salwyrr
                    </a>
                </div>
            </div>

            <!-- SKLauncher -->
            <div class="dl-card animate-fade-in" style="animation-delay: 0.5s;">
                <div class="dl-card-header" style="background: linear-gradient(135deg, #0f766e, #14b8a6);">
                    <div class="dl-card-icon" style="background: linear-gradient(135deg, #2dd4bf, #5eead4);">
                        <i class="fas fa-play-circle text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white relative z-[1]">SKLauncher</h3>
                    <p class="text-xs text-white/60 relative z-[1]">Launcher เบาๆ ใช้ง่าย</p>
                </div>
                <div class="dl-card-body">
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>ฟรี เบา ไม่กินแรม</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>UI สวย ใช้ง่าย</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>รองรับ Mods / Forge</span></div>
                        <div class="flex items-center gap-2"><i class="fas fa-check text-green-400 w-4"></i> <span>Windows / Mac / Linux</span></div>
                    </div>
                    <a href="https://skmedix.pl/sklauncher" target="_blank" rel="noopener" class="dl-btn dl-btn-primary" style="background: linear-gradient(135deg, #14b8a6, #0f766e);">
                        <i class="fas fa-download"></i> ดาวน์โหลด SKLauncher
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ How to Connect ═══ -->
    <section class="mb-12">
        <h2 class="text-2xl font-bold mb-6 text-center">
            <i class="fas fa-plug mr-2" style="color: var(--color-primary);"></i> วิธีเข้าเซิร์ฟเวอร์
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Java -->
            <div class="card p-6" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
                <h3 class="font-bold text-lg mb-4"><i class="fas fa-desktop mr-2" style="color: #4ade80;"></i> Java Edition</h3>
                <div class="space-y-3">
                    <?php
                    $javaSteps = [
                        ['เปิด Minecraft แล้วกด Multiplayer', 'var(--color-primary)'],
                        ['กด Add Server หรือ Direct Connect', 'var(--color-secondary)'],
                        ['กรอก Server Address: <strong style="color:var(--color-accent);">' . e($serverIp) . '</strong>', 'var(--color-accent)'],
                        ['กด Join Server แล้วสนุกได้เลย!', '#34d399'],
                    ];
                    foreach ($javaSteps as $i => $step):
                    ?>
                    <div class="setup-step">
                        <div class="step-num-badge" style="background: <?= $step[1] ?>;"><?= $i + 1 ?></div>
                        <div class="flex-1">
                            <p class="text-sm"><?= $step[0] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button onclick="App.copyText('<?= e($serverIp) ?>')" class="dl-btn dl-btn-primary mt-4" style="padding: 10px;">
                    <i class="fas fa-copy"></i> คัดลอก IP: <?= e($serverIp) ?>
                </button>
            </div>

            <!-- Bedrock -->
            <div class="card p-6" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
                <h3 class="font-bold text-lg mb-4"><i class="fas fa-mobile-alt mr-2" style="color: #fbbf24;"></i> Bedrock Edition</h3>
                <div class="space-y-3">
                    <?php
                    $bedrockSteps = [
                        ['เปิด Minecraft แล้วกด Play &rarr; Servers', '#f59e0b'],
                        ['เลื่อนลงล่างกด Add Server', '#d97706'],
                        ['กรอก Address: <strong style="color:var(--color-accent);">' . e($serverIp) . '</strong> Port: <strong style="color:var(--color-accent);">' . e(Settings::get('server_port', '19132')) . '</strong>', 'var(--color-accent)'],
                        ['กด Play แล้วสนุกได้เลย!', '#34d399'],
                    ];
                    foreach ($bedrockSteps as $i => $step):
                    ?>
                    <div class="setup-step">
                        <div class="step-num-badge" style="background: <?= $step[1] ?>;"><?= $i + 1 ?></div>
                        <div class="flex-1">
                            <p class="text-sm"><?= $step[0] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button onclick="App.copyText('<?= e($serverIp) ?>')" class="dl-btn dl-btn-orange mt-4" style="padding: 10px;">
                    <i class="fas fa-copy"></i> คัดลอก IP: <?= e($serverIp) ?>
                </button>
            </div>
        </div>
    </section>

    <!-- ═══ System Requirements ═══ -->
    <section class="mb-12">
        <h2 class="text-2xl font-bold mb-6 text-center">
            <i class="fas fa-microchip mr-2" style="color: var(--color-secondary);"></i> สเปคขั้นต่ำ
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Minimum -->
            <div class="card p-6" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: rgba(245,158,11,0.15);">
                        <i class="fas fa-cog" style="color: #f59e0b;"></i>
                    </div>
                    <div>
                        <h3 class="font-bold">ขั้นต่ำ</h3>
                        <p class="text-xs opacity-40">เล่นได้แต่อาจกระตุก</p>
                    </div>
                </div>
                <table class="req-table">
                    <tr><td><i class="fas fa-microchip mr-2"></i>CPU</td><td>Intel Core i3 / AMD A8</td></tr>
                    <tr><td><i class="fas fa-memory mr-2"></i>RAM</td><td>4 GB</td></tr>
                    <tr><td><i class="fas fa-hdd mr-2"></i>GPU</td><td>Intel HD 4000 / AMD Radeon R5</td></tr>
                    <tr><td><i class="fas fa-database mr-2"></i>พื้นที่</td><td>2 GB ขึ้นไป</td></tr>
                    <tr><td><i class="fab fa-java mr-2"></i>Java</td><td>Java 17+</td></tr>
                </table>
            </div>

            <!-- Recommended -->
            <div class="card p-6" style="background: var(--gradient-card); border: 1px solid rgba(52,211,153,0.2);">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: rgba(52,211,153,0.15);">
                        <i class="fas fa-bolt" style="color: #34d399;"></i>
                    </div>
                    <div>
                        <h3 class="font-bold">แนะนำ</h3>
                        <p class="text-xs opacity-40">เล่นลื่นไม่มีสะดุด</p>
                    </div>
                </div>
                <table class="req-table">
                    <tr><td><i class="fas fa-microchip mr-2"></i>CPU</td><td>Intel Core i5 / AMD Ryzen 5</td></tr>
                    <tr><td><i class="fas fa-memory mr-2"></i>RAM</td><td>8 GB ขึ้นไป</td></tr>
                    <tr><td><i class="fas fa-hdd mr-2"></i>GPU</td><td>GTX 1050 / RX 570 ขึ้นไป</td></tr>
                    <tr><td><i class="fas fa-database mr-2"></i>พื้นที่</td><td>4 GB ขึ้นไป (SSD)</td></tr>
                    <tr><td><i class="fab fa-java mr-2"></i>Java</td><td>Java 21 (GraalVM)</td></tr>
                </table>
            </div>
        </div>
    </section>

    <!-- ═══ FAQ ═══ -->
    <section class="mb-12">
        <h2 class="text-2xl font-bold mb-6 text-center">
            <i class="fas fa-question-circle mr-2" style="color: var(--color-accent);"></i> คำถามที่พบบ่อย
        </h2>
        <div class="max-w-3xl mx-auto space-y-3">
            <?php
            $faqs = [
                ['เซิร์ฟเวอร์นี้ Java หรือ Bedrock?', 'เซิร์ฟเวอร์ของเรารองรับทั้ง Java และ Bedrock Edition สามารถเข้าเล่นได้ทั้งจาก PC และมือถือ ใช้ IP เดียวกัน'],
                ['ใช้ TLauncher เข้าเล่นได้ไหม?', 'ได้ครับ! เซิร์ฟเวอร์ของเรารองรับทั้ง Premium และ Non-Premium (Cracked) สามารถใช้ TLauncher, SKLauncher หรือ Launcher อื่นๆ ได้'],
                ['เวอร์ชันอะไรถึงเข้าเล่นได้?', 'รองรับเวอร์ชัน ' . e($serverVersion) . ' ขึ้นไป แนะนำให้ใช้เวอร์ชันล่าสุดเพื่อประสบการณ์ที่ดีที่สุด'],
                ['เล่นบนมือถือต้องทำยังไง?', 'สำหรับ Android/iOS สามารถใช้ Pojav Launcher (เล่น Java Edition) หรือ Bedrock Edition จาก Play Store / App Store ได้เลย'],
                ['ดาวน์โหลดแล้วเข้าเซิร์ฟไม่ได้?', 'ตรวจสอบว่า: 1) ใช้เวอร์ชันที่ถูกต้อง 2) กรอก IP ถูกต้อง 3) เน็ตเชื่อมต่อได้ปกติ หากยังไม่ได้ ติดต่อเราผ่าน Discord หรือหน้าติดต่อ'],
                ['Mods อะไรที่แนะนำ?', 'แนะนำ OptiFine หรือ Sodium สำหรับเพิ่ม FPS และ ViaVersion สำหรับความเข้ากันได้ของเวอร์ชัน ไม่แนะนำให้ใช้ Hack/Cheat Mods เด็ดขาด'],
            ];
            foreach ($faqs as $faq):
            ?>
            <div class="faq-item" onclick="this.classList.toggle('open')">
                <div class="faq-q">
                    <span><?= $faq[0] ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-a"><?= $faq[1] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ═══ CTA ═══ -->
    <section class="mb-8">
        <div class="card p-8 rounded-2xl text-center" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
            <i class="fas fa-gamepad text-4xl mb-4" style="color: var(--color-primary);"></i>
            <h2 class="text-2xl font-bold mb-2">พร้อมเล่นแล้วใช่ไหม?</h2>
            <p class="opacity-50 mb-6 max-w-md mx-auto text-sm">ดาวน์โหลดเกม เข้าเซิร์ฟเวอร์ แล้วมาสนุกด้วยกัน!</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="<?= url('shop') ?>" class="dl-btn dl-btn-primary" style="width: auto; padding: 12px 28px;">
                    <i class="fas fa-store"></i> เข้าร้านค้า
                </a>
                <a href="<?= url('topup') ?>" class="dl-btn dl-btn-outline" style="width: auto; padding: 12px 28px;">
                    <i class="fas fa-coins"></i> เติมเงิน
                </a>
                <?php if ($discord = Settings::get('discord_invite')): ?>
                <a href="<?= e($discord) ?>" target="_blank" rel="noopener" class="dl-btn dl-btn-outline" style="width: auto; padding: 12px 28px;">
                    <i class="fab fa-discord"></i> Discord
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
