<?php defined('ABSPATH') || exit; 
$current_user_id = get_current_user_id();
?>
<h3><?php _e('Mutasi Saldo', 'wp-ppob'); ?></h3>
<p><?php _e('Berikut adalah riwayat penambahan dan penggunaan saldo Anda.', 'wp-ppob'); ?></p>

<?php
global $wpdb;
$table_name = $wpdb->prefix . 'wppob_balance_mutations';
$mutations = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC LIMIT 50", 
    $current_user_id
));
?>

<table class="shop_table shop_table_responsive">
    <thead>
        <tr>
            <th class=""><?php _e('Tanggal', 'wp-ppob'); ?></th>
            <th class=""><?php _e('Tipe', 'wp-ppob'); ?></th>
            <th class=""><?php _e('Jumlah', 'wp-ppob'); ?></th>
            <th class=""><?php _e('Keterangan', 'wp-ppob'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($mutations)): ?>
            <?php foreach ($mutations as $mutation): ?>
                <tr>
                    <td><?php echo date_i18n('d M Y H:i', strtotime($mutation->created_at)); ?></td>
                    <td>
                        <?php if ($mutation->type === 'credit'): ?>
                            <span style="color: green;"><?php _e('Masuk', 'wp-ppob'); ?></span>
                        <?php else: ?>
                            <span style="color: red;"><?php _e('Keluar', 'wp-ppob'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $amount_display = ($mutation->type === 'credit' ? '+' : '-') . wppob_format_rp($mutation->amount);
                        echo $amount_display;
                        ?>
                    </td>
                    <td><?php echo esc_html($mutation->description); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4"><?php _e('Belum ada mutasi saldo.', 'wp-ppob'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
