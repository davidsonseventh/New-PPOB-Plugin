<div class="wrap wppob-wrap">
    <h1><?php _e('Pengaturan PPOB Manager', 'wp-ppob'); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('wppob_settings_group');
        do_settings_sections('wppob-settings');
        submit_button();
        ?>
    </form>
</div>
