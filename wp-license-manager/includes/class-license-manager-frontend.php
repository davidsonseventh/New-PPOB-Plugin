<?php
if (!defined('WPINC')) {
    die;
}

class WPLM_Frontend {
    public function __construct() {
        add_shortcode('wplm_find_license', [$this, 'render_find_license_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('wplm-frontend-js', plugin_dir_url(__FILE__) . '../assets/js/frontend.js', ['jquery'], WPLM_VERSION, true);
    }

    public function render_find_license_form() {
        ob_start();
        include_once WPLM_PLUGIN_DIR . 'templates/shortcodes/find-license-form.php';
        return ob_get_clean();
    }
}
