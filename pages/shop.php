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

// Pagination
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));

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

$totalProducts = (int)$db->fetch(
    "SELECT COUNT(*) AS n FROM products p
     WHERE p.server_id = ? AND p.is_active = 1{$catFilter}",
    $params
)['n'];
$totalPages = max(1, (int)ceil($totalProducts / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$paramsPage = array_merge($params, [$perPage, $offset]);
$products = $db->fetchAll(
    "SELECT p.*, c.name AS category_name FROM products p
     JOIN categories c ON p.category_id = c.id
     WHERE p.server_id = ? AND p.is_active = 1{$catFilter}
     ORDER BY p.display_order, p.created_at DESC
     LIMIT ? OFFSET ?",
    $paramsPage
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
                <span class="text-sm opacity-60"><?= $totalProducts ?> สินค้า</span>
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
                                        <button onclick="openBuyModal(this)"
                                                data-product-id="<?= (int)$prod['id'] ?>"
                                                data-server-id="<?= e($currentServer['id']) ?>"
                                                data-price="<?= (float)$prod['price'] ?>"
                                                data-one-per-user="<?= $prod['one_per_user'] ? '1' : '0' ?>"
                                                data-name="<?= e($prod['name']) ?>"
                                                class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">
                                            <i class="fas fa-bolt mr-1"></i> ซื้อเลย
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= url('login') ?>" class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">
                                            <i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <?php if ($prod['stock'] !== null && $prod['stock'] > 0 && $prod['stock'] <= 5): ?>
                                    <p class="text-xs text-red-400 mt-2"><i class="fas fa-exclamation-triangle mr-1"></i> เหลือ <?= (int)$prod['stock'] ?> ชิ้น</p>
                                <?php elseif ($prod['stock'] !== null && $prod['stock'] == 0): ?>
                                    <p class="text-xs text-red-400 mt-2"><i class="fas fa-times-circle mr-1"></i> สินค้าหมด</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            $shopBase = url('shop/' . e($serverSlug) . ($categorySlug ? '/' . e($categorySlug) : ''));
            echo paginationHtml($page, $totalPages, $shopBase);
            ?>
        </div>
    </div>
</div>

<script>
var SHOP_BUY_URL  = '<?= e(url("api/shop/buynow")) ?>';
var SHOP_CSRF     = '<?= e(csrf_token()) ?>';

function formatMoney(n) {
    return parseFloat(n).toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' \u0e1a\u0e32\u0e17';
}

function shopToggleGift() {
    var checked = document.getElementById('swal-gift').checked;
    document.getElementById('gift-section').style.display = checked ? 'block' : 'none';
}

function openBuyModal(btn) {
    var productId    = parseInt(btn.dataset.productId);
    var serverId     = btn.dataset.serverId;
    var price        = parseFloat(btn.dataset.price);
    var isOnePerUser = btn.dataset.onePerUser === '1';
    var productName  = btn.dataset.name;

    var html = '<div style="text-align:left;font-size:14px;">';
    html += '<div style="margin-bottom:12px;padding:10px;background:rgba(255,255,255,0.05);border-radius:8px;">';
    html += '<span style="opacity:0.6;">\u0e23\u0e32\u0e04\u0e32:</span> ';
    html += '<strong style="color:var(--color-accent);font-size:18px;margin-left:6px;">' + formatMoney(price) + '</strong>';
    if (isOnePerUser) {
        html += '<span style="font-size:11px;background:rgba(239,68,68,0.2);color:#ef4444;padding:2px 8px;border-radius:4px;margin-left:8px;">\u0e0b\u0e37\u0e49\u0e2d\u0e44\u0e14\u0e49\u0e04\u0e23\u0e31\u0e49\u0e07\u0e40\u0e14\u0e35\u0e22\u0e27</span>';
    }
    html += '</div>';
    if (!isOnePerUser) {
        html += '<div style="margin-bottom:12px;">';
        html += '<label style="font-size:12px;opacity:0.6;display:block;margin-bottom:4px;">\u0e08\u0e33\u0e19\u0e27\u0e19</label>';
        html += '<input type="number" id="swal-qty" min="1" max="99" value="1" style="width:100%;padding:8px 12px;border-radius:8px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);color:inherit;font-size:14px;" oninput="document.getElementById(\'swal-total\').textContent=formatMoney(' + price + '*Math.max(1,parseInt(this.value)||1))">';
        html += '</div>';
    }
    html += '<div style="margin-bottom:12px;">';
    html += '<label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px;background:rgba(255,255,255,0.04);border-radius:8px;">';
    html += '<input type="checkbox" id="swal-gift" onchange="shopToggleGift()" style="width:16px;height:16px;cursor:pointer;">';
    html += '<span><i class="fas fa-gift" style="color:var(--color-accent);"></i> \u0e2a\u0e48\u0e07\u0e40\u0e1b\u0e47\u0e19\u0e02\u0e2d\u0e07\u0e02\u0e27\u0e31\u0e0d\u0e43\u0e2b\u0e49\u0e40\u0e1e\u0e37\u0e48\u0e2d\u0e19</span>';
    html += '</label></div>';
    html += '<div id="gift-section" style="display:none;margin-bottom:12px;">';
    html += '<label style="font-size:12px;opacity:0.6;display:block;margin-bottom:4px;">\u0e0a\u0e37\u0e48\u0e2d\u0e1c\u0e39\u0e49\u0e40\u0e25\u0e48\u0e19\u0e17\u0e35\u0e48\u0e23\u0e31\u0e1a\u0e02\u0e2d\u0e07\u0e02\u0e27\u0e31\u0e0d</label>';
    html += '<input type="text" id="swal-gift-to" placeholder="username \u0e43\u0e19\u0e40\u0e01\u0e21" style="width:100%;padding:8px 12px;border-radius:8px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);color:inherit;font-size:14px;">';
    html += '<p style="font-size:11px;opacity:0.4;margin-top:4px;">\u0e1c\u0e39\u0e49\u0e23\u0e31\u0e1a\u0e15\u0e49\u0e2d\u0e07\u0e2d\u0e2d\u0e19\u0e44\u0e25\u0e19\u0e4c\u0e2d\u0e22\u0e39\u0e48\u0e43\u0e19\u0e40\u0e0b\u0e34\u0e23\u0e4c\u0e1f\u0e40\u0e27\u0e2d\u0e23\u0e4c\u0e01\u0e48\u0e2d\u0e19</p>';
    html += '</div>';
    html += '<div style="font-size:12px;opacity:0.5;text-align:center;margin-top:4px;">\u0e22\u0e2d\u0e14\u0e23\u0e27\u0e21: <strong id="swal-total">' + formatMoney(price) + '</strong></div>';
    html += '</div>';

    Swal.fire({
        title: productName,
        html: html,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-bolt mr-1"></i> \u0e22\u0e37\u0e19\u0e22\u0e31\u0e19\u0e0b\u0e37\u0e49\u0e2d',
        cancelButtonText: '\u0e22\u0e01\u0e40\u0e25\u0e34\u0e01',
        confirmButtonColor: 'var(--color-primary)',
        background: 'var(--color-surface)',
        color: 'var(--color-text)',
        preConfirm: function() {
            var qtyEl = document.getElementById('swal-qty');
            var qty = isOnePerUser ? 1 : Math.max(1, Math.min(99, parseInt(qtyEl ? qtyEl.value : '1') || 1));
            var isGift = document.getElementById('swal-gift').checked;
            var giftTo = isGift ? document.getElementById('swal-gift-to').value.trim() : '';
            if (isGift && !giftTo) {
                Swal.showValidationMessage('\u0e01\u0e23\u0e38\u0e13\u0e32\u0e01\u0e23\u0e2d\u0e01\u0e0a\u0e37\u0e48\u0e2d\u0e1c\u0e39\u0e49\u0e40\u0e25\u0e48\u0e19\u0e17\u0e35\u0e48\u0e15\u0e49\u0e2d\u0e07\u0e01\u0e32\u0e23\u0e2a\u0e48\u0e07\u0e02\u0e2d\u0e07\u0e02\u0e27\u0e31\u0e0d');
                return false;
            }
            return { qty: qty, giftTo: giftTo };
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            buyNow(productId, serverId, result.value.qty, result.value.giftTo);
        }
    });
}

function buyNow(productId, serverId, quantity, giftTo) {
    Swal.fire({ title: '\u0e01\u0e33\u0e25\u0e31\u0e07\u0e14\u0e33\u0e40\u0e19\u0e34\u0e19\u0e01\u0e32\u0e23...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
    fetch(SHOP_BUY_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': SHOP_CSRF },
        body: JSON.stringify({ product_id: productId, server_id: serverId, quantity: quantity, gift_to: giftTo || '' })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            Swal.fire({ icon: 'success', title: '\u0e2a\u0e33\u0e40\u0e23\u0e47\u0e08!', text: data.message, timer: 2000, showConfirmButton: false })
                .then(function() { if (data.redirect) window.location.href = data.redirect; });
        } else {
            Swal.fire({ icon: 'error', title: '\u0e44\u0e21\u0e48\u0e2a\u0e33\u0e40\u0e23\u0e47\u0e08', text: data.message });
        }
    })
    .catch(function() { Swal.fire({ icon: 'error', title: '\u0e40\u0e01\u0e34\u0e14\u0e02\u0e49\u0e2d\u0e1c\u0e34\u0e14\u0e1e\u0e25\u0e32\u0e14', text: '\u0e01\u0e23\u0e38\u0e13\u0e32\u0e25\u0e2d\u0e07\u0e43\u0e2b\u0e21\u0e48\u0e2d\u0e35\u0e01\u0e04\u0e23\u0e31\u0e49\u0e07' }); });
}
</script>

<?php include BASE_PATH . '/layout/footer.php'; ?>
