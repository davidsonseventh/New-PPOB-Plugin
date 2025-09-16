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
    <a href="<?php echo esc_url(get_edit_profile_url()); ?>">
        <i class="fa-solid fa-user"></i>
        <span>Saya</span>
    </a>
</div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>