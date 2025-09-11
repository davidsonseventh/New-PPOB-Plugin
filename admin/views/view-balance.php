<?php defined('ABSPATH') || exit; ?>
<div class="wrap wppob-wrap">
    <h1 class="wp-heading-inline"><?php _e('Manajemen Saldo Admin', 'wp-ppob'); ?></h1>
    
    <p><?php _e('Halaman ini menampilkan informasi mengenai saldo server PPOB dan mutasi.', 'wp-ppob'); ?></p>

    <?php
    // Ambil data saldo terkini dari API
    $api = new WPPOB_API();
    $balance_data = $api->check_balance();
    $api_balance = isset($balance_data['data']['deposit']) ? wppob_format_rp($balance_data['data']['deposit']) : 'Gagal mengambil data';
    ?>

    <div id="wppob-admin-balance-widget" class="postbox">
        <h2 class="hndle"><span><?php _e('Informasi Saldo', 'wp-ppob'); ?></span></h2>
        <div class="inside">
            <p><strong><?php _e('Saldo Server PPOB Saat Ini:', 'wp-ppob'); ?></strong></p>
            <h2 style="font-size: 2em; margin-top: 0;"><?php echo $api_balance; ?></h2>
            <p class="description"><?php _e('Ini adalah saldo Anda di server penyedia layanan PPOB. Pastikan saldo ini mencukupi untuk melayani transaksi.', 'wp-ppob'); ?></p>
            <a href="#" class="button button-primary"><?php _e('Top Up Saldo Server (Coming Soon)', 'wp-ppob'); ?></a>
        </div>
    </div>

    <hr>
    
    <h2><?php _e('Mutasi Saldo (Coming Soon)', 'wp-ppob'); ?></h2>
    <p><?php _e('Riwayat penambahan dan pengurangan saldo Anda di server PPOB akan ditampilkan di sini.', 'wp-ppob'); ?></p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('Tanggal', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Deskripsi', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Jumlah', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Saldo Akhir', 'wp-ppob'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4"><?php _e('Fitur mutasi saldo akan segera tersedia.', 'wp-ppob'); ?></td>
            </tr>
        </tbody>
    </table>
</div>
