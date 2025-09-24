<?php
defined('ABSPATH') || exit;


// --- HARAP GANTI KUNCI ENKRIPSI INI DENGAN KUNCI RAHASIA ANDA SENDIRI ---
// Kunci ini harus sama persis dengan yang Anda gunakan di panel generator lisensi (file config.php).
// Ganti dengan 32 karakter acak yang kuat.
define('WPPPOB_LICENSE_ENCRYPTION_KEY', 'Ezidcode_MyS3cr3tKey_f0r_L1c3n5e'); 
// Ganti dengan 16 karakter acak yang kuat.
define('WPPPOB_LICENSE_ENCRYPTION_IV', 'MyS3cur316CharIV'); 




class WPPOB_Admin {

    public function __construct() {
        
        
        
        
        
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // INI BAGIAN PENTING YANG DITAMBAHKAN KEMBALI
        add_action('admin_init', [$this, 'handle_category_form_submission']);

        // AJAX hooks
        add_action('wp_ajax_wppob_sync_products', [$this, 'ajax_sync_products']);
        add_action('wp_ajax_wppob_update_category_order', [$this, 'ajax_update_category_order']);
        add_action('wp_ajax_wppob_update_product_order_in_category', [$this, 'ajax_update_product_order']);
        add_action('wp_ajax_wppob_bulk_update_images', [$this, 'ajax_bulk_update_images']);
         add_action('wp_ajax_wppob_prepare_sync', [$this, 'ajax_prepare_sync']);
        add_action('wp_ajax_wppob_process_sync_batch', [$this, 'ajax_process_sync_batch']);
    
        
        // Hooks for settings update
        add_action('update_option_wppob_profit_amount', [$this, 'handle_settings_update'], 10, 2);
        add_action('update_option_wppob_profit_type', [$this, 'handle_settings_update'], 10, 2);
        add_action('update_option_wppob_license_key', [$this, 'validate_license_key_offline'], 10, 2);
        
        
        
        
        
      // ...Fungsi Untuk Mengelola User
        add_action('wp_ajax_wppob_search_users', [$this, 'ajax_search_users']);
        add_action('wp_ajax_wppob_get_user_details', [$this, 'ajax_get_user_details']);
        add_action('wp_ajax_wppob_adjust_user_balance', [$this, 'ajax_adjust_user_balance']);
    // ...Akhir Fungsi
      
        
        // --- Menampilkan User ---
        add_action('wp_ajax_wppob_get_recent_users', [$this, 'ajax_get_recent_users']);
        
    }


 /**
     * Helper function to check if the license is currently valid.
     *
     * @return bool
     */
    public function is_license_valid() {
        return get_option('wppob_license_status') === 'valid';
    }

   

    /**
     * Menambahkan menu admin.
     */
    public function add_admin_menu() {
        add_menu_page('PPOB Manager', 'PPOB Manager', 'manage_options', 'wppob-dashboard', [$this, 'render_dashboard_page'], 'dashicons-store', 58);
        
        add_submenu_page('wppob-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'wppob-dashboard', [$this, 'render_dashboard_page']);
        add_submenu_page('wppob-dashboard', 'Transaksi', 'Transaksi', 'manage_options', 'wppob-transactions', [$this, 'render_transactions_page']);
        add_submenu_page('wppob-dashboard', 'Saldo', 'Saldo', 'manage_options', 'wppob-balance', [$this, 'render_balance_page']);
        add_submenu_page('wppob-dashboard', 'Produk', 'Produk', 'manage_options', 'wppob-products', [$this, 'render_products_page']);
        add_submenu_page('wppob-dashboard', 'Pengguna', 'Pengguna', 'manage_options', 'wppob-users', [$this, 'render_users_page']);
        // [TAMBAHKAN BARIS INI]
        add_submenu_page('wppob-dashboard', 'Referral', 'Referral', 'manage_options', 'wppob-referral', [$this, 'render_referral_page']);
        add_submenu_page('wppob-dashboard', 'Kategori Tampilan', 'Kategori Tampilan', 'manage_options', 'wppob-display-categories', [$this, 'render_display_categories_page']);
        add_submenu_page('wppob-dashboard', 'Edit Gambar Massal', 'Edit Gambar Massal', 'manage_options', 'wppob-bulk-edit-images', [$this, 'render_bulk_edit_images_page']);
        add_submenu_page('wppob-dashboard', 'Pengaturan', 'Pengaturan', 'manage_options', 'wppob-settings', [$this, 'render_settings_page']);
    }

    /**
     * Memuat aset CSS dan JavaScript.
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wppob-') === false) return;
        
        wp_enqueue_media();
        wp_enqueue_style('wppob-admin-css', WP_PPOB_MANAGER_PLUGIN_URL . 'admin/assets/css/admin.css', [], WP_PPOB_MANAGER_VERSION);
        
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('wppob-admin-js', WP_PPOB_MANAGER_PLUGIN_URL . 'admin/assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], WP_PPOB_MANAGER_VERSION, true);
        
        wp_localize_script('wppob-admin-js', 'wppob_admin_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wppob_admin_nonce')
        ]);
    }

    /**
     * Fungsi baru untuk menangani submit form kategori.
     */
    public function handle_category_form_submission() {
        if (!isset($_POST['wppob_save_category_nonce']) || !wp_verify_nonce($_POST['wppob_save_category_nonce'], 'wppob_save_category_action')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('Anda tidak memiliki izin untuk melakukan aksi ini.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_display_categories';

        $id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $products = isset($_POST['assigned_products']) ? array_map('intval', $_POST['assigned_products']) : [];

        $data = [
            'name' => sanitize_text_field($_POST['cat_name']),
            'parent_id' => isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0, // <-- TAMBAHKAN BARIS INI
            'image_id' => intval($_POST['cat_image_id']),
            'display_style' => sanitize_key($_POST['display_style']),
            'assigned_products' => json_encode($products),
            'product_display_style' => sanitize_key($_POST['product_display_style']),
            'product_display_mode' => sanitize_key($_POST['product_display_mode']),
        ];

        if ($id > 0) {
            $wpdb->update($table_name, $data, ['id' => $id]);
        } else {
            $wpdb->insert($table_name, $data);
        }
        
        // Redirect untuk menghindari submit ulang form
        wp_redirect(admin_url('admin.php?page=wppob-display-categories&status=saved'));
        exit;
    }

    // --- FUNGSI RENDER HALAMAN ---
    // [TAMBAHKAN FUNGSI INI]
    public function render_referral_page() { include_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/views/view-referral.php'; }
    public function render_dashboard_page() { include_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/views/view-dashboard.php'; }
    public function render_transactions_page() { include_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/views/view-transactions.php'; }
    public function render_balance_page() { include_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/views/view-balance.php'; }
    public function render_products_page() { include_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/views/view-products.php'; }
    public function render_users_page() { include_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/views/view-users.php'; }
    public function render_display_categories_page() { 
        if (isset($_GET['status']) && $_GET['status'] == 'saved') {
            echo '<div class="notice notice-success is-dismissible"><p>Kategori berhasil disimpan.</p></div>';
        
        }
        include_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/views/view-display-categories.php'; 
        
        
    }
    public function render_bulk_edit_images_page() { include_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/views/view-bulk-edit-images.php'; }
    public function render_settings_page() { include_once WP_PPOB_MANAGER_PLUGIN_DIR . 'admin/views/view-settings.php'; }

     public function register_settings() {
        // Mendaftarkan grup pengaturan
        register_setting('wppob_settings_group', 'wppob_api_username');
        register_setting('wppob_settings_group', 'wppob_api_key');
        register_setting('wppob_settings_group', 'wppob_profit_type');
        register_setting('wppob_settings_group', 'wppob_profit_amount');
        register_setting('wppob_settings_group', 'wppob_license_key');
        register_setting('wppob_settings_group', 'wppob_webhook_secret');
        register_setting('wppob_settings_group', 'wppob_webhook_id');
        
        // Pengaturan Grid Kategori
        register_setting('wppob_settings_group', 'wppob_grid_columns');
        register_setting('wppob_settings_group', 'wppob_category_image_size');
        
        
        // [TAMBAHKAN KODE INI]
        // Pengaturan Referral
        register_setting('wppob_settings_group', 'wppob_referral_enable');
        register_setting('wppob_settings_group', 'wppob_referral_type');
        register_setting('wppob_settings_group', 'wppob_signup_bonus_amount');
        register_setting('wppob_settings_group', 'wppob_commission_type');
        register_setting('wppob_settings_group', 'wppob_commission_amount');
        // [AKHIR DARI KODE TAMBAHAN]
        
        register_setting('wppob_settings_group', 'wppob_transfer_enable');
        
        // Seksi untuk Tampilan
        add_settings_section('wppob_display_section', __('Pengaturan Tampilan', 'wp-ppob'), null, 'wppob-settings');
        add_settings_field('wppob_grid_columns', __('Kolom Grid Kategori', 'wp-ppob'), [$this, 'render_grid_columns_field'], 'wppob-settings', 'wppob_display_section');
        add_settings_field('wppob_category_image_size', __('Ukuran Ikon Kategori (px)', 'wp-ppob'), [$this, 'render_category_image_size_field'], 'wppob-settings', 'wppob_display_section');

        // Menambahkan seksi untuk API
        add_settings_section('wppob_api_section', __('Kredensial API', 'wp-ppob'), null, 'wppob-settings');
        add_settings_field('wppob_api_username', __('Username API', 'wp-ppob'), [$this, 'render_api_username_field'], 'wppob-settings', 'wppob_api_section');
        
        // Webhook
        add_settings_field('wppob_api_key', __('API Key', 'wp-ppob'), [$this, 'render_api_key_field'], 'wppob-settings', 'wppob_api_section');
        add_settings_field('wppob_webhook_id', __('Webhook ID', 'wp-ppob'), [$this, 'render_webhook_id_field'], 'wppob-settings', 'wppob_api_section');
        add_settings_field('wppob_webhook_secret', __('Webhook Secret Key', 'wp-ppob'), [$this, 'render_webhook_secret_field'], 'wppob-settings', 'wppob_api_section');
        
        // Menambahkan seksi untuk Lisensi
        add_settings_section('wppob_license_section', __('Aktivasi Lisensi Plugin', 'wp-ppob'), [$this, 'render_license_section_text'], 'wppob-settings');
        add_settings_field('wppob_license_key', __('Kode Lisensi', 'wp-ppob'), [$this, 'render_license_key_field'], 'wppob-settings', 'wppob_license_section');

        // Menambahkan seksi untuk Keuntungan
        add_settings_section('wppob_profit_section', __('Pengaturan Keuntungan', 'wp-ppob'), null, 'wppob-settings');
        add_settings_field('wppob_profit_type', __('Tipe Keuntungan', 'wp-ppob'), [$this, 'render_profit_type_field'], 'wppob-settings', 'wppob_profit_section');
        add_settings_field('wppob_profit_amount', __('Jumlah Keuntungan', 'wp-ppob'), [$this, 'render_profit_amount_field'], 'wppob-settings', 'wppob_profit_section');
        
        // Baris Untuk Transfer Section
        add_settings_section('wppob_transfer_section', __('Pengaturan Transfer Saldo', 'wp-ppob'), null, 'wppob-settings');
add_settings_field('wppob_transfer_enable', __('Aktifkan Fitur Transfer', 'wp-ppob'), [$this, 'render_transfer_enable_field'], 'wppob-settings', 'wppob_transfer_section');
        
        
        
        // [TAMBAHKAN KODE INI]
        // Seksi untuk Referral
        add_settings_section('wppob_referral_section', __('Pengaturan Referral', 'wp-ppob'), null, 'wppob-settings');
        add_settings_field('wppob_referral_enable', __('Aktifkan Sistem Referral', 'wp-ppob'), [$this, 'render_referral_enable_field'], 'wppob-settings', 'wppob_referral_section');
        add_settings_field('wppob_referral_type', __('Jenis Program Referral', 'wp-ppob'), [$this, 'render_referral_type_field'], 'wppob-settings', 'wppob_referral_section');
        add_settings_field('wppob_signup_bonus_amount', __('Bonus Pendaftaran (Rp)', 'wp-ppob'), [$this, 'render_signup_bonus_field'], 'wppob-settings', 'wppob_referral_section');
        add_settings_field('wppob_commission_type', __('Jenis Komisi Transaksi', 'wp-ppob'), [$this, 'render_commission_type_field'], 'wppob-settings', 'wppob_referral_section');
        add_settings_field('wppob_commission_amount', __('Jumlah Komisi', 'wp-ppob'), [$this, 'render_commission_amount_field'], 'wppob-settings', 'wppob_referral_section');
        // [AKHIR DARI KODE TAMBAHAN]
    }

    // --- FUNGSI RENDER UNTUK SETIAP KOLOM PENGATURAN ---

    public function render_api_username_field() {
        $value = get_option('wppob_api_username', '');
        echo '<input type="text" name="wppob_api_username" value="' . esc_attr($value) . '" class="regular-text" placeholder="Username Digiflazz Anda">';
    }

    public function render_api_key_field() {
        $value = get_option('wppob_api_key', '');
        echo '<input type="password" name="wppob_api_key" value="' . esc_attr($value) . '" class="regular-text" placeholder="Production API Key Anda">';
    }



    public function render_webhook_id_field() {
    $value = get_option('wppob_webhook_id', '');
    echo '<input type="text" name="wppob_webhook_id" value="' . esc_attr($value) . '" class="regular-text" placeholder="ID unik dari webhook Digiflazz">';
    echo '<p class="description">' . __('Salin Webhook ID dari dashboard Digiflazz dan tempelkan di sini.', 'wp-ppob') . '</p>';
}


public function render_webhook_secret_field() {
    $value = get_option('wppob_webhook_secret', '');
    echo '<input type="text" name="wppob_webhook_secret" value="' . esc_attr($value) . '" class="regular-text" placeholder="Kunci rahasia untuk validasi webhook">';
    echo '<p class="description">' . __('Buat sebuah string acak dan kuat, lalu masukkan di sini DAN di kolom Secret pada Digiflazz.', 'wp-ppob') . '</p>';
}




    public function render_license_section_text() {
        $status = get_option('wppob_license_status', 'invalid');
        $status_text = ucfirst($status);
        $status_color = ($status === 'valid') ? 'green' : 'red';
        echo '<p>Masukkan kode lisensi Anda untuk mengaktifkan semua fitur premium.</p>';
        echo '<strong>Status Lisensi: <span style="color:' . $status_color . '; text-transform: uppercase;">' . $status_text . '</span></strong>';
        
        // TAMBAHKAN INI: Link pembelian
        if ($status !== 'valid') {
            // Ganti URL '#' dengan URL halaman penjualan lisensi Anda nanti
            echo '<p>Belum memiliki kunci lisensi? <a href="https://ezidcode.com/product/plugin-wp-ppob-manager/" target="_blank">Dapatkan di sini</a>.</p>';
        }
    }

    public function render_license_key_field() {
        $value = get_option('wppob_license_key', '');
        echo '<input type="text" name="wppob_license_key" value="' . esc_attr($value) . '" class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX">';
    }

    public function render_profit_type_field() {
        $value = get_option('wppob_profit_type', 'fixed');
        $disabled = $this->is_license_valid() ? '' : 'disabled="disabled"';
        echo '<select name="wppob_profit_type" ' . $disabled . '><option value="fixed"'.selected($value,'fixed',false).'>'.__('Tetap (Fixed)','wp-ppob').'</option><option value="percentage"'.selected($value,'percentage',false).'>'.__('Persentase (%)','wp-ppob').'</option></select>';
    }

    public function render_profit_amount_field() {
        $value = get_option('wppob_profit_amount', 0);
        $disabled = $this->is_license_valid() ? '' : 'disabled="disabled"';
        echo '<input type="number" name="wppob_profit_amount" value="' . esc_attr($value) . '" class="regular-text" ' . $disabled . '>';
        echo '<p class="description">' . __('Isi angka saja. Contoh: 1000 untuk profit Rp 1.000, atau 5 untuk profit 5%. Fitur ini hanya aktif dengan lisensi valid.', 'wp-ppob') . '</p>';
    }
    

   
   public function render_grid_columns_field() {
        $value = get_option('wppob_grid_columns', 4);
        echo '<input type="number" name="wppob_grid_columns" value="' . esc_attr($value) . '" class="small-text" min="1" max="10">';
        echo '<p class="description">' . __('Jumlah ikon kategori yang ditampilkan per baris (misal: 4).', 'wp-ppob') . '</p>';
    }

    public function render_category_image_size_field() {
        $value = get_option('wppob_category_image_size', 60);
        echo '<input type="number" name="wppob_category_image_size" value="' . esc_attr($value) . '" class="small-text" min="20" max="150">';
        echo '<p class="description">' . __('Ukuran ikon dalam pixel (misal: 60). Ini akan mengatur tinggi dan lebar ikon.', 'wp-ppob') . '</p>';
    }
   

    // --- FUNGSI LAINNYA & AJAX ---

    public function handle_settings_update($old_value, $new_value) {
        if ($old_value !== $new_value && class_exists('WPPOB_Products')) {
            $product_manager = new WPPOB_Products();
            $product_manager->bulk_update_prices();
        }
    }

   // WP-PPOB-Manager/includes/class-wppob-admin.php

/**
 * Memvalidasi kunci lisensi secara offline saat opsi disimpan.
 *
 * @param mixed $old_value Nilai lama dari opsi.
 * @param string $new_value Kunci lisensi baru yang dimasukkan.
 * @return string Mengembalikan nilai baru untuk disimpan.
 */
public function validate_license_key_offline($old_value, $new_value) {
    
    $license_key = trim($new_value);

    // Fungsi helper untuk mereset profit dan memperbarui harga
    $reset_profit_and_update_prices = function() {
        update_option('wppob_profit_amount', 0);
        if (class_exists('WPPOB_Products')) {
            $product_manager = new WPPOB_Products();
            $product_manager->bulk_update_prices();
        }
    };

    // 1. Jika kunci dikosongkan, nonaktifkan lisensi.
    if (empty($license_key)) {
        update_option('wppob_license_status', 'inactive');
        add_settings_error('wppob_license_errors', 'license_cleared', 'Kunci lisensi dihapus, status menjadi tidak aktif. Keuntungan direset.', 'warning');
        $reset_profit_and_update_prices();
        return $new_value; 
    }

    // 2. Decode base64
    $encrypted_data = base64_decode($license_key, true);
    if ($encrypted_data === false) {
        update_option('wppob_license_status', 'invalid');
        add_settings_error('wppob_license_errors', 'license_format_error', 'Format kunci lisensi tidak valid. Keuntungan direset.', 'error');
        $reset_profit_and_update_prices();
        return $new_value;
    }

    // 3. Dekripsi data
    $decrypted_string = openssl_decrypt(
        $encrypted_data,
        'aes-256-cbc',
        WPPPOB_LICENSE_ENCRYPTION_KEY,
        0,
        WPPPOB_LICENSE_ENCRYPTION_IV
    );

    if ($decrypted_string === false) {
        update_option('wppob_license_status', 'invalid');
        add_settings_error('wppob_license_errors', 'license_key_error', 'Kunci lisensi ini tidak valid atau rusak. Keuntungan direset.', 'error');
        $reset_profit_and_update_prices();
        return $new_value;
    }
    
    // 4. Pisahkan data: domain|tanggal
    $parts = explode('|', $decrypted_string);
    if (count($parts) !== 2) {
        update_option('wppob_license_status', 'invalid');
        add_settings_error('wppob_license_errors', 'license_data_error', 'Data di dalam lisensi tidak lengkap atau korup. Keuntungan direset.', 'error');
        $reset_profit_and_update_prices();
        return $new_value;
    }

    $license_domain = $parts[0];
    $expiry_date_str = $parts[1];

    // 5. Validasi Domain
    $site_domain = home_url();
    $site_domain = preg_replace('/^https?:\/\//', '', $site_domain);
    $site_domain = preg_replace('/^www\./i', '', $site_domain);
    $site_domain = rtrim($site_domain, '/');

    if (strcasecmp($license_domain, $site_domain) !== 0) {
        update_option('wppob_license_status', 'invalid_domain');
        add_settings_error('wppob_license_errors', 'license_domain_error', 'Kunci lisensi ini tidak berlaku untuk domain ' . esc_html($site_domain) . '. Keuntungan direset.', 'error');
        $reset_profit_and_update_prices();
        return $new_value;
    }

    // 6. Validasi Tanggal Kadaluwarsa
    $expiry_timestamp = strtotime($expiry_date_str);
    
    if ($expiry_timestamp === false) {
        update_option('wppob_license_status', 'invalid');
        add_settings_error('wppob_license_errors', 'license_date_format_error', 'Format tanggal di dalam lisensi tidak valid. Keuntungan direset.', 'error');
        $reset_profit_and_update_prices();
        return $new_value;
    }
    
    if (time() > $expiry_timestamp + (24 * 60 * 60 - 1)) {
        update_option('wppob_license_status', 'expired');
        add_settings_error('wppob_license_errors', 'license_expired', 'Lisensi Anda telah kedaluwarsa pada tanggal ' . date_i18n('d F Y', $expiry_timestamp) . '. Keuntungan direset.', 'error');
        $reset_profit_and_update_prices();
        return $new_value;
    }

    // 7. Jika semua validasi lolos
    update_option('wppob_license_status', 'valid');
    add_settings_error('wppob_license_errors', 'license_success', 'Lisensi berhasil diaktifkan. Berlaku hingga ' . date_i18n('d F Y', $expiry_timestamp) . '.', 'success');
    
    return $new_value;
}


    /**
     * AJAX handler untuk tahap 1: Mengambil data dari API dan menyimpannya.
     */
    public function ajax_prepare_sync() {
        if (!check_ajax_referer('wppob_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Nonce tidak valid.'], 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Akses ditolak.'], 403);
        }

        $products_manager = new WPPOB_Products();
        $total_products = $products_manager->prepare_sync();

        if ($total_products !== false) {
            wp_send_json_success(['total' => $total_products]);
        } else {
            wp_send_json_error(['message' => 'Gagal mengambil daftar produk dari API. Periksa kembali kredensial Anda.']);
        }
    }

    /**
     * AJAX handler untuk tahap 2: Memproses data per batch.
     */
    public function ajax_process_sync_batch() {
        if (!check_ajax_referer('wppob_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Nonce tidak valid.'], 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Akses ditolak.'], 403);
        }

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 25;

        $products_manager = new WPPOB_Products();
        $result = $products_manager->process_sync_batch($offset, $batch_size);

        wp_send_json_success($result);
    }

    public function ajax_update_category_order() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

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

    public function ajax_update_product_order() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Unauthorized']); }

        $category_id = intval($_POST['category_id']);
        $product_ids = array_map('intval', $_POST['product_ids']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_display_categories';
        $wpdb->update($table_name, ['assigned_products' => json_encode($product_ids)], ['id' => $category_id]);
        
        wp_send_json_success();
    }

    public function ajax_bulk_update_images() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Unauthorized']); }

        $product_ids = array_map('intval', $_POST['product_ids']);
        $image_id = intval($_POST['image_id']);

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $product->set_image_id($image_id);
                $product->save();
            }
        }
        
        wp_send_json_success(['message' => count($product_ids) . ' produk berhasil diperbarui.']);
    }
    
    
    
    
    public function ajax_search_users() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $search_term = sanitize_text_field($_POST['search_term']);
        $users = WPPOB_Users::search_users($search_term);
        
        $results = [];
        foreach ($users as $user) {
            $results[] = ['id' => $user->ID, 'username' => $user->user_login, 'email' => $user->user_email];
        }
        wp_send_json_success($results);
    }

    public function ajax_get_user_details() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $user_id = intval($_POST['user_id']);
        $details = WPPOB_Users::get_user_details($user_id);
        
        if ($details) {
            wp_send_json_success($details);
        } else {
            wp_send_json_error(['message' => 'Gagal mengambil detail pengguna.']);
        }
    }
    
    public function ajax_adjust_user_balance() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $user_id = intval($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $note = sanitize_text_field($_POST['note']);

        if (empty($user_id) || empty($amount)) {
            wp_send_json_error(['message' => 'ID Pengguna atau Jumlah tidak valid.']);
        }
        
        $description = 'Penyesuaian manual oleh Admin: ' . $note;

        if ($amount > 0) {
            WPPOB_Balances::add_balance($user_id, $amount, $description);
        } else {
            WPPOB_Balances::deduct_balance($user_id, abs($amount), $description);
        }

        $new_balance = wppob_format_rp(WPPOB_Balances::get_user_balance($user_id));
        wp_send_json_success(['message' => 'Saldo berhasil diperbarui.', 'new_balance' => $new_balance]);
    }
    
    
    
    
    public function ajax_get_recent_users() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $users = WPPOB_Users::get_recent_users();
        
        $results = [];
        foreach ($users as $user) {
            $results[] = ['id' => $user->ID, 'username' => $user->user_login, 'email' => $user->user_email];
        }
        wp_send_json_success($results);
    }
    
    
    
    // [TAMBAHKAN SEMUA FUNGSI DI BAWAH INI]

    public function render_referral_enable_field() {
        $value = get_option('wppob_referral_enable', 'no');
        echo '<label><input type="checkbox" name="wppob_referral_enable" value="yes" ' . checked($value, 'yes', false) . '> Aktifkan</label>';
        echo '<p class="description">Jika dicentang, pengguna dapat menggunakan link referral untuk mengajak teman.</p>';
    }
    
    
    
    
    
    
    public function render_transfer_enable_field() {
    $value = get_option('wppob_transfer_enable', 'yes');
    echo '<label><input type="checkbox" name="wppob_transfer_enable" value="yes" ' . checked($value, 'yes', false) . '> Aktifkan</label>';
    echo '<p class="description">Jika dinonaktifkan, menu dan fitur transfer saldo di halaman pengguna akan disembunyikan.</p>';
}
    
    
    
    

   public function render_referral_type_field() {
        $value = get_option('wppob_referral_type', 'signup_bonus');
        echo '<select name="wppob_referral_type">';
        echo '<option value="signup_bonus"' . selected($value, 'signup_bonus', false) . '>Bonus Pendaftaran</option>';
        echo '<option value="commission"' . selected($value, 'commission', false) . '>Komisi Transaksi (dari Profit Admin)</option>';
        // [TAMBAHKAN BARIS DI BAWAH INI]
        echo '<option value="markup_commission"' . selected($value, 'markup_commission', false) . '>Komisi Markup Harga (untuk Downline)</option>';
        echo '</select>';
        echo '<p class="description">Pilih jenis keuntungan yang didapat oleh referrer.</p>';
    }

    public function render_signup_bonus_field() {
        $value = get_option('wppob_signup_bonus_amount', '5000');
        echo '<input type="number" name="wppob_signup_bonus_amount" value="' . esc_attr($value) . '">';
        echo '<p class="description">Jumlah bonus dalam Rupiah yang diberikan kepada referrer DAN pengguna baru saat pendaftaran berhasil. Hanya berlaku jika "Jenis Program" adalah "Bonus Pendaftaran".</p>';
    }

    public function render_commission_type_field() {
        $value = get_option('wppob_commission_type', 'fixed');
        echo '<select name="wppob_commission_type">';
        echo '<option value="fixed"' . selected($value, 'fixed', false) . '>Tetap (Fixed)</option>';
        echo '<option value="percentage"' . selected($value, 'percentage', false) . '>Persentase (%)</option>';
        echo '</select>';
        echo '<p class="description">Hanya berlaku jika "Jenis Program" adalah "Komisi Transaksi".</p>';
    }

    public function render_commission_amount_field() {
        $value = get_option('wppob_commission_amount', '100');
        echo '<input type="number" step="any" name="wppob_commission_amount" value="' . esc_attr($value) . '">';
        echo '<p class="description">Jika Tetap, isi jumlah Rupiah (misal: 100). Jika Persentase, isi angka persennya (misal: 5 untuk 5%). Komisi dihitung dari PROFIT transaksi.</p>';
    }
    // [AKHIR DARI KODE TAMBAHAN]
    
    
}