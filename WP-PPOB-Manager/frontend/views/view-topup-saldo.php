<?php defined('ABSPATH') || exit; ?>
<h3><?php _e('Isi Saldo Dompet', 'wp-ppob'); ?></h3>
<p><?php _e('Pilih jumlah dan metode pembayaran untuk menambah saldo Anda.', 'wp-ppob'); ?></p>

<form id="wppob-topup-form" method="POST" action="">
    <div class="wppob-form-group">
        <label for="wppob-topup-amount"><?php _e('Jumlah Top Up (IDR)', 'wp-ppob'); ?></label>
        <input type="number" id="wppob-topup-amount" name="amount" min="10000" step="1000" placeholder="Contoh: 50000" required>
    </div>

    <h4><?php _e('Pilih Metode Pembayaran', 'wp-ppob'); ?></h4>
    <div class="wppob-payment-methods">
        <?php 
        // Dummy payment methods - In a real scenario, this would be dynamic
        $payment_methods = [
            'BCA_VA' => ['name' => 'BCA Virtual Account', 'logo' => 'url/to/bca_logo.png'],
            'BNI_VA' => ['name' => 'BNI Virtual Account', 'logo' => 'url/to/bni_logo.png'],
            'QRIS' => ['name' => 'QRIS', 'logo' => 'url/to/qris_logo.png'],
        ];
        foreach ($payment_methods as $code => $method):
        ?>
            <div class="wppob-payment-method" data-code="<?php echo esc_attr($code); ?>">
                <span><?php echo esc_html($method['name']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    
    <input type="hidden" id="wppob-payment-method-input" name="payment_method" value="">
    <input type="hidden" name="action" value="wppob_process_topup">
    <?php wp_nonce_field('wppob_topup_nonce', 'nonce'); ?>

    <div id="wppob-topup-notification-area" style="margin-top: 15px;"></div>
    
    <button type="submit" id="wppob-submit-topup" disabled style="margin-top: 15px; width: 100%; padding: 10px;"><?php _e('Lanjutkan Pembayaran', 'wp-ppob'); ?></button>
</form>
