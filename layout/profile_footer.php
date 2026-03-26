        </div><!-- end page content -->
    </div><!-- end profile-content -->

    <script src="<?= url('assets/js/app.js') ?>"></script>
    <?php if (Auth::check()): ?>
        <script src="<?= url('assets/js/realtime.js') ?>"></script>
    <?php endif; ?>
    <script>
        function toggleSidebar() {
            document.getElementById('profile-sidebar').classList.toggle('open');
            document.getElementById('sidebar-overlay').classList.toggle('open');
        }
    </script>
</body>
</html>
