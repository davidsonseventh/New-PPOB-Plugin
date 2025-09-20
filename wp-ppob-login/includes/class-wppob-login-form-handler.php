<?php

class WPPOB_Login_Form_Handler {

    public function __construct() {
        add_action( 'wp_ajax_nopriv_wppob_login_register', array( $this, 'ajax_login_register' ) );
        add_action( 'wp_ajax_nopriv_wppob_verify_otp', array( $this, 'ajax_verify_otp' ) );
        add_action( 'wp_ajax_wppob_set_pin', array( $this, 'ajax_set_pin' ) );
    }

    public function ajax_login_register() {
        $phone = sanitize_text_field( $_POST['phone'] );
        $user = get_users( array( 'meta_key' => 'phone_number', 'meta_value' => $phone ) );

        if ( empty( $user ) ) {
            // User does not exist, proceed with registration
            $this->register_user();
        } else {
            // User exists, proceed with login
            $this->login_user( $user[0] );
        }
    }

    private function register_user() {
        $full_name = sanitize_text_field( $_POST['full_name'] );
        $phone = sanitize_text_field( $_POST['phone'] );
        $email = sanitize_email( $_POST['email'] );

        $user_id = wp_create_user( $email, wp_generate_password(), $email );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
        }

        update_user_meta( $user_id, 'full_name', $full_name );
        update_user_meta( $user_id, 'phone_number', $phone );
        
        $this->send_otp( $user_id, $phone );
    }

    private function login_user( $user ) {
        $this->send_otp( $user->ID, get_user_meta( $user->ID, 'phone_number', true ) );
    }

    private function send_otp( $user_id, $phone ) {
        $otp = rand( 100000, 999999 );
        update_user_meta( $user_id, 'otp_code', $otp );

        $fonnte_api = new WPPOB_Fonnte_Api();
        if ( $fonnte_api->send_otp( $phone, $otp ) ) {
            wp_send_json_success( array( 'user_id' => $user_id ) );
        } else {
            wp_send_json_error( array( 'message' => 'Gagal mengirim OTP.' ) );
        }
    }

    public function ajax_verify_otp() {
        $user_id = intval( $_POST['user_id'] );
        $otp = sanitize_text_field( $_POST['otp'] );
        $stored_otp = get_user_meta( $user_id, 'otp_code', true );

        if ( $otp === $stored_otp ) {
            delete_user_meta( $user_id, 'otp_code' );
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => 'Kode OTP salah.' ) );
        }
    }

    public function ajax_set_pin() {
        $user_id = get_current_user_id();
        $pin = sanitize_text_field( $_POST['pin'] );

        if ( strlen( $pin ) === 6 && ctype_digit( $pin ) ) {
            update_user_meta( $user_id, 'user_pin', wp_hash_password( $pin ) );
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => 'PIN harus 6 digit angka.' ) );
        }
    }
}
