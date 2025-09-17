<?php
class WPLM_Product {

    public function __construct() {
        // Hook yang dijalankan setelah pembayaran selesai
        add_action('woocommerce_order_status_completed', [$this, 'generate_license_on_purchase']);
    }



// Di dalam class WPLM_Product

// Tambahkan hook baru di __construct()
public function __construct() {
    add_action('woocommerce_order_status_completed', [$this, 'generate_license_on_purchase']);
    
    // Hook untuk menambah field di halaman edit produk
    add_action('woocommerce_product_options_general_product_data', [$this, 'add_download_link_field']);
    add_action('woocommerce_process_product_meta', [$this, 'save_download_link_field']);
}

// Tambahkan fungsi baru ini
public function add_download_link_field() {
    woocommerce_wp_text_input([
        'id'          => '_wplm_download_link',
        'label'       => __('Plugin Download Link', 'wp-license-manager'),
        'placeholder' => 'https://example.com/path/to/plugin.zip',
        'desc_tip'    => 'true',
        'description' => __('Enter the direct download link for this licensed product.', 'wp-license-manager'),
    ]);
}

// Tambahkan fungsi baru ini
public function save_download_link_field($post_id) {
    $download_link = isset($_POST['_wplm_download_link']) ? esc_url_raw($_POST['_wplm_download_link']) : '';
    update_post_meta($post_id, '_wplm_download_link', $download_link);
}
    

    
    /**
     * Fungsi yang dipicu saat pesanan WooCommerce selesai.
     *
     * @param int $order_id ID pesanan.
     */
    public function generate_license_on_purchase($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_customer_id();

        // Jika bukan pesanan dari pelanggan terdaftar, lewati
        if (!$user_id) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wplm_licenses';

        // Loop melalui setiap item dalam pesanan
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            // Contoh sederhana: Anggap semua produk adalah lisensi
            // Nantinya bisa dibuat lebih spesifik, misal hanya untuk kategori "Lisensi"
            
            // Buat kunci baru, domain bisa dikosongkan dulu untuk diisi saat aktivasi
            // Durasi 365 hari sebagai contoh
            $new_key = WPLM_Generator::create_license_key('unactivated', 365);
            $expiry_date = date('Y-m-d', strtotime("+365 days"));

            // Simpan ke database
            $wpdb->insert(
                $table_name,
                [
                    'license_key' => $new_key,
                    'product_id'  => $product->get_id(),
                    'order_id'    => $order_id,
                    'user_id'     => $user_id,
                    'status'      => 'inactive', // Status awal, belum diaktivasi
                    'expiry_date' => $expiry_date,
                    'created_at'  => current_time('mysql'),
                ]
            );
        }
    }
}
