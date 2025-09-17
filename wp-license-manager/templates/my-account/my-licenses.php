<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'wplm_licenses';
$user_id = get_current_user_id();
$licenses = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC", $user_id));
?>

<h2><?php _e('Lisensi & Unduhan Saya', 'wp-license-manager'); ?></h2>

<?php if (!empty($licenses)) : ?>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
        <thead>
            <tr>
                <th><?php _e('Produk', 'wp-license-manager'); ?></th>
                <th><?php _e('Kunci Lisensi', 'wp-license-manager'); ?></th>
                <th class="text-center"><?php _e('Unduhan', 'wp-license-manager'); ?></th>
                <th class="text-center"><?php _e('Kadaluwarsa', 'wp-license-manager'); ?></th>
                <th class="text-center"><?php _e('Status', 'wp-license-manager'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($licenses as $license) :
                $product = wc_get_product($license->product_id);
                $download_link = $product ? get_post_meta($product->get_id(), '_wplm_download_link', true) : '';
            ?>
                <tr>
                    <td>
                        <?php echo $product ? '<a href="' . esc_url($product->get_permalink()) . '">' . esc_html($product->get_name()) . '</a>' : 'Produk Dihapus'; ?>
                    </td>
                    <td>
                        <div class="wplm-key-display">
                            <pre id="license-key-<?php echo esc_attr($license->id); ?>"><?php echo esc_html($license->license_key); ?></pre>
                            <button class="wplm-copy-btn" data-clipboard-target="#license-key-<?php echo esc_attr($license->id); ?>"><?php _e('Salin', 'wp-license-manager'); ?></button>
                        </div>
                    </td>
                    <td class="text-center">
                        <?php if ($download_link): ?>
                            <a href="<?php echo esc_url($download_link); ?>" class="woocommerce-button button">Unduh</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <time><?php echo esc_html(date_i18n('d F Y', strtotime($license->expiry_date))); ?></time>
                    </td>
                    <td class="text-center">
                        <span class="wplm-status status-<?php echo esc_attr($license->status); ?>"><?php echo esc_html(ucfirst($license->status)); ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else : ?>
    <div class="woocommerce-message woocommerce-message--info">
        <?php _e('Anda belum memiliki lisensi.', 'wp-license-manager'); ?>
        <a class="woocommerce-Button button" href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>">
            <?php _e('Belanja Sekarang', 'wp-license-manager'); ?>
        </a>
    </div>
<?php endif; ?>

<style>
    .wplm-key-display { display: flex; align-items: center; gap: 10px; }
    .wplm-key-display pre { margin: 0; background: #eee; padding: 5px; user-select: all; }
    .wplm-status { padding: 4px 8px; border-radius: 4px; color: #fff; font-size: 0.8em; }
    .wplm-status.status-active, .wplm-status.status-inactive { background-color: #28a745; }
    .wplm-status.status-expired { background-color: #6c757d; }
    .wplm-status.status-disabled { background-color: #dc3545; }
    .text-center { text-align: center; }
</style>
