<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body <?php body_class(); ?>>

    <header class="app-header">
        <div class="header-main">
            <div class="site-branding">
                <div class="site-logo">S</div>
                <div class="site-info">
                    <span class="site-name"><?php bloginfo('name'); ?></span>
                    <?php if (is_user_logged_in()) : 
                        $user_balance = 0;
                        if (class_exists('WPPOB_Balances')) {
                            $user_balance = WPPOB_Balances::get_user_balance(get_current_user_id());
                        }
                    ?>
                        <span class="user-balance"><?php echo wppob_format_rp($user_balance); ?></span>
                    <?php else: ?>
                        <span class="user-balance"><a href="<?php echo esc_url(wp_login_url()); ?>">Login untuk lihat saldo</a></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="header-promo">
                <a href="#">
                    <i class="fa-solid fa-tags"></i>
                    <span>Promo</span>
                </a>
            </div>
        </div>
        <div class="header-menu">
            <div class="menu-item">
                <a href="<?php echo esc_url(home_url('/dashboard-saya/?tab=topup')); // Ganti dengan URL halaman topup Anda ?>">
                    <div class="icon-circle"><i class="fa-solid fa-wallet"></i></div>
                    <span>Top Up</span>
                </a>
            </div>
            <div class="menu-item">
    <a href="<?php echo esc_url(home_url('/dashboard-saya/?tab=transfer')); ?>">
        <div class="icon-circle"><i class="fa-solid fa-paper-plane"></i></div>
        <span>Transfer</span>
    </a>
</div>
            <div class="menu-item">
    <a href="<?php echo esc_url(home_url('/dashboard-saya/?tab=tarik-komisi')); ?>">
        <div class="icon-circle"><i class="fa-solid fa-sack-dollar"></i></div>
        <span>Tarik Komisi</span>
    </a>
</div>
            <div class="menu-item">
                <a href="<?php echo esc_url(home_url('/dashboard-saya/')); // Ganti dengan URL halaman dashboard Anda ?>">
                    <div class="icon-circle"><i class="fa-solid fa-receipt"></i></div>
                    <span>History</span>
                </a>
            </div>
        </div>
    </header>

    <div id="content" class="site-content">