<?php defined('ABSPATH') || exit; ?>

 <div class="wrap wppob-wrap">
    <h1 class="wp-heading-inline"><?php _e('Manajemen Produk PPOB', 'wp-ppob'); ?></h1>
    <button id="wppob-sync-products" class="page-title-action"><?php _e('Sinkronkan Produk dari API', 'wp-ppob'); ?></button>
    <span class="spinner" style="float: none; margin-top: 4px;"></span>
    
    <div id="wppob-sync-progress" style="display:none; margin-top: 10px; padding: 10px; background-color: #f0f0f1; border: 1px solid #ddd; border-radius: 4px;"></div>

    <p><?php _e('Daftar produk PPOB yang tersedia. Harga jual diatur melalui halaman Pengaturan.', 'wp-ppob'); ?></p>
     <p><?php _e('Sinkronisasi terakhir:', 'wp-ppob'); ?> <strong><?php echo get_option('wppob_last_sync', 'Belum pernah'); ?></strong></p>
    </div>   
    <?php
    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-error"><p>' . __('Plugin WooCommerce tidak aktif. Halaman ini memerlukan WooCommerce.', 'wp-ppob') . '</p></div>';
        return;
    }

    $products_args = [
        'limit'   => -1,
        'status'  => ['publish', 'private'],
        'meta_query' => [
            ['key' => '_wppob_base_price', 'compare' => 'EXISTS']
        ]
    ];
    $products = wc_get_products($products_args);
    ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('Nama Produk', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('SKU', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Kategori', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Harga Dasar', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Harga Jual', 'wp-ppob'); ?></th>
                <th scope="col"><?php _e('Profit', 'wp-ppob'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)) : ?>
                <?php foreach ($products as $product) : ?>
                    <?php 
                        $base_price = (float)$product->get_meta('_wppob_base_price');
                        $sale_price = (float)$product->get_price();
                    ?>
                    <tr>
                        <td><strong><a href="<?php echo get_edit_post_link($product->get_id()); ?>"><?php echo esc_html($product->get_name()); ?></a></strong></td>
                        <td><?php echo esc_html($product->get_sku()); ?></td>
                        <td><?php echo esc_html($product->get_meta('_wppob_category')); ?></td>
                        <td><?php echo wppob_format_rp($base_price); ?></td>
                        <td><?php echo wppob_format_rp($sale_price); ?></td>
                        <td><?php echo wppob_format_rp($sale_price - $base_price); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6"><?php _e('Tidak ada produk PPOB. Silakan lakukan sinkronisasi.', 'wp-ppob'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
