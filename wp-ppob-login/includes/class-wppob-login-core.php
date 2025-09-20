<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPPOB_Login_Core {

    public function run() {
        // Actions and Filters
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('wppob_login_form', [$this, 'render_login_form_shortcode']);

        // AJAX Handlers for non-logged-in users
        add_action('wp_ajax_nopriv_wppob_check_phone', [$this, 'ajax_check_phone']);
        add_action('wp_ajax_nopriv_wppob_register_user', [$this, 'ajax_register_user']);
        add_action('wp_ajax_nopriv_wppob_verify_otp', [$this, 'ajax_verify_otp']);
        
        // AJAX Handler for logged-in users (setting PIN)
        add_action('wp_ajax_wppob_set_pin', [$this, 'ajax_set_pin']);
        add_action('wp_ajax_nopriv_wppob_set_pin', [$this, 'ajax_set_pin']);
    }

    // SECTION 1: ASSETS & SHORTCODE
    // ===================================

    public function enqueue_assets() {
        if (is_page() || is_single()) {
            global $post;
            if (has_shortcode($post->post_content, 'wppob_login_form')) {
                wp_enqueue_style('wppob-login-style', WPPPOB_LOGIN_PLUGIN_URL . 'assets/css/frontend.css', [], WPPPOB_LOGIN_VERSION);
                wp_enqueue_script('wppob-login-script', WPPPOB_LOGIN_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], WPPPOB_LOGIN_VERSION, true);
                wp_localize_script('wppob-login-script', 'wppob_login_params', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wppob-login-nonce')
                ]);
            }
        }
    }

    public function render_login_form_shortcode() {
        if (is_user_logged_in()) {
            return '<div class="wppob-login-container" style="text-align:center;"><p>Anda sudah login. <a href="' . wp_logout_url(home_url()) . '">Logout</a></p></div>';
        }
        ob_start();
        // We will include all template parts here, controlled by JS
        include_once WPPPOB_LOGIN_PLUGIN_DIR . 'templates/form-login-register.php';
        return ob_get_clean();
    }

    // SECTION 2: ADMIN SETTINGS
    // ===================================

    public function add_admin_menu() {
        add_submenu_page(
            'wppob-dashboard',
            'Pengaturan Login',
            'Pengaturan Login',
            'manage_options',
            'wppob-login-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('wppob_login_settings_group', 'wppob_fonnte_api_key');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Pengaturan Login PPOB via WhatsApp</h1>
            <p>Masukkan API Key dari Fonnte untuk mengaktifkan pengiriman OTP.</p>
            <form method="post" action="options.php">
                <?php settings_fields('wppob_login_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="wppob_fonnte_api_key">Fonnte API Key</label></th>
                        <td>
                            <input type="text" id="wppob_fonnte_api_key" name="wppob_fonnte_api_key" value="<?php echo esc_attr(get_option('wppob_fonnte_api_key')); ?>" class="regular-text" placeholder="Masukkan API Key Anda"/>
                            <p class="description">Dapatkan API Key dari dashboard Fonnte Anda.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // SECTION 3: FONNTE API LOGIC
    // ===================================

    private function send_otp_via_fonnte($phone, $otp) {
        $api_key = get_option('wppob_fonnte_api_key');
        if (empty($api_key)) {
            return ['status' => false, 'message' => 'Fonnte API Key belum diatur di Pengaturan PPOB.'];
        }

        $message = "Kode verifikasi Anda: *{$otp}*. Jangan berikan kode ini kepada siapapun. Kode ini hanya berlaku 5 menit.";

        $response = wp_remote_post('https://api.fonnte.com/send', [
            'headers' => ['Authorization' => $api_key],
            'body'    => ['target' => $phone, 'message' => $message],
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return ['status' => false, 'message' => $response->get_error_message()];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['status']) && $data['status'] === true) {
            return ['status' => true];
        } else {
            return ['status' => false, 'message' => 'Gagal mengirim pesan: ' . ($data['reason'][0] ?? 'Alasan tidak diketahui.')];
        }
    }

    // SECTION 4: AJAX HANDLERS
    // ===================================

    private function generate_and_send_otp($user_id, $phone) {
        $otp = rand(100000, 999999);
        update_user_meta($user_id, '_wppob_otp_code', $otp);
        update_user_meta($user_id, '_wppob_otp_expiry', time() + (5 * 60)); // 5 minutes validity
        return $this->send_otp_via_fonnte($phone, $otp);
    }
    
    public function ajax_check_phone() {
        check_ajax_referer('wppob-login-nonce', 'nonce');
        $phone = sanitize_text_field($_POST['phone']);
        if (!ctype_digit($phone) || strlen($phone) < 9) {
            wp_send_json_error(['message' => 'Format nomor HP tidak valid.']);
        }
        
        $user = get_users(['meta_key' => '_wppob_phone_number', 'meta_value' => $phone, 'number' => 1]);

        if (!empty($user)) {
            $result = $this->generate_and_send_otp($user[0]->ID, $phone);
            if ($result['status']) {
                wp_send_json_success(['action' => 'login', 'user_id' => $user[0]->ID]);
            } else {
                wp_send_json_error(['message' => 'Gagal mengirim OTP: ' . $result['message']]);
            }
        } else {
            wp_send_json_success(['action' => 'register']);
        }
    }

    public function ajax_register_user() {
        check_ajax_referer('wppob-login-nonce', 'nonce');
        $full_name = sanitize_text_field($_POST['full_name']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);

        if (email_exists($email)) wp_send_json_error(['message' => 'Email sudah terdaftar.']);
        if (username_exists($email)) wp_send_json_error(['message' => 'Username (berdasarkan email) sudah ada.']);

        $user_id = wp_create_user($email, wp_generate_password(), $email);
        if (is_wp_error($user_id)) wp_send_json_error(['message' => $user_id->get_error_message()]);
        
        wp_update_user(['ID' => $user_id, 'display_name' => $full_name]);
        update_user_meta($user_id, '_wppob_phone_number', $phone);
        
        $result = $this->generate_and_send_otp($user_id, $phone);
        if ($result['status']) {
            wp_send_json_success(['user_id' => $user_id]);
        } else {
            wp_delete_user($user_id);
            wp_send_json_error(['message' => 'Gagal mendaftar: ' . $result['message']]);
        }
    }

    public function ajax_verify_otp() {
        check_ajax_referer('wppob-login-nonce', 'nonce');
        $user_id = intval($_POST['user_id']);
        $otp = sanitize_text_field($_POST['otp']);
        
        $user = get_user_by('id', $user_id);
        if (!$user) wp_send_json_error(['message' => 'Pengguna tidak ditemukan.']);

        if (time() > get_user_meta($user_id, '_wppob_otp_expiry', true)) wp_send_json_error(['message' => 'Kode OTP sudah kedaluwarsa.']);
        if ($otp != get_user_meta($user_id, '_wppob_otp_code', true)) wp_send_json_error(['message' => 'Kode OTP salah.']);

        delete_user_meta($user_id, '_wppob_otp_code');
        delete_user_meta($user_id, '_wppob_otp_expiry');
        
        wp_set_current_user($user_id, $user->user_login);
        wp_set_auth_cookie($user_id, true);
        
        wp_send_json_success(['has_pin' => metadata_exists('user', $user_id, '_wppob_user_pin')]);
    }
    
   public function ajax_set_pin() {
    // Selalu periksa nonce untuk keamanan
    check_ajax_referer('wppob-login-nonce', 'nonce');

    // 1. Validasi ID Pengguna
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if (empty($user_id)) {
        wp_send_json_error(['message' => 'Kesalahan: ID Pengguna tidak valid.']);
        return;
    }

    // 2. Pastikan pengguna benar-benar ada sebelum melanjutkan
    $user = get_user_by('id', $user_id);
    if (!$user) {
        wp_send_json_error(['message' => 'Kesalahan: Pengguna tidak ditemukan di database.']);
        return;
    }

    // 3. Validasi PIN
    $pin = isset($_POST['pin']) ? sanitize_text_field($_POST['pin']) : '';
    if (strlen($pin) !== 6 || !ctype_digit($pin)) {
        wp_send_json_error(['message' => 'PIN harus terdiri dari 6 digit angka.']);
        return;
    }

    // 4. Coba simpan PIN ke database dan periksa hasilnya
    $hashed_pin = wp_hash_password($pin);
    $result = update_user_meta($user_id, '_wppob_transaction_pin', $hashed_pin);

    if (false === $result) {
        // Ini terjadi jika ada masalah koneksi database saat menyimpan
        wp_send_json_error(['message' => 'Terjadi kesalahan internal saat menyimpan PIN ke database.']);
        return;
    }

    // 5. Jika semua berhasil, kirim respons sukses
    wp_send_json_success(['message' => 'PIN berhasil dibuat. Anda akan dialihkan...']);
    }
}