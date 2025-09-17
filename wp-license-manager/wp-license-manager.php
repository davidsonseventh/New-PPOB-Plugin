<?php
/**
 * Plugin Name: WP License Manager
 * Description: Menjual dan mengelola kunci lisensi untuk produk digital melalui WooCommerce.
 * Version: 1.0.0
 * Author: Davidson Iglesias Rumondor
 * Text Domain: wp-license-manager
 */

if (!defined('WPINC')) {
    die;
}

define('WPLM_VERSION', '1.0.0');
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

// Inisialisasi kelas
new WPLM_Product();
new WPLM_API();
