<?php defined('ABSPATH') || exit; ?>
<h3><i class="fa-solid fa-sack-dollar"></i> <?php _e('Tarik Komisi (Penarikan Saldo)', 'wp-ppob'); ?></h3>
<p><?php _e('Lakukan penarikan saldo Anda ke rekening bank. Dana akan diproses oleh penyedia layanan payout kami.', 'wp-ppob'); ?></p>

<form id="wppob-withdraw-form" method="POST">
    <div class="wppob-form-group">
        <label for="wppob-withdraw-bank-code"><?php _e('Pilih Bank Tujuan', 'wp-ppob'); ?></label>
        <select id="wppob-withdraw-bank-code" name="bank_code" required>
            <option value="" disabled selected>-- Pilih Bank --</option>
            <option value="BCA">BANK BCA</option>
            <option value="BNI">BANK BNI</option>
            <option value="BRI">BANK BRI</option>
            <option value="MANDIRI">BANK MANDIRI</option>
        </select>
    </div>
    <div class="wppob-form-group">
        <label for="wppob-withdraw-account-number"><?php _e('Nomor Rekening', 'wp-ppob'); ?></label>
        <input type="number" id="wppob-withdraw-account-number" name="account_number" placeholder="Masukkan nomor rekening valid" required>
    </div>
    <div class="wppob-form-group">
        <label for="wppob-withdraw-amount"><?php _e('Jumlah Penarikan (IDR)', 'wp-ppob'); ?></label>
        <input type="number" id="wppob-withdraw-amount" name="amount" min="10000" placeholder="Minimal penarikan: 10000" required>
    </div>
    <input type="hidden" name="action" value="wppob_process_withdrawal">
    <?php wp_nonce_field('wppob_withdrawal_nonce', 'nonce'); ?>
    <div id="wppob-withdraw-notification-area" style="margin-top: 15px;"></div>
    <button type="submit" id="wppob-submit-withdrawal" style="margin-top: 15px; width: 100%; padding: 10px;">Tarik Dana Sekarang</button>
</form>