<?php
defined('ABSPATH') || exit;

class WPPOB_Frontend {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Shortcodes
        add_shortcode('wppob_form', [$this, 'render_ppob_form']);
        add_shortcode('wppob_user_dashboard', [$this, 'render_user_dashboard']);
    
        
        add_action('wp_ajax_wppob_submit_purchase', [$this, 'ajax_submit_purchase']);
        add_action('wp_ajax_nopriv_wppob_submit_purchase', [$this, 'ajax_submit_purchase']);
     add_action('wp_ajax_wppob_process_topup', [$this, 'ajax_process_topup']);
        add_action('woocommerce_order_status_completed', [$this, 'handle_topup_order_completion'], 10, 1);
        
        // ..Mengambil status pesanan
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
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
     * --- KODE DIPERBAIKI ---
     * Logika sekarang memeriksa parameter URL ($_GET) terlebih dahulu.
     * Ini memungkinkan navigasi dari kategori ke sub-kategori, dan ke halaman produk.
     */
    public function render_ppob_form($atts) {
        $atts = shortcode_atts(['category_id' => 0], $atts);
        
        // Prioritaskan parameter dari URL untuk navigasi
        $category_id_from_url = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
        
        // Tentukan category_id yang akan digunakan
        $category_id = $category_id_from_url > 0 ? $category_id_from_url : intval($atts['category_id']);

        ob_start();
        
        // Jika ada category_id (dari URL atau shortcode), tampilkan form produk
        if ($category_id > 0) {
            include WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/views/view-form-ppob.php';
        } 
        // Jika tidak, tampilkan daftar kategori (yang juga menangani sub-kategori)
        else {
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

        // Cek apakah URL meminta untuk melihat rincian transaksi tunggal
        if (isset($_GET['view_transaction']) && !empty($_GET['view_transaction'])) {
            // Jika ya, muat template struk
            include WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/views/view-transaction-receipt.php';
        } else {
            // Jika tidak, muat dashboard utama
            include WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/views/view-dashboard-user.php';
        }

        return ob_get_clean();
    }
    
    
    
    /**
     * AJAX handler untuk memproses pembelian produk PPOB.
     */
    public function ajax_submit_purchase() {
        // 1. Verifikasi Keamanan
        check_ajax_referer('wppob_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Anda harus login untuk melakukan transaksi.']);
        }

        // 2. Ambil dan Sanitasi Input
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $customer_no = isset($_POST['customer_no']) ? sanitize_text_field($_POST['customer_no']) : '';

        if (empty($product_id) || empty($customer_no)) {
            wp_send_json_error(['message' => 'Produk atau nomor tujuan tidak boleh kosong.']);
        }
        
        // 3. Dapatkan Detail Produk & Pengguna
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(['message' => 'Produk tidak ditemukan.']);
        }

        $user_id = get_current_user_id();
        $sale_price = (float) $product->get_price();
        $base_price = (float) $product->get_meta('_wppob_base_price');
        $product_sku = $product->get_sku();

        // 4. Cek dan Potong Saldo Pengguna
        if (!class_exists('WPPOB_Balances') || !WPPOB_Balances::deduct_balance($user_id, $sale_price, 'Pembelian ' . $product->get_name())) {
            wp_send_json_error(['message' => 'Saldo Anda tidak mencukupi untuk transaksi ini.']);
        }
        
        // 5. Buat Catatan Transaksi Awal
        $order_id = WPPOB_Orders::create_order([
            'user_id' => $user_id,
            'product_code' => $product_sku,
            'customer_no' => $customer_no,
            'base_price' => $base_price,
            'sale_price' => $sale_price,
            'profit' => $sale_price - $base_price,
            'status' => 'processing', // Status awal: sedang diproses
        ]);

        if (!$order_id) {
            // Jika gagal buat order, kembalikan saldo
            WPPOB_Balances::add_balance($user_id, $sale_price, 'Refund gagal order ' . $product->get_name());
            wp_send_json_error(['message' => 'Gagal membuat catatan transaksi.']);
        }

        // 6. Panggil API Digiflazz
        $api = new WPPOB_API();
        $transaction_ref_id = WPPOB_Orders::get_order($order_id)->ref_id;
        $api_response = $api->create_transaction($product_sku, $customer_no, $transaction_ref_id);
        
        // 7. Proses Respons dari API
        if (isset($api_response['data']['status']) && in_array(strtolower($api_response['data']['status']), ['sukses', 'pending'])) {
            // Jika transaksi sukses atau pending di sisi API
            WPPOB_Orders::update_order($order_id, [
                'status' => strtolower($api_response['data']['status']) === 'sukses' ? 'success' : 'pending',
                'api_response' => json_encode($api_response['data'])
            ]);
            wp_send_json_success(['message' => 'Transaksi berhasil! Status: ' . ucfirst($api_response['data']['status'])]);
        } else {
            // Jika transaksi gagal di sisi API, kembalikan saldo dan tandai order gagal
            WPPOB_Balances::add_balance($user_id, $sale_price, 'Refund transaksi gagal: ' . $product->get_name());
            WPPOB_Orders::update_order($order_id, [
                'status' => 'failed',
                'api_response' => json_encode($api_response)
            ]);
            $error_message = $api_response['data']['message'] ?? 'Transaksi gagal di server. Silakan hubungi admin.';
            wp_send_json_error(['message' => $error_message]);
        }
    }
    
    
    
    /**
     * AJAX handler untuk memproses permintaan Top Up.
     * Ini akan membuat order di WooCommerce.
     */
    public function ajax_process_topup() {
        check_ajax_referer('wppob_topup_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Silakan login untuk top up saldo.']);
        }

        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';

        if ($amount < 10000) {
            wp_send_json_error(['message' => 'Jumlah top up minimal adalah Rp 10.000.']);
        }

        // Buat order baru di WooCommerce
        $order = wc_create_order(['customer_id' => get_current_user_id()]);
        if (is_wp_error($order)) {
            wp_send_json_error(['message' => 'Gagal membuat permintaan top up. Silakan coba lagi.']);
        }

        // Tambahkan produk "Top Up Saldo" ke order
        $order->add_product(wc_get_product(0), 1, [
            'name' => 'Top Up Saldo PPOB',
            'total' => $amount
        ]);
        
        $order->set_total($amount);
        $order->update_meta_data('_is_wppob_topup', 'yes'); // Penanda khusus
        $order->set_payment_method_title($method);
        $order->calculate_totals();
        $order->save();

        wp_send_json_success([
            'message' => 'Order dibuat, mengalihkan ke halaman pembayaran...',
            'redirect_url' => $order->get_checkout_payment_url()
        ]);
    }

    /**
     * Menambahkan saldo setelah order top up di WooCommerce selesai (Completed).
     */
    public function handle_topup_order_completion($order_id) {
        $order = wc_get_order($order_id);
        
        // Cek apakah ini order top up PPOB
        if ($order && $order->get_meta('_is_wppob_topup') === 'yes') {
            $user_id = $order->get_customer_id();
            $amount = $order->get_total();

            if ($user_id && $amount > 0) {
                // Gunakan class WPPOB_Balances untuk menambah saldo
                WPPOB_Balances::add_balance($user_id, $amount, 'Top Up melalui Order #' . $order->get_order_number());
            }
        }
    }
    
    
    
    
    
    /**
     * Mendaftarkan endpoint API untuk webhook dari Digiflazz.
     */
    public function register_webhook_endpoint() {
        register_rest_route('wppob/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook_request'],
            'permission_callback' => '__return_true' // Izinkan akses publik
        ]);
    }

    /**
     * Menangani data yang masuk dari webhook Digiflazz.
     */
     // Melihat status pesanan
    public function handle_webhook_request($request) {
        $body = $request->get_body();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['data'])) {
            return new WP_REST_Response(['status' => 'error', 'message' => 'Invalid JSON'], 400);
        }
        
        // --- Validasi Keamanan Menggunakan Secret Key (Signature) ---
        $secret_key = get_option('wppob_webhook_secret');
        $signature = $request->get_header('x_hub_signature');
        $expected_signature = 'sha1=' . hash_hmac('sha1', $body, $secret_key);

        if (empty($secret_key) || !hash_equals($expected_signature, $signature)) {
            return new WP_REST_Response(['status' => 'error', 'message' => 'Invalid signature'], 403);
        }

        // --- Proses Data Jika Aman ---
        $payload = $data['data'];
        if (isset($payload['ref_id'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wppob_transactions';
            $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE ref_id = %s", $payload['ref_id']));

            if ($order && $order->status !== 'success' && $order->status !== 'failed') {
                $new_status = strtolower($payload['status']) === 'sukses' ? 'success' : 'failed';
                
                // Simpan SEMUA rincian dari Digiflazz ke database
                $wpdb->update($table_name, 
                    [
                        'status' => $new_status,
                        'api_response' => json_encode($payload) // Simpan semua rincian
                    ],
                    ['id' => $order->id]
                );

                if ($new_status === 'failed') {
                    WPPOB_Balances::add_balance($order->user_id, $order->sale_price, 'Refund otomatis: Transaksi #' . $order->id . ' gagal.');
                }
            }
        }
        
        return new WP_REST_Response(['status' => 'success'], 200);
    }
}