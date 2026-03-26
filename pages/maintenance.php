<?php
$pageTitle = 'ปิดปรับปรุง';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปิดปรับปรุง — <?= e(Settings::get('site_name', 'MCStore')) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/theme.php') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>body{background:var(--color-bg);color:var(--color-text);font-family:var(--font-family);}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="text-center animate-fade-in">
        <i class="fas fa-tools text-6xl mb-4" style="color: var(--color-accent);"></i>
        <h1 class="text-3xl font-bold mb-3">ปิดปรับปรุงชั่วคราว</h1>
        <p class="opacity-70 mb-2"><?= e(Settings::get('maintenance_message', 'เว็บไซต์กำลังปรับปรุง กรุณากลับมาใหม่ภายหลัง')) ?></p>
        <p class="text-sm opacity-40 mt-6">เซิร์ฟเวอร์: <?= e(Settings::get('server_ip', '')) ?></p>
    </div>
</body>
</html>
