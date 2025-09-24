<?php
defined('ABSPATH') || exit;
$user_id = get_current_user_id();
$has_pin = metadata_exists('user', $user_id, '_wppob_transaction_pin');
?>
<h3><?php _e('Pengaturan PIN Keamanan', 'wp-ppob'); ?></h3>

<?php if ($has_pin) : ?>
    <p><?php _e('Ubah PIN transaksi Anda. PIN ini digunakan untuk mengotorisasi setiap transaksi.', 'wp-ppob'); ?></p>
    <form id="wppob-change-pin-form" method="POST">
        <div class="wppob-form-group">
            <label for="wppob-current-pin"><?php _e('PIN Saat Ini', 'wp-ppob'); ?></label>
            <input type="password" id="wppob-current-pin" name="current_pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" required>
        </div>
        <div class="wppob-form-group">
            <label for="wppob-new-pin"><?php _e('PIN Baru (6 Digit Angka)', 'wp-ppob'); ?></label>
            <input type="password" id="wppob-new-pin" name="new_pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" required>
        </div>
        <div class="wppob-form-group">
            <label for="wppob-confirm-pin"><?php _e('Konfirmasi PIN Baru', 'wp-ppob'); ?></label>
            <input type="password" id="wppob-confirm-pin" name="confirm_pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" required>
        </div>
        <input type="hidden" name="action" value="wppob_update_pin">
        <?php wp_nonce_field('wppob_update_pin_nonce', 'nonce'); ?>
        <div id="wppob-pin-notification-area"></div>
        <button type="submit" id="wppob-submit-update-pin"><?php _e('Ubah PIN', 'wp-ppob'); ?></button>
    </form>
<?php else : ?>
    <p><?php _e('Anda belum membuat PIN transaksi. Buat PIN sekarang untuk meningkatkan keamanan akun Anda.', 'wp-ppob'); ?></p>
    <form id="wppob-create-pin-form" method="POST">
        <div class="wppob-form-group">
            <label for="wppob-new-pin"><?php _e('PIN Baru (6 Digit Angka)', 'wp-ppob'); ?></label>
            <input type="password" id="wppob-new-pin" name="new_pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" required>
        </div>
        <div class="wppob-form-group">
            <label for="wppob-confirm-pin"><?php _e('Konfirmasi PIN', 'wp-ppob'); ?></label>
            <input type="password" id="wppob-confirm-pin" name="confirm_pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" required>
        </div>
        <input type="hidden" name="action" value="wppob_create_pin">
        <?php wp_nonce_field('wppob_create_pin_nonce', 'nonce'); ?>
        <div id="wppob-pin-notification-area"></div>
        <button type="submit" id="wppob-submit-create-pin"><?php _e('Buat PIN', 'wp-ppob'); ?></button>
    </form>
<?php endif; ?>