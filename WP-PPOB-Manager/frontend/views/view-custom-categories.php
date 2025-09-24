<?php
defined('ABSPATH') || exit;
global $wpdb;
$table_name = $wpdb->prefix . 'wppob_display_categories';

// --- Ambil Pengaturan dari Database ---
$grid_columns = get_option('wppob_grid_columns', 4);
$image_size = get_option('wppob_category_image_size', 60);

// Mendeteksi apakah kita sedang melihat sub-kategori
$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
$current_page_url = strtok($_SERVER["REQUEST_URI"], '?');

// Mengambil semua kategori untuk membangun struktur pohon
$all_categories_raw = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY sort_order ASC");
$categories_tree = [];
$category_lookup = [];

if (!empty($all_categories_raw)) {
    foreach ($all_categories_raw as $cat) {
        $cat->children = [];
        $category_lookup[$cat->id] = $cat;
    }
    foreach ($all_categories_raw as $cat) {
        if ($cat->parent_id != 0 && isset($category_lookup[$cat->parent_id])) {
            $category_lookup[$cat->parent_id]->children[] = $cat;
        } else {
            $categories_tree[$cat->id] = $cat;
        }
    }
}

// Tentukan kategori mana yang akan ditampilkan
$categories_to_display = [];
if ($parent_id > 0 && isset($category_lookup[$parent_id])) {
    $categories_to_display = $category_lookup[$parent_id]->children;
} else {
    foreach ($all_categories_raw as $cat) {
        if ($cat->parent_id == 0) {
            $categories_to_display[] = $cat;
        }
    }
}
?>

<style>
    .wppob-category-item img {
        max-width: <?php echo esc_attr($image_size); ?>px !important;
        height: <?php echo esc_attr($image_size); ?>px !important;
    }
</style>

<div class="wppob-frontend-wrap">
    <?php if ($parent_id > 0): ?>
        <p><a href="<?php echo esc_url($current_page_url); ?>">&larr; Kembali ke Kategori Utama</a></p>
    <?php endif; ?>

    <?php if (!empty($categories_to_display)): ?>
        <div class="wppob-category-grid" style="grid-template-columns: repeat(<?php echo esc_attr($grid_columns); ?>, 1fr);">
            <?php foreach ($categories_to_display as $category): ?>
                <?php
                $has_children = !empty($category_lookup[$category->id]->children);
                
                if ($has_children) {
                    $link = add_query_arg('parent_id', $category->id, $current_page_url);
                } else {
                    $link = add_query_arg('category_id', $category->id, $current_page_url);
                }
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