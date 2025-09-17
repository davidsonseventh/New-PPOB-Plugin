<?php
if (!defined('WPINC')) {
    die;
}

$domain_search = isset($_POST['domain_search']) ? sanitize_text_field($_POST['domain_search']) : '';
$license_info = null;

if (!empty($domain_search)) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wplm_licenses';
    // Bersihkan domain input
    $clean_domain = preg_replace('/^https?:\/\//', '', $domain_search);
    $clean_domain = rtrim($clean_domain, '/');
    
    $license_info = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE activation_domain = %s",
        $clean_domain
    ));
}
?>

<div class="wplm-find-license-container">
    <h3><?php _e('Find My License Key', 'wp-license-manager'); ?></h3>
    <p><?php _e('Enter the domain where your license is activated to retrieve your key.', 'wp-license-manager'); ?></p>
    <form method="POST" action="">
        <input type="text" name="domain_search" placeholder="contoh.com" value="<?php echo esc_attr($domain_search); ?>" required>
        <button type="submit"><?php _e('Find Key', 'wp-license-manager'); ?></button>
    </form>

    <?php if (!empty($domain_search)) : ?>
        <div class="wplm-search-result">
            <?php if ($license_info) : ?>
                <h4><?php _e('License Found!', 'wp-license-manager'); ?></h4>
                <p><?php _e('Your license key is:', 'wp-license-manager'); ?></p>
                <div class="wplm-key-display">
                    <pre id="wplm-retrieved-key"><?php echo esc_html($license_info->license_key); ?></pre>
                    <button class="wplm-copy-btn" data-clipboard-target="#wplm-retrieved-key"><?php _e('Copy', 'wp-license-manager'); ?></button>
                </div>
            <?php else : ?>
                 <p><?php _e('No active license found for this domain.', 'wp-license-manager'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style> /* CSS Sederhana untuk Shortcode */
.wplm-key-display { display: flex; align-items: center; gap: 10px; background: #f0f0f1; padding: 5px 10px; }
.wplm-key-display pre { margin: 0; flex-grow: 1; }
</style>
