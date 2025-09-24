<?php get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="container">

            <div class="notification-banner">
                <i class="fa-solid fa-bell"></i>
                <marquee>Transaksi nya lancar terus bossku 👋 Selamat datang di <?php bloginfo('name'); ?>!</marquee>
            </div>

            <div id="page-content" class="page-content-wrapper">
                <?php
                while (have_posts()) :
                    the_post();
                    the_content();
                endwhile;
                ?>
            </div>

            <div class="promo-banner">
                <div class="promo-info">
                    <span class="promo-title">Info & Promo Spesial</span>
                    <span class="promo-subtitle">Jangan lewatkan penawaran terbaik!</span>
                </div>
                <a href="#" class="promo-link">Lihat Semua</a>
            </div>

        </div>
    </main>
</div>

<?php get_footer(); ?>