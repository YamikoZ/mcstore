<?php
$pageTitle = 'ข้อความติดต่อ - แอดมิน';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'CSRF token ไม่ถูกต้อง'); redirect('admin/contacts'); }

    $action = $_POST['action'] ?? '';
    $msgId = (int)($_POST['msg_id'] ?? 0);

    if ($action === 'reply' && $msgId) {
        $reply = trim($_POST['admin_reply'] ?? '');
        if (!$reply) { flash('error', 'กรุณากรอกข้อความตอบกลับ'); redirect('admin/contacts?view=' . $msgId); }

        $db->execute("UPDATE contact_messages SET admin_reply = ?, status = 'replied' WHERE id = ?", [$reply, $msgId]);
        auditLog(Auth::id(), 'admin_contact_reply', "Contact #{$msgId}");
        flash('success', 'ตอบกลับเรียบร้อย');
        redirect('admin/contacts');
    }

    if ($action === 'mark_read' && $msgId) {
        $db->execute("UPDATE contact_messages SET status = 'read' WHERE id = ? AND status = 'unread'", [$msgId]);
        redirect('admin/contacts');
    }

    if ($action === 'delete' && $msgId) {
        $db->execute("DELETE FROM contact_messages WHERE id = ?", [$msgId]);
        auditLog(Auth::id(), 'admin_contact_delete', "Contact #{$msgId}");
        flash('success', 'ลบข้อความเรียบร้อย');
        redirect('admin/contacts');
    }
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($filterStatus) { $where = "WHERE status = ?"; $params[] = $filterStatus; }

$totalMsgs = $db->count("SELECT COUNT(*) FROM contact_messages {$where}", $params);
$totalPages = ceil($totalMsgs / $perPage);

$messages = $db->fetchAll("SELECT * FROM contact_messages {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);

$unreadCount = $db->count("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");

$viewMsg = (int)($_GET['view'] ?? 0);
$msgDetail = null;
if ($viewMsg) {
    $msgDetail = $db->fetch("SELECT * FROM contact_messages WHERE id = ?", [$viewMsg]);
    if ($msgDetail && $msgDetail['status'] === 'unread') {
        $db->execute("UPDATE contact_messages SET status = 'read' WHERE id = ?", [$viewMsg]);
        $msgDetail['status'] = 'read';
    }
}

$statusLabels = ['unread'=>'ยังไม่อ่าน','read'=>'อ่านแล้ว','replied'=>'ตอบแล้ว'];
$statusColors = ['unread'=>'bg-yellow-500/20 text-yellow-400','read'=>'bg-blue-500/20 text-blue-400','replied'=>'bg-green-500/20 text-green-400'];

include BASE_PATH . '/layout/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fas fa-envelope mr-2" style="color: var(--color-primary);"></i>ข้อความติดต่อ</h1>
            <p class="text-sm opacity-60 mt-1">
                <?php if ($unreadCount > 0): ?>
                    <span class="text-yellow-400"><i class="fas fa-exclamation-circle"></i> <?= $unreadCount ?> ข้อความยังไม่อ่าน</span>
                <?php else: ?>
                    ไม่มีข้อความใหม่
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= url('admin') ?>" class="text-sm opacity-60 hover:opacity-100 px-3 py-2"><i class="fas fa-arrow-left mr-1"></i> กลับ</a>
    </div>

    <!-- Filters -->
    <div class="card p-4 mb-4">
        <div class="flex flex-wrap gap-2">
            <a href="<?= url('admin/contacts') ?>" class="px-3 py-1 rounded-lg text-sm <?= !$filterStatus ? 'font-bold' : 'opacity-60 hover:opacity-100' ?>" <?= !$filterStatus ? 'style="background-color: var(--color-primary); color: #fff;"' : '' ?>>ทั้งหมด</a>
            <?php foreach ($statusLabels as $k => $v): ?>
                <a href="?status=<?= $k ?>" class="px-3 py-1 rounded-lg text-sm <?= $filterStatus === $k ? 'font-bold' : 'opacity-60 hover:opacity-100' ?>" <?= $filterStatus === $k ? 'style="background-color: var(--color-primary); color: #fff;"' : '' ?>><?= $v ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($msgDetail): ?>
    <!-- Message Detail -->
    <div class="card p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold"><?= e($msgDetail['subject']) ?></h2>
            <a href="<?= url('admin/contacts') ?>" class="text-sm opacity-60 hover:opacity-100"><i class="fas fa-times"></i></a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
            <div><p class="text-xs opacity-50">ชื่อ</p><p class="font-semibold"><?= e($msgDetail['name']) ?></p></div>
            <div><p class="text-xs opacity-50">อีเมล</p><p><?= e($msgDetail['email']) ?></p></div>
            <div><p class="text-xs opacity-50">สถานะ</p><span class="text-xs px-2 py-0.5 rounded <?= $statusColors[$msgDetail['status']] ?? '' ?>"><?= $statusLabels[$msgDetail['status']] ?? $msgDetail['status'] ?></span></div>
            <div><p class="text-xs opacity-50">เวลา</p><p class="text-xs"><?= $msgDetail['created_at'] ?></p></div>
        </div>

        <div class="p-4 rounded-lg border border-white/10 mb-4" style="background-color: var(--color-surface-dark);">
            <p class="whitespace-pre-wrap"><?= e($msgDetail['message']) ?></p>
        </div>

        <?php if ($msgDetail['admin_reply']): ?>
            <div class="p-4 rounded-lg border border-green-500/30 mb-4" style="background-color: rgba(34,197,94,0.05);">
                <p class="text-xs text-green-400 mb-1"><i class="fas fa-reply"></i> ตอบกลับแล้ว</p>
                <p class="whitespace-pre-wrap"><?= e($msgDetail['admin_reply']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Reply Form -->
        <form method="POST" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="msg_id" value="<?= $msgDetail['id'] ?>">
            <label class="block text-sm font-semibold"><?= $msgDetail['admin_reply'] ? 'แก้ไขคำตอบ' : 'ตอบกลับ' ?></label>
            <textarea name="admin_reply" rows="4" class="w-full px-3 py-2 rounded-lg border border-white/10 text-sm" style="background-color: var(--color-surface-dark);" placeholder="พิมพ์ข้อความตอบกลับ..."><?= e($msgDetail['admin_reply'] ?? '') ?></textarea>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary px-4 py-2 rounded-lg text-sm"><i class="fas fa-reply mr-1"></i> ส่งคำตอบ</button>
                <form method="POST" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="msg_id" value="<?= $msgDetail['id'] ?>">
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-red-500/20 text-red-400 hover:bg-red-500/30" onclick="return confirm('ลบข้อความนี้?')"><i class="fas fa-trash mr-1"></i> ลบ</button>
                </form>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Message List -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3">#</th>
                    <th class="text-left px-4 py-3">ชื่อ</th>
                    <th class="text-left px-4 py-3">หัวข้อ</th>
                    <th class="text-center px-4 py-3">สถานะ</th>
                    <th class="text-right px-4 py-3">เวลา</th>
                    <th class="text-right px-4 py-3">จัดการ</th>
                </tr></thead>
                <tbody>
                <?php foreach ($messages as $m): ?>
                    <tr class="border-b border-white/5 hover:bg-white/5 <?= $m['status'] === 'unread' ? 'font-semibold' : '' ?>">
                        <td class="px-4 py-3">
                            <?= $m['id'] ?>
                            <?php if ($m['status'] === 'unread'): ?><span class="inline-block w-2 h-2 bg-yellow-400 rounded-full ml-1"></span><?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><?= e($m['name']) ?></td>
                        <td class="px-4 py-3"><?= e(mb_strimwidth($m['subject'], 0, 50, '...')) ?></td>
                        <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded <?= $statusColors[$m['status']] ?? '' ?>"><?= $statusLabels[$m['status']] ?? $m['status'] ?></span></td>
                        <td class="px-4 py-3 text-right text-xs opacity-60"><?= timeAgo($m['created_at']) ?></td>
                        <td class="px-4 py-3 text-right flex justify-end gap-1">
                            <a href="?view=<?= $m['id'] ?>" class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400 hover:bg-blue-500/30"><i class="fas fa-eye"></i></a>
                            <form method="POST" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="msg_id" value="<?= $m['id'] ?>">
                                <button type="submit" class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30" onclick="return confirm('ลบข้อความนี้?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($messages)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center opacity-50">ไม่พบข้อความ</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-1 mt-4">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?><?= $filterStatus ? '&status=' . urlencode($filterStatus) : '' ?>" class="px-3 py-1 rounded text-sm <?= $i === $page ? 'font-bold' : 'opacity-60 hover:opacity-100' ?>" <?= $i === $page ? 'style="background-color: var(--color-primary); color: #fff;"' : '' ?>><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/layout/admin_footer.php'; ?>
