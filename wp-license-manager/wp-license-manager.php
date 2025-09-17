<?php
/**
 * Plugin Name: WP License Manager
 * Description: Menjual dan mengelola kunci lisensi untuk produk digital melalui WooCommerce.
 * Version: 1.0.1
 * Author: Davidson Iglesias Rumondor
 * Text Domain: wp-license-manager
 */

if (!defined('WPINC')) {
    die;
}

define('WPLM_VERSION', '1.0.1');
define('WPLM_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Fungsi aktivasi plugin
function wplm_activate() {
    require_once WPLM_PLUGIN_DIR . 'includes/class-license-manager-activator.php';
    WPLM_Activator::activate();
}
register_activation_hook(__FILE__, 'wplm_activate');

// Memuat kelas-kelas inti
require_once WPLM_PLUGIN_DIR . 'includes/class-license-manager-generator.php';
require_once WPLM_PLUGIN_DIR . 'includes/class-license-manager-product.php';
require_once WPLM_PLUGIN_DIR . 'includes/class-license-manager-api.php';
require_once WPLM_PLUGIN_DIR . 'includes/class-license-manager-admin.php';
require_once WPLM_PLUGIN_DIR . 'includes/class-license-manager-frontend.php';

// Inisialisasi kelas
new WPLM_Product();
new WPLM_API();
new WPLM_Admin();
new WPLM_Frontend();

// --- Integrasi Halaman Akun Saya WooCommerce ---
add_filter('query_vars', function ($vars) { $vars[] = 'my-licenses'; return $vars; }, 0);
add_filter('woocommerce_account_menu_items', function ($items) {
    $new_items = [];
    $logout_item = isset($items['customer-logout']) ? $items['customer-logout'] : '';
    if(isset($items['customer-logout'])) unset($items['customer-logout']);
    foreach ($items as $key => $value) {
        $new_items[$key] = $value;
        if ($key === 'orders') {
            $new_items['my-licenses'] = __('Lisensi Saya', 'wp-license-manager');
        }
    }
    if($logout_item) $new_items['customer-logout'] = $logout_item;
    return $new_items;
});
add_action('woocommerce_account_my-licenses_endpoint', function () {
    include_once WPLM_PLUGIN_DIR . 'templates/my-account/my-licenses.php';
});
