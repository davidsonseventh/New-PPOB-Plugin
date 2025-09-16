<?php defined('ABSPATH') || exit; ?>
<h3><i class="fa-brands fa-paypal"></i> <?php _e('Transfer ke Saldo PayPal', 'wp-ppob'); ?></h3>
<p><?php _e('Saldo Anda akan dipotong dan dikirimkan ke akun PayPal tujuan.', 'wp-ppob'); ?></p>

<form id="wppob-transfer-paypal-form" method="POST">
     <div class="wppob-form-group">
        <label for="wppob-paypal-email"><?php _e('Email PayPal Penerima', 'wp-ppob'); ?></label>
        <input type="email" id="wppob-paypal-email" name="paypal_email" required>
    </div>
    <div class="wppob-form-group">
        <label for="wppob-paypal-amount"><?php _e('Jumlah Transfer (IDR)', 'wp-ppob'); ?></label>
        <input type="number" id="wppob-paypal-amount" name="amount" min="10000" placeholder="Minimal: 10000" required>
    </div>
    <input type="hidden" name="action" value="wppob_process_paypal_transfer">
    <?php wp_nonce_field('wppob_transfer_paypal_nonce', 'nonce'); ?>
    <div id="wppob-transfer-paypal-notification-area" style="margin-top: 15px;"></div>
    <button type="submit" id="wppob-submit-paypal-transfer" style="margin-top: 15px; width: 100%; padding: 10px;">Kirim ke PayPal</button>
</form>