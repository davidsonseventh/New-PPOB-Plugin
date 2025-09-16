(function ($) {
    'use strict';
    $(document).ready(function () {
        
        // --- FUNGSI SINKRONISASI PRODUK (SISTEM BATCH BARU V2) ---
        let totalProducts = 0;
        let processedProducts = 0;
        const batchSize = 25; // Proses 25 produk per permintaan

        $('#wppob-sync-products').on('click', function (e) {
            e.preventDefault();
            const button = $(this);
            const spinner = button.next('.spinner');
            const progressArea = $('#wppob-sync-progress');

            button.prop('disabled', true);
            spinner.addClass('is-active');
            progressArea.html('Memulai sinkronisasi, mengambil daftar produk dari API...').show();
            processedProducts = 0;

            $.post(wppob_admin_params.ajax_url, {
                action: 'wppob_prepare_sync', // Aksi untuk tahap 1
                nonce: wppob_admin_params.nonce
            })
            .done(function(response) {
                if (response.success) {
                    totalProducts = response.data.total;
                    progressArea.html(`Ditemukan ${totalProducts} produk. Memulai proses penyimpanan...`);
                    processBatch(0); // Mulai batch pertama
                } else {
                    progressArea.html('Gagal: ' + (response.data.message || 'Tidak dapat mengambil daftar produk dari API.'));
                    button.prop('disabled', false);
                    spinner.removeClass('is-active');
                }
            })
            .fail(function() {
                progressArea.html('Error: Gagal terhubung ke server. Periksa koneksi internet Anda.');
                button.prop('disabled', false);
                spinner.removeClass('is-active');
            });
        });

        function processBatch(offset) {
            const progressArea = $('#wppob-sync-progress');

            $.post(wppob_admin_params.ajax_url, {
                action: 'wppob_process_sync_batch', // Aksi untuk tahap 2
                nonce: wppob_admin_params.nonce,
                offset: offset,
                batch_size: batchSize
            })
            .done(function(response) {
                if (response.success) {
                    processedProducts += response.data.processed;
                    let percentage = totalProducts > 0 ? (processedProducts / totalProducts * 100).toFixed(2) : 100;
                    progressArea.html(`Memproses... ${processedProducts} dari ${totalProducts} produk telah disimpan. (${percentage}%)`);

                    if (response.data.done) {
                        progressArea.html('Sinkronisasi selesai! Semua produk berhasil disimpan. Halaman akan dimuat ulang...');
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        // Lanjut ke batch berikutnya
                        processBatch(offset + batchSize);
                    }
                } else {
                    progressArea.html(`Error: ${response.data.message || 'Terjadi kesalahan saat memproses batch.'}`);
                    $('#wppob-sync-products').prop('disabled', false).next('.spinner').removeClass('is-active');
                }
            })
            .fail(function() {
                progressArea.html('Error: Koneksi ke server terputus saat memproses. Silakan coba lagi.');
                $('#wppob-sync-products').prop('disabled', false).next('.spinner').removeClass('is-active');
            });
        }


        // --- FUNGSI UPLOAD GAMBAR (UNTUK SEMUA UPLOADER) ---
        $(document).on('click', '.wppob-upload-btn', function(e){
            e.preventDefault();
            const button = $(this);
            const uploaderWrapper = button.closest('.wppob-image-uploader');
            const frame = wp.media({ title: 'Pilih Gambar', button: { text: 'Gunakan Gambar Ini' }, multiple: false });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                uploaderWrapper.find('.wppob-image-id').val(attachment.id).trigger('change');
                uploaderWrapper.find('.wppob-image-preview').attr('src', attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url).show();
                button.hide();
                uploaderWrapper.find('.wppob-remove-btn').show();
            });
            frame.open();
        });

        $(document).on('click', '.wppob-remove-btn', function(e){
            e.preventDefault();
            const button = $(this);
            const uploaderWrapper = button.closest('.wppob-image-uploader');
            uploaderWrapper.find('.wppob-image-id').val('').trigger('change');
            uploaderWrapper.find('.wppob-image-preview').attr('src', '').hide();
            button.hide();
            button.siblings('.wppob-upload-btn').show();
        });

        // --- FUNGSI DRAG & DROP KATEGORI ---
        const organizer = $('#wppob-category-organizer');
        if (organizer.length) {
            const spinner = organizer.parent().find('.spinner');
            organizer.find('ol.wppob-sortable-list').sortable({
                placeholder: 'wppob-sortable-placeholder',
                connectWith: '.wppob-sortable-list',
                handle: '.dashicons-move',
                update: function(event, ui) {
                    spinner.addClass('is-active');
                    const data = [];
                    organizer.find('li').each(function(index, elem) {
                        const item = $(elem);
                        const parent = item.parent('ol').parent('li');
                        data.push({ id: item.attr('id'), parent_id: parent.length ? parent.attr('id') : null });
                    });
                    $.post(wppob_admin_params.ajax_url, {
                        action: 'wppob_update_category_order',
                        nonce: wppob_admin_params.nonce,
                        order: data
                    }).done(function() {
                        spinner.removeClass('is-active');
                    });
                }
            }).disableSelection();

            organizer.on('click', '.wppob-toggle-children', function() {
                const button = $(this);
                const childrenOl = button.closest('li').children('ol.wppob-sortable-list');
                if (childrenOl.is(':visible')) {
                    childrenOl.slideUp('fast');
                    button.removeClass('dashicons-minus').addClass('dashicons-plus-alt2');
                } else {
                    childrenOl.slideDown('fast');
                    button.removeClass('dashicons-plus-alt2').addClass('dashicons-minus');
                }
            });
        }
        
      // --- FUNGSI DRAG & DROP PRODUK DALAM KATEGORI ---
        const sortableProducts = $('#wppob-sortable-products');
        if (sortableProducts.length) {
            const spinner = sortableProducts.next('.spinner');
            sortableProducts.sortable({
                placeholder: 'wppob-sortable-placeholder',
                handle: '.dashicons-move',
                update: function(event, ui) {
                    spinner.addClass('is-active');
                    
                    const product_ids = $(this).sortable('toArray', { attribute: 'data-id' });
                    const category_id = $('input[name="category_id"]').val();

                    $.post(wppob_admin_params.ajax_url, {
                        action: 'wppob_update_product_order_in_category',
                        nonce: wppob_admin_params.nonce,
                        category_id: category_id,
                        product_ids: product_ids
                    }).done(function(response) {
                        if (!response.success) {
                            alert('Gagal menyimpan urutan produk: ' + (response.data.message || 'Error tidak diketahui.'));
                        }
                    }).fail(function() {
                        alert('Terjadi kesalahan koneksi saat menyimpan urutan produk.');
                    }).always(function() {
                        spinner.removeClass('is-active');
                    });
                }
            }).disableSelection();
        }

       // --- FUNGSI EDIT GAMBAR MASSAL ---
        const bulkEditor = $('#wppob-bulk-image-editor');
        if (bulkEditor.length) {
            const applyBtn = $('#wppob-apply-bulk-image');
            const productCheckboxes = bulkEditor.find('.wppob-product-check');
            const imageIdInput = $('#wppob-new-image-id');
            const spinner = applyBtn.next('.spinner');

            const checkBulkEditState = function () {
                const productsSelected = productCheckboxes.is(':checked');
                const imageSelected = imageIdInput.val() && imageIdInput.val() !== '0';
                applyBtn.prop('disabled', !(productsSelected && imageSelected));
            };

            productCheckboxes.on('change', checkBulkEditState);
            imageIdInput.on('change', checkBulkEditState);

            applyBtn.on('click', function (e) {
                e.preventDefault();
                const product_ids = productCheckboxes.filter(':checked').map(function () {
                    return $(this).val();
                }).get();
                const image_id = imageIdInput.val();

                if (product_ids.length === 0 || !image_id || image_id === '0') {
                    alert('Silakan pilih produk dan gambar terlebih dahulu.');
                    return;
                }

                applyBtn.prop('disabled', true);
                spinner.addClass('is-active');

                $.post(wppob_admin_params.ajax_url, {
                    action: 'wppob_bulk_update_images',
                    nonce: wppob_admin_params.nonce,
                    product_ids: product_ids,
                    image_id: image_id
                })
                .done(function (response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.reload();
                    } else {
                        alert('Gagal: ' + (response.data.message || 'Respons tidak diketahui.'));
                    }
                })
                .fail(function () {
                    alert('Terjadi kesalahan koneksi saat memperbarui gambar.');
                })
                .always(function () {
                    checkBulkEditState(); 
                    spinner.removeClass('is-active');
                });
            });
            
            checkBulkEditState();
        }
    });
    
    
    
    
    // --- FUNGSI MANAJEMEN PENGGUNA ---
        const userManager = $('#wppob-user-manager');
        if (userManager.length) {
            const searchInput = $('#wppob-user-search-input');
            const searchResults = $('#wppob-user-search-results');
            const userDetailsWrapper = $('#wppob-user-details-wrapper');
            const userPlaceholder = $('#wppob-user-placeholder');
            const messageArea = $('.wppob-message-area-user');
            let searchTimeout;

            // --- Fungsi untuk Menampilkan Pengguna di Daftar ---
            function populateUserList(users) {
                searchResults.html('');
                if (users.length) {
                    users.forEach(function (user) {
                        searchResults.append('<li data-userid="' + user.id + '">' + user.username + ' (' + user.email + ')</li>');
                    });
                } else {
                    searchResults.html('<li>Tidak ada pengguna yang ditemukan.</li>');
                }
            }

            // --- Memuat Pengguna Terbaru Saat Halaman Dibuka ---
            function loadRecentUsers() {
                searchResults.html('<li>Memuat pengguna...</li>');
                $.post(wppob_admin_params.ajax_url, {
                    action: 'wppob_get_recent_users',
                    nonce: wppob_admin_params.nonce
                }).done(function (response) {
                    if (response.success) {
                        populateUserList(response.data);
                    }
                });
            }
            loadRecentUsers(); // Panggil fungsi ini saat halaman dimuat

            // --- Fungsi Pencarian Pengguna ---
            searchInput.on('keyup', function () {
                const searchTerm = $(this).val();
                clearTimeout(searchTimeout);

                // Jika kotak pencarian kosong, muat ulang pengguna terbaru
                if (searchTerm.length === 0) {
                    loadRecentUsers();
                    return;
                }
                
                if (searchTerm.length < 3) {
                    return; // Jangan cari jika kurang dari 3 huruf
                }

                searchTimeout = setTimeout(function () {
                    searchResults.html('<li>Mencari...</li>');
                    $.post(wppob_admin_params.ajax_url, {
                        action: 'wppob_search_users',
                        nonce: wppob_admin_params.nonce,
                        search_term: searchTerm
                    }).done(function (response) {
                        if (response.success) {
                            populateUserList(response.data);
                        }
                    });
                }, 500); // Delay 500ms
            });

            // --- Tampilkan Detail Saat Pengguna Dipilih ---
            searchResults.on('click', 'li', function () {
                const userId = $(this).data('userid');
                if (!userId) return;

                userPlaceholder.hide();
                userDetailsWrapper.css('opacity', 0.5);
                
                $.post(wppob_admin_params.ajax_url, {
                    action: 'wppob_get_user_details',
                    nonce: wppob_admin_params.nonce,
                    user_id: userId
                }).done(function (response) {
                    if (response.success) {
                        $('#wppob-detail-username').text(response.data.username);
                        $('#wppob-detail-email').text(response.data.email);
                        $('#wppob-detail-balance').text(response.data.balance);
                        $('#wppob-adjust-user-id').val(response.data.id);
                        
                        const profileLink = 'user-edit.php?user_id=' + response.data.id;
                        $('#wppob-user-profile-link').attr('href', profileLink).show();

                        userDetailsWrapper.show().css('opacity', 1);
                    }
                });
            });

            // --- Simpan Perubahan Saldo ---
            $('#wppob-adjust-balance-submit').on('click', function(e) {
                e.preventDefault();
                const button = $(this);
                const spinner = button.next('.spinner');
                
                const data = {
                    action: 'wppob_adjust_user_balance',
                    nonce: wppob_admin_params.nonce,
                    user_id: $('#wppob-adjust-user-id').val(),
                    amount: $('#wppob-adjust-amount').val(),
                    note: $('#wppob-adjust-note').val()
                };

                button.prop('disabled', true);
                spinner.addClass('is-active');
                messageArea.html('');

                $.post(wppob_admin_params.ajax_url, data)
                .done(function(response) {
                    if(response.success) {
                        $('#wppob-detail-balance').text(response.data.new_balance);
                        messageArea.html('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                        $('#wppob-adjust-amount, #wppob-adjust-note').val('');
                    } else {
                        messageArea.html('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                    }
                })
                .always(function() {
                    button.prop('disabled', false);
                    spinner.removeClass('is-active');
                });
            });
        }
    
    
    
    
})(jQuery);
