<?php
/**
 * Plugin Name: WP PPOB Login
 * Description: Plugin untuk login dan registrasi menggunakan nomor HP dan OTP WhatsApp, melengkapi WP PPOB Manager.
 * Version: 1.0
 * Author: Davidson Iglesias Rumondor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_PPOB_LOGIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_PPOB_LOGIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WP_PPOB_LOGIN_PLUGIN_DIR . 'includes/class-wppob-login-core.php';

function run_wppob_login() {
    $plugin = new WPPOB_Login_Core();
    $plugin->run();
}
run_wppob_login();
