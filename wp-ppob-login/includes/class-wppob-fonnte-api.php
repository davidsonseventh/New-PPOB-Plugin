<?php

class WPPOB_Fonnte_Api {

    private $api_key;
    private $api_url = 'https://api.fonnte.com/send';

    public function __construct() {
        $this->api_key = get_option( 'wppob_fonnte_api_key' );
    }

    public function send_otp( $phone, $otp ) {
        if ( empty( $this->api_key ) ) {
            return false;
        }

        $message = "Kode OTP Anda adalah: *{$otp}*. Jangan berikan kode ini kepada siapa pun.";
        
        $response = wp_remote_post( $this->api_url, array(
            'method'    => 'POST',
            'headers'   => array(
                'Authorization' => $this->api_key,
            ),
            'body'      => array(
                'target'  => $phone,
                'message' => $message,
            ),
        ));

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return isset( $data['status'] ) && $data['status'] === true;
    }
}
