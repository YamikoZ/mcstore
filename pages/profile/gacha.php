<?php
$pageTitle = 'ประวัติกาชา';
$requireAuth();
$db = Database::getInstance();
$user = Auth::user();

$history = $db->fetchAll(
    "SELECT gh.*, gc.name AS crate_name, gr.name AS reward_name, gr.rarity, gc.server_id, s.name AS server_name 
     FROM gacha_history gh 
     JOIN gacha_crates gc ON gh.crate_id = gc.id 
     JOIN gacha_rewards gr ON gh.reward_id = gr.id 
     JOIN servers s ON gc.server_id = s.id 
     WHERE gh.username = ? ORDER BY gh.created_at DESC LIMIT 50",
    [$user['id']]
);

$rarityColors = ['common' => '#9CA3AF', 'uncommon' => '#34D399', 'rare' => '#60A5FA', 'epic' => '#A78BFA', 'legendary' => '#FBBF24'];

include BASE_PATH . '/layout/profile_header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-dice mr-2" style="color: var(--color-secondary);"></i> ประวัติกาชา</h1>

    <?php if (empty($history)): ?>
        <div class="card p-12 text-center">
            <i class="fas fa-dice text-5xl mb-4 opacity-20"></i>
            <p class="opacity-60">ยังไม่มีประวัติการหมุนกาชา</p>
        </div>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($history as $h): ?>
                <div class="card p-4 flex items-center justify-between">
                    <div>
                        <span class="font-bold" style="color: <?= $rarityColors[$h['rarity']] ?? '#fff' ?>;">
                            <?= e($h['reward_name']) ?>
                        </span>
                        <span class="text-xs opacity-40 ml-2"><?= e(strtoupper($h['rarity'])) ?></span>
                        <p class="text-xs opacity-50 mt-1"><?= e($h['crate_name']) ?> — <?= e($h['server_name']) ?></p>
                    </div>
                    <span class="text-xs opacity-40"><?= timeAgo($h['created_at']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/profile_footer.php'; ?>
