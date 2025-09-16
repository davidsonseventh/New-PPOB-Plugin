<?php
defined('ABSPATH') || exit;

class WPPOB_Orders {

    public static function create_order($args) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_transactions';

        $defaults = [
            'user_id' => get_current_user_id(),
            'product_code' => '',
            'customer_no' => '',
            'ref_id' => uniqid('WPPOB-'),
            'base_price' => 0,
            'sale_price' => 0,
            'profit' => 0,
            'status' => 'pending',
            'api_response' => '',
            'created_at' => current_time('mysql'),
        ];
        $data = wp_parse_args($args, $defaults);

        $wpdb->insert($table_name, $data);
        return $wpdb->insert_id;
    }

    public static function update_order($order_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_transactions';
        $wpdb->update($table_name, $data, ['id' => $order_id]);
    }

    public static function get_order($order_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_transactions';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $order_id));
    }
}
