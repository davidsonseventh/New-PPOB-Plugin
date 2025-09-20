<?php
defined('ABSPATH') || exit;

class WP_PPOB_Manager {

    protected $version;
    protected $plugin_name;

    public function __construct() {
        $this->version = defined('WP_PPOB_MANAGER_VERSION') ? WP_PPOB_MANAGER_VERSION : '2.0.1';
        $this->plugin_name = 'wp-ppob-manager';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Core Helpers
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/core/helpers.php';
        
        // Admin & Frontend Logic Handlers
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/core/class-wppob-admin.php';
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/core/class-wppob-dashboard.php';
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/core/class-wppob-frontend.php';

        // API & Database Classes
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/api/class-wppob-api.php';
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/database/class-wppob-products.php';
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/database/class-wppob-orders.php';
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/database/class-wppob-users.php';
        
        // Payments & Balances
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/payments/class-wppob-balances.php';
        
        // [TAMBAHKAN BARIS INI]
        // Referral System
        require_once WP_PPOB_MANAGER_PLUGIN_DIR . 'includes/core/class-wppob-referral.php';
    }

    private function define_admin_hooks() {
        // Inisialisasi semua hook admin
        new WPPOB_Admin();
    }

    private function define_public_hooks() {
        // Inisialisasi semua hook frontend
        new WPPOB_Frontend();
    }

    public function run() {
        // Plugin berjalan melalui inisialisasi di constructor
    }
}