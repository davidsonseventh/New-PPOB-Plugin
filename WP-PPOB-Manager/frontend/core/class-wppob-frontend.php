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
        add_action('woocommerce_order_status_completed', [$this, 'handle_topup_order_completion'], 10, 1);
        add_action('woocommerce_order_status_processing', [$this, 'handle_topup_order_completion'], 10, 1); // Tambahkan baris ini
        // Verifikasi Pin
        add_action('wp_ajax_wppob_create_pin', [$this, 'ajax_create_pin']);
        add_action('wp_ajax_wppob_update_pin', [$this, 'ajax_update_pin']);
        
        
        // ... Reeal time status transaksi
add_action('wp_ajax_wppob_submit_purchase', [$this, 'ajax_submit_purchase']);
add_action('wp_ajax_nopriv_wppob_submit_purchase', [$this, 'ajax_submit_purchase']);
add_action('wp_ajax_wppob_check_transaction_status', [$this, 'ajax_check_transaction_status']); // <-- TAMBAHKAN BARIS INI
add_action('wp_ajax_wppob_process_topup', [$this, 'ajax_process_topup']);
// ... (sisa kode)
        
      
      
       // ... (kode lain yang sudah ada) ...
        add_action('wp_ajax_wppob_process_topup', [$this, 'ajax_process_topup']);

    // --- Trasfer sesama pengguna ---
        add_action('wp_ajax_wppob_process_transfer', [$this, 'ajax_process_transfer']); 
    // ------------------------

        add_action('woocommerce_order_status_completed', [$this, 'handle_topup_order_completion'], 10, 1);
  
      
        
        // ..Mengambil status pesanan
         add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
      
        // Menambahkan fitur tranfer bank
        if (class_exists('Gateway_Bank_Payout')) {
         add_action('wp_ajax_wppob_process_bank_transfer', [$this, 'ajax_process_bank_transfer']);
    }
    if (class_exists('Gateway_PayPal_Payout')) {
         add_action('wp_ajax_wppob_process_paypal_transfer', [$this, 'ajax_process_paypal_transfer']);
         
         
         
       

    // --- TAMBAHKAN BLOK KODE INI ---
    if (class_exists('Gateway_Bank_Payout')) {
        add_action('wp_ajax_wppob_process_withdrawal', [$this, 'ajax_process_withdrawal']);
    
        }
    }
    
 }    
    
    
    
    

   // GANTI FUNGSI LAMA DENGAN KODE LENGKAP INI
public function enqueue_frontend_assets() {
    wp_enqueue_style('wppob-frontend-css', WP_PPOB_MANAGER_PLUGIN_URL . 'frontend/assets/css/frontend.css', [], WP_PPOB_MANAGER_VERSION);
    wp_enqueue_script('wppob-frontend-js', WP_PPOB_MANAGER_PLUGIN_URL . 'frontend/assets/js/frontend.js', ['jquery'], WP_PPOB_MANAGER_VERSION, true);

    // MENAMBAHKAN BLOK KODE YANG HILANG DAN MEMPERBAIKINYA
    wp_localize_script('wppob-frontend-js', 'wppob_frontend_params', [
        'ajax_url'      => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('wppob_frontend_nonce'),
        'dashboard_url' => home_url('/dashboard-saya/?tab=security') // Pastikan URL ini sesuai
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
 * AJAX handler untuk membuat PIN baru.
 */
public function ajax_create_pin() {
    check_ajax_referer('wppob_create_pin_nonce', 'nonce');
    $user_id = get_current_user_id();

    if (metadata_exists('user', $user_id, '_wppob_transaction_pin')) {
        wp_send_json_error(['message' => 'Anda sudah memiliki PIN.']);
    }

    $new_pin = $_POST['new_pin'];
    $confirm_pin = $_POST['confirm_pin'];

    if (strlen($new_pin) !== 6 || !ctype_digit($new_pin)) {
        wp_send_json_error(['message' => 'PIN harus terdiri dari 6 digit angka.']);
    }

    if ($new_pin !== $confirm_pin) {
        wp_send_json_error(['message' => 'Konfirmasi PIN tidak cocok.']);
    }

    // Simpan PIN yang sudah di-hash
    update_user_meta($user_id, '_wppob_transaction_pin', wp_hash_password($new_pin));
    wp_send_json_success(['message' => 'PIN berhasil dibuat! Halaman akan dimuat ulang.']);
}

/**
 * AJAX handler untuk mengubah PIN yang ada.
 */
public function ajax_update_pin() {
    check_ajax_referer('wppob_update_pin_nonce', 'nonce');
    $user_id = get_current_user_id();

    $current_pin = $_POST['current_pin'];
    $new_pin = $_POST['new_pin'];
    $confirm_pin = $_POST['confirm_pin'];

    $stored_hash = get_user_meta($user_id, '_wppob_transaction_pin', true);

    if (!wp_check_password($current_pin, $stored_hash)) {
        wp_send_json_error(['message' => 'PIN saat ini salah.']);
    }

    if (strlen($new_pin) !== 6 || !ctype_digit($new_pin)) {
        wp_send_json_error(['message' => 'PIN baru harus terdiri dari 6 digit angka.']);
    }

    if ($new_pin !== $confirm_pin) {
        wp_send_json_error(['message' => 'Konfirmasi PIN baru tidak cocok.']);
    }

    update_user_meta($user_id, '_wppob_transaction_pin', wp_hash_password($new_pin));
    wp_send_json_success(['message' => 'PIN berhasil diubah.']);
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



 // --- TAMBAHAN: Validasi PIN ---
    $user_id = get_current_user_id();
    $pin = isset($_POST['transaction_pin']) ? $_POST['transaction_pin'] : '';

    // Cek apakah pengguna sudah punya PIN
    $stored_hash = get_user_meta($user_id, '_wppob_transaction_pin', true);
    if (!$stored_hash) {
        // Jika belum punya PIN, kirim kode khusus ke frontend
        wp_send_json_error(['message' => 'Anda harus membuat PIN terlebih dahulu.', 'require_pin_setup' => true]);
    }

    // Verifikasi PIN yang dimasukkan
    if (empty($pin) || !wp_check_password($pin, $stored_hash)) {
        wp_send_json_error(['message' => 'PIN Transaksi salah.']);
    }
    // --- AKHIR TAMBAHAN ---




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
    $sale_price = wppob_get_price_for_user($product, $user_id); 
    $base_price = (float) $product->get_meta('_wppob_base_price');
    $product_sku = $product->get_sku();
    
     // Hitung profit admin (Harga Jual Normal - Harga Dasar)
    $admin_profit = (float)$product->get_price() - $base_price;

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
        'profit' => $admin_profit, // Simpan profit untuk admin, bukan total
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

    $barcode = isset($_POST['barcode_no']) ? sanitize_text_field($_POST['barcode_no']) : '';
    $api_response = null;

    if (!empty($barcode)) {
        $api_response = $api->create_inject_transaction($product_sku, $customer_no, $transaction_ref_id, $barcode);
    } else {
        $api_response = $api->create_transaction($product_sku, $customer_no, $transaction_ref_id);
    }

    // --- INI ADALAH PERBAIKAN UTAMA ---
    if (isset($api_response['data']['status'])) {
         WPPOB_Orders::update_order($order_id, ['api_response' => json_encode($api_response['data'])]);

        if (strtolower($api_response['data']['status']) === 'gagal') {
            WPPOB_Orders::update_order($order_id, ['status' => 'failed']);
            WPPOB_Balances::add_balance($user_id, $sale_price, 'Refund otomatis: Transaksi #' . $order_id . ' gagal.');
            wp_send_json_error(['message' => 'Transaksi Gagal: ' . ($api_response['data']['message'] ?? 'Alasan tidak diketahui.')]);
        } else {
            wp_send_json_success([
                'message' => 'Permintaan terkirim, menunggu konfirmasi dari server.',
                'transaction_id' => $order_id
            ]);
        }
    } else {
        WPPOB_Orders::update_order($order_id, ['status' => 'failed', 'api_response' => 'Koneksi ke API Gagal.']);
        WPPOB_Balances::add_balance($user_id, $sale_price, 'Refund otomatis: Gagal terhubung ke API.');
        wp_send_json_error(['message' => 'Gagal terhubung ke server PPOB. Dana telah dikembalikan.']);
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
        
        $current_user = wp_get_current_user();
if ($current_user) {
    $order->set_billing_first_name($current_user->display_name);
    // Anda juga bisa menambahkan nama belakang jika diperlukan
    // $order->set_billing_last_name($current_user->last_name); 
}

        // Tambahkan produk "Top Up Saldo" ke order
       $order->add_product(wc_get_product(0), 1, [
    'name'      => 'Top Up Saldo PPOB',
    'total'     => $amount,
    'meta_data' => [
        'customer_name' => $current_user->display_name,
    ],
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
       if ($order && $order->get_meta('_is_wppob_topup') === 'yes' && !$order->get_meta('_wppob_balance_added')) {
            $user_id = $order->get_customer_id();
            $amount = $order->get_total();

            if ($user_id && $amount > 0) {
                // Gunakan class WPPOB_Balances untuk menambah saldo
                WPPOB_Balances::add_balance($user_id, $amount, 'Top Up melalui Order #' . $order->get_order_number());
$order->update_meta_data('_wppob_balance_added', true);
$order->save();
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
    
    
    /**
 * AJAX handler untuk memproses transfer ke bank via payment gateway.
 */
public function ajax_process_bank_transfer() {
    check_ajax_referer('wppob_transfer_bank_nonce', 'nonce');
    if (!class_exists('Gateway_Bank_Payout')) {
        wp_send_json_error(['message' => 'Fitur transfer bank tidak aktif.']);
    }

    // 1. Dapatkan data dari form
    $user_id = get_current_user_id();
    $amount = floatval($_POST['amount']);
    $bank_code = sanitize_text_field($_POST['bank_code']);
    $account_number = sanitize_text_field($_POST['account_number']);

    // 2. Potong saldo internal pengguna
    if (!WPPOB_Balances::deduct_balance($user_id, $amount, "Penarikan dana ke bank {$bank_code}")) {
        wp_send_json_error(['message' => 'Saldo internal Anda tidak mencukupi.']);
    }

    // 3. Panggil fungsi dari plugin payment gateway
    // PENTING: Nama fungsi 'create_disbursement' ini HANYA CONTOH.
    // Ganti dengan nama fungsi yang benar dari plugin yang Anda install.
    $result = Gateway_Bank_Payout::create_disbursement([
        'bank_code' => $bank_code,
        'account_number' => $account_number,
        'amount' => $amount,
        'description' => 'Payout dari ' . get_bloginfo('name')
    ]);

    // 4. Proses hasilnya
    if ($result['success']) {
        wp_send_json_success(['message' => 'Permintaan transfer bank berhasil dibuat dan sedang diproses.']);
    } else {
        // Jika gagal, kembalikan saldo pengguna
        WPPOB_Balances::add_balance($user_id, $amount, "Refund: Gagal transfer ke bank {$bank_code}");
        wp_send_json_error(['message' => 'Gagal membuat permintaan transfer: ' . $result['error_message']]);
    }
}

/**
 * AJAX handler untuk memproses transfer ke PayPal via plugin.
 */
public function ajax_process_paypal_transfer() {
    check_ajax_referer('wppob_transfer_paypal_nonce', 'nonce');
    if (!class_exists('Gateway_PayPal_Payout')) {
        wp_send_json_error(['message' => 'Fitur transfer PayPal tidak aktif.']);
    }

    $user_id = get_current_user_id();
    $amount = floatval($_POST['amount']);
    $paypal_email = sanitize_email($_POST['paypal_email']);

    // Potong saldo internal
    if (!WPPOB_Balances::deduct_balance($user_id, $amount, "Penarikan dana ke PayPal: {$paypal_email}")) {
        wp_send_json_error(['message' => 'Saldo internal Anda tidak mencukupi.']);
    }

    // Panggil fungsi dari plugin PayPal Payout
    // PENTING: Nama fungsi 'send_payout' ini HANYA CONTOH.
    $result = Gateway_PayPal_Payout::send_payout($paypal_email, $amount, 'IDR', 'Payout dari ' . get_bloginfo('name'));

    if ($result['success']) {
        wp_send_json_success(['message' => 'Permintaan transfer ke PayPal berhasil.']);
    } else {
        // Jika gagal, kembalikan saldo
        WPPOB_Balances::add_balance($user_id, $amount, "Refund: Gagal transfer ke PayPal {$paypal_email}");
        wp_send_json_error(['message' => 'Gagal transfer ke PayPal: ' . $result['error_message']]);
    }
}
    
    
    /**
 * AJAX handler untuk memproses transfer saldo antar pengguna.
 */
public function ajax_process_transfer() {
    check_ajax_referer('wppob_transfer_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Anda harus login untuk melakukan transfer.']);
    }
    
    
    if (get_option('wppob_transfer_enable') !== 'yes') {
    wp_send_json_error(['message' => 'Fitur transfer saldo sedang tidak aktif.']);
}

    $sender_id = get_current_user_id();
    $recipient_identifier = sanitize_text_field($_POST['recipient']);
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

    if (empty($recipient_identifier) || $amount <= 0) {
        wp_send_json_error(['message' => 'Penerima dan jumlah transfer tidak boleh kosong.']);
    }

    $recipient = get_user_by('email', $recipient_identifier);
    if (!$recipient) {
        $recipient = get_user_by('login', $recipient_identifier);
    }

    if (!$recipient) {
        wp_send_json_error(['message' => 'Pengguna penerima tidak ditemukan.']);
    }

    $recipient_id = $recipient->ID;

    if ($sender_id === $recipient_id) {
        wp_send_json_error(['message' => 'Anda tidak bisa mengirim saldo ke diri sendiri.']);
    }

    $sender_balance = WPPOB_Balances::get_user_balance($sender_id);
    if ($sender_balance < $amount) {
        wp_send_json_error(['message' => 'Saldo Anda tidak mencukupi untuk transfer ini.']);
    }

    $deduct_success = WPPOB_Balances::deduct_balance($sender_id, $amount, 'Transfer saldo ke ' . $recipient->user_login);

    if ($deduct_success) {
        WPPOB_Balances::add_balance($recipient_id, $amount, 'Menerima saldo dari ' . wp_get_current_user()->user_login);
        wp_send_json_success(['message' => 'Transfer saldo berhasil!']);
    } else {
        wp_send_json_error(['message' => 'Gagal memproses transfer. Silakan coba lagi.']);
    }
}
    
    
    
    /**
 * AJAX handler untuk memproses penarikan dana (tarik komisi) ke bank.
 */
public function ajax_process_withdrawal() {
    check_ajax_referer('wppob_withdrawal_nonce', 'nonce');

    if (!class_exists('Gateway_Bank_Payout')) {
        wp_send_json_error(['message' => 'Fitur penarikan dana tidak aktif.']);
    }

    $user_id = get_current_user_id();
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $bank_code = sanitize_text_field($_POST['bank_code']);
    $account_number = sanitize_text_field($_POST['account_number']);

    if ($amount <= 0 || empty($bank_code) || empty($account_number)) {
        wp_send_json_error(['message' => 'Harap isi semua kolom dengan benar.']);
    }

    // Potong saldo internal pengguna terlebih dahulu
    if (!WPPOB_Balances::deduct_balance($user_id, $amount, "Penarikan dana ke rekening bank {$bank_code}")) {
        wp_send_json_error(['message' => 'Saldo Anda tidak mencukupi untuk melakukan penarikan.']);
    }

    // Panggil fungsi dari plugin payment gateway
    // PENTING: Nama 'create_disbursement' ini HANYA CONTOH.
    // Ganti dengan fungsi yang benar dari plugin payout yang Anda install.
    $result = Gateway_Bank_Payout::create_disbursement([
        'bank_code'      => $bank_code,
        'account_number' => $account_number,
        'amount'         => $amount,
        'description'    => 'Penarikan Komisi oleh user #' . $user_id
    ]);

    if (isset($result['success']) && $result['success']) {
        wp_send_json_success(['message' => 'Permintaan penarikan dana berhasil dibuat dan akan segera diproses.']);
    } else {
        // Jika GAGAL, kembalikan saldo pengguna
        WPPOB_Balances::add_balance($user_id, $amount, "Refund: Gagal melakukan penarikan dana ke bank");
        $error_msg = $result['error_message'] ?? 'Terjadi kesalahan di sistem gateway.';
        wp_send_json_error(['message' => 'Gagal membuat permintaan penarikan: ' . $error_msg]);
    }
}
    
    
    
    /**
 * AJAX handler untuk memeriksa status transaksi.
 */
public function ajax_check_transaction_status() {
    check_ajax_referer('wppob_frontend_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Akses ditolak.']);
    }

    $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
    if (empty($transaction_id)) {
        wp_send_json_error(['message' => 'ID Transaksi tidak valid.']);
    }

    $order = WPPOB_Orders::get_order($transaction_id);

    if (!$order || $order->user_id != get_current_user_id()) {
        wp_send_json_error(['message' => 'Transaksi tidak ditemukan.']);
    }

    $response_data = [
        'status' => $order->status,
        'details' => json_decode($order->api_response, true)
    ];

    wp_send_json_success($response_data);
}
    
    
    
}