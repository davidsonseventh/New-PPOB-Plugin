</div><div style="height: 80px;"></div> <footer class="app-footer-nav">
        <div class="footer-nav-item active">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <i class="fa-solid fa-house"></i>
                <span>Beranda</span>
            </a>
        </div>
        <div class="footer-nav-item">
            <a href="<?php echo esc_url(home_url('/dashboard-saya/')); // Ganti dengan URL halaman dashboard ?>">
                <i class="fa-solid fa-list-ul"></i>
                <span>Transaksi</span>
            </a>
        </div>
        <div class="footer-nav-item">
     <a href="<?php echo esc_url(home_url('/event/')); ?>">
        <i class="fa-solid fa-calendar-day"></i>
        <span>Event</span>
    </a>
</div>
     <div class="footer-nav-item">
    <a href="#" id="open-sidebar-btn">
        <i class="fa-solid fa-bars"></i>
        <span>Menu</span>
    </a>
</div>
    </footer>
    
    <?php include_once(get_template_directory() . '/sidebar-menu.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('user-sidebar-menu');
    const overlay = document.getElementById('sidebar-overlay');
    const openBtn = document.getElementById('open-sidebar-btn');
    const closeBtn = document.getElementById('close-sidebar-btn');

    openBtn.addEventListener('click', function(e) {
        e.preventDefault();
        sidebar.classList.add('active');
        overlay.classList.add('active');
    });

    closeBtn.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });

    overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });
});
</script>

    <?php wp_footer(); ?>
</body>
</html>