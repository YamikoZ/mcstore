<?php
$pageTitle = 'ตะกร้าสินค้า';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

// Get cart items from session
$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$total = 0;

foreach ($cart as $key => $item) {
    $product = $db->fetch("SELECT p.*, s.name AS server_name FROM products p JOIN servers s ON p.server_id = s.id WHERE p.id = ?", [$item['product_id']]);
    if ($product && $product['is_active']) {
        $subtotal = $product['price'] * $item['quantity'];
        $cartItems[] = [
            'key'       => $key,
            'product'   => $product,
            'server_id' => $item['server_id'],
            'quantity'  => $item['quantity'],
            'subtotal'  => $subtotal,
        ];
        $total += $subtotal;
    }
}

include BASE_PATH . '/layout/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-shopping-cart mr-2" style="color: var(--color-primary);"></i> ตะกร้าสินค้า</h1>

    <?php if (empty($cartItems)): ?>
        <div class="card p-12 text-center">
            <i class="fas fa-shopping-cart text-5xl mb-4 opacity-20"></i>
            <p class="text-lg opacity-60 mb-4">ตะกร้าว่างเปล่า</p>
            <a href="<?= url('shop') ?>" class="btn-primary px-6 py-3 rounded-lg font-bold inline-block">
                <i class="fas fa-store mr-2"></i> ไปเลือกสินค้า
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-3 mb-6">
            <?php foreach ($cartItems as $item): ?>
                <div class="card p-4 flex items-center gap-4">
                    <div class="w-16 h-16 rounded-lg flex items-center justify-center text-2xl shrink-0" style="background: var(--color-bg);">
                        <?php if ($item['product']['image']): ?>
                            <img src="<?= e($item['product']['image']) ?>" class="w-full h-full object-cover rounded-lg">
                        <?php else: ?>
                            <i class="fas fa-box" style="color: var(--color-primary);"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold"><?= e($item['product']['name']) ?></h3>
                        <p class="text-xs opacity-50"><?= e($item['product']['server_name']) ?></p>
                        <p class="text-sm mt-1" style="color: var(--color-accent);"><?= formatMoney($item['product']['price']) ?> x <?= (int)$item['quantity'] ?></p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-bold text-lg" style="color: var(--color-accent);"><?= formatMoney($item['subtotal']) ?></p>
                        <button onclick="removeFromCart('<?= e($item['key']) ?>')" class="text-red-400 text-sm mt-1 hover:underline">
                            <i class="fas fa-trash mr-1"></i> ลบ
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary -->
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="text-lg">รวมทั้งหมด</span>
                <span class="text-2xl font-bold" style="color: var(--color-accent);"><?= formatMoney($total) ?></span>
            </div>
            <div class="flex items-center justify-between mb-4 text-sm opacity-70">
                <span>ยอดเงินคงเหลือ</span>
                <span data-balance><?= formatMoney($user['balance']) ?></span>
            </div>
            <?php if ($user['balance'] >= $total): ?>
                <a href="<?= url('checkout') ?>" class="btn-primary block text-center py-3 rounded-lg font-bold text-lg">
                    <i class="fas fa-credit-card mr-2"></i> ชำระเงิน (<?= formatMoney($total) ?>)
                </a>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-red-400 mb-3"><i class="fas fa-exclamation-triangle mr-1"></i> เงินไม่พอ ต้องเติมอีก <?= formatMoney($total - $user['balance']) ?></p>
                    <a href="<?= url('topup') ?>" class="btn-primary inline-block px-6 py-3 rounded-lg font-bold">
                        <i class="fas fa-coins mr-2"></i> เติมเงิน
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
