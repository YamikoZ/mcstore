<?php
$pageTitle = 'กระเป๋าเงิน';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$perPage     = 20;
$page        = max(1, (int)($_GET['page'] ?? 1));
$totalLedger = (int)$db->fetch("SELECT COUNT(*) AS n FROM wallet_ledger WHERE username = ?", [$user['username']])['n'];
$totalPages  = max(1, (int)ceil($totalLedger / $perPage));
$page        = min($page, $totalPages);
$offset      = ($page - 1) * $perPage;

$ledger = $db->fetchAll(
    "SELECT * FROM wallet_ledger WHERE username = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
    [$user['username'], $perPage, $offset]
);

// Summary
$totalCredit = (float)($db->fetch("SELECT COALESCE(SUM(amount),0) AS s FROM wallet_ledger WHERE username = ? AND type='credit'", [$user['username']])['s'] ?? 0);
$totalDebit  = (float)($db->fetch("SELECT COALESCE(SUM(amount),0) AS s FROM wallet_ledger WHERE username = ? AND type='debit'",  [$user['username']])['s'] ?? 0);

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-4xl mx-auto space-y-5">

    <!-- Header -->
    <h1 class="text-xl font-bold flex items-center gap-2">
        <i class="fas fa-wallet" style="color: var(--color-accent);"></i> กระเป๋าเงิน
    </h1>

    <!-- Balance Card -->
    <div class="relative overflow-hidden rounded-2xl p-6" style="background: linear-gradient(135deg, var(--color-surface) 0%, var(--color-surface-dark) 100%); border: 1px solid var(--color-border);">
        <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 80% 50%, var(--color-accent) 0%, transparent 60%);"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center gap-5">
            <div class="flex-1">
                <p class="text-xs opacity-50 mb-1">ยอดเงินคงเหลือ</p>
                <p class="text-4xl font-bold" style="color: var(--color-accent);" data-balance><?= formatMoney($user['balance']) ?></p>
            </div>
            <?php if (Settings::get('topup_enabled', '1') === '1'): ?>
            <a href="<?= url('topup') ?>" class="btn-primary px-6 py-3 rounded-xl font-bold text-sm shrink-0">
                <i class="fas fa-plus mr-2"></i> เติมเงิน
            </a>
            <?php endif; ?>
        </div>

        <!-- Mini Stats -->
        <div class="relative grid grid-cols-2 gap-4 mt-5 pt-5 border-t border-white/10">
            <div>
                <p class="text-xs opacity-40 mb-0.5">รับเข้าทั้งหมด</p>
                <p class="font-bold text-green-400">+<?= formatMoney($totalCredit) ?></p>
            </div>
            <div>
                <p class="text-xs opacity-40 mb-0.5">ใช้ไปทั้งหมด</p>
                <p class="font-bold text-red-400">−<?= formatMoney($totalDebit) ?></p>
            </div>
        </div>
    </div>

    <!-- Ledger -->
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between" style="background: var(--color-surface-dark);">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fas fa-list text-xs opacity-60"></i> ประวัติรายการ
            </h3>
            <span class="text-xs opacity-40"><?= $totalLedger ?> รายการ</span>
        </div>

        <?php if (empty($ledger)): ?>
            <div class="p-12 text-center opacity-40">
                <i class="fas fa-scroll text-3xl mb-3"></i>
                <p class="text-sm">ยังไม่มีรายการ</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-white/5">
                <?php foreach ($ledger as $entry):
                    $isCredit = $entry['type'] === 'credit';
                ?>
                <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-white/3 transition">
                    <!-- Icon -->
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-sm"
                         style="background: <?= $isCredit ? 'rgba(74,222,128,.12)' : 'rgba(248,113,113,.12)' ?>; color: <?= $isCredit ? '#4ade80' : '#f87171' ?>;">
                        <i class="fas <?= $isCredit ? 'fa-arrow-down-left' : 'fa-arrow-up-right' ?>"></i>
                    </div>
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"><?= e($entry['note']) ?></p>
                        <p class="text-xs opacity-40"><?= timeAgo($entry['created_at']) ?></p>
                    </div>
                    <!-- Amount / Balance -->
                    <div class="text-right shrink-0">
                        <p class="font-bold text-sm <?= $isCredit ? 'text-green-400' : 'text-red-400' ?>">
                            <?= $isCredit ? '+' : '−' ?><?= formatMoney($entry['amount']) ?>
                        </p>
                        <p class="text-xs opacity-40"><?= formatMoney($entry['balance_after']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?= paginationHtml($page, $totalPages, url('profile/wallet')) ?>

</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
