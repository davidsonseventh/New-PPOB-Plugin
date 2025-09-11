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
