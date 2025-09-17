<?php
/**
 * Template Halaman Lisensi Saya
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'wplm_licenses';
$user_id = get_current_user_id();

$licenses = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC",
    $user_id
));
?>

<h2><?php _e('Lisensi Saya', 'wp-license-manager'); ?></h2>

<?php if (!empty($licenses)) : ?>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
        <thead>
            <tr>
                <th class=""><span class="nobr"><?php _e('Produk', 'wp-license-manager'); ?></span></th>
                <th class="text-center"><span class="nobr"><?php _e('Kunci Lisensi', 'wp-license-manager'); ?></span></th>
                <th class="text-center"><span class="nobr"><?php _e('Domain Aktif', 'wp-license-manager'); ?></span></th>
                <th class="text-center"><span class="nobr"><?php _e('Kadaluwarsa', 'wp-license-manager'); ?></span></th>
                <th class="text-center"><span class="nobr"><?php _e('Status', 'wp-license-manager'); ?></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($licenses as $license) :
                $product = wc_get_product($license->product_id);
            ?>
                <tr class="woocommerce-orders-table__row">
                    <td>
                        <?php echo $product ? '<a href="' . esc_url($product->get_permalink()) . '">' . esc_html($product->get_name()) . '</a>' : 'Produk Dihapus'; ?>
                    </td>
                    <td style="font-family: monospace; user-select: all;">
                        <?php echo esc_html($license->license_key); ?>
                    </td>
                    <td class="text-center">
                        <?php echo esc_html($license->activation_domain) ?: '<em>Belum aktif</em>'; ?>
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
    .wplm-status { padding: 4px 8px; border-radius: 4px; color: #fff; font-size: 0.8em; }
    .wplm-status.status-active { background-color: #28a745; }
    .wplm-status.status-inactive { background-color: #ffc107; color: #333; }
    .wplm-status.status-expired { background-color: #6c757d; }
    .wplm-status.status-disabled { background-color: #dc3545; }
    .text-center { text-align: center; }
</style>
