<?php
defined('ABSPATH') || exit;

class WPPOB_Activator {

    public static function activate() {
        self::create_database_tables();
        flush_rewrite_rules();
    }

    private static function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tabel Transaksi
        $table_name = $wpdb->prefix . 'wppob_transactions';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            product_code varchar(50) NOT NULL,
            customer_no varchar(100) NOT NULL,
            ref_id varchar(100) NOT NULL,
            base_price decimal(10, 2) NOT NULL,
            sale_price decimal(10, 2) NOT NULL,
            profit decimal(10, 2) NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            api_response text,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);

        // Tabel Kategori Tampilan
        $table_name = $wpdb->prefix . 'wppob_display_categories';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            image_id bigint(20) UNSIGNED DEFAULT 0,
            parent_id mediumint(9) DEFAULT 0,
            sort_order mediumint(9) DEFAULT 0,
            display_style varchar(20) DEFAULT 'image_text',
            assigned_products longtext,
            product_display_style varchar(20) DEFAULT 'image_text',
            product_display_mode varchar(20) DEFAULT 'grid',
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Tabel Mutasi Saldo
        $table_name = $wpdb->prefix . 'wppob_balance_mutations';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(15, 2) NOT NULL,
            type varchar(10) NOT NULL, -- credit or debit
            description text,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql);
    }
}
