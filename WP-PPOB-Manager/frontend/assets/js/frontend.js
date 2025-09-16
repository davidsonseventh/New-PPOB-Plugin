(function ($) {
    'use strict';
    $(document).ready(function () {

        // --- PEMILIHAN PRODUK ---
        $('.wppob-product-item').on('click', function () {
            const item = $(this);
            const productId = item.data('product-id');
            const productName = item.data('product-name');
            const productPrice = item.data('product-price');

            // Update UI
            $('.wppob-product-item').removeClass('selected');
            item.addClass('selected');

            // Update form
            $('#wppob-product-id').val(productId);

            // Update detail pembelian
            $('#wppob-detail-product').text(productName);
            $('#wppob-detail-price').text(productPrice);
            $('#wppob-purchase-details').slideDown();

            checkFormState();
        });

        // --- CEK STATUS FORM ---
        function checkFormState() {
            const productId = $('#wppob-product-id').val();
            const customerNo = $('#wppob-customer-no').val();
            const purchaseButton = $('#wppob-submit-purchase');

            if (productId && customerNo.length > 5) { // Anggap nomor valid minimal 5 digit
                purchaseButton.prop('disabled', false);
            } else {
                purchaseButton.prop('disabled', true);
            }
        }
        $('#wppob-customer-no').on('keyup', checkFormState);

        // --- SUBMIT PEMBELIAN ---
        $('#wppob-purchase-form').on('submit', function (e) {
            e.preventDefault();
            const form = $(this);
            const button = form.find('#wppob-submit-purchase');
            const notifArea = $('#wppob-notification-area');

            button.prop('disabled', true).text('Memproses...');
            notifArea.html('');

            $.post(wppob_frontend_params.ajax_url, form.serialize())
                .done(function (response) {
                    if (response.success) {
                        notifArea.html('<div class="wppob-notice wppob-notice-success">' + response.data.message + '</div>');
                        form.trigger('reset');
                        $('#wppob-purchase-details').slideUp();
                    } else {
                        notifArea.html('<div class="wppob-notice wppob-notice-error">' + response.data.message + '</div>');
                    }
                })
                .fail(function () {
                    notifArea.html('<div class="wppob-notice wppob-notice-error">Terjadi kesalahan. Silakan coba lagi.</div>');
                })
                .always(function () {
                    button.prop('disabled', false).text('Beli Sekarang');
                    checkFormState();
                });
        });

      // --- BAGIAN TOP UP SALDO (KODE LENGKAP) ---

        // Fungsi untuk memeriksa status form top up dan mengaktifkan tombol
        function checkTopupFormState() {
            const amount = $('#wppob-topup-amount').val();
            const method = $('#wppob-payment-method-input').val();
            // Tombol aktif jika jumlah minimal 10000 DAN metode sudah dipilih
            if (amount >= 10000 && method) {
                $('#wppob-submit-topup').prop('disabled', false);
            } else {
                $('#wppob-submit-topup').prop('disabled', true);
            }
        }
        
        // Event handler saat metode pembayaran diklik
        $('.wppob-payment-method').on('click', function() {
            $('.wppob-payment-method').removeClass('selected');
            $(this).addClass('selected');
            $('#wppob-payment-method-input').val($(this).data('code'));
            checkTopupFormState(); // Periksa status form setiap kali metode dipilih
        });
        
        // Event handler saat jumlah top up diisi
        $('#wppob-topup-amount').on('keyup', checkTopupFormState);

        // Event handler saat form top up disubmit
        $('#wppob-topup-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const button = form.find('#wppob-submit-topup');
            const notifArea = $('#wppob-topup-notification-area');
            
            button.prop('disabled', true).text('Memproses...');
            notifArea.html('');

            $.post(wppob_frontend_params.ajax_url, form.serialize())
                .done(function(response) {
                    if (response.success) {
                        notifArea.html('<div class="wppob-notice wppob-notice-success">' + response.data.message + '</div>');
                        // Alihkan ke halaman pembayaran WooCommerce
                        window.location.href = response.data.redirect_url;
                    } else {
                        notifArea.html('<div class="wppob-notice wppob-notice-error">' + response.data.message + '</div>');
                        button.prop('disabled', false).text('Lanjutkan Pembayaran');
                    }
                })
                .fail(function() {
                     notifArea.html('<div class="wppob-notice wppob-notice-error">Terjadi kesalahan. Silakan coba lagi.</div>');
                     button.prop('disabled', false).text('Lanjutkan Pembayaran');
                });
        });

    });
})(jQuery);