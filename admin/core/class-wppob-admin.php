<?php
defined('ABSPATH') || exit;

class WPPOB_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'process_forms']);
        
        // AJAX hooks
        add_action('wp_ajax_wppob_update_category_order', [$this, 'ajax_update_category_order']);
        
        // Hooks for settings update
        add_action('update_option_wppob_profit_amount', [$this, 'handle_settings_update'], 10, 2);
        add_action('update_option_wppob_profit_type', [$this, 'handle_settings_update'], 10, 2);
        add_action('update_option_wppob_license_key', [$this, 'validate_license_key_offline'], 10, 2);
    }

    public function add_admin_menu() {
        add_menu_page('PPOB Manager', 'PPOB Manager', 'manage_options', 'wppob-dashboard', [$this, 'render_page'], 'dashicons-store', 58);
        add_submenu_page('wppob-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'wppob-dashboard', [$this, 'render_page']);
        add_submenu_page('wppob-dashboard', 'Transaksi', 'Transaksi', 'manage_options', 'wppob-transactions', [$this, 'render_page']);
        add_submenu_page('wppob-dashboard', 'Saldo', 'Saldo', 'manage_options', 'wppob-balance', [$this, 'render_page']);
        add_submenu_page('wppob-dashboard', 'Manajemen Produk', 'Produk', 'manage_options', 'wppob-products', [$this, 'render_page']);
        add_submenu_page('wppob-dashboard', 'Manajemen Pengguna', 'Pengguna', 'manage_options', 'wppob-users', [$this, 'render_page']);
        add_submenu_page('wppob-dashboard', 'Kategori Tampilan', 'Kategori Tampilan', 'manage_options', 'wppob-display-categories', [$this, 'render_page']);
        add_submenu_page('wppob-dashboard', 'Edit Gambar Massal', 'Edit Gambar Massal', 'manage_options', 'wppob-bulk-edit-images', [$this, 'render_page']);
        add_submenu_page('wppob-dashboard', 'Pengaturan', 'Pengaturan', 'manage_options', 'wppob-settings', [$this, 'render_page']);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wppob-') === false) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_style('wppob-admin-css', WP_PPOB_MANAGER_PLUGIN_URL . 'admin/assets/css/admin.css', [], WP_PPOB_MANAGER_VERSION);
        
        if (strpos($hook, 'wppob-display-categories') !== false) {
            wp_enqueue_script('jquery-ui-sortable');
        }
        
        wp_enqueue_script('wppob-admin-js', WP_PPOB_MANAGER_PLUGIN_URL . 'admin/assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], WP_PPOB_MANAGER_VERSION, true);
        wp_localize_script('wppob-admin-js', 'wppob_admin_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wppob_admin_nonce')
        ]);
    }

    public function render_page() {
        $screen = get_current_screen();
        $page_slug = str_replace('toplevel_page_', '', $screen->id);
        $page_slug = str_replace('ppob-manager_', '', $page_slug);
        
        // Sanitize to prevent directory traversal
        $view_file = WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/views/view-' . str_replace('_', '-', $page_slug) . '.php';

        if (file_exists($view_file)) {
            include_once $view_file;
        } else {
            echo '<div class="wrap"><h2>Error: View file not found.</h2></div>';
        }
    }

    public function process_forms() {
        // This function can be expanded to handle various form submissions from the admin area
        // Example for display categories form
        if (isset($_POST['wppob_save_category_nonce']) && wp_verify_nonce($_POST['wppob_save_category_nonce'], 'wppob_save_category_action')) {
            $this->save_display_category();
        }
    }
    
    private function save_display_category() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_display_categories';

        $id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $products = isset($_POST['assigned_products']) ? array_map('intval', $_POST['assigned_products']) : [];

        $data = [
            'name' => sanitize_text_field($_POST['cat_name']),
            'image_id' => intval($_POST['cat_image_id']),
            'display_style' => sanitize_key($_POST['display_style']),
            'assigned_products' => json_encode($products),
            'product_display_style' => sanitize_key($_POST['product_display_style']),
            'product_display_mode' => sanitize_key($_POST['product_display_mode']),
        ];

        if ($id > 0) {
            $child_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$table_name} WHERE parent_id = %d", $id));
            if ($child_count > 0) {
                $data['assigned_products'] = '[]'; 
            }
            $wpdb->update($table_name, $data, ['id' => $id]);
        } else {
            $wpdb->insert($table_name, $data);
        }
        
        // Add admin notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Kategori berhasil disimpan.</p></div>';
        });
    }

    public function handle_settings_update($old_value, $new_value) {
        if ($old_value !== $new_value && class_exists('WPPOB_Products')) {
            $product_manager = new WPPOB_Products();
            $product_manager->bulk_update_prices();
        }
    }

    public function ajax_update_category_order() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) { wp_send_json_error(); }

        $order = isset($_POST['order']) ? (array) $_POST['order'] : [];
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_display_categories';

        foreach ($order as $sort_order => $item) {
            $id = intval(str_replace('cat-', '', $item['id']));
            $parent_id = !empty($item['parent_id']) ? intval(str_replace('cat-', '', $item['parent_id'])) : 0;
            if ($id > 0) {
                $wpdb->update($table_name, ['sort_order' => $sort_order + 1, 'parent_id' => $parent_id], ['id' => $id]);
            }
        }
        wp_send_json_success();
    }

    public function validate_license_key_offline($old_value, $new_value) {
        // This functionality should be moved to a dedicated license handler class in the future
        // For now, keeping it here for simplicity
        // The encryption keys should be defined securely, e.g., in wp-config.php
        if (!defined('WPPPOB_LICENSE_ENCRYPTION_KEY') || !defined('WPPPOB_LICENSE_ENCRYPTION_IV')) {
            define('WPPPOB_LICENSE_ENCRYPTION_KEY', 'your-very-long-and-secret-key-goes-here');
            define('WPPPOB_LICENSE_ENCRYPTION_IV', '16-random-chars-iv'); 
        }

        $license_key = trim($new_value);
        if (empty($license_key)) { update_option('wppob_license_status', 'invalid'); return; }

        $encrypted_data = base64_decode($license_key, true);
        if ($encrypted_data === false) { update_option('wppob_license_status', 'invalid'); return; }

        $decrypted_string = openssl_decrypt($encrypted_data, 'aes-256-cbc', WPPPOB_LICENSE_ENCRYPTION_KEY, 0, WPPPOB_LICENSE_ENCRYPTION_IV);
        if ($decrypted_string === false) { update_option('wppob_license_status', 'invalid'); return; }
        
        $parts = explode('|', $decrypted_string);
        if (count($parts) !== 2) { update_option('wppob_license_status', 'invalid'); return; }

        $license_domain = $parts[0];
        $expiry_date_str = $parts[1];

        $site_domain = home_url();
        $site_domain = preg_replace('/^https?:\/\//', '', $site_domain);
        $site_domain = preg_replace('/^www\./', '', $site_domain);
        $site_domain = rtrim($site_domain, '/');

        if ($license_domain !== $site_domain) { update_option('wppob_license_status', 'invalid'); return; }

        if (time() > strtotime($expiry_date_str)) { update_option('wppob_license_status', 'expired'); return; }

        update_option('wppob_license_status', 'valid');
    }

    public function register_settings() {
        register_setting('wppob_settings_group', 'wppob_api_username');
        register_setting('wppob_settings_group', 'wppob_api_key');
        register_setting('wppob_settings_group', 'wppob_profit_type');
        register_setting('wppob_settings_group', 'wppob_profit_amount');
        register_setting('wppob_settings_group', 'wppob_license_key');

        add_settings_section('wppob_api_section', __('Kredensial API', 'wp-ppob'), null, 'wppob-settings');
        add_settings_field('wppob_api_username', __('Username API', 'wp-ppob'), [$this, 'render_api_username_field'], 'wppob-settings', 'wppob_api_section');
        add_settings_field('wppob_api_key', __('API Key', 'wp-ppob'), [$this, 'render_api_key_field'], 'wppob-settings', 'wppob_api_section');
        
        add_settings_section('wppob_license_section', __('Aktivasi Lisensi Plugin', 'wp-ppob'), [$this, 'render_license_section_text'], 'wppob-settings');
        add_settings_field('wppob_license_key', __('Kode Lisensi', 'wp-ppob'), [$this, 'render_license_key_field'], 'wppob-settings', 'wppob_license_section');

        add_settings_section('wppob_profit_section', __('Pengaturan Keuntungan', 'wp-ppob'), null, 'wppob-settings');
        add_settings_field('wppob_profit_type', __('Tipe Keuntungan', 'wp-ppob'), [$this, 'render_profit_type_field'], 'wppob-settings', 'wppob_profit_section');
        add_settings_field('wppob_profit_amount', __('Jumlah Keuntungan', 'wp-ppob'), [$this, 'render_profit_amount_field'], 'wppob-settings', 'wppob_profit_section');
    }
    
    // --- Render Functions for Settings --- //
    public function render_api_username_field() {
        $value = get_option('wppob_api_username', '');
        echo '<input type="text" name="wppob_api_username" value="' . esc_attr($value) . '" class="regular-text" placeholder="Username Digiflazz Anda">';
    }

    public function render_api_key_field() {
        $value = get_option('wppob_api_key', '');
        echo '<input type="password" name="wppob_api_key" value="' . esc_attr($value) . '" class="regular-text" placeholder="Production API Key Anda">';
    }

    public function render_license_section_text() {
        $status = get_option('wppob_license_status', 'invalid');
        $status_text = ucfirst($status);
        $status_color = ($status === 'valid') ? 'green' : 'red';
        echo '<p>Masukkan kode lisensi Anda untuk mengaktifkan semua fitur premium, termasuk pengaturan keuntungan.</p>';
        echo '<strong>Status Lisensi: <span style="color:' . $status_color . '; text-transform: uppercase;">' . $status_text . '</span></strong>';
    }

    public function render_license_key_field() {
        $value = get_option('wppob_license_key', '');
        echo '<input type="text" name="wppob_license_key" value="' . esc_attr($value) . '" class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX">';
        echo '<p class="description">Tidak punya lisensi? <a href="https://your-license-panel.com/purchase.php" target="_blank">Beli di sini</a>.</p>';
    }

    public function render_profit_type_field() {
        $is_valid = get_option('wppob_license_status', 'invalid') === 'valid';
        if (!$is_valid) {
            echo '<select disabled><option>Fitur Terkunci</option></select>';
            echo '<p class="description">Aktifkan lisensi untuk menggunakan fitur ini.</p>';
            return;
        }
        $value = get_option('wppob_profit_type', 'fixed');
        echo '<select name="wppob_profit_type"><option value="fixed"'.selected($value,'fixed',false).'>'.__('Tetap (Fixed)','wp-ppob').'</option><option value="percentage"'.selected($value,'percentage',false).'>'.__('Persentase (%)','wp-ppob').'</option></select>';
    }

    public function render_profit_amount_field() {
        $is_valid = get_option('wppob_license_status', 'invalid') === 'valid';
        if (!$is_valid) {
            echo '<input type="number" value="0" class="regular-text" readonly>';
            echo '<input type="hidden" name="wppob_profit_amount" value="0">';
            return;
        }
        $value = get_option('wppob_profit_amount', 1000);
        echo '<input type="number" name="wppob_profit_amount" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . __('Isi angka saja. Contoh: 1000 untuk profit Rp 1.000, atau 5 untuk profit 5%.', 'wp-ppob') . '</p>';
    }
}
