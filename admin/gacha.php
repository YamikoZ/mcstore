<?php
$pageTitle = 'จัดการกาชา - แอดมิน';
$db = Database::getInstance();
$servers = $db->fetchAll("SELECT * FROM servers WHERE is_active = 1 ORDER BY display_order");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('admin/gacha'); }

    $action = $_POST['action'] ?? '';

    // === CRATE CRUD ===
    if ($action === 'create_crate') {
        $db->execute(
            "INSERT INTO gacha_crates (server_id, name, description, image, price, crate_type, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$_POST['server_id'], trim($_POST['name']), trim($_POST['description'] ?? ''), trim($_POST['image'] ?? ''), (float)$_POST['price'], trim($_POST['crate_type'] ?? 'normal'), isset($_POST['is_active']) ? 1 : 0, (int)($_POST['display_order'] ?? 0)]
        );
        auditLog(Auth::id(), 'admin_gacha_create_crate', 'Created crate: ' . $_POST['name']);
        flash('success', 'เพิ่มกล่องกาชาเรียบร้อย');
        redirect('admin/gacha');
    }

    if ($action === 'update_crate') {
        $id = (int)$_POST['id'];
        $db->execute(
            "UPDATE gacha_crates SET server_id=?, name=?, description=?, image=?, price=?, crate_type=?, is_active=?, display_order=? WHERE id=?",
            [$_POST['server_id'], trim($_POST['name']), trim($_POST['description'] ?? ''), trim($_POST['image'] ?? ''), (float)$_POST['price'], trim($_POST['crate_type'] ?? 'normal'), isset($_POST['is_active']) ? 1 : 0, (int)($_POST['display_order'] ?? 0), $id]
        );
        auditLog(Auth::id(), 'admin_gacha_update_crate', 'Updated crate #' . $id);
        flash('success', 'อัพเดทกล่องกาชาเรียบร้อย');
        redirect('admin/gacha');
    }

    if ($action === 'delete_crate') {
        $id = (int)$_POST['id'];
        $db->execute("DELETE FROM gacha_crates WHERE id = ?", [$id]);
        auditLog(Auth::id(), 'admin_gacha_delete_crate', 'Deleted crate #' . $id);
        flash('success', 'ลบกล่องกาชาเรียบร้อย');
        redirect('admin/gacha');
    }

    // === REWARD CRUD ===
    if ($action === 'create_reward') {
        $crateId = (int)$_POST['crate_id'];
        $db->execute(
            "INSERT INTO gacha_rewards (crate_id, name, image, rarity, weight, command, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$crateId, trim($_POST['name']), trim($_POST['image'] ?? ''), $_POST['rarity'], (int)$_POST['weight'], trim($_POST['command']), isset($_POST['is_active']) ? 1 : 0]
        );
        auditLog(Auth::id(), 'admin_gacha_create_reward', 'Created reward: ' . $_POST['name']);
        flash('success', 'เพิ่มรางวัลเรียบร้อย');
        redirect('admin/gacha?rewards=' . $crateId);
    }

    if ($action === 'update_reward') {
        $id = (int)$_POST['id'];
        $crateId = (int)$_POST['crate_id'];
        $db->execute(
            "UPDATE gacha_rewards SET name=?, image=?, rarity=?, weight=?, command=?, is_active=? WHERE id=?",
            [trim($_POST['name']), trim($_POST['image'] ?? ''), $_POST['rarity'], (int)$_POST['weight'], trim($_POST['command']), isset($_POST['is_active']) ? 1 : 0, $id]
        );
        auditLog(Auth::id(), 'admin_gacha_update_reward', 'Updated reward #' . $id);
        flash('success', 'อัพเดทรางวัลเรียบร้อย');
        redirect('admin/gacha?rewards=' . $crateId);
    }

    if ($action === 'delete_reward') {
        $id = (int)$_POST['id'];
        $crateId = (int)$_POST['crate_id'];
        $db->execute("DELETE FROM gacha_rewards WHERE id = ?", [$id]);
        auditLog(Auth::id(), 'admin_gacha_delete_reward', 'Deleted reward #' . $id);
        flash('success', 'ลบรางวัลเรียบร้อย');
        redirect('admin/gacha?rewards=' . $crateId);
    }
}

// View mode
$viewRewards = (int)($_GET['rewards'] ?? 0);
$editCrate = (int)($_GET['edit_crate'] ?? 0);
$editReward = (int)($_GET['edit_reward'] ?? 0);
$createCrate = isset($_GET['create_crate']);
$createReward = isset($_GET['create_reward']) ? (int)$_GET['create_reward'] : 0;

$crates = $db->fetchAll("SELECT gc.*, s.name as server_name, (SELECT COUNT(*) FROM gacha_rewards WHERE crate_id = gc.id) as reward_count FROM gacha_crates gc LEFT JOIN servers s ON gc.server_id = s.id ORDER BY gc.server_id, gc.display_order");

$editCrateData = $editCrate ? $db->fetch("SELECT * FROM gacha_crates WHERE id = ?", [$editCrate]) : null;

$rewards = [];
$crateForRewards = null;
if ($viewRewards) {
    $crateForRewards = $db->fetch("SELECT gc.*, s.name as server_name FROM gacha_crates gc LEFT JOIN servers s ON gc.server_id = s.id WHERE gc.id = ?", [$viewRewards]);
    $rewards = $db->fetchAll("SELECT * FROM gacha_rewards WHERE crate_id = ? ORDER BY weight DESC", [$viewRewards]);
}

$editRewardData = $editReward ? $db->fetch("SELECT * FROM gacha_rewards WHERE id = ?", [$editReward]) : null;

$rarityColors = ['common' => 'text-gray-300', 'rare' => 'text-blue-400', 'epic' => 'text-purple-400', 'mythic' => 'text-pink-400', 'legendary' => 'text-yellow-400'];

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-dice mr-2" style="color: var(--color-accent);"></i>จัดการกาชา</h1>
            <p class="text-sm opacity-60 mt-1"><?= $viewRewards ? 'รางวัลใน: ' . e($crateForRewards['name'] ?? '') : count($crates) . ' กล่องกาชา' ?></p>
        </div>
        <div class="flex gap-2">
            <?php if ($viewRewards): ?>
                <a href="<?= url('admin/gacha') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
                <a href="?create_reward=<?= $viewRewards ?>" class="btn-primary px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> เพิ่มรางวัล</a>
            <?php else: ?>
                <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
                <a href="?create_crate=1" class="btn-primary px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> เพิ่มกล่องกาชา</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($createCrate || $editCrateData): ?>
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4"><?= $editCrateData ? 'แก้ไขกล่อง' : 'เพิ่มกล่องกาชาใหม่' ?></h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editCrateData ? 'update_crate' : 'create_crate' ?>">
            <?php if ($editCrateData): ?><input type="hidden" name="id" value="<?= $editCrateData['id'] ?>"><?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อกล่อง <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="<?= e($editCrateData['name'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">เซิร์ฟเวอร์ <span class="text-red-400">*</span></label>
                    <select name="server_id" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                        <?php foreach ($servers as $s): ?>
                            <option value="<?= e($s['id']) ?>" <?= ($editCrateData['server_id'] ?? '') === $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ราคาต่อครั้ง <span class="text-red-400">*</span></label>
                    <input type="number" name="price" step="0.01" min="0" value="<?= e($editCrateData['price'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ประเภท</label>
                    <input type="text" name="crate_type" value="<?= e($editCrateData['crate_type'] ?? 'normal') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">รูปภาพ (URL)</label>
                    <input type="text" name="image" value="<?= e($editCrateData['image'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ลำดับ</label>
                    <input type="number" name="display_order" value="<?= e($editCrateData['display_order'] ?? '0') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);"><?= e($editCrateData['description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= ($editCrateData['is_active'] ?? 1) ? 'checked' : '' ?>> <span class="text-sm">เปิดใช้งาน</span></label>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg text-sm font-semibold"><i class="fas fa-save mr-1"></i> บันทึก</button>
                <a href="<?= url('admin/gacha') ?>" class="px-6 py-2 rounded-lg text-sm border border-white/10 hover:bg-white/5">ยกเลิก</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($createReward || $editRewardData): ?>
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4"><?= $editRewardData ? 'แก้ไขรางวัล' : 'เพิ่มรางวัลใหม่' ?></h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editRewardData ? 'update_reward' : 'create_reward' ?>">
            <input type="hidden" name="crate_id" value="<?= $editRewardData['crate_id'] ?? $createReward ?>">
            <?php if ($editRewardData): ?><input type="hidden" name="id" value="<?= $editRewardData['id'] ?>"><?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อรางวัล <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="<?= e($editRewardData['name'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">ความหายาก <span class="text-red-400">*</span></label>
                    <select name="rarity" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                        <?php foreach (['common'=>'Common','rare'=>'Rare','epic'=>'Epic','mythic'=>'Mythic','legendary'=>'Legendary'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($editRewardData['rarity'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">น้ำหนัก (Weight) <span class="text-red-400">*</span></label>
                    <input type="number" name="weight" min="1" value="<?= e($editRewardData['weight'] ?? '100') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                    <p class="text-xs opacity-40 mt-1">ยิ่งมาก ยิ่งดรอปง่าย (common=1000, rare=200, epic=50, mythic=10, legendary=1)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">รูปภาพ (URL)</label>
                    <input type="text" name="image" value="<?= e($editRewardData['image'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">คำสั่ง (Command) <span class="text-red-400">*</span></label>
                    <input type="text" name="command" value="<?= e($editRewardData['command'] ?? '') ?>" required class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm font-mono" style="background-color: var(--color-surface-dark);" placeholder="give {player} diamond 64">
                </div>
                <div>
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= ($editRewardData['is_active'] ?? 1) ? 'checked' : '' ?>> <span class="text-sm">เปิดใช้งาน</span></label>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg text-sm font-semibold"><i class="fas fa-save mr-1"></i> บันทึก</button>
                <a href="?rewards=<?= $editRewardData['crate_id'] ?? $createReward ?>" class="px-6 py-2 rounded-lg text-sm border border-white/10 hover:bg-white/5">ยกเลิก</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($viewRewards): ?>
    <!-- Rewards List -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3">ID</th>
                    <th class="text-left px-4 py-3">รางวัล</th>
                    <th class="text-center px-4 py-3">ความหายาก</th>
                    <th class="text-center px-4 py-3">น้ำหนัก</th>
                    <th class="text-center px-4 py-3">%</th>
                    <th class="text-center px-4 py-3">สถานะ</th>
                    <th class="text-right px-4 py-3">จัดการ</th>
                </tr></thead>
                <tbody>
                <?php
                $totalWeight = array_sum(array_column($rewards, 'weight'));
                foreach ($rewards as $r):
                    $pct = $totalWeight > 0 ? round(($r['weight'] / $totalWeight) * 100, 2) : 0;
                ?>
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="px-4 py-3 opacity-60"><?= $r['id'] ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <?php if ($r['image']): ?><img src="<?= e($r['image']) ?>" class="w-8 h-8 rounded" onerror="this.style.display='none'"><?php endif; ?>
                                <span class="font-semibold"><?= e($r['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center"><span class="<?= $rarityColors[$r['rarity']] ?? '' ?> font-semibold text-xs uppercase"><?= e($r['rarity']) ?></span></td>
                        <td class="px-4 py-3 text-center"><?= $r['weight'] ?></td>
                        <td class="px-4 py-3 text-center"><?= $pct ?>%</td>
                        <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded <?= $r['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>"><?= $r['is_active'] ? 'เปิด' : 'ปิด' ?></span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="?rewards=<?= $viewRewards ?>&edit_reward=<?= $r['id'] ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-edit"></i></a>
                            <form method="POST" class="inline" onsubmit="return confirm('ลบรางวัล: <?= e(addslashes($r['name'])) ?>?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_reward">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="crate_id" value="<?= $viewRewards ?>">
                                <button type="submit" class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rewards)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center opacity-50">ยังไม่มีรางวัลในกล่องนี้</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <!-- Crate List -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3">ID</th>
                    <th class="text-left px-4 py-3">กล่อง</th>
                    <th class="text-left px-4 py-3">เซิร์ฟเวอร์</th>
                    <th class="text-right px-4 py-3">ราคา</th>
                    <th class="text-center px-4 py-3">รางวัล</th>
                    <th class="text-center px-4 py-3">สถานะ</th>
                    <th class="text-right px-4 py-3">จัดการ</th>
                </tr></thead>
                <tbody>
                <?php foreach ($crates as $c): ?>
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="px-4 py-3 opacity-60"><?= $c['id'] ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <?php if ($c['image']): ?><img src="<?= e($c['image']) ?>" class="w-8 h-8 rounded" onerror="this.style.display='none'"><?php endif; ?>
                                <span class="font-semibold"><?= e($c['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3"><?= e($c['server_name'] ?? $c['server_id']) ?></td>
                        <td class="px-4 py-3 text-right font-semibold" style="color: var(--color-accent);"><?= formatMoney($c['price']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <a href="?rewards=<?= $c['id'] ?>" class="text-xs px-2 py-1 rounded bg-purple-500/20 text-purple-400 hover:bg-purple-500/30"><?= $c['reward_count'] ?> รางวัล</a>
                        </td>
                        <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded <?= $c['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>"><?= $c['is_active'] ? 'เปิด' : 'ปิด' ?></span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="?edit_crate=<?= $c['id'] ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-edit"></i></a>
                            <form method="POST" class="inline" onsubmit="return confirm('ลบกล่อง: <?= e(addslashes($c['name'])) ?>? (รางวัลทั้งหมดจะถูกลบด้วย)')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_crate">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($crates)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center opacity-50">ยังไม่มีกล่องกาชา</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
