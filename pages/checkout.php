<?php
$pageTitle = 'ชำระเงิน';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) redirect('cart');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('checkout'); }
    
    // Re-calculate total
    $total = 0;
    $orderItems = [];
    
    foreach ($cart as $item) {
        $product = $db->fetch("SELECT * FROM products p WHERE p.id = ? AND p.is_active = 1", [$item['product_id']]);
        if (!$product) continue;
        
        // Check stock
        if ($product['stock'] !== null && $product['stock'] < $item['quantity']) {
            flash('error', "สินค้า \"{$product['name']}\" มีไม่เพียงพอ");
            redirect('cart');
        }
        
        $subtotal = $product['price'] * $item['quantity'];
        $total += $subtotal;
        $orderItems[] = [
            'product'   => $product,
            'server_id' => $item['server_id'],
            'quantity'  => $item['quantity'],
            'price'     => $product['price'],
            'subtotal'  => $subtotal,
        ];
    }
    
    // Re-fetch user balance
    $user = Auth::user();
    if ($user['balance'] < $total) {
        flash('error', 'ยอดเงินไม่เพียงพอ');
        redirect('cart');
    }
    
    $db->beginTransaction();
    try {
        // Deduct balance
        $db->execute("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?", [$total, $user['id'], $total]);
        
        // Create order
        $db->execute(
            "INSERT INTO orders (username, total, status) VALUES (?, ?, 'processing')",
            [$user['username'], $total]
        );
        $orderId = $db->lastInsertId();
        
        // Create order items + delivery queue
        foreach ($orderItems as $oi) {
            $db->execute(
                "INSERT INTO order_items (order_id, product_id, server_id, name, quantity, price, command) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$orderId, $oi['product']['id'], $oi['server_id'], $oi['product']['name'], $oi['quantity'], $oi['price'], $oi['product']['command']]
            );
            
            // Reduce stock
            if ($oi['product']['stock'] !== null) {
                $db->execute("UPDATE products SET stock = stock - ? WHERE id = ?", [$oi['quantity'], $oi['product']['id']]);
            }
            
            // Queue delivery commands
            if ($oi['product']['command']) {
                $commands = json_decode($oi['product']['command'], true) ?: [$oi['product']['command']];
                for ($q = 0; $q < $oi['quantity']; $q++) {
                    foreach ($commands as $cmd) {
                        $finalCmd = str_replace('{player}', $user['username'], $cmd);
                        $finalCmd = str_replace('{amount}', (string)$oi['quantity'], $finalCmd);
                        $db->execute(
                            "INSERT INTO delivery_queue (order_id, username, server_id, player_name, command, item_name) VALUES (?, ?, ?, ?, ?, ?)",
                            [$orderId, $user['username'], $oi['server_id'], $user['username'], $finalCmd, $oi['product']['name']]
                        );
                    }
                }
            }
        }
        
        // Wallet ledger
        $db->execute(
            "INSERT INTO wallet_ledger (username, type, amount, balance_after, reference, note) 
             VALUES (?, 'debit', ?, (SELECT balance FROM users WHERE id = ?), ?, ?)",
            [$user['username'], $total, $user['id'], 'order#' . $orderId, "สั่งซื้อ #" . $orderId]
        );
        
        // Clear cart
        unset($_SESSION['cart']);
        
        $db->commit();
        
        auditLog($user['id'], 'purchase', "Order #{$orderId}, total: {$total}");
        createNotification($user['id'], 'สั่งซื้อสำเร็จ', "ออเดอร์ #{$orderId} จำนวน " . formatMoney($total), 'success', 'profile/orders');
        flash('success', "สั่งซื้อสำเร็จ! ออเดอร์ #{$orderId}");
        redirect('profile/orders');
    } catch (Exception $e) {
        $db->rollback();
        flash('error', 'เกิดข้อผิดพลาดในการสั่งซื้อ');
        redirect('cart');
    }
}

// Show confirmation page
$cartItems = [];
$total = 0;
foreach ($cart as $item) {
    $product = $db->fetch("SELECT p.*, s.name AS server_name FROM products p JOIN servers s ON p.server_id = s.id WHERE p.id = ?", [$item['product_id']]);
    if ($product) {
        $subtotal = $product['price'] * $item['quantity'];
        $cartItems[] = ['product' => $product, 'quantity' => $item['quantity'], 'subtotal' => $subtotal];
        $total += $subtotal;
    }
}

include BASE_PATH . '/layout/header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-credit-card mr-2" style="color: var(--color-primary);"></i> ยืนยันการชำระเงิน</h1>

    <div class="card p-6 mb-6">
        <h3 class="font-bold mb-4">สรุปรายการ</h3>
        <?php foreach ($cartItems as $item): ?>
            <div class="flex justify-between py-2 border-b border-white/5">
                <span><?= e($item['product']['name']) ?> <span class="text-xs opacity-50">(<?= e($item['product']['server_name']) ?>) x<?= (int)$item['quantity'] ?></span></span>
                <span class="font-semibold"><?= formatMoney($item['subtotal']) ?></span>
            </div>
        <?php endforeach; ?>
        <div class="flex justify-between py-3 text-lg font-bold mt-2">
            <span>รวมทั้งหมด</span>
            <span style="color: var(--color-accent);"><?= formatMoney($total) ?></span>
        </div>
    </div>

    <div class="card p-6 mb-6">
        <div class="flex justify-between text-sm mb-2">
            <span class="opacity-60">ยอดเงินปัจจุบัน</span>
            <span><?= formatMoney($user['balance']) ?></span>
        </div>
        <div class="flex justify-between text-sm mb-2">
            <span class="opacity-60">หักค่าสินค้า</span>
            <span class="text-red-400">-<?= formatMoney($total) ?></span>
        </div>
        <div class="flex justify-between font-bold border-t border-white/10 pt-2">
            <span>คงเหลือหลังซื้อ</span>
            <span style="color: var(--color-accent);"><?= formatMoney($user['balance'] - $total) ?></span>
        </div>
    </div>

    <form method="POST">
        <?= csrf_field() ?>
        <div class="flex gap-3">
            <a href="<?= url('cart') ?>" class="flex-1 text-center py-3 rounded-lg font-bold" style="background: var(--color-surface-dark);">
                <i class="fas fa-arrow-left mr-2"></i> กลับ
            </a>
            <button type="submit" class="flex-1 btn-primary py-3 rounded-lg font-bold text-lg">
                <i class="fas fa-check mr-2"></i> ยืนยันชำระเงิน
            </button>
        </div>
    </form>
</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
