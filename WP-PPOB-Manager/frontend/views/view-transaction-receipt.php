<?php
defined('ABSPATH') || exit;

// Ambil ID transaksi dari URL dan pastikan itu angka
$transaction_id = isset($_GET['view_transaction']) ? intval($_GET['view_transaction']) : 0;
$current_user_id = get_current_user_id();

if ($transaction_id === 0 || $current_user_id === 0) {
    echo '<p>Transaksi tidak valid atau Anda tidak memiliki izin.</p>';
    return;
}

global $wpdb;
$table_name = $wpdb->prefix . 'wppob_transactions';

// Ambil data transaksi DARI PENGGUNA YANG SEDANG LOGIN SAJA (untuk keamanan)
$transaction = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE id = %d AND user_id = %d",
    $transaction_id,
    $current_user_id
));

if (!$transaction) {
    echo '<p>Rincian transaksi tidak ditemukan.</p>';
    return;
}

// Decode rincian dari API
$details = !empty($transaction->api_response) ? json_decode($transaction->api_response, true) : [];
$sn = $details['sn'] ?? 'N/A';
$message = $details['message'] ?? 'Tidak ada pesan.';
?>

<div class="wppob-receipt-wrap">
    <div class="wppob-receipt-header">
        <h2>Struk Pembelian</h2>
        <p>Transaksi #<?php echo esc_html($transaction->id); ?></p>
    </div>

    <table class="wppob-receipt-table">
        <tr>
            <td>Tanggal</td>
            <td><strong><?php echo esc_html(date_i18n('d F Y, H:i:s', strtotime($transaction->created_at))); ?></strong></td>
        </tr>
        <tr>
            <td>Produk</td>
            <td><strong><?php echo esc_html($transaction->product_code); ?></strong></td>
        </tr>
        <tr>
            <td>Nomor Tujuan</td>
            <td><strong><?php echo esc_html($transaction->customer_no); ?></strong></td>
        </tr>
        <tr>
            <td>Harga</td>
            <td><strong><?php echo wppob_format_rp($transaction->sale_price); ?></strong></td>
        </tr>
        <tr>
            <td>Status</td>
            <td><strong style="color: #27ae60; text-transform: uppercase;"><?php echo esc_html($transaction->status); ?></strong></td>
        </tr>
        <tr class="wppob-receipt-sn">
            <td colspan="2">
                <div class="sn-title">TOKEN/SN:</div>
                <div class="sn-content"><?php echo esc_html($sn); ?></div>
            </td>
        </tr>
         <tr class="wppob-receipt-footer">
            <td colspan="2">
                <p>Terima kasih telah melakukan transaksi!</p>
            </td>
        </tr>
    </table>

    <div class="wppob-receipt-actions">
        <a href="<?php echo esc_url(remove_query_arg('view_transaction')); ?>" class="button receipt-back-btn">&larr; Kembali ke Riwayat</a>
        <button onclick="window.print();" class="button receipt-print-btn">Cetak Struk</button>
    </div>
</div>