<?php
$pageTitle = 'รีดีมโค้ด';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('redeem'); }
    
    $code = strtoupper(trim($_POST['code'] ?? ''));
    if (empty($code)) { flash('error', 'กรุณากรอกโค้ด'); redirect('redeem'); }
    
    if (!rateLimitCheck('redeem', 5, 60)) {
        flash('error', 'ลองมากเกินไป กรุณารอสักครู่');
        redirect('redeem');
    }
    
    $redeemCode = $db->fetch("SELECT * FROM redeem_codes WHERE code = ? AND is_active = 1", [$code]);
    
    if (!$redeemCode) {
        flash('error', 'โค้ดไม่ถูกต้องหรือหมดอายุ');
        redirect('redeem');
    }
    
    // Check expiry
    if ($redeemCode['expires_at'] && strtotime($redeemCode['expires_at']) < time()) {
        flash('error', 'โค้ดนี้หมดอายุแล้ว');
        redirect('redeem');
    }
    
    // Check max uses
    if ($redeemCode['max_uses'] > 0 && $redeemCode['used_count'] >= $redeemCode['max_uses']) {
        flash('error', 'โค้ดนี้ถูกใช้ครบจำนวนแล้ว');
        redirect('redeem');
    }
    
    // Check if user already used this code
    $alreadyUsed = $db->fetch("SELECT id FROM redeem_usage WHERE code_id = ? AND username = ?", [$redeemCode['id'], $user['username']]);
    if ($alreadyUsed) {
        flash('error', 'คุณใช้โค้ดนี้ไปแล้ว');
        redirect('redeem');
    }
    
    $db->beginTransaction();
    try {
        $rewardType = $redeemCode['reward_type'];
        $rewardValue = $redeemCode['reward_value'];
        
        if ($rewardType === 'balance') {
            // Add balance
            $amount = (float)$rewardValue;
            $db->execute("UPDATE users SET balance = balance + ? WHERE id = ?", [$amount, $user['id']]);
            $db->execute(
                "INSERT INTO wallet_ledger (username, type, amount, balance_after, reference, note) 
                 VALUES (?, 'credit', ?, (SELECT balance FROM users WHERE id = ?), ?, ?)",
                [$user['username'], $amount, $user['id'], 'redeem#' . $redeemCode['id'], "รีดีมโค้ด: {$code}"]
            );
            $msg = "ได้รับ " . formatMoney($amount) . "!";
        } elseif ($rewardType === 'command') {
            // Execute command via delivery queue
            $commands = json_decode($rewardValue, true) ?: [$rewardValue];
            $serverId = $redeemCode['server_id'] ?? 1;
            foreach ($commands as $cmd) {
                $finalCmd = str_replace('{player}', $user['username'], $cmd);
                $db->execute(
                    "INSERT INTO delivery_queue (username, server_id, player_name, command, item_name) VALUES (?, ?, ?, ?, ?)",
                    [$user['username'], $serverId, $user['username'], $finalCmd, 'ไอเทมรีดีม']
                );
            }
            $msg = "รับไอเทมสำเร็จ! รอรับในเกม";
        } else {
            $msg = "รีดีมสำเร็จ!";
        }
        
        // Record usage
        $db->execute("INSERT INTO redeem_usage (code_id, username) VALUES (?, ?)", [$redeemCode['id'], $user['username']]);
        $db->execute("UPDATE redeem_codes SET used_count = used_count + 1 WHERE id = ?", [$redeemCode['id']]);
        
        $db->commit();
        auditLog($user['id'], 'redeem', "Redeemed code: {$code}");
        createNotification($user['id'], 'รีดีมสำเร็จ', $msg, 'success');
        flash('success', $msg);
    } catch (Exception $e) {
        $db->rollback();
        flash('error', 'เกิดข้อผิดพลาด');
    }
    redirect('redeem');
}

// Redeem history
$history = $db->fetchAll(
    "SELECT ru.*, rc.code, rc.reward_type, rc.reward_value FROM redeem_usage ru 
     JOIN redeem_codes rc ON ru.code_id = rc.id 
     WHERE ru.username = ? ORDER BY ru.created_at DESC LIMIT 20",
    [$user['username']]
);

include BASE_PATH . '/layout/header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-gift mr-2" style="color: var(--color-accent);"></i> รีดีมโค้ด</h1>

    <div class="card p-6 mb-6">
        <form method="POST" class="flex gap-3">
            <?= csrf_field() ?>
            <input type="text" name="code" placeholder="กรอกโค้ดที่นี่" required
                   class="flex-1 px-4 py-3 rounded-lg border-0 focus:ring-2 focus:outline-none uppercase"
                   style="background: var(--color-bg); color: var(--color-text); --tw-ring-color: var(--color-primary);">
            <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-bold">
                <i class="fas fa-check mr-2"></i> ใช้โค้ด
            </button>
        </form>
    </div>

    <!-- History -->
    <?php if (!empty($history)): ?>
    <div class="card p-6">
        <h3 class="font-bold mb-4"><i class="fas fa-history mr-2"></i> ประวัติการรีดีม</h3>
        <div class="space-y-2">
            <?php foreach ($history as $h): ?>
                <div class="flex justify-between py-2 border-b border-white/5 text-sm">
                    <div>
                        <span class="font-mono font-bold"><?= e($h['code']) ?></span>
                        <span class="text-xs opacity-50 ml-2"><?= timeAgo($h['used_at']) ?></span>
                    </div>
                    <span class="opacity-70">
                        <?= $h['reward_type'] === 'balance' ? formatMoney($h['reward_value']) : 'ไอเทม' ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
