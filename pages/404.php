<?php
$pageTitle = 'ไม่พบหน้า';
http_response_code(404);
include BASE_PATH . '/layout/header.php';
?>

<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center animate-fade-in">
        <div class="text-8xl font-bold opacity-20 mb-4">404</div>
        <h1 class="text-2xl font-bold mb-2">ไม่พบหน้าที่คุณต้องการ</h1>
        <p class="opacity-60 mb-6">หน้านี้อาจถูกลบหรือย้ายไปแล้ว</p>
        <a href="<?= url('') ?>" class="btn-primary px-6 py-3 rounded-lg font-bold inline-block">
            <i class="fas fa-home mr-2"></i> กลับหน้าแรก
        </a>
    </div>
</div>

<?php include BASE_PATH . '/layout/footer.php'; ?>
