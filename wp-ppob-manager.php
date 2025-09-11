<?php
/**
 * Plugin Name: WP PPOB Manager
 * Plugin URI: https://yourwebsite.com/
 * Description: A complete PPOB (Payment Point Online Bank) solution for WordPress.
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com/
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
define( 'WP_PPOB_MANAGER_VERSION', '2.0.0' );
define( 'WP_PPOB_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_PPOB_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/core/class-wppob-activator.php
 */
function activate_wp_ppob_manager() {
	require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/core/class-wppob-activator.php';
	WPPOB_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/core/class-wppob-deactivator.php
 */
function deactivate_wp_ppob_manager() {
	require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/core/class-wppob-deactivator.php';
	WPPOB_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_ppob_manager' );
register_deactivation_hook( __FILE__, 'deactivate_wp_ppob_manager' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 *
 * DIGANTI DARI 'require' menjadi 'require_once' UNTUK MENCEGAH REDECLARATION.
 */
require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/class-wppob-loader.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_ppob_manager() {

	$plugin = new WP_PPOB_Manager();
	$plugin->run();

}
run_wp_ppob_manager();
