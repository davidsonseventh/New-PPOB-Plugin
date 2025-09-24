<?php
defined('ABSPATH') || exit;

class WPPOB_Products {

    /**
     * ## FUNGSI DIPERBARUI ##
     * Fungsi ini sekarang membuat 2 permintaan terpisah ke API dan menggabungkan hasilnya.
     */
    public function prepare_sync() {
        delete_transient('wppob_sync_product_list');
        $api = new WPPOB_API();

        // Permintaan 1: Ambil semua produk prabayar
        $prepaid_response = $api->get_price_list('prepaid');
        $prepaid_products = (isset($prepaid_response['data']) && is_array($prepaid_response['data'])) ? $prepaid_response['data'] : [];

        // Permintaan 2: Ambil semua produk pascabayar (menggunakan 'pasca' sesuai dokumentasi)
        $postpaid_response = $api->get_price_list('pasca');
        $postpaid_products = (isset($postpaid_response['data']) && is_array($postpaid_response['data'])) ? $postpaid_response['data'] : [];

        // Gabungkan kedua hasil menjadi satu daftar lengkap
        $all_products = array_merge($prepaid_products, $postpaid_products);

        if (!empty($all_products)) {
            set_transient('wppob_sync_product_list', $all_products, HOUR_IN_SECONDS);
            return count($all_products);
        }

        return false;
    }

    public function process_sync_batch($offset, $limit) {
        $all_products = get_transient('wppob_sync_product_list');
        if (empty($all_products)) {
            return ['processed' => 0, 'done' => true, 'message' => 'Tidak ada data produk untuk diproses.'];
        }

        $product_batch = array_slice($all_products, $offset, $limit);

        if (empty($product_batch)) {
            delete_transient('wppob_sync_product_list');
            update_option('wppob_last_sync', current_time('mysql'));
            return ['processed' => 0, 'done' => true];
        }

        foreach ($product_batch as $product_data) {
            $this->create_or_update_product($product_data);
        }

        $is_last_batch = ($offset + count($product_batch)) >= count($all_products);
        if ($is_last_batch) {
            delete_transient('wppob_sync_product_list');
            update_option('wppob_last_sync', current_time('mysql'));
        }

        return ['processed' => count($product_batch), 'done' => $is_last_batch];
    }

    private function create_or_update_product($data) {
        if (!function_exists('wc_get_product_id_by_sku') || empty($data['buyer_sku_code'])) {
            return;
        }

        $product_id = wc_get_product_id_by_sku($data['buyer_sku_code']);
        $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();

        $product->set_sku($data['buyer_sku_code']);
        $product->set_name(sanitize_text_field($data['product_name']));

        // Logika untuk membaca harga dari 'price' (prabayar) atau 'admin' (pascabayar)
        if (isset($data['pasca']) && $data['pasca'] === true && isset($data['admin'])) {
            $base_price = (float) $data['admin'];
        } else {
           // ## PERBAIKAN LOGIKA HARGA ##
// Logika ini akan mencari harga yang benar untuk semua jenis produk.
$base_price = 0;
if (isset($data['price'])) {
    // Utamakan mengambil dari kolom 'price' (untuk prabayar dan beberapa pascabayar)
    $base_price = (float) $data['price'];
} elseif (isset($data['admin'])) {
    // Jika 'price' tidak ada, cari di kolom 'admin' sebagai alternatif
    $base_price = (float) $data['admin'];
}
        }

        $sale_price = $this->calculate_sale_price($base_price);

        $product->set_regular_price($sale_price);
        $product->set_price($sale_price);

        $product->update_meta_data('_wppob_base_price', $base_price);
        $product->update_meta_data('_wppob_category', sanitize_text_field($data['category']));
        $product->update_meta_data('_wppob_brand', sanitize_text_field($data['brand']));

        $product->set_virtual(true);
        $product->set_manage_stock(false);

        if (!$product_id) {
            $product->set_status('private');
        }

        $product->save();
    }

   public function calculate_sale_price($base_price) {
    if (class_exists('WPPOB_Admin')) {
        $admin_instance = new WPPOB_Admin();
        if (!$admin_instance->is_license_valid()) {
            return ceil((float)$base_price);
        }
    }

    $base_price = (float) $base_price;
    $profit_type = get_option('wppob_profit_type', 'fixed');
    $profit_amount = (float) get_option('wppob_profit_amount', 0);

    if ($profit_type === 'percentage') {
        $sale_price = $base_price + ($base_price * ($profit_amount / 100));
    } else {
        $sale_price = $base_price + $profit_amount;
    }
    return ceil($sale_price);
}

    public function bulk_update_prices() {
        $args = ['limit' => -1, 'meta_key' => '_wppob_base_price'];
        $products = wc_get_products($args);

        foreach ($products as $product) {
            $base_price = $product->get_meta('_wppob_base_price');
            if (!empty($base_price)) {
                $new_price = $this->calculate_sale_price($base_price);
                $product->set_regular_price($new_price);
                $product->set_price($new_price);
                $product->save();
            }
        }
    }
}