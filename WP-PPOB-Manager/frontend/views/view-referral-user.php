<?php
// WP-PPOB-Manager/frontend/views/view-referral-user.php
defined('ABSPATH') || exit;
$current_user = wp_get_current_user();
// Kode referral adalah username pengguna
$referral_code = $current_user->user_login;
$referral_link = add_query_arg('ref', $referral_code, home_url('/'));

global $wpdb;
$log_table = $wpdb->prefix . 'wppob_referral_log';
// Menghitung total komisi yang didapat pengguna ini
$total_commission = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(commission_amount) FROM {$log_table} WHERE referrer_id = %d",
    $current_user->ID
));

// Mencari semua pengguna yang direferensikan oleh pengguna ini
$referred_users_meta = get_users([
    'meta_key' => '_wppob_referrer_id',
    'meta_value' => $current_user->ID,
    'fields' => ['user_login', 'user_registered'],
]);
?>

<h3><?php _e('Program Referral', 'wp-ppob'); ?></h3>
<p><?php _e('Ajak teman Anda untuk bergabung dan dapatkan keuntungan dari setiap pendaftaran atau transaksi yang mereka lakukan.', 'wp-ppob'); ?></p>

<div class="wppob-referral-box">
    <h4><?php _e('Link Referral Anda', 'wp-ppob'); ?></h4>
    <p><?php _e('Bagikan tautan ini kepada teman Anda:', 'wp-ppob'); ?></p>
    <input type="text" value="<?php echo esc_attr($referral_link); ?>" readonly onclick="this.select(); document.execCommand('copy'); alert('Link disalin!');">
    <small><?php _e('Klik untuk menyalin link.', 'wp-ppob'); ?></small>
</div>

<div class="wppob-referral-stats">
    <div>
        <strong><?php _e('Total Keuntungan Didapat', 'wp-ppob'); ?></strong>
        <span><?php echo wppob_format_rp($total_commission ?: 0); ?></span>
    </div>
    <div>
        <strong><?php _e('Total Pengguna Direferensikan', 'wp-ppob'); ?></strong>
        <span><?php echo count($referred_users_meta); ?></span>
    </div>
</div>

<h4><?php _e('Pengguna yang Anda Referensikan', 'wp-ppob'); ?></h4>
<?php if (!empty($referred_users_meta)) : ?>
<table class="shop_table shop_table_responsive">
    <thead>
        <tr>
            <th><?php _e('Username', 'wp-ppob'); ?></th>
            <th><?php _e('Tanggal Bergabung', 'wp-ppob'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($referred_users_meta as $referred_user) : ?>
        <tr>
            <td><?php echo esc_html($referred_user->user_login); ?></td>
            <td><?php echo date_i18n('d M Y', strtotime($referred_user->user_registered)); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p><?php _e('Anda belum mereferensikan siapa pun.', 'wp-ppob'); ?></p>
<?php endif; ?>

<style>
.wppob-referral-box { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 5px; }
.wppob-referral-box input { width: 100%; padding: 8px; background: #fff; border: 1px solid #ccc; cursor: pointer; }
.wppob-referral-stats { display: flex; gap: 20px; margin-bottom: 20px; }
.wppob-referral-stats > div { flex: 1; padding: 15px; background: #f0f8ff; text-align: center; border-radius: 5px; }
.wppob-referral-stats strong { display: block; font-size: 14px; }
.wppob-referral-stats span { font-size: 20px; font-weight: bold; }
</style>