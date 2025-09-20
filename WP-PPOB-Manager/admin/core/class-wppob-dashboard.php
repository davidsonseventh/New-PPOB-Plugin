<?php
defined('ABSPATH') || exit;

class WPPOB_Dashboard {

    public static function get_dashboard_data() {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'wppob_transactions';
        
        $data = [];

        // Profit stats
        $data['today_profit'] = $wpdb->get_var("SELECT SUM(profit) FROM {$transactions_table} WHERE status = 'success' AND DATE(created_at) = CURDATE()");
        $data['week_profit'] = $wpdb->get_var("SELECT SUM(profit) FROM {$transactions_table} WHERE status = 'success' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $data['month_profit'] = $wpdb->get_var("SELECT SUM(profit) FROM {$transactions_table} WHERE status = 'success' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");

        // Transaction stats
        $data['total_success'] = $wpdb->get_var("SELECT COUNT(id) FROM {$transactions_table} WHERE status = 'success'");
        $data['total_pending'] = $wpdb->get_var("SELECT COUNT(id) FROM {$transactions_table} WHERE status = 'processing'");
        
        // User stats
        $data['active_users'] = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$transactions_table}");
        
        // API Balance
       $api = new WPPOB_API();
$balance_data = $api->check_balance();

// Periksa apakah respons adalah objek kesalahan
if (is_wp_error($balance_data)) {
    $data['api_balance'] = 'Gagal mengambil data: ' . $balance_data->get_error_message();
} else {
    $data['api_balance'] = isset($balance_data['data']['deposit']) ? $balance_data['data']['deposit'] : 'Tidak dapat mengambil data';
}
}
}
