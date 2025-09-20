<?php
defined('ABSPATH') || exit;
$all_products = function_exists('wc_get_products') ? wc_get_products(['limit' => -1, 'meta_key' => '_wppob_base_price', 'orderby' => 'name', 'order' => 'ASC']) : [];
?>
<div class="wrap">
    <h1>Edit Gambar Produk Massal</h1>
    <p>Pilih beberapa produk, lalu pilih gambar baru untuk diterapkan ke semua produk yang dipilih.</p>
    
    <div id="wppob-bulk-image-editor" class="wppob-bulk-editor-grid">
        <div class="editor-col">
            <h3>1. Pilih Produk</h3>
            <div class="wppob-product-checkbox-container" id="bulk-product-list">
                <?php if (!empty($all_products)): ?>
                    <?php foreach($all_products as $product): ?>
                        <div>
                            <label>
                                <input type="checkbox" class="wppob-product-check" value="<?php echo $product->get_id(); ?>"> 
                                <?php echo esc_html($product->get_name()); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Tidak ada produk PPOB yang ditemukan.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="editor-col">
            <h3>2. Pilih Gambar Baru</h3>
            <div class="wppob-image-uploader" id="bulk-image-uploader-wrapper">
                <img src="" class="wppob-image-preview" style="display:none; max-width: 150px; height: auto; margin-bottom: 10px; border: 1px solid #ddd;">
                <input type="hidden" id="wppob-new-image-id" class="wppob-image-id" value="">
                <button type="button" class="button wppob-upload-btn">Pilih Gambar</button>
                <button type="button" class="button wppob-remove-btn" style="display:none;">Hapus Gambar</button>
            </div>
            <hr>
            <button id="wppob-apply-bulk-image" class="button button-primary button-large" disabled>Terapkan ke Produk Terpilih</button>
            <span class="spinner"></span>
        </div>
    </div>
</div>
