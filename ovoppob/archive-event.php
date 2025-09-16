<?php get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="container" id="page">

            <header class="page-header">
                <h1 class="page-title"><?php _e('Info & Events', 'ovoppob'); ?></h1>
            </header>

            <?php if (have_posts()) : ?>
                <div class="event-list">
                    <?php while (have_posts()) : the_post(); ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class('event-item'); ?>>
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="event-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium_large'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="event-content">
                                <h2 class="event-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                <div class="event-meta">
                                    <span class="event-date"><?php echo get_the_date(); ?></span>
                                </div>
                                <div class="event-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                                <a href="<?php the_permalink(); ?>" class="read-more-link">Baca Selengkapnya &rarr;</a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                <?php the_posts_pagination(); ?>
            <?php else : ?>
                <p><?php _e('Belum ada event yang dipublikasikan.', 'ovoppob'); ?></p>
            <?php endif; ?>

        </div>
    </main>
</div>

<style>
    .event-item {
        display: flex;
        margin-bottom: 30px;
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        gap: 20px;
    }
    .event-thumbnail {
        flex-shrink: 0;
        width: 30%;
    }
    .event-thumbnail img {
        width: 100%;
        height: auto;
        border-radius: 8px;
    }
    .event-content {
        flex-grow: 1;
    }
    .event-title a {
        text-decoration: none;
        color: var(--app-purple);
    }
    .read-more-link {
        font-weight: bold;
        text-decoration: none;
        color: var(--app-purple-dark);
    }
</style>

<?php get_footer(); ?>