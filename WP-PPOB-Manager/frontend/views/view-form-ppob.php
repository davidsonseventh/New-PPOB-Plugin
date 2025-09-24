<?php
defined('ABSPATH') || exit;
global $wpdb;
$category_table = $wpdb->prefix . 'wppob_display_categories';
$category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$category_table} WHERE id = %d", $category_id));
if (!$category) { echo '<p>' . __('Kategori tidak ditemukan.', 'wp-ppob') . '</p>'; return; }
$product_ids = json_decode($category->assigned_products, true);
$products = [];
if (!empty($product_ids) && function_exists('wc_get_products')) {
    $args = ['limit' => -1, 'include' => $product_ids, 'orderby' => 'post__in'];
    $products = wc_get_products($args);
}
$is_postpaid = (stripos($category->name, 'pascabayar') !== false || stripos($category->name, 'tagihan') !== false || stripos($category->name, 'bpjs') !== false);
?>
<?php
// Cek apakah kategori ini pascabayar
$is_postpaid = (stripos($category->name, 'pascabayar') !== false || stripos($category->name, 'tagihan') !== false || stripos($category->name, 'bpjs') !== false);
?>
<div class="wppob-frontend-wrap" data-is-postpaid="<?php echo $is_postpaid ? 'true' : 'false'; ?>">
    <h2><?php echo esc_html($category->name); ?></h2>
    
    <?php if (!empty($products)): ?>
        <div class="wppob-product-grid">
            
            <?php foreach ($products as $product): ?>
            
                <?php
    // [TAMBAHKAN BARIS INI]
    $user_price = wppob_get_price_for_user($product, get_current_user_id());
?>
<div class="wppob-product-item" 
     data-product-id="<?php echo esc_attr($product->get_id()); ?>"
     data-product-name="<?php echo esc_attr($product->get_name()); ?>"
     data-product-price="<?php echo esc_attr(wppob_format_rp($user_price)); ?>"
     data-brand="<?php echo esc_attr(strtolower($product->get_meta('_wppob_brand'))); ?>">
    <?php 
                    $image = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
                    if ($image) {
                        echo '<img src="' . esc_url($image) . '" alt="' . esc_attr($product->get_name()) . '">';
                    }
                    ?>
                    <div class="wppob-product-name"><?php echo esc_html($product->get_name()); ?></div>
                    <div class="wppob-product-price"><?php echo wppob_format_rp($user_price); ?></div>

                </div>
            <?php endforeach; ?>
        </div>

       <form id="wppob-purchase-form" class="wppob-purchase-form" novalidate>
    <div class="wppob-form-group">
        <label for="wppob-customer-no"><?php _e('Nomor Tujuan / ID Pelanggan', 'wp-ppob'); ?></label>
        <div class="wppob-input-with-spinner">
            <input type="text" id="wppob-customer-no" name="customer_no" required placeholder="Masukkan nomor tujuan di sini">
            <span id="wppob-inquiry-spinner" class="spinner"></span>
        </div>
    </div>

    <div id="wppob-purchase-details" style="display: none; margin-top:15px;">
        </div>

    <input type="hidden" id="wppob-product-id" name="product_id" value="">
    <?php wp_nonce_field('wppob_frontend_nonce', 'nonce'); ?>

    <div id="wppob-notification-area" style="margin-top:15px;"></div>

    <button type="submit" id="wppob-submit-purchase" disabled>
        <?php echo $is_postpaid ? __('Cek Tagihan', 'wp-ppob') : __('Beli Sekarang', 'wp-ppob'); ?>
    </button>
</form>
    <?php else: ?>
        <p><?php _e('Tidak ada produk yang tersedia untuk kategori ini.', 'wp-ppob'); ?></p>
    <?php endif; ?>
</div>




<div id="wppob-pin-modal" style="display: none;">
    <div id="wppob-pin-modal-content">
        <h4>Masukkan PIN Transaksi</h4>
        <p>Untuk keamanan, masukkan 6 digit PIN Anda.</p>
        <input type="password" id="wppob-pin-input" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="******">
        <div id="wppob-pin-error" style="color: red; font-size: 12px; display:none;"></div>
        <div class="wppob-pin-modal-actions">
            <button type="button" id="wppob-pin-cancel">Batal</button>
            <button type="button" id="wppob-pin-confirm" class="button-primary">Konfirmasi</button>
        </div>
    </div>
</div>