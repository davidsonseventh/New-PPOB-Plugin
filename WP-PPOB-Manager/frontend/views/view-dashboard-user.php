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
        <a href="?tab=dashboard" class="<?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>"><?php _e('Transaksi', 'wp-ppob'); ?></a>
        <a href="?tab=balance" class="<?php echo $active_tab === 'balance' ? 'active' : ''; ?>"><?php _e('Mutasi', 'wp-ppob'); ?></a>
        <a href="?tab=topup" class="<?php echo $active_tab === 'topup' ? 'active' : ''; ?>"><?php _e('Isi Saldo', 'wp-ppob'); ?></a>
        <a href="?tab=transfer" class="<?php echo $active_tab === 'transfer' ? 'active' : ''; ?>"><?php _e('Transfer', 'wp-ppob'); ?></a>
        
        <?php // Tab Tarik Komisi sekarang selalu muncul ?>
        <a href="?tab=tarik-komisi" class="<?php echo $active_tab === 'tarik-komisi' ? 'active' : ''; ?>"><?php _e('Tarik Komisi', 'wp-ppob'); ?></a>
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
            case 'transfer':
                include WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/views/view-transfer-saldo.php';
                break;
            case 'tarik-komisi':
                // Cek lagi di sini, jika plugin belum ada, tampilkan pesan.
                if (class_exists('Gateway_Bank_Payout')) {
                    include WP_PPOB_MANAGER_PLUGIN_DIR . 'frontend/views/view-tarik-komisi.php';
                } else {
                    echo '<h3>Fitur Segera Hadir</h3>';
                    echo '<p>Fitur penarikan dana ke rekening bank akan aktif setelah kami mengintegrasikan sistem dengan payment gateway. Terima kasih telah menunggu!</p>';
                }
                break;
            default:
                // =============================================================
                // KODE UNTUK MENAMPILKAN RIWAYAT TRANSAKSI YANG HILANG (DIPERBAIKI)
                // =============================================================
                global $wpdb;
                $table_name = $wpdb->prefix . 'wppob_transactions';
                $transactions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC LIMIT 20", $current_user->ID));
                
                echo '<h3>' . __('20 Transaksi Terakhir Anda', 'wp-ppob') . '</h3>';

                if (!empty($transactions)) {
                    echo '<table class="shop_table shop_table_responsive">';
                    echo '<thead><tr><th>' . __('Tanggal', 'wp-ppob') . '</th><th>' . __('Produk', 'wp-ppob') . '</th><th>' . __('Tujuan', 'wp-ppob') . '</th><th>' . __('Harga', 'wp-ppob') . '</th><th>' . __('Status', 'wp-ppob') . '</th><th>' . __('Rincian', 'wp-ppob') . '</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($transactions as $tx) {
                        $receipt_url = add_query_arg('view_transaction', $tx->id);
                        echo '<tr>';
                        echo '<td>' . date_i18n('d M Y H:i', strtotime($tx->created_at)) . '</td>';
                        echo '<td>' . esc_html($tx->product_code) . '</td>';
                        echo '<td>' . esc_html($tx->customer_no) . '</td>';
                        echo '<td>' . wppob_format_rp($tx->sale_price) . '</td>';
                        echo '<td><span class="wppob-status-badge status-' . esc_attr($tx->status) . '">' . wppob_get_status_label($tx->status) . '</span></td>';
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