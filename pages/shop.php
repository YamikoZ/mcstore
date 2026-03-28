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
function openBuyModal(btn) {
    const productId   = parseInt(btn.dataset.productId);
    const serverId    = btn.dataset.serverId;
    const price       = parseFloat(btn.dataset.price);
    const isOnePerUser = btn.dataset.onePerUser === '1';
    const productName = btn.dataset.name;
    const isGiftChecked = false;
    Swal.fire({
        title: productName,
        html: `
            <div style="text-align:left; font-size:14px;">
                <div style="margin-bottom:12px; padding:10px; background:rgba(255,255,255,0.05); border-radius:8px;">
                    <span style="opacity:0.6;">ราคา:</span>
                    <strong style="color:var(--color-accent); font-size:18px; margin-left:6px;">${formatMoney(price)}</strong>
                    ${isOnePerUser ? '<span style="font-size:11px; background:rgba(239,68,68,0.2); color:#ef4444; padding:2px 8px; border-radius:4px; margin-left:8px;">ซื้อได้ครั้งเดียว</span>' : ''}
                </div>
                ${!isOnePerUser ? `
                <div style="margin-bottom:12px;">
                    <label style="font-size:12px; opacity:0.6; display:block; margin-bottom:4px;">จำนวน</label>
                    <input type="number" id="swal-qty" min="1" max="99" value="1"
                        style="width:100%; padding:8px 12px; border-radius:8px; background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.1); color:inherit; font-size:14px;"
                        oninput="document.getElementById('swal-total').textContent = formatMoney(price * Math.max(1, parseInt(this.value)||1))">
                </div>` : ''}
                <div style="margin-bottom:12px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; padding:10px; background:rgba(255,255,255,0.04); border-radius:8px;">
                        <input type="checkbox" id="swal-gift" onchange="toggleGift()"
                            style="width:16px; height:16px; cursor:pointer; accent-color:var(--color-primary);">
                        <span><i class="fas fa-gift" style="color:var(--color-accent);"></i> ส่งเป็นของขวัญให้เพื่อน</span>
                    </label>
                </div>
                <div id="gift-section" style="display:none; margin-bottom:12px;">
                    <label style="font-size:12px; opacity:0.6; display:block; margin-bottom:4px;">ชื่อผู้เล่นที่รับของขวัญ</label>
                    <input type="text" id="swal-gift-to" placeholder="username ในเกม"
                        style="width:100%; padding:8px 12px; border-radius:8px; background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.1); color:inherit; font-size:14px;">
                    <p style="font-size:11px; opacity:0.4; margin-top:4px;"><i class="fas fa-info-circle"></i> ผู้รับต้องออนไลน์อยู่ในเซิร์ฟเวอร์</p>
                </div>
                <div style="font-size:12px; opacity:0.5; text-align:center; margin-top:4px;">
                    ยอดรวม: <strong id="swal-total">${formatMoney(price)}</strong>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-bolt mr-1"></i> ยืนยันซื้อ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: 'var(--color-primary)',
        background: 'var(--color-surface)',
        color: 'var(--color-text)',
        didOpen: () => {
            window.formatMoney = (n) => parseFloat(n).toLocaleString('th-TH', {minimumFractionDigits:2}) + ' บาท';
            window.toggleGift = () => {
                const checked = document.getElementById('swal-gift').checked;
                document.getElementById('gift-section').style.display = checked ? 'block' : 'none';
            };
        },
        preConfirm: () => {
            const qty = isOnePerUser ? 1 : Math.max(1, Math.min(99, parseInt(document.getElementById('swal-qty')?.value) || 1));
            const isGift = document.getElementById('swal-gift').checked;
            const giftTo = isGift ? document.getElementById('swal-gift-to').value.trim() : '';
            if (isGift && !giftTo) {
                Swal.showValidationMessage('กรุณากรอกชื่อผู้เล่นที่ต้องการส่งของขวัญ');
                return false;
            }
            return { qty, giftTo };
        }
    }).then(result => {
        if (result.isConfirmed) {
            buyNow(productId, serverId, result.value.qty, result.value.giftTo);
        }
    });
}

function formatMoney(n) {
    return parseFloat(n).toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท';
}

function buyNow(productId, serverId, quantity, giftTo) {
    const btn = Swal.getConfirmButton?.();
    Swal.fire({ title: 'กำลังดำเนินการ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch('<?= url('api/shop/buynow') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
        body: JSON.stringify({ product_id: productId, server_id: serverId, quantity: quantity, gift_to: giftTo || '' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: data.message, timer: 2000, showConfirmButton: false })
                .then(() => { if (data.redirect) window.location.href = data.redirect; });
        } else {
            Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: data.message });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'กรุณาลองใหม่อีกครั้ง' }));
}
</script>

<?php include BASE_PATH . '/layout/footer.php'; ?>
