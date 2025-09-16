<?php
defined('ABSPATH') || exit;

global $wpdb;
$category_table = $wpdb->prefix . 'wppob_display_categories';
$category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$category_table} WHERE id = %d", $category_id));

if (!$category) {
    echo '<p>' . __('Kategori tidak ditemukan.', 'wp-ppob') . '</p>';
    return;
}

$product_ids = json_decode($category->assigned_products, true);
$products = [];
if (!empty($product_ids) && function_exists('wc_get_products')) {
    $args = ['limit' => -1, 'include' => $product_ids, 'orderby' => 'post__in'];
    $products = wc_get_products($args);
}
?>
<div class="wppob-frontend-wrap">
    <h2><?php echo esc_html($category->name); ?></h2>
    
    
    <?php if (!is_user_logged_in()): ?>
    <div class="wppob-notice wppob-notice-error" style="margin-bottom: 20px;">
        <strong>Anda harus login untuk melanjutkan.</strong>
        <p>Silakan masuk ke akun Anda atau daftar jika Anda pengguna baru.</p>
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="button">Login</a>
        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="button">Daftar</a>
    </div>
<?php endif; ?>
    
    
    
    
    <?php if (!empty($products)): ?>
        <div class="wppob-product-container <?php echo ($category->product_display_mode === 'list') ? 'wppob-product-list' : 'wppob-product-grid'; ?>">
            <?php foreach ($products as $product): ?>
                <div class="wppob-product-item" 
                     data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                     data-product-name="<?php echo esc_attr($product->get_name()); ?>"
                     data-product-price="<?php echo esc_attr(wppob_format_rp($product->get_price())); ?>">
                    <?php 
                    $image = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
                    if ($image) {
                        echo '<img src="' . esc_url($image) . '" alt="' . esc_attr($product->get_name()) . '">';
                    }
                    ?>
                    <div class="wppob-product-name"><?php echo esc_html($product->get_name()); ?></div>
                    <div class="wppob-product-price"><?php echo wppob_format_rp($product->get_price()); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <form id="wppob-purchase-form" class="wppob-purchase-form">
            <div class="wppob-form-group">
                <label for="wppob-customer-no"><?php _e('Nomor Tujuan', 'wp-ppob'); ?></label>
                <input type="text" id="wppob-customer-no" name="customer_no" required placeholder="Masukkan nomor tujuan di sini" <?php disabled(!is_user_logged_in()); ?>>
            </div>

            <div id="wppob-purchase-details" style="display: none;">
                <h4><?php _e('Detail Pembelian', 'wp-ppob'); ?></h4>
                <p><strong><?php _e('Produk:', 'wp-ppob'); ?></strong> <span id="wppob-detail-product">-</span></p>
                <p><strong><?php _e('Harga:', 'wp-ppob'); ?></strong> <span id="wppob-detail-price">-</span></p>
            </div>

            <input type="hidden" id="wppob-product-id" name="product_id" value="">
            <input type="hidden" name="action" value="wppob_submit_purchase">
            <?php wp_nonce_field('wppob_frontend_nonce', 'nonce'); ?>

            <div id="wppob-notification-area"></div>

           <button type="submit" id="wppob-submit-purchase" <?php disabled(!is_user_logged_in()); ?> disabled><?php _e('Beli Sekarang', 'wp-ppob'); ?></button>
        </form>
    <?php else: ?>
        <p><?php _e('Tidak ada produk yang tersedia untuk kategori ini.', 'wp-ppob'); ?></p>
    <?php endif; ?>
</div>
