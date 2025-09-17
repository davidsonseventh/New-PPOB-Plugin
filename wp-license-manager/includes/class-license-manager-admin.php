<?php
if (!defined('WPINC')) {
    die;
}

class WPLM_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_manual_license_creation']);
    }

    public function add_admin_menu() {
        add_menu_page(
            __('License Manager', 'wp-license-manager'),
            __('License Manager', 'wp-license-manager'),
            'manage_options',
            'wplm_licenses',
            [$this, 'render_licenses_page'],
            'dashicons-admin-network',
            58
        );
        add_submenu_page(
            'wplm_licenses',
            __('Generate License', 'wp-license-manager'),
            __('Generate License', 'wp-license-manager'),
            'manage_options',
            'wplm_generate_license',
            [$this, 'render_generate_page']
        );
    }

    public function render_licenses_page() {
        // Halaman untuk menampilkan daftar semua lisensi (akan dikembangkan nanti)
        echo '<div class="wrap"><h1>' . __('All Licenses', 'wp-license-manager') . '</h1><p>Fitur untuk menampilkan semua lisensi akan datang.</p></div>';
    }

    public function render_generate_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Generate License Manually', 'wp-license-manager'); ?></h1>
            <p><?php _e('Buat kunci lisensi baru secara manual untuk pelanggan.', 'wp-license-manager'); ?></p>
            <form method="POST" action="">
                <?php wp_nonce_field('wplm_generate_manual_license'); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="wplm-product"><?php _e('Product', 'wp-license-manager'); ?></label></th>
                            <td>
                                <select id="wplm-product" name="product_id" required>
                                    <?php
                                    $products = wc_get_products(['limit' => -1]);
                                    foreach ($products as $product) {
                                        echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="wplm-user"><?php _e('User (Customer)', 'wp-license-manager'); ?></label></th>
                            <td>
                                <select id="wplm-user" name="user_id" required>
                                     <?php
                                    $users = get_users(['role__in' => ['customer', 'administrator']]);
                                    foreach ($users as $user) {
                                        echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                         <tr>
                            <th scope="row"><label for="wplm-domain"><?php _e('Activation Domain', 'wp-license-manager'); ?></label></th>
                            <td><input type="text" id="wplm-domain" name="domain" class="regular-text" placeholder="contoh.com">
                            <p class="description"><?php _e('Kosongkan jika ingin diaktivasi oleh pengguna nanti.', 'wp-license-manager'); ?></p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="wplm-validity"><?php _e('Validity (days)', 'wp-license-manager'); ?></label></th>
                            <td><input type="number" id="wplm-validity" name="validity_days" class="regular-text" value="365"></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button(__('Generate License', 'wp-license-manager')); ?>
            </form>
        </div>
        <?php
    }

    public function handle_manual_license_creation() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wplm_generate_manual_license')) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wplm_licenses';

        $product_id = intval($_POST['product_id']);
        $user_id = intval($_POST['user_id']);
        $domain = sanitize_text_field($_POST['domain']) ?: 'unactivated';
        $validity_days = intval($_POST['validity_days']);
        
        $new_key = WPLM_Generator::create_license_key($domain, $validity_days);
        $expiry_date = date('Y-m-d', strtotime("+" . $validity_days . " days"));

        $result = $wpdb->insert(
            $table_name,
            [
                'license_key' => $new_key,
                'product_id'  => $product_id,
                'order_id'    => 0, // 0 untuk lisensi manual
                'user_id'     => $user_id,
                'activation_domain' => ($domain !== 'unactivated') ? $domain : '',
                'status'      => ($domain !== 'unactivated') ? 'active' : 'inactive',
                'expiry_date' => $expiry_date,
                'created_at'  => current_time('mysql'),
            ]
        );

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Lisensi baru berhasil dibuat!</p></div>';
            });
        }
    }
}
