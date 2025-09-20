<?php
// WP-PPOB-Manager/includes/core/class-wppob-referral.php
defined('ABSPATH') || exit;

class WPPOB_Referral {

    public function __construct() {
        // 1. Hook saat pengguna baru mendaftar
        add_action('user_register', [$this, 'handle_user_registration'], 10, 1);
        
        // 2. Hook setelah transaksi PPOB sukses (dari webhook)
        add_action('wppob_transaction_success', [$this, 'handle_transaction_commission'], 10, 1);

        // 3. Hook untuk menangkap kode referral dari URL
        add_action('init', [$this, 'capture_referral_code']);
    }

    /**
     * Menangkap kode referral dari URL (?ref=kode) dan menyimpannya di cookie.
     */
    public function capture_referral_code() {
        if (isset($_GET['ref'])) {
            $referrer_code = sanitize_text_field($_GET['ref']);
            // Cookie akan berlaku selama 7 hari
            setcookie('wppob_referrer_code', $referrer_code, time() + (86400 * 7), '/'); 
        }
    }

    /**
     * Menangani pendaftaran pengguna baru yang menggunakan kode referral.
     */
    public function handle_user_registration($user_id) {
        if (get_option('wppob_referral_enable') !== 'yes') return;

        $referrer_code = isset($_COOKIE['wppob_referrer_code']) ? sanitize_text_field($_COOKIE['wppob_referrer_code']) : '';
        if (empty($referrer_code)) return;

        $referrer = get_user_by('login', $referrer_code);
        if (!$referrer) return;

        // Simpan data siapa yang mereferensikan pengguna baru ini
        update_user_meta($user_id, '_wppob_referrer_id', $referrer->ID);

        // Berikan bonus pendaftaran jika diaktifkan
        if (get_option('wppob_referral_type') === 'signup_bonus') {
            $bonus_amount = (float) get_option('wppob_signup_bonus_amount', 0);
            if ($bonus_amount > 0) {
                $new_user_login = get_user_by('id', $user_id)->user_login;
                // Tambah saldo untuk referrer
                WPPOB_Balances::add_balance($referrer->ID, $bonus_amount, 'Bonus referral dari pendaftaran: ' . $new_user_login);
                // Tambah saldo untuk pengguna baru
                WPPOB_Balances::add_balance($user_id, $bonus_amount, 'Bonus pendaftaran via referral dari: ' . $referrer->user_login);
                // Catat di log referral
                $this->log_activity($referrer->ID, $user_id, $bonus_amount, 'signup');
            }
        }
        
        // Hapus cookie setelah digunakan
        setcookie('wppob_referrer_code', '', time() - 3600, '/');
    }

   
   /**
     * Menangani komisi transaksi untuk referrer.
     * [FUNGSI TELAH DIPERBARUI]
     */
    public function handle_transaction_commission($transaction) {
        if (get_option('wppob_referral_enable') !== 'yes') return;

        $user_id = $transaction->user_id;
        $referrer_id = get_user_meta($user_id, '_wppob_referrer_id', true);

        if (empty($referrer_id)) return;
        
        $referral_type = get_option('wppob_referral_type');
        $commission_earned = 0;

        // Logika untuk Komisi Markup Harga
        if ($referral_type === 'markup_commission') {
            $commission_earned = (float) get_option('wppob_commission_amount', 0);
        } 
        // Logika untuk Komisi dari Profit (yang sudah ada sebelumnya)
        else if ($referral_type === 'commission') {
            $transaction_profit = (float) $transaction->profit;
            if ($transaction_profit <= 0) return;

            $commission_type = get_option('wppob_commission_type', 'fixed');
            $commission_amount = (float) get_option('wppob_commission_amount', 0);

            if ($commission_type === 'percentage') {
                $commission_earned = $transaction_profit * ($commission_amount / 100);
            } else {
                $commission_earned = min($transaction_profit, $commission_amount);
            }
        }

        // Berikan komisi jika ada
        if ($commission_earned > 0) {
            WPPOB_Balances::add_balance($referrer_id, $commission_earned, 'Komisi referral dari transaksi #' . $transaction->id);
            // Catat di log referral
            $this->log_activity($referrer_id, $user_id, $commission_earned, 'commission', $transaction->id);
        }
    }

    /**
     * Fungsi helper untuk mencatat semua aktivitas referral ke database.
     */
    private function log_activity($referrer_id, $referred_user_id, $amount, $type, $transaction_id = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_referral_log';
        $wpdb->insert($table_name, [
            'referrer_id'       => $referrer_id,
            'referred_user_id'  => $referred_user_id,
            'transaction_id'    => $transaction_id,
            'commission_amount' => $amount,
            'log_type'          => $type,
            'created_at'        => current_time('mysql'),
        ]);
    }
}

// Inisialisasi kelas secara global agar hook selalu aktif
new WPPOB_Referral();