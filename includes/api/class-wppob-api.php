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
     */
    private function generate_signature($ref_id) {
        return md5($this->username . $this->api_key . $ref_id);
    }

    /**
     * Mengirim request ke API Digiflazz.
     */
    private function send_request($endpoint, $body = []) {
        if (empty($this->username) || empty($this->api_key)) {
            return ['error' => 'API credentials are not set.'];
        }

        $url = $this->api_url . '/' . ltrim($endpoint, '/');
        
        $body['username'] = $this->username;

        $response = wp_remote_post($url, [
            'method'    => 'POST',
            'headers'   => ['Content-Type' => 'application/json'],
            'body'      => json_encode($body),
            'timeout'   => 30,
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $response_body = wp_remote_retrieve_body($response);
        return json_decode($response_body, true);
    }

    /**
     * Mengambil daftar harga produk dari API.
     */
    public function get_price_list() {
        $body = [
            'cmd' => 'prepaid',
            'code' => 'prepaid', // Parameter tambahan jika diperlukan
            'username' => $this->username,
            'sign' => md5($this->username . $this->api_key . 'pricelist')
        ];
        return $this->send_request('price-list', $body);
    }

    /**
     * Cek saldo di API.
     */
    public function check_balance() {
        $body = [
            'cmd' => 'deposit',
            'username' => $this->username,
            'sign' => md5($this->username . $this->api_key . 'depo')
        ];
        return $this->send_request('check-balance', $body);
    }

    /**
     * Melakukan transaksi topup.
     */
    public function create_transaction($product_code, $customer_no, $ref_id) {
        $body = [
            'buyer_sku_code' => $product_code,
            'customer_no'    => $customer_no,
            'ref_id'         => $ref_id,
            'sign'           => $this->generate_signature($ref_id)
        ];
        return $this->send_request('transaction', $body);
    }
}
