<?php
defined('ABSPATH') || exit;

class WPPOB_Products {

    public function prepare_sync() {
        $api = new WPPOB_API();
        $response = $api->get_price_list();

        if (isset($response['data']) && is_array($response['data'])) {
            set_transient('wppob_sync_product_list', $response['data'], HOUR_IN_SECONDS);
            return count($response['data']);
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
            if (isset($product_data['pasca']) && $product_data['pasca'] === false) {
                $this->create_or_update_product($product_data);
            }
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
        
        $base_price = (float) $data['price'];
        $sale_price = $this->calculate_sale_price($base_price);
        
        $product->set_regular_price($sale_price);
        $product->set_price($sale_price);

        $product->update_meta_data('_wppob_base_price', $base_price);
        $product->update_meta_data('_wppob_category', sanitize_text_field($data['category']));
        $product->update_meta_data('_wppob_brand', sanitize_text_field($data['brand']));
        
        $product->set_virtual(true);
        $product->set_manage_stock(false);

        if (!$product_id) {
            $product->set_status('private'); // Produk baru dibuat sebagai "Private"
        }
        
        $product->save();
    }

    public function calculate_sale_price($base_price) {
        $base_price = (float) $base_price;
        $profit_type = get_option('wppob_profit_type', 'fixed');
        $profit_amount = (float) get_option('wppob_profit_amount', 1000);

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