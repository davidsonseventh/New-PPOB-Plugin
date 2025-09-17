<?php
if (!defined('WPINC')) {
    die;
}

class WPLM_API {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Mendaftarkan endpoint REST API.
     */
    public function register_routes() {
        register_rest_route('wplm/v1', '/activate', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_activation'],
            'permission_callback' => '__return_true' // Bisa diakses publik
        ]);

        register_rest_route('wplm/v1', '/validate', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_validation'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Menangani permintaan aktivasi lisensi.
     */
    public function handle_activation(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplm_licenses';

        $license_key = sanitize_text_field($request->get_param('license_key'));
        $domain = sanitize_text_field($request->get_param('domain'));

        if (empty($license_key) || empty($domain)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Kunci lisensi dan domain diperlukan.'], 400);
        }

        // Cari lisensi di database
        $license = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE license_key = %s", $license_key));

        if (!$license) {
            return new WP_REST_Response(['success' => false, 'message' => 'Kunci lisensi tidak valid.'], 404);
        }

        // Cek status lisensi
        if ($license->status === 'disabled') {
            return new WP_REST_Response(['success' => false, 'message' => 'Lisensi ini telah dinonaktifkan.'], 403);
        }
        
        if (strtotime($license->expiry_date) < time()) {
             return new WP_REST_Response(['success' => false, 'message' => 'Lisensi telah kedaluwarsa.'], 403);
        }

        // Jika lisensi sudah aktif di domain lain
        if ($license->status === 'active' && $license->activation_domain !== $domain) {
            return new WP_REST_Response(['success' => false, 'message' => 'Lisensi ini sudah digunakan di domain lain.'], 403);
        }

        // Jika lisensi belum aktif atau di domain yang sama, aktifkan
        if ($license->status === 'inactive' || $license->activation_domain === $domain) {
            $wpdb->update(
                $table_name,
                ['status' => 'active', 'activation_domain' => $domain],
                ['id' => $license->id]
            );

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Lisensi berhasil diaktifkan.',
                'expires' => $license->expiry_date
            ], 200);
        }
        
        return new WP_REST_Response(['success' => false, 'message' => 'Terjadi kesalahan yang tidak diketahui.'], 500);
    }

    /**
     * Menangani permintaan validasi lisensi.
     */
    public function handle_validation(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplm_licenses';

        $license_key = sanitize_text_field($request->get_param('license_key'));
        $domain = sanitize_text_field($request->get_param('domain'));

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE license_key = %s AND activation_domain = %s",
            $license_key,
            $domain
        ));
        
        if (!$license || $license->status !== 'active' || strtotime($license->expiry_date) < time()) {
             return new WP_REST_Response(['success' => false, 'valid' => false], 200);
        }

        return new WP_REST_Response(['success' => true, 'valid' => true], 200);
    }
}
