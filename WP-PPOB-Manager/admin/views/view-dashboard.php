<?php
defined('ABSPATH') || exit;
// Mengambil data dari kelas dashboard yang terpisah
$data = WPPOB_Dashboard::get_dashboard_data();
?>
<div class="wrap wppob-wrap">
    <h1><?php _e('Dashboard PPOB', 'wp-ppob'); ?></h1>
    <p><?php _e('Ringkasan aktivitas dan keuntungan bisnis PPOB Anda.', 'wp-ppob'); ?></p>

    <div id="wppob-dashboard-widgets-wrap">
        <div class="metabox-holder">
            <div class="postbox-container">
                
                <div class="wppob-col">
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Keuntungan', 'wp-ppob'); ?></span></h2>
                        <div class="inside">
                            <ul>
                                <li><strong><?php _e('Hari Ini:', 'wp-ppob'); ?></strong> <?php echo wppob_format_rp($data['today_profit'] ?: 0); ?></li>
                                <li><strong><?php _e('7 Hari Terakhir:', 'wp-ppob'); ?></strong> <?php echo wppob_format_rp($data['week_profit'] ?: 0); ?></li>
                                <li><strong><?php _e('Bulan Ini:', 'wp-ppob'); ?></strong> <?php echo wppob_format_rp($data['month_profit'] ?: 0); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="wppob-col">
                     <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Statistik Transaksi', 'wp-ppob'); ?></span></h2>
                        <div class="inside">
                            <ul>
                                <li><strong><?php _e('Transaksi Sukses:', 'wp-ppob'); ?></strong> <?php echo number_format_i18n($data['total_success']); ?></li>
                                <li><strong><?php _e('Transaksi Pending:', 'wp-ppob'); ?></strong> <?php echo number_format_i18n($data['total_pending']); ?></li>
                                <li><strong><?php _e('Pengguna Aktif:', 'wp-ppob'); ?></strong> <?php echo number_format_i18n($data['active_users']); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="wppob-col">
                     <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Info Akun', 'wp-ppob'); ?></span></h2>
                        <div class="inside">
                          <ul>
    <li>
        <strong><?php _e('Saldo Server PPOB:', 'wp-ppob'); ?></strong>
        <?php
        // Periksa apakah data saldo adalah objek kesalahan
        if (is_wp_error($data['api_balance'])) {
            echo 'Gagal mengambil data: ' . esc_html($data['api_balance']->get_error_message());
        } else {
            echo is_numeric($data['api_balance']) ? wppob_format_rp($data['api_balance']) : esc_html($data['api_balance']);
        }
        ?>
    </li>
    <li>
        <strong><?php _e('Sinkronisasi Terakhir:', 'wp-ppob'); ?></strong>
        <?php echo get_option('wppob_last_sync', 'Belum pernah'); ?>
    </li>
</ul>
                            <button id="wppob-sync-products" class="button button-secondary"><?php _e('Sinkronkan Produk Sekarang', 'wp-ppob'); ?></button>
                            <span class="spinner"></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
