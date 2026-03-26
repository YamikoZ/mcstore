<?php
$licenseError = $_SESSION['license_error'] ?? null;
unset($_SESSION['license_error']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใส่ License Key</title>
    <link rel="stylesheet" href="<?= url('assets/css/theme.php') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>body { background: var(--color-bg); color: var(--color-text); font-family: var(--font-family); }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">

        <!-- Icon + Title -->
        <div class="text-center mb-8">
            <div class="text-6xl mb-4" style="color: var(--color-primary);">
                <i class="fas fa-key"></i>
            </div>
            <h1 class="text-2xl font-bold mb-1">ใส่ License Key</h1>
            <p class="text-sm opacity-50">กรอก License Key เพื่อเริ่มใช้งาน</p>
        </div>

        <!-- Error -->
        <?php if ($licenseError): ?>
            <div class="mb-4 px-4 py-3 rounded-lg bg-red-500/15 border border-red-500/30 text-red-400 text-sm flex items-center gap-2">
                <i class="fas fa-circle-xmark"></i>
                <?= e($licenseError) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="rounded-xl p-6" style="background: var(--color-surface);">
            <input type="hidden" name="action" value="setup_license">
            <?= csrf_field() ?>

            <div class="mb-5">
                <label class="block text-sm opacity-70 mb-1.5">License Key</label>
                <input type="text" name="license_key"
                       value="<?= e(Settings::get('license_key', '')) ?>"
                       placeholder="XXXX-XXXX-XXXX-XXXX"
                       autocomplete="off" spellcheck="false"
                       class="w-full px-4 py-2.5 rounded-lg text-sm font-mono tracking-wider"
                       style="background: var(--color-surface-dark, rgba(0,0,0,0.3)); border: 1px solid var(--color-border); color: var(--color-text);"
                       required>
                <p class="text-xs opacity-40 mt-1.5">
                    Domain: <code><?= e($_SERVER['HTTP_HOST'] ?? '') ?></code>
                </p>
            </div>

            <button type="submit"
                    class="w-full py-2.5 rounded-lg font-semibold text-sm btn-primary"
                    style="background: var(--gradient-btn, var(--color-primary)); color: #fff;">
                <i class="fas fa-check mr-2"></i>ยืนยัน License
            </button>
        </form>

    </div>
</body>
</html>
