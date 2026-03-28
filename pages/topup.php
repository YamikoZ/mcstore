<?php
$pageTitle = 'เติมเงิน';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

// ─── Fee settings ───
$feeType  = Settings::get('topup_fee_type', 'none');   // percent | fixed | none
$feeValue = (float) Settings::get('topup_fee_value', 0);
$feeMin   = (float) Settings::get('topup_fee_min', 0);
$feeMax   = (float) Settings::get('topup_fee_max', 0);

function calculateFee($amount, $feeType, $feeValue, $feeMin, $feeMax) {
    if ($feeType === 'none' || $feeValue <= 0) return 0;
    if ($feeType === 'fixed') {
        $fee = $feeValue;
    } else { // percent
        $fee = round($amount * $feeValue / 100, 2);
    }
    if ($feeMin > 0 && $fee < $feeMin) $fee = $feeMin;
    if ($feeMax > 0 && $fee > $feeMax) $fee = $feeMax;
    return $fee;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('topup'); }

    $postType = $_POST['post_type'] ?? 'voucher';

    // ═══ Redeem Code ═══
    if ($postType === 'redeem') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        if (empty($code)) { flash('error', 'กรุณากรอกโค้ด'); redirect('topup'); }

        if (!rateLimitCheck('redeem', 5, 60)) {
            flash('error', 'ลองมากเกินไป กรุณารอสักครู่');
            redirect('topup');
        }

        $redeemCode = $db->fetch("SELECT * FROM redeem_codes WHERE code = ? AND is_active = 1", [$code]);

        if (!$redeemCode) {
            flash('error', 'โค้ดไม่ถูกต้องหรือหมดอายุ');
            redirect('topup');
        }

        if ($redeemCode['expires_at'] && strtotime($redeemCode['expires_at']) < time()) {
            flash('error', 'โค้ดนี้หมดอายุแล้ว');
            redirect('topup');
        }

        if ($redeemCode['max_uses'] > 0 && $redeemCode['used_count'] >= $redeemCode['max_uses']) {
            flash('error', 'โค้ดนี้ถูกใช้ครบจำนวนแล้ว');
            redirect('topup');
        }

        $alreadyUsed = $db->fetch("SELECT id FROM redeem_usage WHERE code_id = ? AND username = ?", [$redeemCode['id'], $user['username']]);
        if ($alreadyUsed) {
            flash('error', 'คุณใช้โค้ดนี้ไปแล้ว');
            redirect('topup');
        }

        $db->beginTransaction();
        try {
            $rewardType = $redeemCode['reward_type'];
            $rewardValue = $redeemCode['reward_value'];

            if ($rewardType === 'balance') {
                $amount = (float)$rewardValue;
                $db->execute("UPDATE users SET balance = balance + ? WHERE id = ?", [$amount, $user['id']]);
                $db->execute(
                    "INSERT INTO wallet_ledger (username, type, amount, balance_after, reference, note)
                     VALUES (?, 'credit', ?, (SELECT balance FROM users WHERE id = ?), ?, ?)",
                    [$user['username'], $amount, $user['id'], 'redeem#' . $redeemCode['id'], "รีดีมโค้ด: {$code}"]
                );
                $msg = "ได้รับ " . formatMoney($amount) . "!";
            } elseif ($rewardType === 'command') {
                $serverId   = $redeemCode['server_id'] ?? 1;
                $topupCmds  = buildDeliveryCommands($rewardValue, $user['username'], 1);
                foreach ($topupCmds as $finalCmd) {
                    $db->execute(
                        "INSERT INTO delivery_queue (username, server_id, player_name, command, item_name) VALUES (?, ?, ?, ?, ?)",
                        [$user['username'], $serverId, $user['username'], $finalCmd, 'ไอเทมรีดีม']
                    );
                }
                $msg = "รับไอเทมสำเร็จ! รอรับในเกม";
            } else {
                $msg = "รีดีมสำเร็จ!";
            }

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
        redirect('topup');
    }

    // ═══ TrueWallet Voucher ═══
    $voucherUrl = trim($_POST['voucher_url'] ?? '');
    if (empty($voucherUrl)) {
        flash('error', 'กรุณากรอกลิงก์ซองอั่งเปา');
        redirect('topup');
    }

    // Validate TrueWallet voucher URL format
    if (!preg_match('/^https:\/\/gift\.truemoney\.com\/campaign\/\?v=([a-zA-Z0-9]+)$/', $voucherUrl, $matches)) {
        flash('error', 'ลิงก์ซองอั่งเปาไม่ถูกต้อง');
        redirect('topup');
    }

    if (!rateLimitCheck('topup', 5, 60)) {
        flash('error', 'ลองมากเกินไป กรุณารอสักครู่');
        redirect('topup');
    }

    $voucherCode = $matches[1];

    // Check if voucher already used
    $existing = $db->fetch("SELECT id FROM topup_transactions WHERE gateway_ref = ? AND gateway = 'truewallet'", [$voucherCode]);
    if ($existing) {
        flash('error', 'ซองอั่งเปานี้ถูกใช้ไปแล้ว');
        redirect('topup');
    }

    // Redeem via TrueWallet API — phone from payment_gateways config
    $twGw = $db->fetch("SELECT config_json FROM payment_gateways WHERE id = 'truewallet'");
    $twConfig = json_decode($twGw['config_json'] ?? '{}', true);
    $twPhone = $twConfig['phone'] ?? '';
    $redeemResult = redeemTruewalletVoucher($voucherUrl, $twPhone);

    if ($redeemResult['success']) {
        $grossAmount = $redeemResult['amount'];
        $fee = calculateFee($grossAmount, $feeType, $feeValue, $feeMin, $feeMax);
        $netAmount = max(0, $grossAmount - $fee);

        $db->beginTransaction();
        try {
            // Record transaction (store gross, fee, net)
            $feeNote = $fee > 0 ? "ค่าธรรมเนียม {$fee} บาท" : '';
            $db->execute(
                "INSERT INTO topup_transactions (username, gateway, amount, status, gateway_ref, note) VALUES (?, 'truewallet', ?, 'completed', ?, ?)",
                [$user['username'], $netAmount, $voucherCode, ($redeemResult['reference'] ?? '') . ($feeNote ? " | {$feeNote}" : '')]
            );

            // Add net balance
            $db->execute("UPDATE users SET balance = balance + ? WHERE id = ?", [$netAmount, $user['id']]);

            // Wallet ledger
            $db->execute(
                "INSERT INTO wallet_ledger (username, type, amount, balance_after, reference, note)
                 VALUES (?, 'credit', ?, (SELECT balance FROM users WHERE id = ?), ?, ?)",
                [$user['username'], $netAmount, $user['id'], 'topup#' . $db->lastInsertId(), 'เติมเงิน TrueWallet' . ($feeNote ? " ({$feeNote})" : '')]
            );

            $db->commit();
            auditLog($user['id'], 'topup', "TrueWallet topup: gross={$grossAmount}, fee={$fee}, net={$netAmount} THB");
            $successMsg = "เติมเงิน {$netAmount} บาท สำเร็จ!";
            if ($fee > 0) $successMsg .= " (หักค่าธรรมเนียม {$fee} บาท จากยอด {$grossAmount} บาท)";
            createNotification($user['id'], 'เติมเงินสำเร็จ', $successMsg, 'success', 'profile/wallet');
            flash('success', $successMsg);
        } catch (Exception $e) {
            $db->rollback();
            flash('error', 'เกิดข้อผิดพลาดในการบันทึก');
        }
    } else {
        // Record failed attempt
        $db->execute(
            "INSERT INTO topup_transactions (username, gateway, amount, status, gateway_ref, note) VALUES (?, 'truewallet', 0, 'failed', ?, ?)",
            [$user['username'], $voucherCode, $redeemResult['message'] ?? 'Unknown error']
        );
        flash('error', $redeemResult['message'] ?? 'ไม่สามารถรับซองอั่งเปาได้');
    }
    redirect('topup');
}

// Topup history
$history = $db->fetchAll(
    "SELECT * FROM topup_transactions WHERE username = ? ORDER BY created_at DESC LIMIT 20",
    [$user['username']]
);

// Redeem history
$redeemHistory = $db->fetchAll(
    "SELECT ru.*, rc.code, rc.reward_type, rc.reward_value FROM redeem_usage ru
     JOIN redeem_codes rc ON ru.code_id = rc.id
     WHERE ru.username = ? ORDER BY ru.created_at DESC LIMIT 10",
    [$user['username']]
);

include BASE_PATH . '/layout/header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-coins mr-2" style="color: var(--color-accent);"></i> เติมเงิน</h1>

    <!-- Balance -->
    <div class="card p-6 mb-8" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-60">ยอดเงินคงเหลือ</p>
                <p class="text-3xl font-bold" style="color: var(--color-accent);" data-balance><?= formatMoney($user['balance']) ?></p>
            </div>
            <div class="text-right opacity-20">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4h-4Z"/></svg>
            </div>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

        <!-- ══════ TrueWallet Voucher ══════ -->
        <div class="card p-6" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
            <!-- Header with illustration -->
            <div class="flex items-center gap-4 mb-5">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, #FF6B00, #FF8C38);">
                    <svg width="36" height="36" viewBox="0 0 48 48" fill="none">
                        <!-- Angpao envelope -->
                        <rect x="8" y="6" width="32" height="36" rx="4" fill="#FFD700" opacity="0.3"/>
                        <rect x="10" y="8" width="28" height="32" rx="3" fill="#FF6B00"/>
                        <path d="M10 20 L24 28 L38 20" stroke="#FFD700" stroke-width="2" fill="none"/>
                        <path d="M10 8 L24 18 L38 8" stroke="#FFD700" stroke-width="2" fill="none"/>
                        <circle cx="24" cy="24" r="6" fill="#FFD700"/>
                        <text x="24" y="27" text-anchor="middle" fill="#FF6B00" font-size="8" font-weight="bold">&#3647;</text>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg">TrueWallet ซองอั่งเปา</h3>
                    <p class="text-xs opacity-50">วางลิงก์ซองอั่งเปาเพื่อเติมเงินอัตโนมัติ</p>
                </div>
            </div>

            <?php if ($feeType !== 'none' && $feeValue > 0): ?>
            <!-- Fee info -->
            <div class="rounded-lg px-4 py-3 mb-4 text-sm" style="background: rgba(255,107,0,0.1); border: 1px solid rgba(255,107,0,0.2);">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-info-circle" style="color: #FF8C38;"></i>
                    <span class="font-semibold" style="color: #FF8C38;">ค่าธรรมเนียม</span>
                </div>
                <p class="opacity-80">
                    <?php if ($feeType === 'percent'): ?>
                        หักค่าธรรมเนียม <strong><?= $feeValue ?>%</strong> จากยอดเงิน
                        <?php if ($feeMin > 0): ?>(ขั้นต่ำ <?= number_format($feeMin) ?> บาท)<?php endif; ?>
                        <?php if ($feeMax > 0): ?>(สูงสุด <?= number_format($feeMax) ?> บาท)<?php endif; ?>
                    <?php else: ?>
                        หักค่าธรรมเนียมคงที่ <strong><?= number_format($feeValue) ?> บาท</strong> ต่อรายการ
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="post_type" value="voucher">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1.5">ลิงก์ซองอั่งเปา</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 opacity-40"><i class="fas fa-link"></i></span>
                        <input type="url" name="voucher_url" id="voucher-url" required
                               class="w-full pl-10 pr-4 py-3 rounded-lg focus:ring-2 focus:outline-none"
                               placeholder="https://gift.truemoney.com/campaign/?v=...">
                    </div>
                </div>

                <?php if ($feeType !== 'none' && $feeValue > 0): ?>
                <!-- Fee calculator (JS) -->
                <div id="fee-calc" class="rounded-lg px-4 py-3 mb-4 text-sm hidden" style="background: var(--color-surface-dark);">
                    <div class="flex justify-between mb-1">
                        <span class="opacity-60">ยอดซอง</span>
                        <span id="fee-gross">-</span>
                    </div>
                    <div class="flex justify-between mb-1" style="color: #f87171;">
                        <span>ค่าธรรมเนียม</span>
                        <span id="fee-amount">-</span>
                    </div>
                    <div class="flex justify-between font-bold pt-1 border-t border-white/10" style="color: var(--color-accent);">
                        <span>ได้รับจริง</span>
                        <span id="fee-net">-</span>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="w-full py-3 rounded-xl font-bold text-white transition-all" style="background: linear-gradient(135deg, #FF6B00, #FF8C38); box-shadow: 0 4px 15px rgba(255,107,0,0.3);" onmouseover="this.style.boxShadow='0 6px 25px rgba(255,107,0,0.5)'" onmouseout="this.style.boxShadow='0 4px 15px rgba(255,107,0,0.3)'">
                    <i class="fas fa-check-circle mr-2"></i> รับซองอั่งเปา
                </button>
            </form>

            <!-- How to -->
            <div class="mt-4 pt-4 border-t border-white/10">
                <p class="text-xs font-semibold opacity-50 mb-2"><i class="fas fa-question-circle mr-1"></i> วิธีใช้งาน</p>
                <ol class="text-xs opacity-50 space-y-1 list-decimal list-inside">
                    <li>เปิดแอป TrueWallet &rarr; สร้างซองอั่งเปา</li>
                    <li>คัดลอกลิงก์ซองอั่งเปามาวาง</li>
                    <li>กดปุ่ม "รับซองอั่งเปา" แล้วรอระบบตรวจสอบ</li>
                </ol>
            </div>
        </div>

        <!-- ══════ Redeem Code ══════ -->
        <div class="card p-6" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
            <!-- Header with illustration -->
            <div class="flex items-center gap-4 mb-5">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
                    <svg width="36" height="36" viewBox="0 0 48 48" fill="none">
                        <!-- Gift box -->
                        <rect x="8" y="20" width="32" height="20" rx="3" fill="currentColor" opacity="0.3"/>
                        <rect x="10" y="22" width="28" height="16" rx="2" fill="var(--color-primary)"/>
                        <rect x="22" y="20" width="4" height="20" fill="#FFD700"/>
                        <rect x="8" y="14" width="32" height="8" rx="3" fill="var(--color-secondary)"/>
                        <rect x="22" y="14" width="4" height="8" fill="#FFD700"/>
                        <!-- Ribbon -->
                        <path d="M24 14 C20 8 14 10 16 14" stroke="#FFD700" stroke-width="2" fill="none"/>
                        <path d="M24 14 C28 8 34 10 32 14" stroke="#FFD700" stroke-width="2" fill="none"/>
                        <!-- Stars -->
                        <circle cx="14" cy="28" r="1.5" fill="#FFD700" opacity="0.6"/>
                        <circle cx="34" cy="32" r="1" fill="#FFD700" opacity="0.4"/>
                        <circle cx="18" cy="34" r="1" fill="#FFD700" opacity="0.5"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg">รีดีมโค้ด</h3>
                    <p class="text-xs opacity-50">กรอกโค้ดเพื่อรับเงินหรือไอเทมพิเศษ</p>
                </div>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="post_type" value="redeem">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1.5">กรอกโค้ด</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 opacity-40"><i class="fas fa-ticket-alt"></i></span>
                        <input type="text" name="code" required
                               class="w-full pl-10 pr-4 py-3 rounded-lg focus:ring-2 focus:outline-none uppercase tracking-widest"
                               placeholder="XXXX-XXXX-XXXX"
                               maxlength="50">
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full py-3 rounded-xl font-bold">
                    <i class="fas fa-gift mr-2"></i> ใช้โค้ด
                </button>
            </form>

            <?php if (!empty($redeemHistory)): ?>
            <div class="mt-4 pt-4 border-t border-white/10">
                <p class="text-xs font-semibold opacity-50 mb-2"><i class="fas fa-history mr-1"></i> ประวัติรีดีม</p>
                <?php foreach ($redeemHistory as $h): ?>
                    <div class="flex justify-between items-center py-1.5 text-xs">
                        <span class="font-mono opacity-80"><?= e($h['code']) ?></span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background: rgba(56,189,248,0.15); color: var(--color-primary);">
                            <?= $h['reward_type'] === 'balance' ? formatMoney($h['reward_value']) : 'ไอเทม' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="mt-4 pt-4 border-t border-white/10">
                <p class="text-xs font-semibold opacity-50 mb-2"><i class="fas fa-lightbulb mr-1"></i> แหล่งรับโค้ด</p>
                <ul class="text-xs opacity-50 space-y-1">
                    <li><i class="fab fa-discord mr-1"></i> ติดตามกิจกรรมใน Discord</li>
                    <li><i class="fas fa-calendar-alt mr-1"></i> อีเวนท์พิเศษตามเทศกาล</li>
                    <li><i class="fas fa-trophy mr-1"></i> รางวัลจากการแข่งขัน</li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- History -->
    <div class="card p-6" style="background: var(--gradient-card); border: 1px solid var(--color-border);">
        <h3 class="font-bold text-lg mb-4"><i class="fas fa-history mr-2" style="color: var(--color-primary);"></i> ประวัติการเติมเงิน</h3>
        <?php if (empty($history)): ?>
            <div class="text-center py-8">
                <div class="opacity-20 mb-3">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
                </div>
                <p class="opacity-50">ยังไม่มีประวัติการเติมเงิน</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left py-2 px-2">วันที่</th>
                            <th class="text-right py-2 px-2">จำนวน</th>
                            <th class="text-center py-2 px-2">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $tx): ?>
                            <tr class="border-b border-white/5 hover:bg-white/5 transition">
                                <td class="py-2.5 px-2"><?= e($tx['created_at']) ?></td>
                                <td class="py-2.5 px-2 text-right font-bold" style="color: var(--color-accent);"><?= formatMoney($tx['amount']) ?></td>
                                <td class="py-2.5 px-2 text-center">
                                    <?php
                                    $statusColors = ['completed' => 'text-green-400', 'pending' => 'text-yellow-400', 'failed' => 'text-red-400'];
                                    $statusLabels = ['completed' => 'สำเร็จ', 'pending' => 'รอตรวจสอบ', 'failed' => 'ล้มเหลว'];
                                    $statusIcons  = ['completed' => 'fa-check-circle', 'pending' => 'fa-clock', 'failed' => 'fa-times-circle'];
                                    ?>
                                    <span class="<?= $statusColors[$tx['status']] ?? '' ?>">
                                        <i class="fas <?= $statusIcons[$tx['status']] ?? '' ?> mr-1"></i><?= $statusLabels[$tx['status']] ?? $tx['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($feeType !== 'none' && $feeValue > 0): ?>
<script>
// Fee calculator — show breakdown when user enters an amount hint
(function() {
    const feeType = '<?= $feeType ?>';
    const feeValue = <?= $feeValue ?>;
    const feeMin = <?= $feeMin ?>;
    const feeMax = <?= $feeMax ?>;
    const calcEl = document.getElementById('fee-calc');
    const grossEl = document.getElementById('fee-gross');
    const feeEl = document.getElementById('fee-amount');
    const netEl = document.getElementById('fee-net');
    const urlInput = document.getElementById('voucher-url');

    function calcFee(amount) {
        if (amount <= 0) return 0;
        let fee = feeType === 'fixed' ? feeValue : Math.round(amount * feeValue / 100 * 100) / 100;
        if (feeMin > 0 && fee < feeMin) fee = feeMin;
        if (feeMax > 0 && fee > feeMax) fee = feeMax;
        return fee;
    }

    // Show example fee when URL is pasted
    if (urlInput && calcEl) {
        urlInput.addEventListener('input', function() {
            if (this.value.includes('gift.truemoney.com')) {
                // Show example with common amounts
                calcEl.classList.remove('hidden');
                const ex = [10, 20, 50, 100, 200, 500];
                const amt = ex[Math.floor(Math.random() * ex.length)];
                const fee = calcFee(amt);
                grossEl.textContent = amt.toLocaleString() + ' ฿ (ตัวอย่าง)';
                feeEl.textContent = '-' + fee.toLocaleString() + ' ฿';
                netEl.textContent = (amt - fee).toLocaleString() + ' ฿';
            } else {
                calcEl.classList.add('hidden');
            }
        });
    }
})();
</script>
<?php endif; ?>

<?php
// TrueWallet voucher redemption using Voucher class
function redeemTruewalletVoucher($voucherUrl, $phone) {
    if (empty($phone)) return ['success' => false, 'message' => 'ยังไม่ได้ตั้งค่าเบอร์ TrueWallet'];

    try {
        $voucher = new Voucher($phone, $voucherUrl);

        if (empty($voucher->getVoucherId())) {
            return ['success' => false, 'message' => 'ลิงก์ไม่ถูกต้อง'];
        }

        // Verify first
        $verify = $voucher->verify();
        if (!isset($verify->status->code) || $verify->status->code !== 'SUCCESS') {
            $msg = $verify->status->message ?? 'ซองอั่งเปาไม่ถูกต้องหรือถูกใช้แล้ว';
            if (in_array($verify->status->code ?? '', ['BLOCKED', 'CURL_ERROR', 'EXEC_ERROR'])) {
                $msg = 'ไม่สามารถเชื่อมต่อ TrueWallet ได้ — กรุณาลองใหม่ภายหลัง';
            }
            return ['success' => false, 'message' => $msg];
        }

        // Redeem
        $result = $voucher->redeem();
        if (isset($result->status->code) && $result->status->code === 'SUCCESS') {
            return [
                'success'   => true,
                'amount'    => (float)($result->data->my_ticket->amount_baht ?? 0),
                'reference' => $result->data->voucher->voucher_id ?? '',
            ];
        }

        return ['success' => false, 'message' => $result->status->message ?? 'ไม่สามารถรับซองอั่งเปาได้'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อ TrueWallet ได้'];
    }
}

include BASE_PATH . '/layout/footer.php';
?>
