<?php defined('ABSPATH') || exit; ?>
<div class="wrap wppob-wrap">
    <h1 class="wp-heading-inline"><?php _e('Riwayat Transaksi PPOB', 'wp-ppob'); ?></h1>
    
    <p><?php _e('Berikut adalah daftar semua transaksi yang telah dilakukan melalui sistem PPOB.', 'wp-ppob'); ?></p>

    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'wppob_transactions';
    
    // Logika filter (sederhana)
    $status_filter = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
    $where_clause = '';
    if (!empty($status_filter)) {
        $where_clause = $wpdb->prepare("WHERE status = %s", $status_filter);
    }

    $transactions = $wpdb->get_results("SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC");
    ?>

    <ul class="subsubsub">
        <li><a href="?page=wppob-transactions" class="<?php echo empty($status_filter) ? 'current' : ''; ?>"><?php _e('Semua', 'wp-ppob'); ?></a> |</li>
        <li><a href="?page=wppob-transactions&status=success" class="<?php echo $status_filter === 'success' ? 'current' : ''; ?>"><?php _e('Sukses', 'wp-ppob'); ?></a> |</li>
        <li><a href="?page=wppob-transactions&status=processing" class="<?php echo $status_filter === 'processing' ? 'current' : ''; ?>"><?php _e('Diproses', 'wp-ppob'); ?></a> |</li>
        <li><a href="?page=wppob-transactions&status=failed" class="<?php echo $status_filter === 'failed' ? 'current' : ''; ?>"><?php _e('Gagal', 'wp-ppob'); ?></a></li>
    </ul>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('ID', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Tanggal', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Pengguna', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Produk', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Nomor Tujuan', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Harga Jual', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Profit', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Status', 'wp-ppob'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($transactions)) : ?>
                <?php foreach ($transactions as $transaction) : ?>
                    <tr>
                        <td><strong>#<?php echo esc_html($transaction->id); ?></strong></td>
                        <td><?php echo esc_html(date_i18n('d M Y H:i', strtotime($transaction->created_at))); ?></td>
                        <td><?php 
                            $user = get_user_by('id', $transaction->user_id);
                            echo $user ? esc_html($user->user_login) : __('Tamu', 'wp-ppob');
                        ?></td>
                        <td><?php echo esc_html($transaction->product_code); ?></td>
                        <td><?php echo esc_html($transaction->customer_no); ?></td>
                        <td><?php echo wppob_format_rp($transaction->sale_price); ?></td>
                        <td><?php echo wppob_format_rp($transaction->profit); ?></td>
                        <td>
                            <span class="wppob-status-badge status-<?php echo esc_attr($transaction->status); ?>">
                                <?php echo wppob_get_status_label($transaction->status); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8"><?php _e('Tidak ada transaksi yang ditemukan.', 'wp-ppob'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
