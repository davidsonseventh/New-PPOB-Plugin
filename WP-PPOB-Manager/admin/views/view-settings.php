<div class="wrap wppob-wrap">
    <h1><?php _e('Pengaturan PPOB Manager', 'wp-ppob'); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('wppob_settings_group');
        do_settings_sections('wppob-settings');
        submit_button();
        ?>
        
        <h2><?php _e('Pengaturan Webhook', 'wp-ppob'); ?></h2>
        <p><?php _e('Gunakan URL berikut untuk menerima pembaruan status transaksi otomatis dari penyedia API (misalnya Digiflazz).', 'wp-ppob'); ?></p>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label><?php _e('URL Webhook Anda', 'wp-ppob'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text" readonly="readonly" value="<?php echo esc_url(home_url('/wp-json/wppob/v1/webhook')); ?>">
                        <p class="description"><?php _e('Salin URL ini dan tempelkan ke kolom Webhook di dashboard Digiflazz Anda.', 'wp-ppob'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

