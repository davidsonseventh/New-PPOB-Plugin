<?php
defined('ABSPATH') || exit;

class WP_PPOB_Manager {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = defined('WP_PPOB_MANAGER_VERSION') ? WP_PPOB_MANAGER_VERSION : '2.0.0';
        $this->plugin_name = 'wp-ppob-manager';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Core
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/core/helpers.php';
        
        // Admin & Frontend Logic
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/core/class-wppob-admin.php';
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/core/class-wppob-dashboard.php';
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/core/class-wppob-frontend.php';

        // API & Database
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/api/class-wppob-api.php';
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/database/class-wppob-products.php';
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/database/class-wppob-orders.php';
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/database/class-wppob-users.php';
        
        // Payments
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/payments/class-wppob-balances.php';
    }

    private function define_admin_hooks() {
        $admin = new WPPOB_Admin();
        // All admin hooks are now inside the WPPOB_Admin class
    }

    private function define_public_hooks() {
        $frontend = new WPPOB_Frontend();
        // All frontend hooks are now inside the WPPOB_Frontend class
    }

    public function run() {
        // The plugin is now running through the constructors of admin and frontend classes
    }
}
