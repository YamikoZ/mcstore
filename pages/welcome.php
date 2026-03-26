<?php
/**
 * Welcome / Rules Gate Page
 * User must scroll through rules and complete guide before entering
 */
$pageTitle = 'ยินดีต้อนรับ';
$siteName = Settings::get('site_name', 'MCStore');
$db = Database::getInstance();
$rulesPage = $db->fetch("SELECT * FROM pages WHERE slug = 'rules' AND is_active = 1");
$rulesContent = $rulesPage ? $rulesPage['content'] : '<p>ยินดีต้อนรับสู่เซิร์ฟเวอร์ของเรา!</p>';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_rules'])) {
    if (csrf_check()) {
        $_SESSION['welcome_accepted'] = true;
        redirect('');
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยินดีต้อนรับ — <?= e($siteName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/theme.php') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background-color: var(--color-bg); color: var(--color-text); font-family: var(--font-family); }
        .rules-box { max-height: 400px; overflow-y: auto; scrollbar-width: thin; }
        .rules-box::-webkit-scrollbar { width: 6px; }
        .rules-box::-webkit-scrollbar-thumb { background: var(--color-primary); border-radius: 3px; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl animate-fade-in">
        <!-- Title -->
        <div class="text-center mb-8">
            <i class="fas fa-cube text-6xl mb-4" style="color: var(--color-primary);"></i>
            <h1 class="text-4xl font-bold gradient-text"><?= e($siteName) ?></h1>
            <p class="text-lg opacity-70 mt-2"><?= e(Settings::get('site_description', '')) ?></p>
        </div>

        <!-- Server Info -->
        <div class="card p-6 mb-6 text-center">
            <h2 class="text-xl font-bold mb-4"><i class="fas fa-server mr-2" style="color: var(--color-primary);"></i> ข้อมูลเซิร์ฟเวอร์</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="opacity-60">IP:</span> <span class="font-bold"><?= e(Settings::get('server_ip', 'play.example.com')) ?></span></div>
                <div><span class="opacity-60">เวอร์ชัน:</span> <span class="font-bold"><?= e(Settings::get('server_version', '1.20.x')) ?></span></div>
            </div>
        </div>

        <!-- Rules -->
        <div class="card p-6 mb-6">
            <h2 class="text-xl font-bold mb-4"><i class="fas fa-scroll mr-2" style="color: var(--color-accent);"></i> กฎเซิร์ฟเวอร์</h2>
            <div id="rules-content" class="rules-box p-4 rounded-lg text-sm leading-relaxed" style="background: var(--color-bg);">
                <?= $rulesContent ?>
            </div>
            <p id="scroll-hint" class="text-center text-xs mt-2 opacity-50"><i class="fas fa-arrow-down mr-1"></i> เลื่อนอ่านกฎให้จบก่อนดำเนินการต่อ</p>
        </div>

        <!-- Accept Form -->
        <form method="POST" class="text-center">
            <?= csrf_field() ?>
            <button type="submit" name="accept_rules" id="accept-btn" disabled
                    class="px-8 py-4 rounded-xl text-lg font-bold transition opacity-50 cursor-not-allowed"
                    style="background-color: var(--color-primary); color: #fff;">
                <i class="fas fa-check-circle mr-2"></i> ยอมรับกฎและเข้าสู่เว็บไซต์
            </button>
            <p id="accept-hint" class="text-xs mt-3 opacity-40">อ่านกฎให้จบก่อนจึงจะสามารถดำเนินการต่อได้</p>
        </form>
    </div>

    <script>
        const rulesBox = document.getElementById('rules-content');
        const acceptBtn = document.getElementById('accept-btn');
        const scrollHint = document.getElementById('scroll-hint');
        const acceptHint = document.getElementById('accept-hint');

        function checkScroll() {
            const atBottom = rulesBox.scrollTop + rulesBox.clientHeight >= rulesBox.scrollHeight - 20;
            if (atBottom) {
                acceptBtn.disabled = false;
                acceptBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                acceptBtn.classList.add('animate-glow');
                scrollHint.style.display = 'none';
                acceptHint.textContent = 'คลิกเพื่อเข้าสู่เว็บไซต์';
                acceptHint.style.opacity = '0.8';
            }
        }

        rulesBox.addEventListener('scroll', checkScroll);
        // If rules are short enough, enable immediately
        if (rulesBox.scrollHeight <= rulesBox.clientHeight + 20) {
            checkScroll();
            rulesBox.scrollTop = rulesBox.scrollHeight;
        }
    </script>
</body>
</html>
