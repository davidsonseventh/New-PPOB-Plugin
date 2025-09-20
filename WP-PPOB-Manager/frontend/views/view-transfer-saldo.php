<?php if (get_option('wppob_transfer_enable', 'yes') !== 'yes') { return; } ?>


<?php defined('ABSPATH') || exit; ?>



<h3><?php _e('Transfer Saldo', 'wp-ppob'); ?></h3>
<p><?php _e('Kirim saldo ke sesama pengguna. Pastikan username atau email penerima sudah benar.', 'wp-ppob'); ?></p>

<form id="wppob-transfer-form" method="POST" action="">
    <div class="wppob-form-group">
        <label for="wppob-recipient"><?php _e('Username atau Email Penerima', 'wp-ppob'); ?></label>
        <input type="text" id="wppob-recipient" name="recipient" placeholder="Contoh: username_teman" required>
    </div>
    <div class="wppob-form-group">
        <label for="wppob-transfer-amount"><?php _e('Jumlah Transfer (IDR)', 'wp-ppob'); ?></label>
        <input type="number" id="wppob-transfer-amount" name="amount" min="1000" step="100" placeholder="Minimal: 1000" required>
    </div>

    <input type="hidden" name="action" value="wppob_process_transfer">
    <?php wp_nonce_field('wppob_transfer_nonce', 'nonce'); ?>

    <div id="wppob-transfer-notification-area" style="margin-top: 15px;"></div>

    <button type="submit" id="wppob-submit-transfer" style="margin-top: 15px; width: 100%; padding: 10px;"><?php _e('Kirim Saldo', 'wp-ppob'); ?></button>
</form>

<h3><i class="fa-solid fa-building-columns"></i> <?php _e('Transfer ke Rekening Bank', 'wp-ppob'); ?></h3>
<p><?php _e('Biaya admin dan waktu proses transfer mengikuti kebijakan dari penyedia layanan.', 'wp-ppob'); ?></p>

<form id="wppob-transfer-bank-form" method="POST">
    <div class="wppob-form-group">
        <label for="wppob-bank-code"><?php _e('Pilih Bank Tujuan', 'wp-ppob'); ?></label>
        <select id="wppob-bank-code" name="bank_code" required>
            <option value="BCA">BANK BCA</option>
            <option value="BNI">BANK BNI</option>
            <option value="BRI">BANK BRI</option>
            <option value="MANDIRI">BANK MANDIRI</option>
        </select>
    </div>
    <div class="wppob-form-group">
        <label for="wppob-bank-account"><?php _e('Nomor Rekening', 'wp-ppob'); ?></label>
        <input type="number" id="wppob-bank-account" name="account_number" required>
    </div>
    <div class="wppob-form-group">
        <label for="wppob-bank-amount"><?php _e('Jumlah Transfer (IDR)', 'wp-ppob'); ?></label>
        <input type="number" id="wppob-bank-amount" name="amount" min="10000" placeholder="Minimal: 10000" required>
    </div>
    <input type="hidden" name="action" value="wppob_process_bank_transfer">
    <?php wp_nonce_field('wppob_transfer_bank_nonce', 'nonce'); ?>
    <div id="wppob-transfer-bank-notification-area" style="margin-top: 15px;"></div>
    <button type="submit" id="wppob-submit-bank-transfer" style="margin-top: 15px; width: 100%; padding: 10px;">Lanjutkan Transfer Bank</button>
</form>