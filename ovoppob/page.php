<?php get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="container" id="page">
            <?php
            while (have_posts()) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <?php // Judul halaman bisa disembunyikan jika Anda mau tampilan seperti aplikasi ?>
                        <?php // the_title('<h1 class="entry-title">', '</h1>'); ?>
                    </header>
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php
            endwhile;
            ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>
