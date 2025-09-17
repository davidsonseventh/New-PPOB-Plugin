<?php
class WPLM_Activator {
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplm_licenses';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            license_key text NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            order_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            activation_domain varchar(255) DEFAULT '' NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            expiry_date date DEFAULT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
