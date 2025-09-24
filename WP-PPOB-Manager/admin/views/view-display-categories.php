<?php
defined('ABSPATH') || exit;

global $wpdb;
$table_name = $wpdb->prefix . 'wppob_display_categories';
$all_categories = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY parent_id ASC, sort_order ASC");

$categories_tree = [];
$category_lookup = [];
if (!empty($all_categories)) {
    foreach ($all_categories as $cat) {
        $category_lookup[$cat->id] = $cat;
        $cat->children = [];
    }
    foreach ($all_categories as $cat) {
        if ($cat->parent_id != 0 && isset($category_lookup[$cat->parent_id])) {
            $category_lookup[$cat->parent_id]->children[] = $cat;
        } else {
            $categories_tree[] = $cat;
        }
    }
}

if (!function_exists('wppob_display_sortable_categories')) {
    function wppob_display_sortable_categories($categories) {
        echo '<ol class="wppob-sortable-list">';
        foreach ($categories as $cat) {
            $has_children = !empty($cat->children);
            echo '<li id="cat-' . esc_attr($cat->id) . '">';
            echo '<div>';
            echo '<span class="dashicons dashicons-move"></span> ';
            if ($has_children) {
                echo '<span class="wppob-toggle-children dashicons dashicons-minus"></span>';
            }
            echo esc_html($cat->name);
            echo '<a href="?page=wppob-display-categories&action=edit&id=' . esc_attr($cat->id) . '" class="wppob-edit-link">Edit</a>';
            echo '</div>';
            if ($has_children) {
                wppob_display_sortable_categories($cat->children);
            }
            echo '</li>';
        }
        echo '</ol>';
    }
}

$edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$item_to_edit = null;
$selected_products_obj = [];

if ($edit_mode) { 
    $item_id = intval($_GET['id']); 
    $item_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $item_id)); 
    $assigned_product_ids = !empty($item_to_edit->assigned_products) ? json_decode($item_to_edit->assigned_products, true) : [];
    
    if (!empty($assigned_product_ids)) {
        $args = [
            'limit' => -1,
            'include' => $assigned_product_ids,
            'orderby' => 'post__in',
        ];
        $selected_products_obj = function_exists('wc_get_products') ? wc_get_products($args) : [];

// --- TAMBAHAN KODE UNTUK MEMASTIKAN URUTAN PRODUK BENAR ---
if (!empty($selected_products_obj) && !empty($assigned_product_ids)) {
    $sorted_products = array_fill_keys($assigned_product_ids, false);
    foreach ($selected_products_obj as $product) {
        $sorted_products[$product->get_id()] = $product;
    }
    $selected_products_obj = array_filter($sorted_products);
}
// --- AKHIR DARI TAMBAHAN KODE ---

    }
}

$ppob_products = function_exists('wc_get_products') ? wc_get_products(['limit' => -1, 'meta_key' => '_wppob_base_price', 'orderby' => 'name', 'order' => 'ASC']) : [];
$assigned_product_ids_for_checkbox = !empty($item_to_edit->assigned_products) ? json_decode($item_to_edit->assigned_products) : [];
?>
<div class="wrap">
    <h1>Kelola Kategori Tampilan</h1>
    <p>Gunakan antarmuka di sebelah kanan untuk menyusun urutan dan hierarki kategori. Klik "Edit" untuk mengubah detail.</p>

    <div id="col-container" class="wp-clearfix">
        <div id="col-left">
            <div class="col-wrap">
                <h3><?php echo $edit_mode ? 'Edit Kategori: ' . esc_html($item_to_edit->name ?? '') : 'Tambah Kategori Baru'; ?></h3>
                <form method="post" action="">
                    <input type="hidden" name="category_id" value="<?php echo esc_attr($item_to_edit->id ?? 0); ?>">
                    <?php wp_nonce_field('wppob_save_category_action', 'wppob_save_category_nonce'); ?>

                    <h4>Pengaturan Dasar</h4>
                    <div class="form-field"><label for="cat_name">Nama Kategori</label><input name="cat_name" id="cat_name" type="text" value="<?php echo esc_attr($item_to_edit->name ?? ''); ?>" required></div>
                    


<div class="form-field">
                        <label for="parent_id">Induk Kategori</label>
                        <select name="parent_id" id="parent_id">
                            <option value="0"><?php _e( '— Tidak ada —', 'wp-ppob' ); ?></option>
                            <?php
                            if ( ! empty( $all_categories ) ) {
                                foreach ( $all_categories as $cat ) {
                                    // Mencegah kategori diedit menjadi anak dari dirinya sendiri
                                    if ( $edit_mode && $item_to_edit->id === $cat->id ) {
                                        continue;
                                    }
                                    echo '<option value="' . esc_attr( $cat->id ) . '"' . selected( $item_to_edit->parent_id ?? 0, $cat->id, false ) . '>' . esc_html( $cat->name ) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <p class="description">Pilih kategori induk untuk membuat sub-kategori.</p>
                    </div>


                    <h4>Tampilan Kategori</h4>
                    <div class="form-field">
                        <label>Gambar</label>
                        <div class="wppob-image-uploader">
                            <?php $image_url = ($item_to_edit->image_id ?? 0) ? wp_get_attachment_image_url($item_to_edit->image_id, 'thumbnail') : ''; ?>
                            <img src="<?php echo esc_url($image_url); ?>" class="wppob-image-preview" style="<?php echo empty($image_url) ? 'display:none;' : ''; ?> max-width: 80px; height: auto;">
                            <input type="hidden" name="cat_image_id" class="wppob-image-id" value="<?php echo esc_attr($item_to_edit->image_id ?? 0); ?>">
                            <button type="button" class="button wppob-upload-btn" style="<?php echo !empty($image_url) ? 'display:none;' : ''; ?>">Pilih Gambar</button>
                            <button type="button" class="button wppob-remove-btn" style="<?php echo empty($image_url) ? 'display:none;' : ''; ?>">Hapus Gambar</button>
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="display_style">Gaya Tampilan Kategori</label>
                        <select name="display_style" id="display_style">
                            <option value="image_text" <?php selected($item_to_edit->display_style ?? 'image_text', 'image_text'); ?>>Gambar & Teks</option>
                            <option value="image_only" <?php selected($item_to_edit->display_style ?? '', 'image_only'); ?>>Gambar Saja</option>
                            <option value="text_only" <?php selected($item_to_edit->display_style ?? '', 'text_only'); ?>>Teks Saja</option>
                        </select>
                    </div>

                   <h4>Konten Kategori</h4>
                    <?php
                    $has_children_check = false;
                    if ($edit_mode) {
                        $child_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$table_name} WHERE parent_id = %d", intval($_GET['id'])));
                        $has_children_check = $child_count > 0;
                    }

                    if ($edit_mode && $has_children_check):
                    ?>
                        <div class="form-field">
                            <p class="description">Kategori ini memiliki sub-kategori, sehingga tidak bisa diisi produk. Atur produk di sub-kategorinya.</p>
                        </div>
                    <?php else: ?>
                        <div class="form-field">
                            <label for="wppob-sku-input"><strong>Opsi 1: Tambah Produk via SKU</strong></label>
                            <p class="description">Masukkan satu atau beberapa SKU, pisahkan dengan spasi atau baris baru (Enter).</p>
                            <textarea id="wppob-sku-input" rows="4" style="width:100%; font-family: monospace;"></textarea>
                            <button type="button" id="wppob-add-products-by-sku" class="button" style="margin-top: 5px;">Cari & Centang Produk</button>
                            <span id="wppob-sku-feedback" style="display: block; margin-top: 5px; font-style: italic;"></span>
                        </div>
                        <div class="form-field" style="margin-top: 20px;">
                            <label><strong>Opsi 2: Pilih Produk Manual</strong></label>
                             <p class="description">Produk yang ditemukan via SKU akan otomatis tercentang di daftar bawah ini.</p>
                            <div class="wppob-product-checkbox-container">
                                <?php if (!empty($ppob_products)): foreach($ppob_products as $product):
                                    $product_id = $product->get_id();
                                    $product_sku = $product->get_sku();
                                    $is_checked = in_array($product_id, $assigned_product_ids_for_checkbox);
                                ?>
                                    <div>
                                        <label>
                                            <input type="checkbox" name="assigned_products[]" value="<?php echo esc_attr($product_id); ?>" data-sku="<?php echo esc_attr($product_sku); ?>" <?php checked($is_checked, true); ?>>
                                            <?php echo esc_html($product->get_name()); ?> (SKU: <strong><?php echo esc_html($product_sku); ?></strong>)
                                        </label>
                                    </div>
                                <?php endforeach; else: ?>
                                    <p>Tidak ada produk PPOB.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-field" style="margin-top: 20px;">
                            <label>Urutkan Produk Terpilih</label>
                            <p class="description">Gunakan drag & drop untuk mengubah urutan produk di kategori ini.</p>
                            <ul id="wppob-sortable-products">
                                <?php if (!empty($selected_products_obj)): ?>
                                    <?php foreach ($selected_products_obj as $product): ?>
                                        <li data-id="<?php echo esc_attr($product->get_id()); ?>">
                                            <span class="dashicons dashicons-move"></span>
                                            <?php echo esc_html($product->get_name()); ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="placeholder">Pilih produk dari daftar di atas untuk mengurutkannya.</li>
                                <?php endif; ?>
                            </ul>
                            <span class="spinner"></span>
                        </div>
                    <?php endif; ?>
                    
                    <h4>Tampilan Produk</h4>
                    <p class="description">Atur bagaimana produk di dalam kategori ini ditampilkan.</p>
                    <div class="form-field">
                        <label for="product_display_style">Gaya Tampilan Produk</label>
                        <select name="product_display_style" id="product_display_style">
                            <option value="image_text" <?php selected($item_to_edit->product_display_style ?? 'image_text', 'image_text'); ?>>Gambar & Teks</option>
                            <option value="image_only" <?php selected($item_to_edit->product_display_style ?? '', 'image_only'); ?>>Gambar Saja</option>
                            <option value="text_only" <?php selected($item_to_edit->product_display_style ?? '', 'text_only'); ?>>Teks Saja</option>
                        </select>
                    </div>
                     <div class="form-field">
                        <label for="product_display_mode">Tata Letak Produk</label>
                        <select name="product_display_mode" id="product_display_mode">
                            <option value="grid" <?php selected($item_to_edit->product_display_mode ?? 'grid', 'grid'); ?>>Grid</option>
                            <option value="list" <?php selected($item_to_edit->product_display_mode ?? '', 'list'); ?>>Daftar (List)</option>
                        </select>
                    </div>
                    
                    <?php submit_button($edit_mode ? 'Perbarui Kategori' : 'Tambah Kategori'); ?>
                </form>
            </div>
        </div>
        <div id="col-right">
            <div class="col-wrap">
                <h3>Struktur Kategori <span class="spinner"></span></h3>
                <div id="wppob-category-organizer">
                    <?php if (function_exists('wppob_display_sortable_categories')) { wppob_display_sortable_categories($categories_tree); } ?>
                </div>
                <p class="description">Gunakan drag & drop untuk mengatur urutan. Perubahan disimpan otomatis.</p>
            </div>
        </div>
    </div>
</div>
