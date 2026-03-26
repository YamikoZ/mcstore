<?php
$pageTitle = 'กาชา';
$db = Database::getInstance();

$serverSlug = $_GET['server'] ?? '';
$crateId = $_GET['type'] ?? '';

$servers = $db->fetchAll("SELECT * FROM servers WHERE is_active = 1 ORDER BY display_order");

if (empty($serverSlug) && !empty($servers)) {
    redirect('gacha/' . $servers[0]['id']);
}

$currentServer = $db->fetch("SELECT * FROM servers WHERE id = ? AND is_active = 1", [$serverSlug]);
if (!$currentServer) {
    include BASE_PATH . '/pages/404.php';
    return;
}

$crates = $db->fetchAll(
    "SELECT * FROM gacha_crates WHERE server_id = ? AND is_active = 1 ORDER BY display_order",
    [$currentServer['id']]
);

$currentCrate = null;
$rewards = [];
if ($crateId) {
    $currentCrate = $db->fetch("SELECT * FROM gacha_crates WHERE id = ? AND server_id = ? AND is_active = 1", [$crateId, $currentServer['id']]);
    if ($currentCrate) {
        $rewards = $db->fetchAll("SELECT * FROM gacha_rewards WHERE crate_id = ? AND is_active = 1 ORDER BY weight ASC", [$currentCrate['id']]);
    }
}

// Handle gacha spin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['spin']) && Auth::check()) {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('gacha/' . $serverSlug); }
    // system integrity check
    if (Settings::get('license_cache_result', '0') !== '1') { redirect('gacha/' . $serverSlug); }

    $spinCrateId = (int)$_POST['crate_id'];
    $crate = $db->fetch("SELECT * FROM gacha_crates WHERE id = ? AND is_active = 1", [$spinCrateId]);
    if (!$crate) { flash('error', 'ไม่พบกล่องกาชานี้'); redirect('gacha/' . $serverSlug); }

    $user = Auth::user();
    if ($user['balance'] < $crate['price']) {
        flash('error', 'เงินไม่พอ กรุณาเติมเงินก่อน');
        redirect('gacha/' . $serverSlug . '/' . $spinCrateId);
    }

    $crateRewards = $db->fetchAll("SELECT * FROM gacha_rewards WHERE crate_id = ? AND is_active = 1", [$spinCrateId]);
    if (empty($crateRewards)) { flash('error', 'กล่องนี้ยังไม่มีรางวัล'); redirect('gacha/' . $serverSlug); }

    $totalWeight = array_sum(array_column($crateRewards, 'weight'));
    $rand = mt_rand(1, $totalWeight);
    $won = null;
    $cumulative = 0;
    foreach ($crateRewards as $rw) {
        $cumulative += $rw['weight'];
        if ($rand <= $cumulative) { $won = $rw; break; }
    }
    if (!$won) $won = end($crateRewards);

    $db->beginTransaction();
    try {
        $db->execute("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?", [$crate['price'], $user['id'], $crate['price']]);
        $newBalance = $user['balance'] - $crate['price'];
        $db->execute("INSERT INTO wallet_ledger (username, type, amount, balance_after, reference, note) VALUES (?, 'debit', ?, ?, ?, ?)",
            [$user['username'], $crate['price'], $newBalance, 'gacha#' . $spinCrateId, 'กาชา: ' . $crate['name']]);
        $db->execute("INSERT INTO gacha_history (username, crate_id, reward_id, rarity) VALUES (?, ?, ?, ?)",
            [$user['username'], $spinCrateId, $won['id'], $won['rarity']]);
        if ($won['command']) {
            $finalCmd = str_replace('{player}', $user['username'], $won['command']);
            $db->execute("INSERT INTO delivery_queue (username, server_id, player_name, command, item_name, status) VALUES (?, ?, ?, ?, ?, 'pending')",
                [$user['username'], $crate['server_id'], $user['username'], $finalCmd, $won['name']]);
        }
        $db->commit();

        $_SESSION['gacha_result'] = [
            'reward_name' => $won['name'],
            'reward_id'   => $won['id'],
            'rarity'      => $won['rarity'],
            'image_url'   => $won['image'] ?? '',
            'crate_name'  => $crate['name'],
        ];

        redirect('gacha/' . $serverSlug . '/' . $spinCrateId . '?result=1');
    } catch (Exception $e) {
        $db->rollback();
        flash('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
        redirect('gacha/' . $serverSlug . '/' . $spinCrateId);
    }
}

// Check for gacha result
$gachaResult = null;
if (isset($_GET['result']) && isset($_SESSION['gacha_result'])) {
    $gachaResult = $_SESSION['gacha_result'];
    unset($_SESSION['gacha_result']);
}

// Recent winners for this server
$recentWinners = $db->fetchAll(
    "SELECT gh.*, gr.name AS reward_name, gr.image AS reward_image, gr.rarity, gc.name AS crate_name
     FROM gacha_history gh
     JOIN gacha_rewards gr ON gh.reward_id = gr.id
     JOIN gacha_crates gc ON gh.crate_id = gc.id
     WHERE gc.server_id = ?
     ORDER BY gh.created_at DESC LIMIT 10",
    [$currentServer['id']]
);

$rarityColors = [
    'common'    => '#9CA3AF',
    'uncommon'  => '#34D399',
    'rare'      => '#60A5FA',
    'epic'      => '#A78BFA',
    'mythic'    => '#EC4899',
    'legendary' => '#FBBF24',
];
$rarityNames = [
    'common'    => 'ธรรมดา',
    'uncommon'  => 'ผิดธรรมดา',
    'rare'      => 'หายาก',
    'epic'      => 'มหากาฬ',
    'mythic'    => 'ตำนาน',
    'legendary' => 'ระดับเทพ',
];
$rarityGlow = [
    'common'    => 'none',
    'uncommon'  => '0 0 15px rgba(52,211,153,0.3)',
    'rare'      => '0 0 20px rgba(96,165,250,0.4)',
    'epic'      => '0 0 25px rgba(167,139,250,0.5)',
    'mythic'    => '0 0 30px rgba(236,72,153,0.5)',
    'legendary' => '0 0 40px rgba(251,191,36,0.6)',
];

include BASE_PATH . '/layout/header.php';
?>

<style>
/* ═══ Gacha Roulette ═══ */
.gacha-roulette-overlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,0.85);
    display: flex; align-items: center; justify-content: center; flex-direction: column;
    opacity: 0; pointer-events: none;
    transition: opacity 0.3s;
}
.gacha-roulette-overlay.active { opacity: 1; pointer-events: all; }

.roulette-container {
    position: relative; width: 90vw; max-width: 700px; height: 160px;
    overflow: hidden; border-radius: 16px;
    border: 2px solid var(--color-border);
    background: rgba(10,22,40,0.95);
}
.roulette-strip {
    display: flex; position: absolute; top: 0; left: 0; height: 100%;
    transition: none;
}
.roulette-strip.animating {
    transition: transform 4s cubic-bezier(0.15, 0.85, 0.25, 1);
}
.roulette-item {
    width: 140px; min-width: 140px; height: 160px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 12px; text-align: center; position: relative;
    border-right: 1px solid rgba(255,255,255,0.05);
}
.roulette-item .roulette-img {
    width: 64px; height: 64px; object-fit: contain; border-radius: 8px; margin-bottom: 8px;
    image-rendering: pixelated;
}
.roulette-item .roulette-icon {
    width: 64px; height: 64px; border-radius: 8px; margin-bottom: 8px;
    display: flex; align-items: center; justify-content: center; font-size: 28px;
}
.roulette-item .roulette-name {
    font-size: 0.75rem; font-weight: 600; white-space: nowrap; overflow: hidden;
    text-overflow: ellipsis; max-width: 120px;
}
.roulette-item .roulette-rarity {
    font-size: 0.6rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-top: 2px;
}
/* Center pointer */
.roulette-pointer {
    position: absolute; top: 0; bottom: 0; left: 50%; transform: translateX(-50%);
    width: 4px; background: var(--color-primary); z-index: 10;
    box-shadow: 0 0 20px var(--color-primary), 0 0 40px var(--color-primary);
}
.roulette-pointer::before, .roulette-pointer::after {
    content: ''; position: absolute; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 10px solid transparent; border-right: 10px solid transparent;
}
.roulette-pointer::before { top: -2px; border-top: 14px solid var(--color-primary); }
.roulette-pointer::after  { bottom: -2px; border-bottom: 14px solid var(--color-primary); }

/* ═══ Result reveal ═══ */
.result-reveal {
    text-align: center; margin-top: 30px; opacity: 0;
    transform: scale(0.5); transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.result-reveal.show { opacity: 1; transform: scale(1); }

.result-card {
    display: inline-block; padding: 32px 48px; border-radius: 20px;
    background: var(--gradient-card); border: 2px solid; position: relative; overflow: hidden;
}
.result-card::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(circle at center, rgba(255,255,255,0.08), transparent 70%);
}
.result-card .result-img { width: 96px; height: 96px; object-fit: contain; margin: 0 auto 12px; image-rendering: pixelated; }
.result-card .result-icon { width: 96px; height: 96px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; font-size: 48px; border-radius: 16px; }
.result-card .result-name { font-size: 1.5rem; font-weight: 700; }
.result-card .result-rarity { font-size: 0.85rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em; margin-top: 4px; }

.result-close-btn {
    margin-top: 24px; padding: 12px 40px; border-radius: 12px; font-weight: 700;
    background: var(--gradient-btn); color: #fff; border: none; cursor: pointer;
    font-size: 1rem; transition: transform 0.2s;
}
.result-close-btn:hover { transform: scale(1.05); }

/* ═══ Particles ═══ */
.particle-container { position: fixed; inset: 0; pointer-events: none; z-index: 10000; }
.particle {
    position: absolute; width: 8px; height: 8px; border-radius: 50%;
    animation: particleFall linear forwards;
}
@keyframes particleFall {
    0%   { opacity: 1; transform: translateY(0) rotate(0deg) scale(1); }
    100% { opacity: 0; transform: translateY(100vh) rotate(720deg) scale(0); }
}

/* ═══ Showcase ═══ */
.showcase-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 12px;
}
.showcase-item {
    border-radius: 12px; padding: 16px 10px; text-align: center;
    background: var(--gradient-card); border: 1px solid var(--color-border);
    transition: all 0.3s; position: relative; overflow: hidden;
}
.showcase-item:hover {
    transform: translateY(-4px);
}
.showcase-item .item-img {
    width: 56px; height: 56px; object-fit: contain; margin: 0 auto 8px;
    image-rendering: pixelated;
}
.showcase-item .item-icon {
    width: 56px; height: 56px; margin: 0 auto 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; border-radius: 10px;
}
.showcase-item .item-name { font-size: 0.8rem; font-weight: 600; line-height: 1.2; }
.showcase-item .item-rarity { font-size: 0.6rem; text-transform: uppercase; font-weight: 700; margin-top: 4px; letter-spacing: 0.05em; }
.showcase-item .item-pct { font-size: 0.65rem; opacity: 0.5; margin-top: 2px; }
.showcase-item .rarity-stripe {
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
}

/* ═══ Crate cards ═══ */
.crate-card {
    border-radius: 16px; overflow: hidden; transition: all 0.3s;
    background: var(--gradient-card); border: 1px solid var(--color-border);
}
.crate-card:hover { transform: translateY(-6px); box-shadow: 0 12px 40px rgba(0,0,0,0.4), 0 0 30px rgba(56,189,248,0.1); }
.crate-banner {
    height: 160px; display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
}
.crate-banner::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(10,22,40,0.8), transparent 60%);
}
.crate-banner .crate-icon { position: relative; z-index: 1; font-size: 4rem; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.5)); }

/* ═══ Spin button ═══ */
.spin-btn {
    position: relative; overflow: hidden;
    background: linear-gradient(135deg, #f59e0b, #f97316, #ef4444);
    color: #fff; border: none; cursor: pointer;
    padding: 16px 48px; border-radius: 16px; font-size: 1.25rem; font-weight: 800;
    box-shadow: 0 4px 20px rgba(245,158,11,0.4);
    transition: all 0.3s;
    animation: btnPulse 2s ease-in-out infinite;
}
.spin-btn:hover { transform: scale(1.05); box-shadow: 0 6px 30px rgba(245,158,11,0.6); }
.spin-btn:active { transform: scale(0.97); }
.spin-btn::before {
    content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    animation: btnShine 3s ease-in-out infinite;
}
@keyframes btnPulse { 0%,100% { box-shadow: 0 4px 20px rgba(245,158,11,0.4); } 50% { box-shadow: 0 4px 30px rgba(245,158,11,0.7); } }
@keyframes btnShine { 0% { transform: translateX(-100%) rotate(45deg); } 100% { transform: translateX(100%) rotate(45deg); } }

/* ═══ Winners ticker ═══ */
.winners-ticker {
    overflow: hidden; position: relative; height: 48px;
}
.winners-track {
    display: flex; gap: 16px;
    animation: tickerScroll 20s linear infinite;
    position: absolute; white-space: nowrap;
}
.winners-track:hover { animation-play-state: paused; }
@keyframes tickerScroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
.winner-chip {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 14px; border-radius: 999px;
    background: rgba(255,255,255,0.05); border: 1px solid var(--color-border);
    font-size: 0.75rem; white-space: nowrap;
}
.winner-chip img { width: 24px; height: 24px; border-radius: 50%; }

/* ═══ Mobile ═══ */
@media (max-width: 640px) {
    .roulette-container { height: 120px; }
    .roulette-item { width: 100px; min-width: 100px; height: 120px; }
    .roulette-item .roulette-img, .roulette-item .roulette-icon { width: 44px; height: 44px; }
    .roulette-item .roulette-name { font-size: 0.65rem; }
    .showcase-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px; }
    .showcase-item { padding: 12px 8px; }
    .result-card { padding: 24px 32px; }
    .spin-btn { padding: 14px 36px; font-size: 1.1rem; }
}
</style>

<!-- Roulette Overlay -->
<div class="gacha-roulette-overlay" id="gacha-overlay">
    <div style="text-align: center; margin-bottom: 20px;">
        <p class="text-lg font-bold" style="color: var(--color-accent);">กำลังหมุน...</p>
    </div>
    <div class="roulette-container">
        <div class="roulette-pointer"></div>
        <div class="roulette-strip" id="roulette-strip"></div>
    </div>
    <div class="result-reveal" id="result-reveal">
        <div class="result-card" id="result-card">
            <div id="result-visual"></div>
            <div class="result-name" id="result-name"></div>
            <div class="result-rarity" id="result-rarity"></div>
        </div>
        <br>
        <button class="result-close-btn" onclick="closeGachaOverlay()">
            <i class="fas fa-check mr-2"></i> รับรางวัล
        </button>
    </div>
</div>
<div class="particle-container" id="particles"></div>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar -->
        <aside class="lg:w-64 shrink-0">
            <div class="card p-4 mb-4" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
                <h3 class="font-bold text-sm mb-3 opacity-70"><i class="fas fa-server mr-2"></i> เซิร์ฟเวอร์</h3>
                <div class="space-y-1">
                    <?php foreach ($servers as $srv): ?>
                        <a href="<?= url('gacha/' . e($srv['id'])) ?>"
                           class="block px-3 py-2 rounded-lg text-sm transition <?= $srv['id'] == $currentServer['id'] ? 'font-bold' : 'opacity-70 hover:opacity-100' ?>"
                           style="<?= $srv['id'] == $currentServer['id'] ? 'background: var(--color-primary); color: #fff;' : '' ?>">
                            <i class="fas fa-cube mr-2"></i> <?= e($srv['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (Auth::check()): ?>
            <div class="card p-4 mb-4" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
                <p class="text-sm opacity-60">ยอดเงินคงเหลือ</p>
                <p class="text-2xl font-bold" style="color: var(--color-accent);" data-balance><?= formatMoney(Auth::user()['balance']) ?></p>
                <a href="<?= url('topup') ?>" class="block mt-2 text-center btn-primary py-2 rounded-lg text-sm font-semibold">
                    <i class="fas fa-plus mr-1"></i> เติมเงิน
                </a>
            </div>
            <?php endif; ?>

            <!-- Recent winners -->
            <?php if (!empty($recentWinners)): ?>
            <div class="card p-4" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
                <h3 class="font-bold text-sm mb-3 opacity-70"><i class="fas fa-trophy mr-2" style="color: #FBBF24;"></i> ผู้โชคดีล่าสุด</h3>
                <div class="space-y-2">
                    <?php foreach (array_slice($recentWinners, 0, 5) as $w): ?>
                    <div class="flex items-center gap-2 text-xs">
                        <img src="https://mc-heads.net/avatar/<?= e($w['username']) ?>/20" class="w-5 h-5 rounded" alt="">
                        <span class="truncate flex-1"><?= e($w['username']) ?></span>
                        <span class="font-bold truncate" style="color: <?= $rarityColors[$w['rarity']] ?? '#fff' ?>; max-width: 80px;">
                            <?= e($w['reward_name']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Main Content -->
        <div class="flex-1">
            <h1 class="text-2xl font-bold mb-2">
                <i class="fas fa-dice mr-2" style="color: var(--color-accent);"></i>
                กาชา — <?= e($currentServer['name']) ?>
            </h1>

            <!-- Winners ticker -->
            <?php if (!empty($recentWinners)): ?>
            <div class="winners-ticker mb-6 rounded-xl" style="background: rgba(255,255,255,0.03); border: 1px solid var(--color-border);">
                <div class="winners-track" id="winners-track">
                    <?php for ($i = 0; $i < 2; $i++): // duplicate for seamless loop ?>
                        <?php foreach ($recentWinners as $w): ?>
                        <div class="winner-chip">
                            <img src="https://mc-heads.net/avatar/<?= e($w['username']) ?>/24" alt="">
                            <span><?= e($w['username']) ?></span>
                            <span class="font-bold" style="color: <?= $rarityColors[$w['rarity']] ?? '#fff' ?>;"><?= e($w['reward_name']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($currentCrate): ?>
                <!-- ═══ Single Crate View ═══ -->
                <div class="card p-6 mb-6" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
                    <!-- Crate header -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl" style="background: linear-gradient(135deg, var(--color-primary), var(--color-accent));">
                                <?php if (!empty($currentCrate['image'])): ?>
                                    <img src="<?= e($currentCrate['image']) ?>" class="w-14 h-14 object-contain rounded-xl" style="image-rendering: pixelated;">
                                <?php else: ?>
                                    <i class="fas fa-box-open"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold"><?= e($currentCrate['name']) ?></h2>
                                <?php if ($currentCrate['description']): ?>
                                    <p class="text-sm opacity-60 mt-0.5"><?= e($currentCrate['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold" style="color: var(--color-accent);"><?= formatMoney($currentCrate['price']) ?></span>
                            <span class="text-sm opacity-50">/ครั้ง</span>
                        </div>
                    </div>

                    <!-- Spin button -->
                    <div class="text-center mb-8">
                        <?php if (Auth::check()): ?>
                            <form method="POST" id="spin-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="crate_id" value="<?= (int)$currentCrate['id'] ?>">
                                <button type="submit" name="spin" class="spin-btn" id="spin-btn">
                                    <i class="fas fa-dice mr-2"></i> หมุนกาชา!
                                </button>
                            </form>
                            <p class="text-xs opacity-40 mt-3">กดปุ่มเพื่อสุ่มรางวัล จะหักเงิน <?= formatMoney($currentCrate['price']) ?> ต่อครั้ง</p>
                        <?php else: ?>
                            <a href="<?= url('login') ?>" class="spin-btn inline-block" style="text-decoration: none;">
                                <i class="fas fa-sign-in-alt mr-2"></i> เข้าสู่ระบบเพื่อหมุน
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- ═══ Showcase ═══ -->
                    <div class="mb-2">
                        <h3 class="font-bold text-lg mb-4">
                            <i class="fas fa-gem mr-2" style="color: var(--color-accent);"></i> ตู้โชว์รางวัล
                            <span class="text-xs opacity-40 font-normal ml-2">(<?= count($rewards) ?> รายการ)</span>
                        </h3>
                        <div class="showcase-grid">
                            <?php
                            $totalWeight = array_sum(array_column($rewards, 'weight'));
                            foreach ($rewards as $rw):
                                $pct = $totalWeight > 0 ? round(($rw['weight'] / $totalWeight) * 100, 2) : 0;
                                $color = $rarityColors[$rw['rarity']] ?? '#fff';
                                $glow = $rarityGlow[$rw['rarity']] ?? 'none';
                                $rName = $rarityNames[$rw['rarity']] ?? $rw['rarity'];
                            ?>
                            <div class="showcase-item" style="box-shadow: <?= $glow ?>;"
                                 onmouseenter="this.style.boxShadow='<?= str_replace(')', ',0 12px 30px rgba(0,0,0,0.3))', $glow) ?>'"
                                 onmouseleave="this.style.boxShadow='<?= $glow ?>'">
                                <div class="rarity-stripe" style="background: <?= $color ?>;"></div>
                                <?php if (!empty($rw['image'])): ?>
                                    <img src="<?= e($rw['image']) ?>" class="item-img" alt="<?= e($rw['name']) ?>">
                                <?php else: ?>
                                    <div class="item-icon" style="background: <?= $color ?>22; color: <?= $color ?>;">
                                        <i class="fas fa-cube"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="item-name"><?= e($rw['name']) ?></div>
                                <div class="item-rarity" style="color: <?= $color ?>;"><?= $rName ?></div>
                                <div class="item-pct"><?= $pct ?>%</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <a href="<?= url('gacha/' . e($currentServer['id'])) ?>" class="text-sm hover:underline" style="color: var(--color-primary);">
                    <i class="fas fa-arrow-left mr-1"></i> กลับไปเลือกกล่อง
                </a>

            <?php else: ?>
                <!-- ═══ Crate List ═══ -->
                <?php if (empty($crates)): ?>
                    <div class="card p-12 text-center" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
                        <i class="fas fa-box-open text-5xl mb-4 opacity-30"></i>
                        <p class="text-lg opacity-60">ยังไม่มีกล่องกาชาสำหรับเซิร์ฟเวอร์นี้</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        <?php foreach ($crates as $crate):
                            $crateRewardCount = $db->count("SELECT COUNT(*) FROM gacha_rewards WHERE crate_id = ? AND is_active = 1", [$crate['id']]);
                        ?>
                            <a href="<?= url('gacha/' . e($currentServer['id']) . '/' . $crate['id']) ?>" class="crate-card group">
                                <div class="crate-banner" style="background: linear-gradient(135deg, <?= $crate['id'] % 2 === 0 ? 'var(--color-primary), var(--color-secondary)' : 'var(--color-accent), var(--color-primary)' ?>);">
                                    <?php if (!empty($crate['image'])): ?>
                                        <img src="<?= e($crate['image']) ?>" class="w-24 h-24 object-contain relative z-[1] group-hover:scale-110 transition-transform duration-300" style="image-rendering: pixelated; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.5));">
                                    <?php else: ?>
                                        <span class="crate-icon group-hover:scale-110 transition-transform duration-300">
                                            <i class="fas fa-box-open"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="p-5">
                                    <h3 class="text-lg font-bold mb-1"><?= e($crate['name']) ?></h3>
                                    <?php if ($crate['description']): ?>
                                        <p class="text-sm opacity-60 mb-3 line-clamp-2"><?= e($crate['description']) ?></p>
                                    <?php endif; ?>
                                    <div class="flex items-center justify-between">
                                        <span class="text-lg font-bold" style="color: var(--color-accent);"><?= formatMoney($crate['price']) ?></span>
                                        <span class="text-xs opacity-40"><i class="fas fa-gift mr-1"></i> <?= $crateRewardCount ?> รางวัล</span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// ═══════════════════════════════════════
// Sound Engine (Web Audio API)
// ═══════════════════════════════════════
const SFX = (() => {
    let ctx;
    function getCtx() {
        if (!ctx) ctx = new (window.AudioContext || window.webkitAudioContext)();
        return ctx;
    }

    function playTone(freq, duration, type = 'sine', vol = 0.15) {
        try {
            const c = getCtx();
            const osc = c.createOscillator();
            const gain = c.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, c.currentTime);
            gain.gain.setValueAtTime(vol, c.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, c.currentTime + duration);
            osc.connect(gain);
            gain.connect(c.destination);
            osc.start(c.currentTime);
            osc.stop(c.currentTime + duration);
        } catch(e) {}
    }

    return {
        tick() { playTone(800, 0.04, 'square', 0.06); },
        tickHigh() { playTone(1200, 0.05, 'square', 0.08); },
        whoosh() {
            for (let i = 0; i < 5; i++) {
                setTimeout(() => playTone(200 + i * 100, 0.1, 'sawtooth', 0.03), i * 30);
            }
        },
        winCommon() {
            [523, 659, 784].forEach((f, i) => setTimeout(() => playTone(f, 0.2, 'sine', 0.12), i * 100));
        },
        winRare() {
            [523, 659, 784, 1047].forEach((f, i) => setTimeout(() => playTone(f, 0.25, 'sine', 0.15), i * 120));
        },
        winEpic() {
            [392, 523, 659, 784, 1047].forEach((f, i) => setTimeout(() => playTone(f, 0.3, 'triangle', 0.18), i * 130));
        },
        winLegendary() {
            // Dramatic fanfare
            const notes = [262, 330, 392, 523, 659, 784, 1047, 1319];
            notes.forEach((f, i) => setTimeout(() => playTone(f, 0.4, 'sine', 0.2), i * 100));
            setTimeout(() => {
                [523, 659, 784, 1047].forEach((f, i) => setTimeout(() => playTone(f, 0.6, 'triangle', 0.15), i * 80));
            }, notes.length * 100);
        },
        drumroll(duration, cb) {
            const interval = 60;
            let count = 0;
            const total = Math.floor(duration / interval);
            const id = setInterval(() => {
                const progress = count / total;
                const freq = 100 + progress * 400;
                playTone(freq, 0.05, 'square', 0.04 + progress * 0.06);
                count++;
                if (count >= total) {
                    clearInterval(id);
                    if (cb) cb();
                }
            }, interval);
        }
    };
})();

// ═══════════════════════════════════════
// Roulette Animation
// ═══════════════════════════════════════
const rarityColors = <?= json_encode($rarityColors) ?>;
const rarityNames = <?= json_encode($rarityNames) ?>;

<?php if ($gachaResult && !empty($rewards)): ?>
(function() {
    const rewards = <?= json_encode(array_values($rewards)) ?>;
    const result = <?= json_encode($gachaResult) ?>;
    const overlay = document.getElementById('gacha-overlay');
    const strip = document.getElementById('roulette-strip');
    const reveal = document.getElementById('result-reveal');

    // Build roulette strip: lots of random items, then place winner near center
    const ITEM_W = window.innerWidth <= 640 ? 100 : 140;
    const containerW = Math.min(window.innerWidth * 0.9, 700);
    const totalItems = 60;
    const winIndex = totalItems - 8; // winner position

    let html = '';
    for (let i = 0; i < totalItems; i++) {
        let item;
        if (i === winIndex) {
            // The winning item
            item = rewards.find(r => r.id == result.reward_id) || rewards[0];
        } else {
            // Random from pool (weighted)
            item = rewards[Math.floor(Math.random() * rewards.length)];
        }
        const color = rarityColors[item.rarity] || '#fff';
        const hasImg = item.image && item.image.length > 0;
        html += `<div class="roulette-item" style="border-bottom: 3px solid ${color};">`;
        if (hasImg) {
            html += `<img src="${item.image}" class="roulette-img" alt="">`;
        } else {
            html += `<div class="roulette-icon" style="background: ${color}22; color: ${color};"><i class="fas fa-cube"></i></div>`;
        }
        html += `<div class="roulette-name" style="color: ${color};">${item.name}</div>`;
        html += `<div class="roulette-rarity" style="color: ${color};">${rarityNames[item.rarity] || item.rarity}</div>`;
        html += '</div>';
    }
    strip.innerHTML = html;

    // Show overlay
    setTimeout(() => {
        overlay.classList.add('active');
        SFX.whoosh();

        // Calculate scroll distance: center the winning item
        const targetX = (winIndex * ITEM_W) - (containerW / 2) + (ITEM_W / 2);

        // Start tick sounds during animation
        let tickCount = 0;
        const tickInterval = setInterval(() => {
            SFX.tick();
            tickCount++;
            if (tickCount > 40) clearInterval(tickInterval);
        }, 80);

        // Animate
        setTimeout(() => {
            strip.classList.add('animating');
            strip.style.transform = `translateX(-${targetX}px)`;
        }, 300);

        // After animation ends
        setTimeout(() => {
            clearInterval(tickInterval);
            SFX.tickHigh();

            // Show result
            setTimeout(() => {
                // Play win sound based on rarity
                const rarity = result.rarity;
                if (rarity === 'legendary' || rarity === 'mythic') {
                    SFX.winLegendary();
                    spawnParticles(rarity === 'legendary' ? '#FBBF24' : '#EC4899', 80);
                } else if (rarity === 'epic') {
                    SFX.winEpic();
                    spawnParticles('#A78BFA', 40);
                } else if (rarity === 'rare') {
                    SFX.winRare();
                    spawnParticles('#60A5FA', 20);
                } else {
                    SFX.winCommon();
                }

                const color = rarityColors[rarity] || '#fff';
                const card = document.getElementById('result-card');
                card.style.borderColor = color;
                card.style.boxShadow = `0 0 60px ${color}44, 0 20px 60px rgba(0,0,0,0.5)`;

                const visual = document.getElementById('result-visual');
                if (result.image_url) {
                    visual.innerHTML = `<img src="${result.image_url}" class="result-img" style="image-rendering: pixelated;">`;
                } else {
                    visual.innerHTML = `<div class="result-icon" style="background: ${color}22; color: ${color};"><i class="fas fa-gem"></i></div>`;
                }
                document.getElementById('result-name').textContent = result.reward_name;
                document.getElementById('result-name').style.color = color;
                document.getElementById('result-rarity').textContent = rarityNames[rarity] || rarity;
                document.getElementById('result-rarity').style.color = color;

                reveal.classList.add('show');
            }, 500);
        }, 4500);

    }, 200);
})();
<?php endif; ?>

function closeGachaOverlay() {
    const overlay = document.getElementById('gacha-overlay');
    overlay.classList.remove('active');
    // Remove result param from URL
    const url = new URL(window.location);
    url.searchParams.delete('result');
    history.replaceState(null, '', url);
}

// ═══════════════════════════════════════
// Particles
// ═══════════════════════════════════════
function spawnParticles(color, count = 40) {
    const container = document.getElementById('particles');
    for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = 4 + Math.random() * 8;
        const x = Math.random() * window.innerWidth;
        const dur = 1.5 + Math.random() * 2;
        const delay = Math.random() * 0.8;
        const colors = [color, '#fff', color + 'aa'];
        p.style.cssText = `
            left: ${x}px; top: -10px;
            width: ${size}px; height: ${size}px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            animation-duration: ${dur}s;
            animation-delay: ${delay}s;
        `;
        container.appendChild(p);
        setTimeout(() => p.remove(), (dur + delay) * 1000 + 100);
    }
}

// ═══════════════════════════════════════
// Volume toggle
// ═══════════════════════════════════════
</script>

<?php include BASE_PATH . '/layout/footer.php'; ?>
