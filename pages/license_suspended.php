<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License ถูกระงับ</title>
    <link rel="stylesheet" href="<?= url('assets/css/theme.php') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: var(--color-bg); color: var(--color-text); font-family: var(--font-family); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md text-center">

        <!-- Icon -->
        <div class="w-24 h-24 rounded-full mx-auto mb-6 flex items-center justify-center"
             style="background: rgba(239,68,68,0.15); border: 2px solid rgba(239,68,68,0.3);">
            <i class="fas fa-ban text-red-400 text-4xl"></i>
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold mb-2">License ถูกระงับ</h1>
        <p class="opacity-50 text-sm mb-6">
            License Key สำหรับ domain นี้ถูกระงับการใช้งานแล้ว
        </p>

        <!-- Info Card -->
        <div class="rounded-xl p-4 mb-6 text-left"
             style="background: var(--color-surface); border: 1px solid rgba(239,68,68,0.2);">
            <div class="flex items-center gap-3 mb-3">
                <i class="fas fa-globe text-red-400 w-4 text-center"></i>
                <div>
                    <p class="text-xs opacity-40">Domain</p>
                    <p class="text-sm font-mono font-semibold"><?= e($_SERVER['HTTP_HOST'] ?? '') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <i class="fas fa-circle-xmark text-red-400 w-4 text-center"></i>
                <div>
                    <p class="text-xs opacity-40">สถานะ</p>
                    <p class="text-sm font-semibold text-red-400">ถูกระงับการใช้งาน</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="space-y-3">
            <a href="https://github.com/YamikoZ/mcstore/issues/new?title=<?= urlencode('ขอปลดระงับ License — ' . ($_SERVER['HTTP_HOST'] ?? '')) ?>&body=<?= urlencode('**Domain:** `' . ($_SERVER['HTTP_HOST'] ?? '') . '`' . "\n\n**เหตุผลที่ขอปลดระงับ:**\n") ?>&labels=license"
               target="_blank"
               class="w-full py-3 px-6 rounded-xl font-semibold text-sm flex items-center justify-center gap-2"
               style="background: var(--gradient-btn, var(--color-primary)); color: #fff;">
                <i class="fab fa-github"></i> ติดต่อขอปลดระงับ
            </a>
            <p class="text-xs opacity-30">
                หากคิดว่าเป็นความผิดพลาด กรุณาติดต่อผู้พัฒนา
            </p>
        </div>

    </div>
</body>
</html>
