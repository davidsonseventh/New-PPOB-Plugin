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
        
        
        // [TAMBAHKAN KODE INI]
        // Tabel Log Referral (untuk melacak komisi)
        $table_name = $wpdb->prefix . 'wppob_referral_log';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            referrer_id bigint(20) UNSIGNED NOT NULL,
            referred_user_id bigint(20) UNSIGNED NOT NULL,
            transaction_id mediumint(9) DEFAULT 0,
            commission_amount decimal(15, 2) NOT NULL,
            log_type varchar(20) NOT NULL, -- 'signup' or 'commission'
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY referrer_id (referrer_id),
            KEY referred_user_id (referred_user_id)
        ) $charset_collate;";
        dbDelta($sql);
        // [AKHIR DARI KODE TAMBAHAN]
    }
    
    
    
    
    
    private static function create_default_pages() {
    $pages = [
        'beranda' => [
            'post_title' => 'Beranda',
            'post_content' => '[wppob_form]',
        ],
        'dashboard-saya' => [
            'post_title' => 'Dashboard Saya',
            'post_content' => '[wppob_user_dashboard]',
        ],
        'panduan' => [
            'post_title' => 'Panduan',
            'post_content' => 'Konten panduan akan ditempatkan di sini.',
        ],
        'kontak-kami' => [
            'post_title' => 'Kontak Kami',
            'post_content' => 'Konten formulir kontak akan ditempatkan di sini.',
        ],
        'tentang-kami' => [
            'post_title' => 'Tentang Kami',
            'post_content' => 'Konten tentang perusahaan akan ditempatkan di sini.',
        ],
        'ketentuan-layanan' => [
            'post_title' => 'Ketentuan Layanan',
            'post_content' => 'Ketentuan layanan akan ditempatkan di sini.',
        ],
        'kebijakan-privasi' => [
            'post_title' => 'Kebijakan Privasi',
            'post_content' => 'Kebijakan privasi akan ditempatkan di sini.',
        ],
    ];

    foreach ($pages as $slug => $page) {
        $existing_page = get_page_by_path($slug);
        if (!$existing_page) {
            $new_page_id = wp_insert_post([
                'post_title'    => $page['post_title'],
                'post_content'  => $page['post_content'],
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_name'     => $slug,
            ]);

            // Khusus untuk halaman "Beranda", atur sebagai halaman depan situs
            if ($slug === 'beranda') {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $new_page_id);
            }
        }
    }
}
    
    
    
    
    
}
