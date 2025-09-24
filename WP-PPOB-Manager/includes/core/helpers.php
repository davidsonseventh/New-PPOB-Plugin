<?php
defined('ABSPATH') || exit;

// Fungsi untuk memformat angka menjadi format Rupiah
if (!function_exists('wppob_format_rp')) {
    function wppob_format_rp($number) {
        return 'Rp ' . number_format($number, 0, ',', '.');
    }
}

// Fungsi untuk mendapatkan label status
if (!function_exists('wppob_get_status_label')) {
    function wppob_get_status_label($status) {
        $labels = [
            'success'    => __('Sukses', 'wp-ppob'),
            'processing' => __('Diproses', 'wp-ppob'),
            'pending'    => __('Pending', 'wp-ppob'),
            'failed'     => __('Gagal', 'wp-ppob'),
            'refunded'   => __('Refund', 'wp-ppob'),
        ];
        return $labels[$status] ?? ucfirst($status);
    }
}


// [FUNGSI BARU TAMBAHAN]
if (!function_exists('wppob_get_price_for_user')) {
    function wppob_get_price_for_user($product, $user_id) {
        // 1. Ambil harga jual normal (yang sudah termasuk profit admin)
        $sale_price = (float) $product->get_price();

        // 2. Cek apakah sistem referral dan mode markup aktif
        if (get_option('wppob_referral_enable') !== 'yes' || get_option('wppob_referral_type') !== 'markup_commission') {
            return $sale_price;
        }

        // 3. Cek apakah pengguna ini punya upline (referrer)
        $referrer_id = get_user_meta($user_id, '_wppob_referrer_id', true);
        if (empty($referrer_id)) {
            return $sale_price;
        }

        // 4. Jika semua syarat terpenuhi, tambahkan markup ke harga
        $markup_amount = (float) get_option('wppob_commission_amount', 0);
        
        return $sale_price + $markup_amount;
    }
}
// [AKHIR DARI FUNGSI TAMBAHAN]

function wppob_get_profit_for_user($product) {
    $profit_amount = 0;
    if(class_exists('WPPOB_Admin')){
        $admin_instance = new WPPOB_Admin();
        if($admin_instance->is_license_valid()){
            $profit_type = get_option('wppob_profit_type', 'fixed');
            $profit_amount = (float) get_option('wppob_profit_amount', 0);
            if ($profit_type === 'percentage') {
                // Untuk pascabayar, profit persentase biasanya tidak berlaku atau dihitung dari admin fee
                // Untuk simpelnya, kita anggap 0 jika persentase
                return 0;
            }
        }
    }
    return $profit_amount;
}
