<?php
defined('ABSPATH') || exit;

class WPPOB_Products {

    public function sync_products() {
        $api = new WPPOB_API();
        $response = $api->get_price_list();

        if (isset($response['data'])) {
            $products = $response['data'];
            foreach ($products as $product_data) {
                if ($product_data['pasca'] === false) { // Hanya proses produk prabayar
                    $this->create_or_update_product($product_data);
                }
            }
            update_option('wppob_last_sync', current_time('mysql'));
            return true;
        }
        return false;
    }

    private function create_or_update_product($data) {
        if (!function_exists('wc_get_product_id_by_sku')) {
            return;
        }

        $product_id = wc_get_product_id_by_sku($data['buyer_sku_code']);
        $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();

        $product->set_sku($data['buyer_sku_code']);
        $product->set_name($data['product_name']);
        
        $base_price = (float)$data['price'];
        $sale_price = $this->calculate_sale_price($base_price);
        
        $product->set_regular_price($sale_price);
        $product->set_price($sale_price);

        $product->update_meta_data('_wppob_base_price', $base_price);
        $product->update_meta_data('_wppob_category', $data['category']);
        $product->update_meta_data('_wppob_brand', $data['brand']);
        
        $product->set_virtual(true);
        $product->set_manage_stock(false);

        if (!$product_id) {
            $product->set_status('private'); // Set produk baru sebagai non-aktif by default
        }
        
        $product->save();
    }

    public function calculate_sale_price($base_price) {
        $profit_type = get_option('wppob_profit_type', 'fixed');
        $profit_amount = (float)get_option('wppob_profit_amount', 1000);
        $sale_price = 0;

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
            $base_price = (float)$product->get_meta('_wppob_base_price');
            if ($base_price > 0) {
                $new_price = $this->calculate_sale_price($base_price);
                $product->set_regular_price($new_price);
                $product->set_price($new_price);
                $product->save();
            }
        }
    }
}
