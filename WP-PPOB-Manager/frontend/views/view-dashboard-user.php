<?php defined('ABSPATH') || exit; 
$current_user = wp_get_current_user();
$user_balance = WPPOB_Balances::get_user_balance($current_user->ID);
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
?>
<div class="wppob-frontend-wrap wppob-user-dashboard">
    <h2><?php _e('Dashboard Saya', 'wp-ppob'); ?></h2>
    <p><?php printf(__('Selamat datang, %s.', 'wp-ppob'), esc_html($current_user->display_name)); ?></p>
    <p><strong><?php _e('Saldo Anda:', 'wp-ppob'); ?></strong> <?php echo wppob_format_rp($user_balance); ?></p>
    
    <nav class="wppob-user-dashboard-nav">
        <a href="?tab=dashboard" class="<?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>"><?php _e('Transaksi Terakhir', 'wp-ppob'); ?></a>
        <a href="?tab=balance" class="<?php echo $active_tab === 'balance' ? 'active' : ''; ?>"><?php _e('Mutasi Saldo', 'wp-ppob'); ?></a>
        <a href="?tab=topup" class="<?php echo $active_tab === 'topup' ? 'active' : ''; ?>"><?php _e('Isi Saldo', 'wp-ppob'); ?></a>
    </nav>

    <div class="wppob-user-dashboard-content">
        <?php
        switch ($active_tab) {
            case 'balance':
                include WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/views/view-balance.php';
                break;
            case 'topup':
                include WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/views/view-topup-saldo.php';
                break;
            default:
                // Default tab is transaction history
                global $wpdb;
                $table_name = $wpdb->prefix . 'wppob_transactions';
                $transactions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC LIMIT 20", $current_user->ID));
                
                if (!empty($transactions)) {
                    // You can create a reusable template part for this table
                   echo '<h3>' . __('20 Transaksi Terakhir Anda', 'wp-ppob') . '</h3>';
                    echo '<table class="shop_table shop_table_responsive">';
                    echo '<thead><tr><th>' . __('Tanggal', 'wp-ppob') . '</th><th>' . __('Produk', 'wp-ppob') . '</th><th>' . __('Tujuan', 'wp-ppob') . '</th><th>' . __('Harga', 'wp-ppob') . '</th><th>' . __('Status', 'wp-ppob') . '</th><th>' . __('Rincian', 'wp-ppob') . '</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($transactions as $tx) {
                        // Buat URL untuk halaman rincian
                        $receipt_url = add_query_arg('view_transaction', $tx->id);

                        echo '<tr>';
                        echo '<td>' . date_i18n('d M Y H:i', strtotime($tx->created_at)) . '</td>';
                        echo '<td>' . esc_html($tx->product_code) . '</td>';
                        echo '<td>' . esc_html($tx->customer_no) . '</td>';
                        echo '<td>' . wppob_format_rp($tx->sale_price) . '</td>';
                        echo '<td><span class="wppob-status-badge status-' . esc_attr($tx->status) . '">' . wppob_get_status_label($tx->status) . '</span></td>';
                        
                        // Kolom Rincian sekarang menjadi link jika transaksi sukses
                        echo '<td>';
                        if ($tx->status === 'success') {
                            echo '<a href="' . esc_url($receipt_url) . '">Lihat Struk</a>';
                        } else {
                            if (!empty($tx->api_response)) {
                                $response = json_decode($tx->api_response, true);
                                echo esc_html($response['message'] ?? '');
                            }
                        }
                        echo '</td>';
                        
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p>' . __('Anda belum memiliki riwayat transaksi.', 'wp-ppob') . '</p>';
                }
                break;
        }
        ?>
    </div>
</div>
