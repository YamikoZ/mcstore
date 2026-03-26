<?php
$pageTitle = 'กระเป๋าเงิน';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$ledger = $db->fetchAll(
    "SELECT * FROM wallet_ledger WHERE username = ? ORDER BY created_at DESC LIMIT 50",
    [$user['username']]
);

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-wallet mr-2" style="color: var(--color-accent);"></i> กระเป๋าเงิน</h1>

    <div class="card p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-60">ยอดเงินคงเหลือ</p>
                <p class="text-3xl font-bold" style="color: var(--color-accent);" data-balance><?= formatMoney($user['balance']) ?></p>
            </div>
            <a href="<?= url('topup') ?>" class="btn-primary px-6 py-3 rounded-lg font-bold">
                <i class="fas fa-plus mr-2"></i> เติมเงิน
            </a>
        </div>
    </div>

    <div class="card p-6">
        <h3 class="font-bold text-lg mb-4"><i class="fas fa-list mr-2"></i> ประวัติรายการ</h3>
        <?php if (empty($ledger)): ?>
            <p class="text-center opacity-60 py-4">ยังไม่มีรายการ</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left py-2">วันที่</th>
                            <th class="text-left py-2">รายละเอียด</th>
                            <th class="text-right py-2">จำนวน</th>
                            <th class="text-right py-2">คงเหลือ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ledger as $entry): ?>
                            <tr class="border-b border-white/5">
                                <td class="py-2 text-xs opacity-60"><?= timeAgo($entry['created_at']) ?></td>
                                <td class="py-2"><?= e($entry['note']) ?></td>
                                <td class="py-2 text-right font-bold <?= $entry['type'] === 'credit' ? 'text-green-400' : 'text-red-400' ?>">
                                    <?= $entry['type'] === 'credit' ? '+' : '-' ?><?= formatMoney($entry['amount']) ?>
                                </td>
                                <td class="py-2 text-right opacity-60"><?= formatMoney($entry['balance_after']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
