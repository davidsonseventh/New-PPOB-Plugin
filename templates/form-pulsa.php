<?php
/**
 * Template untuk Form Pulsa.
 * Template ini bisa di-override dengan menyalinnya ke folder [your-theme]/wppob/form-pulsa.php
 */
defined('ABSPATH') || exit;
?>
<div class="wppob-template-form wppob-form-pulsa">
    <h3>Beli Pulsa</h3>
    <div class="form-group">
        <label for="pulsa-phone-number">Nomor HP</label>
        <input type="tel" id="pulsa-phone-number" name="customer_no_pulsa" placeholder="Contoh: 08123456789">
    </div>
    <button type="submit" class="button">Beli Pulsa</button>
</div>
