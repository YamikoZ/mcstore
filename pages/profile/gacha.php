<?php
$pageTitle = 'ประวัติกาชา';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$perPage      = 20;
$page         = max(1, (int)($_GET['page'] ?? 1));
$totalHistory = (int)($db->fetch("SELECT COUNT(*) AS n FROM gacha_history WHERE username = ?", [$user['username']])['n'] ?? 0);
$totalPages   = max(1, (int)ceil($totalHistory / $perPage));
$page         = min($page, $totalPages);
$offset       = ($page - 1) * $perPage;

$history = $db->fetchAll(
    "SELECT gh.*, gc.name AS crate_name, gr.name AS reward_name, gr.rarity, gc.server_id, s.name AS server_name
     FROM gacha_history gh
     JOIN gacha_crates gc ON gh.crate_id = gc.id
     JOIN gacha_rewards gr ON gh.reward_id = gr.id
     JOIN servers s ON gc.server_id = s.id
     WHERE gh.username = ? ORDER BY gh.created_at DESC LIMIT ? OFFSET ?",
    [$user['username'], $perPage, $offset]
);

$rarityConfig = [
    'common'    => ['color' => '#9CA3AF', 'bg' => 'rgba(156,163,175,.1)',  'label' => 'Common',    'icon' => 'fa-circle'],
    'uncommon'  => ['color' => '#34D399', 'bg' => 'rgba(52,211,153,.1)',   'label' => 'Uncommon',  'icon' => 'fa-circle-dot'],
    'rare'      => ['color' => '#60A5FA', 'bg' => 'rgba(96,165,250,.12)',  'label' => 'Rare',      'icon' => 'fa-gem'],
    'epic'      => ['color' => '#A78BFA', 'bg' => 'rgba(167,139,250,.12)', 'label' => 'Epic',      'icon' => 'fa-star'],
    'legendary' => ['color' => '#FBBF24', 'bg' => 'rgba(251,191,36,.12)',  'label' => 'Legendary', 'icon' => 'fa-crown'],
];

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-4xl mx-auto space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-dice" style="color: var(--color-secondary);"></i> ประวัติกาชา
            </h1>
            <p class="text-xs opacity-40 mt-0.5"><?= $totalHistory ?> รายการทั้งหมด</p>
        </div>
        <?php if (Settings::get('gacha_enabled', '1') === '1'): ?>
        <a href="<?= url('gacha') ?>" class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold">
            <i class="fas fa-dice mr-1.5"></i> หมุนอีกครั้ง
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($history)): ?>
        <div class="card p-16 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 opacity-20"
                 style="background: var(--color-surface-dark);">
                <i class="fas fa-dice"></i>
            </div>
            <p class="font-semibold opacity-50">ยังไม่มีประวัติการหมุนกาชา</p>
        </div>
    <?php else: ?>

        <!-- Rarity Legend -->
        <div class="flex flex-wrap gap-2">
            <?php foreach ($rarityConfig as $key => $r): ?>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                  style="background: <?= $r['bg'] ?>; color: <?= $r['color'] ?>;">
                <i class="fas <?= $r['icon'] ?> text-xs"></i> <?= $r['label'] ?>
            </span>
            <?php endforeach; ?>
        </div>

        <!-- History List -->
        <div class="space-y-2">
            <?php foreach ($history as $h):
                $r = $rarityConfig[$h['rarity']] ?? $rarityConfig['common'];
            ?>
            <div class="card p-4 flex items-center gap-4 rarity-<?= e($h['rarity']) ?>" style="transition: transform .15s;" onmouseover="this.style.transform='translateX(3px)'" onmouseout="this.style.transform=''">
                <!-- Rarity Icon -->
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-sm"
                     style="background: <?= $r['bg'] ?>; color: <?= $r['color'] ?>; box-shadow: 0 0 12px <?= $r['color'] ?>30;">
                    <i class="fas <?= $r['icon'] ?>"></i>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm" style="color: <?= $r['color'] ?>;"><?= e($h['reward_name']) ?></p>
                    <p class="text-xs opacity-50 mt-0.5">
                        <i class="fas fa-box text-xs mr-1"></i><?= e($h['crate_name']) ?>
                        <span class="mx-1.5 opacity-30">·</span>
                        <i class="fas fa-server text-xs mr-1"></i><?= e($h['server_name']) ?>
                    </p>
                </div>

                <!-- Rarity Badge + Time -->
                <div class="text-right shrink-0">
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold"
                          style="background: <?= $r['bg'] ?>; color: <?= $r['color'] ?>;">
                        <?= $r['label'] ?>
                    </span>
                    <p class="text-xs opacity-30 mt-1"><?= timeAgo($h['created_at']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?= paginationHtml($page, $totalPages, url('profile/gacha')) ?>

    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
