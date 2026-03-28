<?php
$pageTitle = 'ตะกร้าสินค้า';
$requireAuth();
$db   = Database::getInstance();
$user = Auth::user();

$cart      = $_SESSION['cart'] ?? [];
$cartItems = [];
$total     = 0;

foreach ($cart as $item) {
    $product = $db->fetch(
        "SELECT p.*, s.name AS server_name FROM products p
         JOIN servers s ON s.id = p.server_id
         WHERE p.id = ? AND p.is_active = 1",
        [$item['product_id']]
    );
    if (!$product) continue;
    $subtotal    = $product['price'] * $item['quantity'];
    $total      += $subtotal;
    $cartItems[] = [
        'product'   => $product,
        'server_id' => $item['server_id'],
        'quantity'  => $item['quantity'],
        'subtotal'  => $subtotal,
    ];
}

include BASE_PATH . '/layout/header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">
        <i class="fas fa-shopping-cart mr-2" style="color: var(--color-primary);"></i> ตะกร้าสินค้า
    </h1>

    <?php if (empty($cartItems)): ?>
        <div class="card p-10 text-center opacity-60">
            <i class="fas fa-shopping-cart text-5xl mb-4 block"></i>
            <p class="text-lg font-semibold mb-2">ตะกร้าว่างเปล่า</p>
            <p class="text-sm mb-6">เพิ่มสินค้าจากร้านค้าก่อนนะ</p>
            <a href="<?= url('shop') ?>" class="btn-primary px-6 py-2 rounded-lg font-semibold">
                <i class="fas fa-store mr-2"></i> ไปร้านค้า
            </a>
        </div>
    <?php else: ?>
        <div class="card p-4 mb-4">
            <?php foreach ($cartItems as $idx => $ci): ?>
                <div class="flex items-center gap-3 py-3 <?= $idx > 0 ? 'border-t border-white/5' : '' ?>" id="cart-row-<?= (int)$ci['product']['id'] ?>">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm truncate"><?= e($ci['product']['name']) ?></div>
                        <div class="text-xs opacity-50"><?= e($ci['product']['server_name']) ?></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="cartQty(<?= (int)$ci['product']['id'] ?>, '<?= e($ci['server_id']) ?>', -1)"
                                class="w-7 h-7 rounded-lg text-sm font-bold flex items-center justify-center"
                                style="background: var(--color-surface-dark);">−</button>
                        <span class="w-8 text-center font-semibold text-sm" id="qty-<?= (int)$ci['product']['id'] ?>"><?= (int)$ci['quantity'] ?></span>
                        <button onclick="cartQty(<?= (int)$ci['product']['id'] ?>, '<?= e($ci['server_id']) ?>', 1)"
                                class="w-7 h-7 rounded-lg text-sm font-bold flex items-center justify-center"
                                style="background: var(--color-surface-dark);">+</button>
                    </div>
                    <div class="text-right min-w-[70px]">
                        <div class="font-semibold text-sm" style="color: var(--color-accent);" id="subtotal-<?= (int)$ci['product']['id'] ?>"><?= formatMoney($ci['subtotal']) ?></div>
                        <div class="text-xs opacity-40"><?= formatMoney($ci['product']['price']) ?> x<?= (int)$ci['quantity'] ?></div>
                    </div>
                    <button onclick="cartRemove(<?= (int)$ci['product']['id'] ?>, '<?= e($ci['server_id']) ?>')"
                            class="text-red-400 hover:text-red-300 transition ml-1 text-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card p-4 mb-4">
            <div class="flex justify-between font-bold text-lg">
                <span>รวมทั้งหมด</span>
                <span id="cart-total" style="color: var(--color-accent);"><?= formatMoney($total) ?></span>
            </div>
            <div class="flex justify-between text-sm opacity-50 mt-1">
                <span>ยอดเงินคงเหลือ</span>
                <span><?= formatMoney($user['balance']) ?></span>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="<?= url('shop') ?>" class="flex-1 text-center py-3 rounded-lg font-semibold text-sm"
               style="background: var(--color-surface-dark);">
                <i class="fas fa-plus mr-1"></i> เพิ่มสินค้า
            </a>
            <button onclick="cartClear()" class="px-4 py-3 rounded-lg font-semibold text-sm text-red-400"
                    style="background: var(--color-surface-dark);">
                <i class="fas fa-trash mr-1"></i> ล้าง
            </button>
            <a href="<?= url('checkout') ?>" class="flex-1 text-center py-3 rounded-lg font-bold btn-primary text-sm">
                <i class="fas fa-credit-card mr-1"></i> ชำระเงิน
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
var CART_URL  = '<?= e(url("api/cart/update")) ?>';
var CART_CSRF = '<?= e($_SESSION['csrf_token'] ?? '') ?>';

function cartApi(payload) {
    return fetch(CART_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CART_CSRF },
        body: JSON.stringify(Object.assign({ _csrf: CART_CSRF }, payload))
    }).then(r => r.json());
}

function cartQty(productId, serverId, delta) {
    var qtyEl = document.getElementById('qty-' + productId);
    var newQty = Math.max(1, parseInt(qtyEl.textContent) + delta);
    cartApi({ action: 'update', product_id: productId, server_id: serverId, quantity: newQty })
        .then(function(d) { if (d.success) location.reload(); });
}

function cartRemove(productId, serverId) {
    cartApi({ action: 'remove', product_id: productId, server_id: serverId })
        .then(function(d) {
            if (d.success) {
                var row = document.getElementById('cart-row-' + productId);
                if (row) row.remove();
                location.reload();
            }
        });
}

function cartClear() {
    Swal.fire({
        title: 'ล้างตะกร้า?',
        text: 'ลบสินค้าทั้งหมดออกจากตะกร้า',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ล้างเลย',
        cancelButtonText: 'ยกเลิก',
    }).then(function(r) {
        if (r.isConfirmed) {
            cartApi({ action: 'clear' }).then(function() { location.reload(); });
        }
    });
}
</script>

<?php include BASE_PATH . '/layout/footer.php'; ?>
