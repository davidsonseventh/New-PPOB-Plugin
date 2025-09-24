<?php
if (!defined('ABSPATH')) {
    exit;
}

class WPPOB_Login_Core {

    public function run() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('wppob_login_form', [$this, 'render_login_form_shortcode']);

        // AJAX Handlers
        add_action('wp_ajax_nopriv_wppob_check_phone', [$this, 'ajax_check_phone']);
        add_action('wp_ajax_nopriv_wppob_register_user', [$this, 'ajax_register_user']);
        add_action('wp_ajax_nopriv_wppob_verify_otp', [$this, 'ajax_verify_otp']);
        add_action('wp_ajax_nopriv_wppob_set_pin', [$this, 'ajax_set_pin']);
        add_action('wp_ajax_nopriv_wppob_verify_pin_and_login', [$this, 'ajax_verify_pin_and_login']);
        add_action('wp_ajax_wppob_set_pin', [$this, 'ajax_set_pin']);
    }

    // ... (Fungsi enqueue_assets, render_login_form_shortcode, add_admin_menu, register_settings, render_settings_page, send_otp_via_fonnte, generate_and_send_otp, ajax_check_phone, ajax_register_user, ajax_verify_otp biarkan seperti adanya) ...

    // (Salin semua fungsi dari file lama Anda mulai dari enqueue_assets hingga ajax_verify_otp dan tempel di sini)
    // ATAU gunakan versi lengkap di bawah ini:

    public function enqueue_assets() {
        if (is_page() || is_single()) {
            global $post;
            if (has_shortcode($post->post_content, 'wppob_login_form')) {
                wp_enqueue_style('wppob-login-style', WPPPOB_LOGIN_PLUGIN_URL . 'assets/css/frontend.css', [], WPPPOB_LOGIN_VERSION);
                wp_enqueue_script('wppob-login-script', WPPPOB_LOGIN_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], WPPPOB_LOGIN_VERSION, true);
                wp_localize_script('wppob-login-script', 'wppob_login_params', ['ajax_url' => admin_url('admin-ajax.php'),'nonce' => wp_create_nonce('wppob-login-nonce')]);
            }
        }
    }

    public function render_login_form_shortcode() {
        if (is_user_logged_in()) { return ''; }
        ob_start();
        include_once WPPPOB_LOGIN_PLUGIN_DIR . 'templates/form-login-register.php';
        return ob_get_clean();
    }

    public function add_admin_menu() {
        add_submenu_page('wppob-dashboard', 'Pengaturan Login', 'Pengaturan Login', 'manage_options', 'wppob-login-settings', [$this, 'render_settings_page']);
    }

    public function register_settings() {
        register_setting('wppob_login_settings_group', 'wppob_fonnte_api_key');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Pengaturan Login PPOB via WhatsApp</h1>
            <p>Masukkan API Key dari Fonnte untuk mengaktifkan pengiriman OTP dan notifikasi login.</p>
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

    private function send_message_via_fonnte($phone, $message) {
        $api_key = get_option('wppob_fonnte_api_key');
        if (empty($api_key)) { return ['status' => false, 'message' => 'Fonnte API Key belum diatur.']; }
        $response = wp_remote_post('https://api.fonnte.com/send', ['headers' => ['Authorization' => $api_key], 'body' => ['target' => $phone, 'message' => $message], 'timeout' => 20]);
        if (is_wp_error($response)) { return ['status' => false, 'message' => $response->get_error_message()];}
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return (isset($data['status']) && $data['status'] === true) ? ['status' => true] : ['status' => false, 'message' => 'Gagal mengirim pesan: ' . ($data['reason'][0] ?? 'Alasan tidak diketahui.')];
    }

    private function generate_and_send_otp($user_id, $phone) {
        $otp = rand(100000, 999999);
        update_user_meta($user_id, '_wppob_otp_code', $otp);
        update_user_meta($user_id, '_wppob_otp_expiry', time() + (5 * 60));
        $message = "Kode verifikasi Anda: *{$otp}*. Jangan berikan kode ini kepada siapapun. Kode ini hanya berlaku 5 menit.";
        return $this->send_message_via_fonnte($phone, $message);
    }

    public function ajax_check_phone() {
        check_ajax_referer('wppob-login-nonce', 'nonce');
        $phone = sanitize_text_field($_POST['phone']);
        if (!ctype_digit($phone) || strlen($phone) < 9) { wp_send_json_error(['message' => 'Format nomor HP tidak valid.']); }
        $user = get_users(['meta_key' => '_wppob_phone_number', 'meta_value' => $phone, 'number' => 1]);
        if (!empty($user)) {
            $result = $this->generate_and_send_otp($user[0]->ID, $phone);
            if ($result['status']) { wp_send_json_success(['action' => 'login', 'user_id' => $user[0]->ID]); } 
            else { wp_send_json_error(['message' => 'Gagal mengirim OTP: ' . $result['message']]); }
        } else { wp_send_json_success(['action' => 'register']); }
    }

    public function ajax_register_user() {
        check_ajax_referer('wppob-login-nonce', 'nonce');
        $full_name = sanitize_text_field($_POST['full_name']); $phone = sanitize_text_field($_POST['phone']); $email = sanitize_email($_POST['email']);
        if (email_exists($email)) wp_send_json_error(['message' => 'Email sudah terdaftar.']);
        if (username_exists($email)) wp_send_json_error(['message' => 'Username (berdasarkan email) sudah ada.']);
        $user_id = wp_create_user($email, wp_generate_password(), $email);
        if (is_wp_error($user_id)) wp_send_json_error(['message' => $user_id->get_error_message()]);
        wp_update_user(['ID' => $user_id, 'display_name' => $full_name]);
        update_user_meta($user_id, '_wppob_phone_number', $phone);
        $result = $this->generate_and_send_otp($user_id, $phone);
        if ($result['status']) { wp_send_json_success(['user_id' => $user_id]); } 
        else { wp_delete_user($user_id); wp_send_json_error(['message' => 'Gagal mendaftar: ' . $result['message']]); }
    }

    public function ajax_verify_otp() {
        check_ajax_referer('wppob-login-nonce', 'nonce');
        $user_id = intval($_POST['user_id']); $otp = sanitize_text_field($_POST['otp']);
        $user = get_user_by('id', $user_id);
        if (!$user) wp_send_json_error(['message' => 'Pengguna tidak ditemukan.']);
        if (time() > get_user_meta($user_id, '_wppob_otp_expiry', true)) wp_send_json_error(['message' => 'Kode OTP sudah kedaluwarsa.']);
        if ($otp != get_user_meta($user_id, '_wppob_otp_code', true)) wp_send_json_error(['message' => 'Kode OTP salah.']);
        delete_user_meta($user_id, '_wppob_otp_code'); delete_user_meta($user_id, '_wppob_otp_expiry');
        wp_send_json_success(['has_pin' => metadata_exists('user', $user_id, '_wppob_transaction_pin')]);
    }

    // --- FUNGSI-FUNGSI DI BAWAH INI TELAH DIPERBARUI ATAU MERUPAKAN FUNGSI BARU ---

    public function ajax_set_pin() {
        check_ajax_referer('wppob-login-nonce', 'nonce');
        $user_id = intval($_POST['user_id']);
        $pin = sanitize_text_field($_POST['pin']);
        $user = get_user_by('id', $user_id);
        if (!$user) wp_send_json_error(['message' => 'Kesalahan: Pengguna tidak ditemukan.']);
        if (strlen($pin) !== 6 || !ctype_digit($pin)) wp_send_json_error(['message' => 'PIN harus 6 digit angka.']);

        update_user_meta($user_id, '_wppob_transaction_pin', wp_hash_password($pin));

        // Login & Kirim Notifikasi
        wp_set_current_user($user_id, $user->user_login);
        wp_set_auth_cookie($user_id, true);
        $this->handle_login_notification($user);

        wp_send_json_success(['message' => 'PIN berhasil dibuat.']);
    }

    public function ajax_verify_pin_and_login() {
        check_ajax_referer('wppob-login-nonce', 'nonce');
        $user_id = intval($_POST['user_id']);
        $pin = sanitize_text_field($_POST['pin']);
        $user = get_user_by('id', $user_id);
        if (!$user) wp_send_json_error(['message' => 'Pengguna tidak ditemukan.']);
        $stored_hash = get_user_meta($user_id, '_wppob_transaction_pin', true);
        if (!$stored_hash || !wp_check_password($pin, $stored_hash)) {
            wp_send_json_error(['message' => 'PIN yang Anda masukkan salah.']);
        }

        // Login & Kirim Notifikasi
        wp_set_current_user($user_id, $user->user_login);
        wp_set_auth_cookie($user_id, true);
        $this->handle_login_notification($user);

        wp_send_json_success(['message' => 'Login berhasil.']);
    }

    /**
     * ## FUNGSI BARU ##
     * Menangani logika pengiriman notifikasi login.
     */
    private function handle_login_notification($user) {
        $user_id = $user->ID;
        $phone = get_user_meta($user_id, '_wppob_phone_number', true);
        if (empty($phone)) return;

        $current_ip = $_SERVER['REMOTE_ADDR'];
        $current_ua = $_SERVER['HTTP_USER_AGENT'];
        $ua_hash = md5($current_ua); // Hash user agent untuk perbandingan

        $known_devices = get_user_meta($user_id, '_wppob_known_devices', true);
        if (!is_array($known_devices)) {
            $known_devices = [];
        }

        $device_details = $this->get_user_agent_details($current_ua);
        $is_known_device = isset($known_devices[$ua_hash]);

        $message = "";
        $sitename = get_bloginfo('name');

        if ($is_known_device) {
            // Notifikasi login biasa
            $message = "Login Berhasil ke akun {$sitename} Anda.\n\n";
        } else {
            // Notifikasi login tidak dikenal
            $message = "‼️ *Peringatan Keamanan* ‼️\nTerdeteksi login dari perangkat baru ke akun {$sitename} Anda.\n\n";
            // Simpan perangkat baru ini
            $known_devices[$ua_hash] = [
                'ip' => $current_ip,
                'ua' => $current_ua,
                'last_login' => current_time('mysql')
            ];
            update_user_meta($user_id, '_wppob_known_devices', $known_devices);
        }

        $message .= "Waktu: " . current_time('d M Y, H:i:s') . "\n";
        $message .= "Perangkat: {$device_details}\n";
        $message .= "IP Address: {$current_ip}\n\n";
        $message .= "Jika ini bukan Anda, segera amankan akun Anda.";

        $this->send_message_via_fonnte($phone, $message);
    }

    /**
     * ## FUNGSI BARU ##
     * Menerjemahkan User Agent menjadi informasi yang mudah dibaca.
     */
    private function get_user_agent_details($ua_string) {
        $os = "Tidak Dikenal";
        if (preg_match('/windows/i', $ua_string)) {
            $os = "Windows";
        } elseif (preg_match('/android/i', $ua_string)) {
            $os = "Android";
        } elseif (preg_match('/iphone|ipad|ipod/i', $ua_string)) {
            $os = "iOS";
        } elseif (preg_match('/mac os x/i', $ua_string)) {
            $os = "macOS";
        } elseif (preg_match('/linux/i', $ua_string)) {
            $os = "Linux";
        }

        $browser = "Tidak Dikenal";
        if (preg_match('/firefox/i', $ua_string)) {
            $browser = "Firefox";
        } elseif (preg_match('/chrome/i', $ua_string) && !preg_match('/edge/i', $ua_string)) {
            $browser = "Chrome";
        } elseif (preg_match('/safari/i', $ua_string) && !preg_match('/chrome/i', $ua_string)) {
            $browser = "Safari";
        } elseif (preg_match('/edge/i', $ua_string)) {
            $browser = "Edge";
        } elseif (preg_match('/opera|opr/i', $ua_string)) {
            $browser = "Opera";
        }

        return "{$os} ({$browser})";
    }
    
    
    /**
 * ## FUNGSI BARU ##
 * Mengirim notifikasi status transaksi ke WhatsApp pengguna.
 * Fungsi ini akan dipanggil dari plugin WP PPOB Manager.
 */
public static function send_transaction_notification($order_id) {
    $order = WPPOB_Orders::get_order($order_id);
    if (!$order) return;

    $user_id = $order->user_id;
    $phone = get_user_meta($user_id, '_wppob_phone_number', true);
    if (empty($phone)) return;

    $sitename = get_bloginfo('name');
    $status_label = ($order->status === 'success') ? "✅ Berhasil" : "❌ Gagal";
    $details = json_decode($order->api_response, true);
    $sn = $details['sn'] ?? 'N/A';

    $message = "🔔 *Notifikasi Transaksi - {$sitename}*\n\n";
    $message .= "Status: *{$status_label}*\n";
    $message .= "Produk: {$order->product_code}\n";
    $message .= "Tujuan: {$order->customer_no}\n";
    $message .= "Total Bayar: " . wppob_format_rp($order->sale_price) . "\n";
    if ($order->status === 'success') {
        $message .= "SN/Token: *{$sn}*\n\n";
    } else {
         $message .= "Pesan: " . ($details['message'] ?? 'Dana telah dikembalikan ke saldo Anda.') . "\n\n";
    }
    $message .= "Terima kasih telah bertransaksi!";

    // Menggunakan instance dari kelas ini untuk mengirim pesan
    $instance = new self();
    $instance->send_message_via_fonnte($phone, $message);
}
}