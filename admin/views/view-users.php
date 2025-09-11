<?php defined('ABSPATH') || exit; ?>
<div class="wrap wppob-wrap">
    <h1 class="wp-heading-inline"><?php _e('Manajemen Pengguna PPOB', 'wp-ppob'); ?></h1>
    
    <p><?php _e('Kelola saldo dan lihat detail pengguna yang terdaftar di sistem PPOB Anda.', 'wp-ppob'); ?></p>

    <div id="wppob-user-manager">
        <div class="wppob-col-left">
            <h3><?php _e('Cari Pengguna', 'wp-ppob'); ?></h3>
            <p><?php _e('Cari pengguna berdasarkan username atau email.', 'wp-ppob'); ?></p>
            <input type="text" id="wppob-user-search-input" class="large-text" placeholder="<?php _e('Ketik untuk mencari...', 'wp-ppob'); ?>">
            <ul id="wppob-user-search-results"></ul>
        </div>

        <div class="wppob-col-right">
            <div id="wppob-user-details-wrapper" style="display: none;">
                <h3><?php _e('Detail Pengguna', 'wp-ppob'); ?></h3>
                <p><strong>Username:</strong> <span id="wppob-detail-username"></span></p>
                <p><strong>Email:</strong> <span id="wppob-detail-email"></span></p>
                <p><strong>Saldo Saat Ini:</strong> <span id="wppob-detail-balance"></span></p>
                
                <hr>

                <h4><?php _e('Sesuaikan Saldo', 'wp-ppob'); ?></h4>
                <div class="form-group">
                    <label for="wppob-adjust-amount"><?php _e('Jumlah', 'wp-ppob'); ?></label>
                    <input type="number" step="any" id="wppob-adjust-amount" placeholder="Gunakan - (minus) untuk mengurangi">
                </div>
                 <div class="form-group">
                    <label for="wppob-adjust-note"><?php _e('Catatan (Opsional)', 'wp-ppob'); ?></label>
                    <input type="text" id="wppob-adjust-note" placeholder="Contoh: Bonus topup">
                </div>
                <input type="hidden" id="wppob-adjust-user-id" value="">
                
                <button id="wppob-adjust-balance-submit" class="button button-primary"><?php _e('Simpan Perubahan', 'wp-ppob'); ?></button>
                <span class="spinner"></span>
                <div class="wppob-message-area-user"></div>
            </div>
            <div id="wppob-user-placeholder">
                <p><?php _e('Pilih pengguna dari daftar di sebelah kiri untuk melihat detail.', 'wp-ppob'); ?></p>
            </div>
        </div>
    </div>
</div>
