    </main>

    <!-- Footer -->
    <footer class="mt-auto py-8" style="background-color: var(--color-surface-dark);">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand -->
                <div>
                    <h3 class="text-xl font-bold mb-3" style="color: var(--color-primary);">
                        <?php if ($footerLogo = Settings::get('site_logo')): ?>
                            <img src="<?= e($footerLogo) ?>" alt="" class="h-6 w-6 rounded inline mr-1">
                        <?php else: ?>
                            <i class="fas fa-cube mr-1"></i>
                        <?php endif; ?>
                        <?= e(Settings::get('site_name', 'MCStore')) ?>
                    </h3>
                    <p class="text-sm opacity-70"><?= e(Settings::get('site_description', '')) ?></p>
                    <div class="flex space-x-3 mt-4">
                        <?php if ($discord = Settings::get('social_discord')): ?>
                            <a href="<?= e($discord) ?>" target="_blank" class="text-lg hover:opacity-80 transition" title="Discord"><i class="fab fa-discord"></i></a>
                        <?php endif; ?>
                        <?php if ($facebook = Settings::get('social_facebook')): ?>
                            <a href="<?= e($facebook) ?>" target="_blank" class="text-lg hover:opacity-80 transition" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <?php endif; ?>
                        <?php if ($youtube = Settings::get('social_youtube')): ?>
                            <a href="<?= e($youtube) ?>" target="_blank" class="text-lg hover:opacity-80 transition" title="YouTube"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                        <?php if ($line = Settings::get('social_line')): ?>
                            <a href="<?= e($line) ?>" target="_blank" class="text-lg hover:opacity-80 transition" title="LINE"><i class="fab fa-line"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Links (respect feature toggles) -->
                <div>
                    <h4 class="font-semibold mb-3">ลิงก์ด่วน</h4>
                    <ul class="space-y-2 text-sm opacity-80">
                        <li><a href="<?= url('') ?>" class="hover:opacity-100 transition">หน้าแรก</a></li>
                        <?php if (Settings::get('shop_enabled', '1') === '1'): ?>
                        <li><a href="<?= url('shop') ?>" class="hover:opacity-100 transition">ร้านค้า</a></li>
                        <?php endif; ?>
                        <?php if (Settings::get('gacha_enabled', '1') === '1'): ?>
                        <li><a href="<?= url('gacha') ?>" class="hover:opacity-100 transition">กาชา</a></li>
                        <?php endif; ?>
                        <?php if (Settings::get('topup_enabled', '1') === '1'): ?>
                        <li><a href="<?= url('topup') ?>" class="hover:opacity-100 transition">เติมเงิน</a></li>
                        <?php endif; ?>
                        <?php if (Settings::get('download_enabled', '1') === '1'): ?>
                        <li><a href="<?= url('download') ?>" class="hover:opacity-100 transition">ดาวน์โหลด</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Information -->
                <div>
                    <h4 class="font-semibold mb-3">ข้อมูล</h4>
                    <ul class="space-y-2 text-sm opacity-80">
                        <li><a href="<?= url('page/rules') ?>" class="hover:opacity-100 transition">กฎเซิร์ฟเวอร์</a></li>
                        <li><a href="<?= url('page/faq') ?>" class="hover:opacity-100 transition">คำถามที่พบบ่อย</a></li>
                        <li><a href="<?= url('page/about') ?>" class="hover:opacity-100 transition">เกี่ยวกับเรา</a></li>
                        <?php if (Settings::get('contact_enabled', '1') === '1'): ?>
                        <li><a href="<?= url('contact') ?>" class="hover:opacity-100 transition">ติดต่อเรา</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Server Info -->
                <div>
                    <h4 class="font-semibold mb-3">เซิร์ฟเวอร์</h4>
                    <div class="text-sm space-y-2">
                        <?php $serverIp = Settings::get('server_ip', 'play.example.com'); ?>
                        <p class="flex items-center gap-2">
                            <i class="fas fa-server" style="color: var(--color-primary);"></i>
                            <span class="font-mono"><?= e($serverIp) ?></span>
                            <button onclick="navigator.clipboard.writeText('<?= e($serverIp) ?>'); this.innerHTML='<i class=\'fas fa-check\'></i>'; setTimeout(()=>this.innerHTML='<i class=\'fas fa-copy\'></i>', 1500)"
                                    class="opacity-40 hover:opacity-80 transition text-xs" title="คัดลอก IP">
                                <i class="fas fa-copy"></i>
                            </button>
                        </p>
                        <p><i class="fas fa-signal mr-2" style="color: var(--color-accent);"></i> <span id="footer-online">-</span> ออนไลน์</p>
                        <?php if ($version = Settings::get('server_version')): ?>
                        <p class="text-xs opacity-50 mt-4">เวอร์ชัน <?= e($version) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="border-t border-white/10 mt-8 pt-4 text-center text-sm opacity-50">
                &copy; <?= date('Y') ?> <?= e(Settings::get('site_name', 'MCStore')) ?> — All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?= url('assets/js/app.js') ?>"></script>
    <?php if (Auth::check()): ?>
        <script src="<?= url('assets/js/realtime.js') ?>"></script>
    <?php endif; ?>
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn')?.addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Profile dropdown toggle (click-based)
        (function() {
            const btn = document.getElementById('user-menu-btn');
            const dropdown = document.getElementById('user-menu-dropdown');
            const arrow = document.getElementById('user-menu-arrow');
            if (!btn || !dropdown) return;

            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpen = !dropdown.classList.contains('hidden');
                dropdown.classList.toggle('hidden');
                if (arrow) arrow.style.transform = isOpen ? '' : 'rotate(180deg)';
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdown.classList.contains('hidden') && !dropdown.contains(e.target) && !btn.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    if (arrow) arrow.style.transform = '';
                }
            });

            // Close on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                    if (arrow) arrow.style.transform = '';
                }
            });
        })();
    </script>
</body>
</html>
