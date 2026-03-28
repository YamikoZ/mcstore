<?php
$pageTitle = 'หน้าแรก';
$db = Database::getInstance();

// Data queries
$banners = $db->fetchAll("SELECT * FROM banners WHERE is_active = 1 ORDER BY display_order");
$servers = $db->fetchAll("SELECT * FROM servers WHERE is_active = 1 ORDER BY display_order");

$featuredProducts = $db->fetchAll("SELECT p.*, c.name AS category_name, s.name AS server_name, s.id AS server_slug
    FROM products p JOIN categories c ON p.category_id = c.id JOIN servers s ON p.server_id = s.id
    WHERE p.is_active = 1 AND p.is_featured = 1 ORDER BY p.display_order, p.created_at DESC LIMIT 8");

$latestProducts = $db->fetchAll("SELECT p.*, c.name AS category_name, s.name AS server_name, s.id AS server_slug
    FROM products p JOIN categories c ON p.category_id = c.id JOIN servers s ON p.server_id = s.id
    WHERE p.is_active = 1 ORDER BY p.created_at DESC LIMIT 8");

$saleProducts = $db->fetchAll("SELECT p.*, c.name AS category_name, s.name AS server_name, s.id AS server_slug
    FROM products p JOIN categories c ON p.category_id = c.id JOIN servers s ON p.server_id = s.id
    WHERE p.is_active = 1 AND p.original_price IS NOT NULL AND p.original_price > p.price
    ORDER BY ((p.original_price - p.price) / p.original_price) DESC LIMIT 4");

$gachaCrates = $db->fetchAll("SELECT gc.*, s.name AS server_name, s.id AS server_slug
    FROM gacha_crates gc JOIN servers s ON gc.server_id = s.id
    WHERE gc.is_active = 1 ORDER BY gc.created_at DESC LIMIT 4");

$totalUsers = (int)($db->fetch("SELECT COUNT(*) AS cnt FROM users")['cnt'] ?? 0);
$totalOrders = (int)($db->fetch("SELECT COUNT(*) AS cnt FROM orders WHERE status IN ('paid','delivered')")['cnt'] ?? 0);
$totalProducts = (int)($db->fetch("SELECT COUNT(*) AS cnt FROM products WHERE is_active = 1")['cnt'] ?? 0);
$totalDelivered = (int)($db->fetch("SELECT COUNT(*) AS cnt FROM delivery_queue WHERE status = 'delivered'")['cnt'] ?? 0);

$recentWinners = $db->fetchAll("SELECT gh.username, gh.rarity, gr.name AS reward_name, gc.name AS crate_name, gh.created_at
    FROM gacha_history gh JOIN gacha_rewards gr ON gh.reward_id = gr.id JOIN gacha_crates gc ON gh.crate_id = gc.id
    ORDER BY gh.created_at DESC LIMIT 10");

$topBuyers = $db->fetchAll("SELECT o.username, SUM(o.total) AS total_spent, COUNT(*) AS order_count
    FROM orders o WHERE o.status IN ('paid','delivered') AND o.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
    GROUP BY o.username ORDER BY total_spent DESC LIMIT 5");

$rarityColors = ['common' => '#94a3b8', 'uncommon' => '#34D399', 'rare' => '#3b82f6', 'epic' => '#a855f7', 'mythic' => '#ec4899', 'legendary' => '#f59e0b'];

include BASE_PATH . '/layout/header.php';
?>

<style>
/* Hero */
.hero-section { position: relative; min-height: 440px; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.hero-bg { position: absolute; inset: 0; background: var(--gradient-hero); }
.hero-bg::after {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse at 30% 50%, rgba(56,189,248,0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 70% 80%, rgba(14,165,233,0.06) 0%, transparent 50%);
}
.hero-particles { position: absolute; inset: 0; overflow: hidden; }
.hero-particle {
    position: absolute; border-radius: 50%; opacity: 0;
    animation: heroFloat linear infinite;
}
@keyframes heroFloat {
    0% { opacity: 0; transform: translateY(100%) scale(0); }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { opacity: 0; transform: translateY(-100vh) scale(1); }
}
.hero-content { position: relative; z-index: 2; text-align: center; padding: 2rem 1rem; }
.hero-title { font-size: clamp(2rem, 6vw, 3.5rem); font-weight: 800; line-height: 1.1; }
.hero-sub { font-size: clamp(0.9rem, 2vw, 1.15rem); opacity: 0.7; max-width: 480px; margin: 0 auto; }
.hero-actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin-top: 1.5rem; }
.hero-btn-primary {
    background: var(--gradient-btn); color: #fff; padding: 14px 32px; border-radius: 14px;
    font-weight: 700; font-size: 1.05rem; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 20px rgba(14,165,233,0.3);
}
.hero-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(14,165,233,0.5); }
.hero-btn-secondary {
    background: rgba(255,255,255,0.08); color: var(--color-text); padding: 14px 32px; border-radius: 14px;
    font-weight: 700; font-size: 1.05rem; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px;
    border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(8px);
}
.hero-btn-secondary:hover { background: rgba(255,255,255,0.14); transform: translateY(-2px); }

/* Banner */
.banner-slide { position: absolute; inset: 0; transition: opacity 0.7s; background-size: cover; background-position: center; }
.banner-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,22,40,0.9), rgba(10,22,40,0.3) 50%, rgba(10,22,40,0.5)); }

/* Stats */
.stats-bar { position: relative; z-index: 10; margin-top: -48px; }
.stat-card {
    text-align: center; padding: 20px 12px;
    background: var(--gradient-card); border: 1px solid var(--color-border);
    border-radius: 16px; backdrop-filter: blur(16px);
    transition: transform 0.3s;
}
.stat-card:hover { transform: translateY(-4px); }
.stat-num { font-size: 1.75rem; font-weight: 800; }

/* Server card */
.server-card {
    border-radius: 16px; padding: 20px 24px;
    background: var(--gradient-card); border: 1px solid var(--color-border);
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    flex-wrap: wrap;
}
.server-pulse { width: 10px; height: 10px; border-radius: 50%; background: #34d399; animation: pulse 2s infinite; display: inline-block; }

/* Product card */
.product-card {
    border-radius: 14px; overflow: hidden; transition: all 0.3s;
    background: var(--gradient-card); border: 1px solid var(--color-border);
}
.product-card:hover { transform: translateY(-6px); box-shadow: 0 12px 40px rgba(0,0,0,0.3), 0 0 20px rgba(56,189,248,0.08); }
.product-img {
    aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, rgba(15,30,60,0.5), rgba(10,22,40,0.8));
    position: relative; overflow: hidden;
}
.product-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
.product-card:hover .product-img img { transform: scale(1.05); }

/* Tabs */
.tab-btn {
    padding: 8px 20px; border-radius: 10px; font-weight: 600; font-size: 0.875rem;
    transition: all 0.3s; cursor: pointer; border: 1px solid transparent;
    background: transparent; color: var(--color-text); opacity: 0.5;
}
.tab-btn:hover { opacity: 0.8; background: rgba(56,189,248,0.06); }
.tab-btn.active { background: var(--color-primary); color: #fff; opacity: 1; border-color: var(--color-primary); }
.tab-content { display: none; }
.tab-content.active { display: block; animation: fadeIn 0.4s ease-out; }

/* Feature card */
.feature-card {
    border-radius: 16px; padding: 28px 20px; text-align: center;
    background: var(--gradient-card); border: 1px solid var(--color-border);
    transition: all 0.3s; position: relative; overflow: hidden;
}
.feature-card:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(0,0,0,0.3); }
.feature-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: var(--gradient-sky); opacity: 0; transition: opacity 0.3s;
}
.feature-card:hover::before { opacity: 1; }
.feature-icon {
    width: 56px; height: 56px; border-radius: 16px; margin: 0 auto 14px;
    display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
    transition: transform 0.3s;
}
.feature-card:hover .feature-icon { transform: scale(1.1); }

/* Gacha card */
.gacha-card {
    border-radius: 16px; overflow: hidden; transition: all 0.3s;
    background: var(--gradient-card); border: 1px solid var(--color-border);
}
.gacha-card:hover { transform: translateY(-6px); box-shadow: 0 12px 40px rgba(0,0,0,0.3); }
.gacha-banner {
    height: 140px; display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
}
.gacha-banner::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(10,22,40,0.8), transparent 60%);
}
.gacha-price-tag {
    position: absolute; top: 10px; right: 10px; z-index: 2;
    background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
    padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;
    color: var(--color-accent);
}

/* Leaderboard */
.leader-row {
    display: flex; align-items: center; gap: 12px; padding: 10px 12px;
    border-radius: 12px; transition: background 0.2s;
}
.leader-row:hover { background: rgba(56,189,248,0.06); }
.leader-rank {
    width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 0.85rem; flex-shrink: 0;
}

/* Discord */
.discord-section {
    border-radius: 20px; padding: 48px 24px; text-align: center;
    background: linear-gradient(135deg, #5865F2 0%, #7289DA 50%, #5865F2 100%);
    background-size: 200% 200%; animation: discordGradient 6s ease infinite;
    position: relative; overflow: hidden;
}
@keyframes discordGradient { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
.discord-section::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(circle at 30% 70%, rgba(255,255,255,0.08), transparent 50%);
}

/* Steps */
.step-card { text-align: center; position: relative; }
.step-num {
    width: 52px; height: 52px; border-radius: 16px; margin: 0 auto 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem; font-weight: 800; color: #fff;
    transition: transform 0.3s;
}
.step-card:hover .step-num { transform: scale(1.1); }
.step-line {
    position: absolute; top: 26px; left: calc(50% + 32px); width: calc(100% - 64px);
    height: 2px; background: var(--color-border);
}

/* Section title */
.section-title {
    font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;
    display: flex; align-items: center; gap: 10px;
}
.section-title .title-icon {
    width: 36px; height: 36px; border-radius: 10px; display: inline-flex;
    align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0;
}
.section-link { font-size: 0.85rem; color: var(--color-primary); transition: opacity 0.2s; }
.section-link:hover { opacity: 0.7; }

@media (max-width: 640px) {
    .hero-section { min-height: 340px; }
    .stats-bar { margin-top: -32px; }
    .stat-num { font-size: 1.3rem; }
    .stat-card { padding: 14px 8px; }
    .hero-btn-primary, .hero-btn-secondary { padding: 12px 24px; font-size: 0.95rem; }
}
</style>

<!-- ═══════════════════════════════════════════════════
     HERO / BANNER
     ═══════════════════════════════════════════════════ -->
<?php if (!empty($banners)): ?>
<div class="hero-section" id="banner-slider">
    <?php foreach ($banners as $i => $banner): ?>
    <div class="banner-slide <?= $i === 0 ? 'opacity-100' : 'opacity-0' ?>"
         style="background-image: url('<?= e($banner['image']) ?>');">
        <div class="banner-overlay"></div>
        <div class="hero-content animate-fade-in">
            <h2 class="hero-title gradient-text mb-3"><?= e($banner['title'] ?? '') ?></h2>
            <?php if (!empty($banner['description'])): ?>
                <p class="hero-sub mb-4"><?= e($banner['description']) ?></p>
            <?php endif; ?>
            <?php if ($banner['link']): ?>
                <a href="<?= e($banner['link']) ?>" class="hero-btn-primary">ดูเพิ่มเติม <i class="fas fa-arrow-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (count($banners) > 1): ?>
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
        <?php foreach ($banners as $i => $b): ?>
            <button class="banner-dot w-3 h-3 rounded-full transition" style="background: var(--color-primary); opacity: <?= $i === 0 ? '1' : '0.4' ?>;" onclick="showSlide(<?= $i ?>)"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="hero-section">
    <div class="hero-bg"></div>
    <div class="hero-particles" id="hero-particles"></div>
    <div class="hero-content">
        <div class="mb-5">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="1.5" stroke-linecap="round" class="mx-auto" style="filter: drop-shadow(0 0 20px rgba(56,189,248,0.4));">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
        </div>
        <h1 class="hero-title gradient-text mb-3"><?= e(Settings::get('site_name', 'MCStore')) ?></h1>
        <p class="hero-sub mb-2"><?= e(Settings::get('site_description', 'ร้านค้าเซิร์ฟเวอร์ Minecraft')) ?></p>
        <div class="hero-actions">
            <a href="<?= url('shop') ?>" class="hero-btn-primary"><i class="fas fa-store"></i> เข้าร้านค้า</a>
            <button onclick="App.copyText('<?= e(Settings::get('server_ip', 'play.example.com')) ?>')" class="hero-btn-secondary">
                <i class="fas fa-gamepad"></i> <?= e(Settings::get('server_ip', 'play.example.com')) ?>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ Stats Bar ═══ -->
<div class="stats-bar max-w-5xl mx-auto px-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <?php
        $stats = [
            ['icon' => 'fa-users',   'num' => $totalUsers,     'label' => 'สมาชิก',     'color' => 'var(--color-primary)'],
            ['icon' => 'fa-box',     'num' => $totalProducts,  'label' => 'สินค้า',      'color' => 'var(--color-secondary)'],
            ['icon' => 'fa-receipt', 'num' => $totalOrders,    'label' => 'ออเดอร์สำเร็จ', 'color' => 'var(--color-accent)'],
            ['icon' => 'fa-truck',   'num' => $totalDelivered, 'label' => 'ส่งของแล้ว',   'color' => '#34d399'],
        ];
        foreach ($stats as $i => $st):
        ?>
        <div class="stat-card animate-fade-in" style="animation-delay: <?= $i * 0.1 ?>s;">
            <div class="stat-num gradient-text"><?= number_format($st['num']) ?></div>
            <div class="text-xs opacity-60 mt-1"><i class="fas <?= $st['icon'] ?> mr-1" style="color: <?= $st['color'] ?>;"></i> <?= $st['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-10">

    <!-- ═══ Server Status ═══ -->
    <section class="mb-10 animate-slide-up">
        <div class="server-card">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background: var(--gradient-btn);">
                    <i class="fas fa-server text-xl text-white"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="server-pulse"></span>
                        <span class="font-bold text-lg"><?= e(Settings::get('server_ip', 'play.example.com')) ?></span>
                    </div>
                    <div class="text-sm opacity-60">
                        <span data-online-count>-</span> ออนไลน์
                        <span class="mx-2 opacity-30">|</span>
                        <i class="fas fa-gamepad mr-1"></i> <?= e(Settings::get('server_version', '1.20.x')) ?>
                    </div>
                </div>
            </div>
            <button onclick="App.copyText('<?= e(Settings::get('server_ip', 'play.example.com')) ?>')"
                    class="hero-btn-primary text-sm" style="padding: 10px 24px;">
                <i class="fas fa-copy"></i> คัดลอก IP
            </button>
        </div>
    </section>

    <!-- ═══ Servers ═══ -->
    <?php if (count($servers) > 1): ?>
    <section class="mb-10">
        <div class="section-title">
            <span class="title-icon" style="background: var(--gradient-btn);"><i class="fas fa-server text-white"></i></span>
            เลือกเซิร์ฟเวอร์
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-<?= min(count($servers), 5) ?> gap-4">
            <?php foreach ($servers as $i => $srv): ?>
            <a href="<?= url('shop/' . e($srv['id'])) ?>" class="feature-card group animate-fade-in" style="animation-delay: <?= $i * 0.08 ?>s;">
                <div class="feature-icon" style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
                    <?php if ($srv['icon']): ?>
                        <img src="<?= e($srv['icon']) ?>" alt="" class="w-8 h-8" style="image-rendering: pixelated;">
                    <?php else: ?>
                        <i class="fas fa-cube text-white"></i>
                    <?php endif; ?>
                </div>
                <h3 class="font-bold text-sm"><?= e($srv['name']) ?></h3>
                <?php if ($srv['description']): ?>
                    <p class="text-xs opacity-50 mt-1"><?= e($srv['description']) ?></p>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ═══ Sale Products ═══ -->
    <?php if (!empty($saleProducts)): ?>
    <section class="mb-10">
        <div class="flex items-center justify-between mb-5">
            <div class="section-title mb-0">
                <span class="title-icon" style="background: linear-gradient(135deg, #ef4444, #f97316);"><i class="fas fa-fire text-white"></i></span>
                โปรโมชั่นลดราคา
            </div>
            <a href="<?= url('shop') ?>" class="section-link">ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($saleProducts as $i => $prod):
                $discount = round((($prod['original_price'] - $prod['price']) / $prod['original_price']) * 100);
            ?>
            <div class="product-card animate-fade-in" style="animation-delay: <?= $i * 0.1 ?>s; border-color: rgba(239,68,68,0.25);">
                <div class="product-img">
                    <?php if ($prod['image']): ?>
                        <img src="<?= e($prod['image']) ?>" alt="<?= e($prod['name']) ?>">
                    <?php else: ?>
                        <i class="fas fa-fire text-4xl text-red-500 opacity-30"></i>
                    <?php endif; ?>
                    <div class="absolute top-2 left-2 z-10 px-2 py-1 rounded-lg text-xs font-bold bg-red-500 text-white">-<?= $discount ?>%</div>
                </div>
                <div class="p-4">
                    <div class="text-xs opacity-40 mb-1"><?= e($prod['server_name']) ?></div>
                    <h3 class="font-bold text-sm mb-2 line-clamp-1"><?= e($prod['name']) ?></h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs line-through opacity-30"><?= formatMoney($prod['original_price']) ?></span>
                            <span class="font-bold text-red-400 ml-1"><?= formatMoney($prod['price']) ?></span>
                        </div>
                        <?php if (Auth::check()): ?>
                            <button onclick="addToCart(<?= (int)$prod['id'] ?>, '<?= e($prod['server_slug']) ?>')" class="btn-primary px-2.5 py-1.5 rounded-lg text-xs"><i class="fas fa-cart-plus"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ═══ Products Tabs (Featured / Latest) ═══ -->
    <?php if (!empty($featuredProducts) || !empty($latestProducts)): ?>
    <section class="mb-10">
        <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
            <div class="flex gap-2" id="product-tabs">
                <?php if (!empty($featuredProducts)): ?>
                    <button class="tab-btn active" onclick="switchTab('featured', this)"><i class="fas fa-star mr-1.5"></i> แนะนำ</button>
                <?php endif; ?>
                <?php if (!empty($latestProducts)): ?>
                    <button class="tab-btn <?= empty($featuredProducts) ? 'active' : '' ?>" onclick="switchTab('latest', this)"><i class="fas fa-clock mr-1.5"></i> ใหม่ล่าสุด</button>
                <?php endif; ?>
            </div>
            <a href="<?= url('shop') ?>" class="section-link">ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i></a>
        </div>

        <?php if (!empty($featuredProducts)): ?>
        <div class="tab-content active" id="tab-featured">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($featuredProducts as $i => $prod): ?>
                <div class="product-card animate-fade-in" style="animation-delay: <?= $i * 0.06 ?>s;">
                    <div class="product-img">
                        <?php if ($prod['image']): ?>
                            <img src="<?= e($prod['image']) ?>" alt="<?= e($prod['name']) ?>">
                        <?php else: ?>
                            <i class="fas fa-box text-4xl opacity-20" style="color: var(--color-primary);"></i>
                        <?php endif; ?>
                        <?php if ($prod['stock'] !== null && $prod['stock'] >= 0 && $prod['stock'] <= 5): ?>
                            <div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">เหลือ <?= (int)$prod['stock'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <div class="text-xs opacity-40 mb-1"><?= e($prod['server_name']) ?> — <?= e($prod['category_name']) ?></div>
                        <h3 class="font-bold text-sm mb-2 line-clamp-1"><?= e($prod['name']) ?></h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <?php if ($prod['original_price'] && $prod['original_price'] > $prod['price']): ?>
                                    <span class="text-xs line-through opacity-30"><?= formatMoney($prod['original_price']) ?></span>
                                <?php endif; ?>
                                <span class="font-bold" style="color: var(--color-accent);"><?= formatMoney($prod['price']) ?></span>
                            </div>
                            <?php if (Auth::check()): ?>
                                <button onclick="addToCart(<?= (int)$prod['id'] ?>, '<?= e($prod['server_slug']) ?>')" class="btn-primary px-2.5 py-1.5 rounded-lg text-xs"><i class="fas fa-cart-plus"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($latestProducts)): ?>
        <div class="tab-content <?= empty($featuredProducts) ? 'active' : '' ?>" id="tab-latest">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($latestProducts as $i => $prod): ?>
                <div class="product-card animate-fade-in" style="animation-delay: <?= $i * 0.06 ?>s;">
                    <div class="product-img">
                        <?php if ($prod['image']): ?>
                            <img src="<?= e($prod['image']) ?>" alt="<?= e($prod['name']) ?>">
                        <?php else: ?>
                            <i class="fas fa-box text-4xl opacity-20" style="color: var(--color-secondary);"></i>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <div class="text-xs opacity-40 mb-1"><?= e($prod['server_name']) ?> — <?= e($prod['category_name']) ?></div>
                        <h3 class="font-bold text-sm mb-2 line-clamp-1"><?= e($prod['name']) ?></h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <?php if ($prod['original_price'] && $prod['original_price'] > $prod['price']): ?>
                                    <span class="text-xs line-through opacity-30"><?= formatMoney($prod['original_price']) ?></span>
                                <?php endif; ?>
                                <span class="font-bold" style="color: var(--color-accent);"><?= formatMoney($prod['price']) ?></span>
                            </div>
                            <?php if (Auth::check()): ?>
                                <button onclick="addToCart(<?= (int)$prod['id'] ?>, '<?= e($prod['server_slug']) ?>')" class="btn-primary px-2.5 py-1.5 rounded-lg text-xs"><i class="fas fa-cart-plus"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- ═══ Gacha & Leaderboard ═══ -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
        <!-- Gacha (2 col) -->
        <div class="lg:col-span-2">
            <?php if (!empty($gachaCrates)): ?>
            <div class="flex items-center justify-between mb-5">
                <div class="section-title mb-0">
                    <span class="title-icon" style="background: linear-gradient(135deg, #ec4899, #a855f7);"><i class="fas fa-dice text-white"></i></span>
                    กาชา
                </div>
                <a href="<?= url('gacha') ?>" class="section-link">ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <?php foreach ($gachaCrates as $i => $crate): ?>
                <a href="<?= url('gacha/' . e($crate['server_slug']) . '/' . $crate['id']) ?>" class="gacha-card group animate-fade-in" style="animation-delay: <?= $i * 0.1 ?>s;">
                    <div class="gacha-banner" style="background: linear-gradient(135deg, <?= $i % 2 === 0 ? 'var(--color-primary), var(--color-secondary)' : '#ec4899, #a855f7' ?>);">
                        <?php if (!empty($crate['image'])): ?>
                            <img src="<?= e($crate['image']) ?>" class="w-20 h-20 object-contain relative z-[1] group-hover:scale-110 transition-transform" style="image-rendering: pixelated; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.5));">
                        <?php else: ?>
                            <i class="fas fa-box-open text-5xl relative z-[1] text-white/80 group-hover:scale-110 transition-transform"></i>
                        <?php endif; ?>
                        <div class="gacha-price-tag"><?= formatMoney($crate['price']) ?>/ครั้ง</div>
                    </div>
                    <div class="p-4">
                        <div class="text-xs opacity-40 mb-1"><?= e($crate['server_name']) ?></div>
                        <h3 class="font-bold text-sm"><?= e($crate['name']) ?></h3>
                        <?php if ($crate['description']): ?>
                            <p class="text-xs opacity-40 mt-1 line-clamp-1"><?= e($crate['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right column: Winners + Leaderboard -->
        <div class="space-y-6">
            <!-- Recent winners -->
            <div>
                <div class="section-title text-lg">
                    <span class="title-icon" style="background: linear-gradient(135deg, #f59e0b, #f97316); width: 32px; height: 32px; border-radius: 8px; font-size: 0.8rem;"><i class="fas fa-trophy text-white"></i></span>
                    ผู้โชคดีล่าสุด
                </div>
                <div class="card p-4 rounded-2xl" style="background: var(--gradient-card); border: 1px solid var(--color-border); max-height: 240px; overflow-y: auto;">
                    <?php if (!empty($recentWinners)): ?>
                    <div class="space-y-2">
                        <?php foreach ($recentWinners as $win):
                            $color = $rarityColors[$win['rarity']] ?? '#94a3b8';
                        ?>
                        <div class="flex items-center gap-2.5 py-1 hover:bg-white/5 rounded-lg px-2 transition">
                            <img src="https://mc-heads.net/avatar/<?= e($win['username']) ?>/24" class="w-6 h-6 rounded" alt="">
                            <div class="flex-1 min-w-0">
                                <span class="text-xs font-bold truncate block"><?= e($win['username']) ?></span>
                            </div>
                            <span class="text-xs font-bold truncate" style="color: <?= $color ?>; max-width: 90px;"><?= e($win['reward_name']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-sm opacity-40 py-4">ยังไม่มีผู้โชคดี</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top buyers -->
            <div>
                <div class="section-title text-lg">
                    <span class="title-icon" style="background: linear-gradient(135deg, var(--color-accent), var(--color-primary)); width: 32px; height: 32px; border-radius: 8px; font-size: 0.8rem;"><i class="fas fa-crown text-white"></i></span>
                    ท็อปเดือนนี้
                </div>
                <div class="card p-3 rounded-2xl" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
                    <?php if (!empty($topBuyers)): ?>
                    <?php foreach ($topBuyers as $i => $buyer):
                        $rankColors = ['linear-gradient(135deg, #f59e0b, #f97316)', 'linear-gradient(135deg, #94a3b8, #64748b)', 'linear-gradient(135deg, #d97706, #b45309)'];
                        $bg = $rankColors[$i] ?? 'rgba(255,255,255,0.05)';
                    ?>
                    <div class="leader-row">
                        <div class="leader-rank" style="background: <?= $bg ?>; color: #fff;"><?= $i + 1 ?></div>
                        <img src="https://mc-heads.net/avatar/<?= e($buyer['username']) ?>/28" class="w-7 h-7 rounded-lg" alt="">
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-sm truncate"><?= e($buyer['username']) ?></div>
                            <div class="text-xs opacity-40"><?= (int)$buyer['order_count'] ?> ออเดอร์</div>
                        </div>
                        <span class="font-bold text-sm" style="color: var(--color-accent);"><?= formatMoney($buyer['total_spent']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p class="text-center text-sm opacity-40 py-4">ยังไม่มีข้อมูล</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Quick Features ═══ -->
    <section class="mb-10">
        <div class="section-title justify-center">
            <span class="title-icon" style="background: var(--gradient-btn);"><i class="fas fa-bolt text-white"></i></span>
            บริการของเรา
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="<?= url('shop') ?>" class="feature-card group">
                <div class="feature-icon" style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
                    <i class="fas fa-store text-white"></i>
                </div>
                <h3 class="font-bold text-sm">ร้านค้า</h3>
                <p class="text-xs opacity-40 mt-1">ซื้อไอเทม แรงค์ และอื่นๆ</p>
            </a>
            <a href="<?= url('topup') ?>" class="feature-card group">
                <div class="feature-icon" style="background: linear-gradient(135deg, #FF6B00, #FF8C38);">
                    <i class="fas fa-coins text-white"></i>
                </div>
                <h3 class="font-bold text-sm">เติมเงิน</h3>
                <p class="text-xs opacity-40 mt-1">เติมเงินผ่านซองอั่งเปา</p>
            </a>
            <a href="<?= url('gacha') ?>" class="feature-card group">
                <div class="feature-icon" style="background: linear-gradient(135deg, #ec4899, #a855f7);">
                    <i class="fas fa-dice text-white"></i>
                </div>
                <h3 class="font-bold text-sm">กาชา</h3>
                <p class="text-xs opacity-40 mt-1">ลุ้นไอเทมหายาก</p>
            </a>
            <a href="<?= url('topup') ?>" class="feature-card group">
                <div class="feature-icon" style="background: linear-gradient(135deg, #10b981, #14b8a6);">
                    <i class="fas fa-gift text-white"></i>
                </div>
                <h3 class="font-bold text-sm">รีดีมโค้ด</h3>
                <p class="text-xs opacity-40 mt-1">แลกโค้ดรับของรางวัล</p>
            </a>
        </div>
    </section>

    <!-- ═══ Discord CTA ═══ -->
    <?php $discordLink = Settings::get('discord_invite'); ?>
    <?php if ($discordLink): ?>
    <section class="mb-10">
        <div class="discord-section">
            <div class="relative z-10">
                <i class="fab fa-discord text-5xl mb-4 text-white" style="filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3));"></i>
                <h2 class="text-2xl font-bold text-white mb-2">เข้าร่วม Discord ของเรา!</h2>
                <p class="text-white/60 mb-6 max-w-md mx-auto text-sm">พบปะเพื่อน รับข่าวสาร โปรโมชั่น และโค้ดพิเศษ</p>
                <a href="<?= e($discordLink) ?>" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 bg-white text-indigo-600 px-8 py-3 rounded-xl font-bold hover:bg-indigo-50 transition shadow-lg">
                    <i class="fab fa-discord"></i> เข้าร่วมเลย!
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ═══ How It Works ═══ -->
    <section class="mb-10">
        <div class="section-title justify-center">
            <span class="title-icon" style="background: var(--gradient-sky);"><i class="fas fa-question text-white"></i></span>
            วิธีซื้อของ
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8">
            <?php
            $steps = [
                ['num' => '1', 'title' => 'สมัครสมาชิก', 'desc' => 'ใช้ชื่อเดียวกับในเกม', 'color' => 'var(--color-primary)'],
                ['num' => '2', 'title' => 'เติมเงิน',     'desc' => 'เติมเงินผ่านซองอั่งเปา', 'color' => 'var(--color-accent)'],
                ['num' => '3', 'title' => 'เลือกสินค้า',  'desc' => 'เลือกไอเทมแล้วกดซื้อ', 'color' => 'var(--color-secondary)'],
                ['num' => '4', 'title' => 'รับของในเกม',  'desc' => 'ของส่งเข้าเกมอัตโนมัติ!', 'color' => '#34d399'],
            ];
            foreach ($steps as $i => $step):
            ?>
            <div class="step-card animate-fade-in" style="animation-delay: <?= $i * 0.1 ?>s;">
                <?php if ($i < count($steps) - 1): ?>
                    <div class="step-line hidden md:block"></div>
                <?php endif; ?>
                <div class="step-num" style="background: <?= $step['color'] ?>;"><?= $step['num'] ?></div>
                <h3 class="font-bold text-sm mb-1"><?= $step['title'] ?></h3>
                <p class="text-xs opacity-50"><?= $step['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

</div>

<script>
// Cart
var HOME_CART_URL  = '<?= e(url("api/cart/update")) ?>';
var HOME_CART_CSRF = '<?= e($_SESSION['csrf_token'] ?? '') ?>';
function addToCart(productId, serverId) {
    fetch(HOME_CART_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add', product_id: productId, server_id: serverId, quantity: 1, _csrf: HOME_CART_CSRF })
    }).then(r => r.json()).then(function(d) {
        if (d.success) {
            Swal.fire({ icon: 'success', title: 'เพิ่มในตะกร้าแล้ว!', timer: 1200, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'warning', title: d.message || 'เกิดข้อผิดพลาด' });
        }
    }).catch(function() {
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด' });
    });
}

// Banner slider
<?php if (count($banners ?? []) > 1): ?>
let currentSlide = 0;
const slides = document.querySelectorAll('.banner-slide');
const dots = document.querySelectorAll('.banner-dot');
function showSlide(n) {
    slides.forEach((s, i) => { s.style.opacity = i === n ? '1' : '0'; });
    dots.forEach((d, i) => { d.style.opacity = i === n ? '1' : '0.4'; });
    currentSlide = n;
}
setInterval(() => showSlide((currentSlide + 1) % slides.length), 5000);
<?php endif; ?>

// Product tabs
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    const el = document.getElementById('tab-' + tabId);
    if (el) el.classList.add('active');
    btn.classList.add('active');
}

// Hero particles
(function() {
    const container = document.getElementById('hero-particles');
    if (!container) return;
    const colors = ['var(--color-primary)', 'var(--color-accent)', 'var(--color-secondary)'];
    for (let i = 0; i < 20; i++) {
        const p = document.createElement('div');
        p.className = 'hero-particle';
        const size = 2 + Math.random() * 4;
        p.style.cssText = `
            width: ${size}px; height: ${size}px;
            left: ${Math.random() * 100}%;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            animation-duration: ${8 + Math.random() * 12}s;
            animation-delay: ${Math.random() * 10}s;
        `;
        container.appendChild(p);
    }
})();
</script>

<?php include BASE_PATH . '/layout/footer.php'; ?>
