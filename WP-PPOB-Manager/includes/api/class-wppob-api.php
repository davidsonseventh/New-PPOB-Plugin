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

    private function generate_signature($key) {
        return md5($this->username . $this->api_key . $key);
    }

    private function send_request($endpoint, $body = []) {
        if (empty($this->username) || empty($this->api_key)) {
            return new WP_Error('api_error', 'Username atau API Key belum diatur di pengaturan.');
        }
        $url = $this->api_url . '/' . ltrim($endpoint, '/');
        $response = wp_remote_post($url, [
            'method'    => 'POST',
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 45,
        ]);
        if (is_wp_error($response)) {
            return $response;
        }
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_response', 'Respons dari API tidak valid.', ['body' => $response_body]);
        }
        return $data;
    }

    /**
     * ## FUNGSI DIPERBARUI ##
     * Fungsi ini sekarang dapat meminta jenis produk spesifik ('prepaid' atau 'pasca').
     * Sesuai dengan dokumentasi yang Anda temukan.
     */
    public function get_price_list($type = 'prepaid') {
        $body = [
            'cmd'       => $type, // Akan diisi dengan 'prepaid' atau 'pasca'
            'username'  => $this->username,
            'sign'      => $this->generate_signature('pricelist')
        ];
        // Nama endpoint tetap 'price-list' sesuai dokumentasi
        return $this->send_request('price-list', $body);
    }

    public function check_balance() {
        $body = [
            'cmd'       => 'deposit',
            'username'  => $this->username,
            'sign'      => $this->generate_signature('depo')
        ];
        return $this->send_request('cek-saldo', $body);
    }

    public function create_transaction($product_code, $customer_no, $ref_id) {
        $body = [
            'username'        => $this->username,
            'buyer_sku_code'  => $product_code,
            'customer_no'     => $customer_no,
            'ref_id'          => $ref_id,
            'sign'            => $this->generate_signature($ref_id)
        ];
        return $this->send_request('transaction', $body);
    }

    public function create_inject_transaction($product_code, $customer_no, $ref_id, $barcode) {
        $body = [
            'username'        => $this->username,
            'buyer_sku_code'  => $product_code,
            'customer_no'     => $customer_no,
            'ref_id'          => $ref_id,
            'ref_id2'         => $barcode,
            'sign'            => $this->generate_signature($ref_id)
        ];
        return $this->send_request('transaction', $body);
    }
    
    
    
    
 /**
 * ## FUNGSI BARU ##
 * Melakukan pengecekan tagihan (inquiry) untuk produk pascabayar.
 */
public function check_postpaid_bill($product_code, $customer_no, $ref_id) {
    $body = [
        'commands'        => 'inq-pasca', // Perintah khusus untuk cek tagihan
        'username'        => $this->username,
        'buyer_sku_code'  => $product_code,
        'customer_no'     => $customer_no,
        'ref_id'          => $ref_id,
        'sign'            => $this->generate_signature($ref_id)
    ];
    return $this->send_request('transaction', $body);
}

/**
 * ## FUNGSI BARU ##
 * Melakukan pembayaran untuk tagihan pascabayar yang sudah dicek (inquiry).
 */
public function pay_postpaid_bill($product_code, $customer_no, $ref_id) {
    $body = [
        'commands'        => 'pay-pasca', // Perintah khusus untuk bayar tagihan
        'username'        => $this->username,
        'buyer_sku_code'  => $product_code,
        'customer_no'     => $customer_no,
        'ref_id'          => $ref_id,
        'sign'            => $this->generate_signature($ref_id)
    ];
    return $this->send_request('transaction', $body);
}
    
    
}