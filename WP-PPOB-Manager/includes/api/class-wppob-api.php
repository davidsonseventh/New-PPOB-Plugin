<?php
defined('ABSPATH') || exit;

class WPPOB_API {

    private $api_url = 'https://api.digiflazz.com/v1';
    private $username;
    private $api_key;

    public function __construct() {
        $this->username = get_option('wppob_api_username');
        $this->api_key = get_option('wppob_api_key');
    }

    /**
     * Membuat signature untuk request API.
     * Kunci signature adalah string unik untuk setiap perintah (cth: 'pricelist', 'deposit', atau ref_id).
     */
    private function generate_signature($key) {
        return md5($this->username . $this->api_key . $key);
    }

    /**
     * Mengirim request ke API Digiflazz.
     * Endpoint adalah bagian akhir dari URL, contoh: 'price-list'.
     * Body adalah data yang akan dikirim dalam format JSON.
     */
    private function send_request($endpoint, $body = []) {
        if (empty($this->username) || empty($this->api_key)) {
            return new WP_Error('api_error', 'Username atau API Key belum diatur di pengaturan.');
        }

        $url = $this->api_url . '/' . ltrim($endpoint, '/');

        $response = wp_remote_post($url, [
            'method'    => 'POST',
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 45, // Menaikkan timeout untuk request yang mungkin lama
        ]);

        if (is_wp_error($response)) {
            return $response; // Mengembalikan WP_Error jika koneksi gagal
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        // Menambahkan penanganan jika respons dari Digiflazz bukan JSON yang valid atau kosong
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_response', 'Respons dari API tidak valid (bukan format JSON).', ['body' => $response_body]);
        }
        
        return $data;
    }

    /**
     * Mengambil daftar harga produk dari API.
     */
    public function get_price_list() {
        $body = [
            // [FIXED] 'cmd' diubah dari 'prepaid' menjadi 'pricelist'
            'cmd'       => 'price-list', 
            'username'  => $this->username,
            // [FIXED] Signature harus menggunakan kunci 'pricelist'
            'sign'      => $this->generate_signature('pricelist') 
        ];
        
        // Endpoint yang benar adalah 'price-list'
        return $this->send_request('price-list', $body);
    }

/**
    /**
     * Cek saldo di API.
     */
    public function check_balance() {
        // Perintah (cmd) untuk cek saldo adalah 'deposit'
        // Kunci untuk signature (tanda tangan) adalah 'depo'
        $body = [
            'cmd'       => 'deposit',
            'username'  => $this->username,
            'sign'      => $this->generate_signature('depo') // Kunci signature yang benar adalah 'depo'
        ];
        
        // Endpoint API untuk cek saldo adalah 'cek-saldo'
        return $this->send_request('cek-saldo', $body);
    }

    /**
     * Melakukan transaksi topup.
     */
    public function create_transaction($product_code, $customer_no, $ref_id) {
        $body = [
            'username'        => $this->username,
            'buyer_sku_code'  => $product_code,
            'customer_no'     => $customer_no,
            'ref_id'          => $ref_id,
            // Signature untuk transaksi menggunakan ref_id sebagai kuncinya
            'sign'            => $this->generate_signature($ref_id)
        ];
        return $this->send_request('transaction', $body);
    }
    
    
    /**
     * Melakukan transaksi inject voucher fisik (dengan barcode).
     */
    public function create_inject_transaction($product_code, $customer_no, $ref_id, $barcode) {
        $body = [
            'username'        => $this->username,
            'buyer_sku_code'  => $product_code,
            'customer_no'     => $customer_no,
            'ref_id'          => $ref_id,
            'ref_id2'         => $barcode, // Parameter tambahan untuk barcode
            'sign'            => $this->generate_signature($ref_id)
        ];
        return $this->send_request('transaction', $body);
    }
    
    
}