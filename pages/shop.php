<?php
$pageTitle = 'ร้านค้า';
$db = Database::getInstance();

// Get current server from URL
$serverSlug = $_GET['server'] ?? '';
$categorySlug = $_GET['category'] ?? '';

$servers = $db->fetchAll("SELECT * FROM servers WHERE is_active = 1 ORDER BY display_order");
$currentServer = null;

// If no server specified, redirect to first server
if (empty($serverSlug) && !empty($servers)) {
    redirect('shop/' . $servers[0]['id']);
}

if ($serverSlug) {
    $currentServer = $db->fetch("SELECT * FROM servers WHERE id = ? AND is_active = 1", [$serverSlug]);
    if (!$currentServer) {
        include BASE_PATH . '/pages/404.php';
        return;
    }
}

// Get categories for this server
$categories = $db->fetchAll(
    "SELECT DISTINCT c.* FROM categories c 
     JOIN products p ON p.category_id = c.id 
     WHERE p.server_id = ? AND p.is_active = 1 AND c.is_active = 1 
     ORDER BY c.display_order",
    [$currentServer['id']]
);

// Get products
$params = [$currentServer['id']];
$catFilter = "";
if ($categorySlug) {
    $cat = $db->fetch("SELECT * FROM categories WHERE id = ?", [$categorySlug]);
    if ($cat) {
        $catFilter = " AND p.category_id = ?";
        $params[] = $cat['id'];
    }
}

$products = $db->fetchAll(
    "SELECT p.*, c.name AS category_name FROM products p 
     JOIN categories c ON p.category_id = c.id 
     WHERE p.server_id = ? AND p.is_active = 1{$catFilter}
     ORDER BY p.display_order, p.created_at DESC",
    $params
);

include BASE_PATH . '/layout/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar -->
        <aside class="lg:w-64 shrink-0">
            <!-- Server Selection -->
            <div class="card p-4 mb-4">
                <h3 class="font-bold text-sm mb-3 opacity-70"><i class="fas fa-server mr-2"></i> เซิร์ฟเวอร์</h3>
                <div class="space-y-1">
                    <?php foreach ($servers as $srv): ?>
                        <a href="<?= url('shop/' . e($srv['id'])) ?>" 
                           class="block px-3 py-2 rounded-lg text-sm transition <?= $srv['id'] === $serverSlug ? 'font-bold' : 'opacity-70 hover:opacity-100' ?>"
                           style="<?= $srv['id'] === $serverSlug ? 'background: var(--color-primary); color: #fff;' : '' ?>">
                            <i class="fas fa-cube mr-2"></i> <?= e($srv['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Category Filter -->
            <div class="card p-4">
                <h3 class="font-bold text-sm mb-3 opacity-70"><i class="fas fa-tags mr-2"></i> หมวดหมู่</h3>
                <div class="space-y-1">
                    <a href="<?= url('shop/' . e($serverSlug)) ?>" 
                       class="block px-3 py-2 rounded-lg text-sm transition <?= empty($categorySlug) ? 'font-bold' : 'opacity-70 hover:opacity-100' ?>"
                       style="<?= empty($categorySlug) ? 'background: var(--color-primary); color: #fff;' : '' ?>">
                        <i class="fas fa-th mr-2"></i> ทั้งหมด
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="<?= url('shop/' . e($serverSlug) . '/' . e($cat['id'])) ?>" 
                           class="block px-3 py-2 rounded-lg text-sm transition <?= (string)$cat['id'] === $categorySlug ? 'font-bold' : 'opacity-70 hover:opacity-100' ?>"
                           style="<?= (string)$cat['id'] === $categorySlug ? 'background: var(--color-primary); color: #fff;' : '' ?>">
                            <i class="fas fa-tag mr-2"></i> <?= e($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- Products Grid -->
        <div class="flex-1">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold">
                    <i class="fas fa-store mr-2" style="color: var(--color-primary);"></i>
                    ร้านค้า — <?= e($currentServer['name']) ?>
                </h1>
                <span class="text-sm opacity-60"><?= count($products) ?> สินค้า</span>
            </div>

            <?php if (empty($products)): ?>
                <div class="card p-12 text-center">
                    <i class="fas fa-box-open text-5xl mb-4 opacity-30"></i>
                    <p class="text-lg opacity-60">ยังไม่มีสินค้าในหมวดนี้</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($products as $prod): ?>
                        <div class="card card-hover overflow-hidden animate-fade-in">
                            <div class="aspect-square flex items-center justify-center text-4xl" style="background: linear-gradient(135deg, var(--color-surface-dark), var(--color-bg));">
                                <?php if ($prod['image']): ?>
                                    <img src="<?= e($prod['image']) ?>" alt="<?= e($prod['name']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-box" style="color: var(--color-primary);"></i>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <div class="text-xs opacity-50 mb-1"><?= e($prod['category_name']) ?></div>
                                <h3 class="font-bold mb-1"><?= e($prod['name']) ?></h3>
                                <?php if ($prod['description']): ?>
                                    <p class="text-xs opacity-60 mb-3 line-clamp-2"><?= e($prod['description']) ?></p>
                                <?php endif; ?>
                                
                                <div class="flex items-center justify-between mt-3">
                                    <div>
                                        <?php if ($prod['original_price'] && $prod['original_price'] > $prod['price']): ?>
                                            <span class="text-xs line-through opacity-40"><?= formatMoney($prod['original_price']) ?></span><br>
                                        <?php endif; ?>
                                        <span class="text-lg font-bold" style="color: var(--color-accent);"><?= formatMoney($prod['price']) ?></span>
                                    </div>
                                    <?php if (Auth::check()): ?>
                                        <button onclick="addToCart(<?= (int)$prod['id'] ?>, <?= (int)$currentServer['id'] ?>)" 
                                                class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">
                                            <i class="fas fa-cart-plus mr-1"></i> ซื้อ
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= url('login') ?>" class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">
                                            <i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <?php if ($prod['stock'] !== null && $prod['stock'] <= 5 && $prod['stock'] > 0): ?>
                                    <p class="text-xs text-red-400 mt-2"><i class="fas fa-exclamation-triangle mr-1"></i> เหลือ <?= (int)$prod['stock'] ?> ชิ้น</p>
                                <?php elseif ($prod['stock'] !== null && $prod['stock'] <= 0): ?>
                                    <p class="text-xs text-red-400 mt-2"><i class="fas fa-times-circle mr-1"></i> สินค้าหมด</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
