<?php
defined('ABSPATH') || exit;

class WPPOB_Frontend {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Shortcodes
        add_shortcode('wppob_form', [$this, 'render_ppob_form']);
        add_shortcode('wppob_user_dashboard', [$this, 'render_user_dashboard']);
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('wppob-frontend-css', WP_PPOB_MANAGER_PLUGIN_URL . 'frontend/assets/css/frontend.css', [], WP_PPOB_MANAGER_VERSION);
        wp_enqueue_script('wppob-frontend-js', WP_PPOB_MANAGER_PLUGIN_URL . 'frontend/assets/js/frontend.js', ['jquery'], WP_PPOB_MANAGER_VERSION, true);
        wp_localize_script('wppob-frontend-js', 'wppob_frontend_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wppob_frontend_nonce')
        ]);
    }

    /**
     * Render the main PPOB form.
     * Use attributes to specify which category to show.
     * e.g., [wppob_form category_id="1"]
     */
    public function render_ppob_form($atts) {
        $atts = shortcode_atts(['category_id' => 0], $atts);
        $category_id = intval($atts['category_id']);

        ob_start();
        if ($category_id > 0) {
            // Include view for a specific category form
            include WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/views/view-form-ppob.php';
        } else {
            // Include view for displaying all categories
            include WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/views/view-custom-categories.php';
        }
        return ob_get_clean();
    }

    /**
     * Render the user dashboard.
     * e.g., [wppob_user_dashboard]
     */
    public function render_user_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Anda harus login untuk mengakses halaman ini.', 'wp-ppob') . '</p>';
        }

        ob_start();
        include WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/views/view-dashboard-user.php';
        return ob_get_clean();
    }
}
