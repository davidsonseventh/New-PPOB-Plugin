<?php
/**
 * Plugin Name: WP PPOB Login & Register
 * Description: Sistem login dan registrasi via No. HP & OTP WhatsApp (Fonnte) untuk WP PPOB Manager.
 * Version: 1.1
 * Author: Davidson Iglesias Rumondor
 * Author URI: https://snackread.web.id/
 */

if (!defined('WPINC')) {
    die;
}

define('WPPPOB_LOGIN_VERSION', '1.1');
define('WPPPOB_LOGIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPPPOB_LOGIN_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WPPPOB_LOGIN_PLUGIN_DIR . 'includes/class-wppob-login-core.php';

function run_wppob_login_register() {
    $plugin = new WPPOB_Login_Core();
    $plugin->run();
}
run_wppob_login_register();