<?php
/**
 * Plugin Name: WP PPOB Manager
 * Plugin URI: https://snackread.web.id/
 * Description: A complete PPOB (Payment Point Online Bank) solution for WordPress.
 * Version: 2.1.0
 * Author: Davidson Iglesias Rumondor
 * Author URI: https://snackread.web.id/
 * License: GPLv2 or later
 * Text Domain: wp-ppob
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define constants
 */
define( 'WP_PPOB_MANAGER_VERSION', '2.0.1' );
define( 'WP_PPOB_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_PPOB_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


/**
 * The code that runs during plugin activation.
 */
function activate_wp_ppob_manager() {
	require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/core/class-wppob-activator.php';
	WPPOB_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wp_ppob_manager() {
	require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/core/class-wppob-deactivator.php';
	WPPOB_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_ppob_manager' );
register_deactivation_hook( __FILE__, 'deactivate_wp_ppob_manager' );

/**
 * The core plugin class.
 * INI BAGIAN YANG DIPERBAIKI: Path dan nama file disesuaikan.
 */
require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/class-wp-ppob-manager.php';

/**
 * Begins execution of the plugin.
 */
function run_wp_ppob_manager() {
	$plugin = new WP_PPOB_Manager();
	$plugin->run();
}

// Jalankan plugin
run_wp_ppob_manager();