<?php
/**
 * Aksi saat plugin dihapus (uninstall).
 *
 * @package WP_PPOB_Manager
 */

// Jika uninstall tidak dipanggil dari WordPress, keluar.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Hapus opsi dari tabel wp_options
delete_option('wppob_api_username');
delete_option('wppob_api_key');
delete_option('wppob_profit_type');
delete_option('wppob_profit_amount');
delete_option('wppob_license_key');
delete_option('wppob_license_status');
delete_option('wppob_last_sync');
delete_option('wppob_grid_columns');

// Hapus custom tables dari database
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wppob_transactions");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wppob_display_categories");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wppob_balance_mutations");

// Hapus data post meta dari produk
$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_wppob_%'");
