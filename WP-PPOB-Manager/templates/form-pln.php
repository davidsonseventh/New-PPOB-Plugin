<?php
/**
 * Template untuk Form Token Listrik PLN.
 * Template ini bisa di-override dengan menyalinnya ke folder [your-theme]/wppob/form-pln.php
 */
defined('ABSPATH') || exit;
?>
<div class="wppob-template-form wppob-form-pln">
    <h3>Beli Token Listrik</h3>
    <div class="form-group">
        <label for="pln-customer-id">ID Pelanggan</label>
        <input type="text" id="pln-customer-id" name="customer_no_pln" placeholder="Masukkan ID Pelanggan...">
    </div>
    <button type="submit" class="button">Beli Token</button>
</div>
