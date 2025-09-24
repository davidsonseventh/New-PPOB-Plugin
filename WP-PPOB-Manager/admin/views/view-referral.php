<?php
// WP-PPOB-Manager/admin/views/view-referral.php
defined('ABSPATH') || exit;
?>
<div class="wrap wppob-wrap">
    <h1><?php _e('Manajemen Referral', 'wp-ppob'); ?></h1>
    <p><?php _e('Lihat statistik dan log dari sistem referral Anda. Pengaturan referral dapat diubah di halaman Pengaturan.', 'wp-ppob'); ?></p>
    
    <a href="?page=wppob-settings" class="button button-primary" style="margin-bottom: 20px;">Buka Pengaturan Referral</a>

    <?php
    global $wpdb;
    $log_table = $wpdb->prefix . 'wppob_referral_log';
    $logs = $wpdb->get_results("SELECT * FROM {$log_table} ORDER BY created_at DESC LIMIT 100");
    ?>
    
    <h2><?php _e('100 Log Komisi & Bonus Terakhir', 'wp-ppob'); ?></h2>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Tanggal', 'wp-ppob'); ?></th>
                <th><?php _e('Referrer', 'wp-ppob'); ?></th>
                <th><?php _e('Pengguna Direferensikan', 'wp-ppob'); ?></th>
                <th><?php _e('Jenis', 'wp-ppob'); ?></th>
                <th><?php _e('Jumlah', 'wp-ppob'); ?></th>
                <th><?php _e('ID Transaksi', 'wp-ppob'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)) : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n('d M Y H:i', strtotime($log->created_at))); ?></td>
                        <td><?php 
                            $referrer = get_user_by('id', $log->referrer_id);
                            echo $referrer ? esc_html($referrer->user_login) : 'N/A';
                        ?></td>
                        <td><?php 
                            $referred = get_user_by('id', $log->referred_user_id);
                            echo $referred ? esc_html($referred->user_login) : 'N/A';
                        ?></td>
                        <td><?php echo esc_html(ucfirst($log->log_type)); ?></td>
                        <td><?php echo wppob_format_rp($log->commission_amount); ?></td>
                        <td><?php echo $log->transaction_id ? '#' . esc_html($log->transaction_id) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6"><?php _e('Belum ada data bonus atau komisi referral.', 'wp-ppob'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>