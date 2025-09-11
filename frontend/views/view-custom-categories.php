<?php
defined('ABSPATH') || exit;
global $wpdb;
$table_name = $wpdb->prefix . 'wppob_display_categories';
$all_categories = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY parent_id ASC, sort_order ASC");

// Build a tree for easier rendering
$categories_tree = [];
$category_lookup = [];
if (!empty($all_categories)) {
    foreach ($all_categories as $cat) {
        $category_lookup[$cat->id] = $cat;
        $cat->children = [];
    }
    foreach ($all_categories as $cat) {
        if ($cat->parent_id != 0 && isset($category_lookup[$cat->parent_id])) {
            $category_lookup[$cat->parent_id]->children[] = $cat;
        } else {
            $categories_tree[] = $cat;
        }
    }
}
?>
<div class="wppob-frontend-wrap">
    <?php if (!empty($categories_tree)): ?>
        <div class="wppob-category-grid" style="grid-template-columns: repeat(<?php echo esc_attr(get_option('wppob_grid_columns', 4)); ?>, 1fr);">
            <?php foreach ($categories_tree as $category): ?>
                <?php
                // Assuming you have a page/post where the [wppob_form] shortcode is placed.
                // You should replace '/ppob-payment/' with the actual slug of your page.
                $page_slug = '/ppob-payment/';
                $link = home_url($page_slug . '?category_id=' . $category->id);
                ?>
                <a href="<?php echo esc_url($link); ?>" class="wppob-category-item">
                    <?php 
                    $image_url = wp_get_attachment_image_url($category->image_id, 'thumbnail');
                    if ($image_url): 
                    ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($category->name); ?>">
                    <?php endif; ?>
                    <span><?php echo esc_html($category->name); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><?php _e('Tidak ada kategori yang tersedia saat ini.', 'wp-ppob'); ?></p>
    <?php endif; ?>
</div>
