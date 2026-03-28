<?php
/**
 * Static Page (rules, faq, about, etc.)
 */
$slug = $_GET['slug'] ?? '';
$db = Database::getInstance();

$page = $db->fetch("SELECT * FROM pages WHERE slug = ? AND is_active = 1", [$slug]);
if (!$page) {
    include BASE_PATH . '/pages/404.php';
    return;
}

$pageTitle = $page['title'];
include BASE_PATH . '/layout/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="card p-8 animate-fade-in">
        <h1 class="text-3xl font-bold mb-6"><?= e($page['title']) ?></h1>
        <div class="prose prose-invert max-w-none leading-relaxed">
            <?= $page['content'] ?>
        </div>
        <p class="text-xs opacity-30 mt-8">อัพเดทล่าสุด: <?= e($page['updated_at'] ?? $page['created_at'] ?? '-') ?></p>
    </div>
</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
