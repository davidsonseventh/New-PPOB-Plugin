(function ($) {
    'use strict';
    $(document).ready(function () {


// --- BAGIAN FORM PEMBELIAN PRODUK (VERSI DITINGKATKAN) ---

        const purchaseFormWrapper = $('.wppob-frontend-wrap[data-category-name]');

        // --- 1. LOGIKA INJECT VOUCHER BERDASARKAN KATEGORI ---
        if (purchaseFormWrapper.length) {
            const categoryName = purchaseFormWrapper.data('category-name');
            const barcodeWrapper = $('#wppob-barcode-wrapper');
            const barcodeInput = $('#wppob-barcode-no');
            
            // Cek apakah nama kategori mengandung "inject" atau "voucher fisik"
            if (categoryName.includes('inject') || categoryName.includes('voucher fisik')) {
                barcodeWrapper.show();
                barcodeInput.prop('required', true);
            }
        }

        // --- 2. LOGIKA DETEKSI OTOMATIS PROVIDER ---
        const providerPrefixes = {
            'telkomsel': ['0811', '0812', '0813', '0821', '0822', '0823', '0851', '0852', '0853'],
            'indosat': ['0814', '0815', '0816', '0855', '0856', '0857', '0858'],
            'xl': ['0817', '0818', '0819', '0859', '0877', '0878'],
            'axis': ['0831', '0832', '0833', '0838'],
            'tri': ['0895', '0896', '0897', '0898', '0899'],
            'smartfren': ['0881', '0882', '0883', '0884', '0885', '0886', '0887', '0888', '0889']
        };

        $('#wppob-customer-no').on('keyup', function () {
            const number = $(this).val();
            let detectedProvider = null;

            if (number.length >= 4) {
                const prefix = number.substring(0, 4);
                for (const provider in providerPrefixes) {
                    if (providerPrefixes[provider].includes(prefix)) {
                        detectedProvider = provider;
                        break;
                    }
                }
            }



        // --- PEMILIHAN PRODUK ---
     $('.wppob-product-item').on('click', function () {
            // ... (Kode on-click tetap sama seperti sebelumnya)
            const item = $(this);
            const productId = item.data('product-id');
            const productName = item.data('product-name');
            const productPrice = item.data('product-price');

            $('.wppob-product-item').removeClass('selected');
            item.addClass('selected');
            $('#wppob-product-id').val(productId);
            $('#wppob-detail-product').text(productName);
            $('#wppob-detail-price').text(productPrice);
            $('#wppob-purchase-details').slideDown();
            checkPurchaseFormState();
        });
        
        
        function checkPurchaseFormState() {
            // ... (Kode checkPurchaseFormState tetap sama)
            const productId = $('#wppob-product-id').val();
            const customerNo = $('#wppob-customer-no').val();
            if (productId && customerNo.length > 5) {
                $('#wppob-submit-purchase').prop('disabled', false);
            } else {
                $('#wppob-submit-purchase').prop('disabled', true);
            }
        }

        $('#wppob-purchase-form').on('submit', function (e) {
            // ... (Kode submit form tetap sama)
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
                    checkPurchaseFormState();
                });
        });
        

            // --- Logika Baru untuk Menampilkan Input Barcode ---
            // Cek apakah nama produk mengandung kata "inject" atau "voucher fisik"
            if (productName.toLowerCase().includes('inject') || productName.toLowerCase().includes('voucher fisik')) {
                barcodeWrapper.slideDown();
                barcodeInput.prop('required', true);
            } else {
                barcodeWrapper.slideUp();
                barcodeInput.prop('required', false);
            }
            // --- Akhir Logika Baru ---

            checkPurchaseFormState();
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
    
    
    // --- KODE UNTUK FORM TRANSFER BANK ---
$('#wppob-transfer-bank-form').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    const button = form.find('#wppob-submit-bank-transfer');
    const notifArea = $('#wppob-transfer-bank-notification-area');
    button.prop('disabled', true).text('Memproses...');
    notifArea.html('');
    $.post(wppob_frontend_params.ajax_url, form.serialize())
        .done(function(response) {
            if (response.success) {
                notifArea.html('<div class="wppob-notice wppob-notice-success">' + response.data.message + '</div>');
                form.trigger('reset');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                notifArea.html('<div class="wppob-notice wppob-notice-error">' + response.data.message + '</div>');
                button.prop('disabled', false).text('Lanjutkan Transfer Bank');
            }
        });
});

// --- KODE UNTUK FORM TRANSFER PAYPAL ---
$('#wppob-transfer-paypal-form').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    const button = form.find('#wppob-submit-paypal-transfer');
    const notifArea = $('#wppob-transfer-paypal-notification-area');
    button.prop('disabled', true).text('Memproses...');
    notifArea.html('');
    $.post(wppob_frontend_params.ajax_url, form.serialize())
        .done(function(response) {
            if (response.success) {
                notifArea.html('<div class="wppob-notice wppob-notice-success">' + response.data.message + '</div>');
                form.trigger('reset');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                notifArea.html('<div class="wppob-notice wppob-notice-error">' + response.data.message + '</div>');
                button.prop('disabled', false).text('Kirim ke PayPal');
            }
        });
});


// --- KODE UNTUK FORM TRANSFER ---
$('#wppob-transfer-form').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    const button = form.find('#wppob-submit-transfer');
    const notifArea = $('#wppob-transfer-notification-area');

    button.prop('disabled', true).text('Memproses...');
    notifArea.html('');

    $.post(wppob_frontend_params.ajax_url, form.serialize())
        .done(function(response) {
            if (response.success) {
                notifArea.html('<div class="wppob-notice wppob-notice-success">' + response.data.message + '</div>');
                form.trigger('reset');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                notifArea.html('<div class="wppob-notice wppob-notice-error">' + response.data.message + '</div>');
                button.prop('disabled', false).text('Kirim Saldo');
            }
        })
        .fail(function() {
             notifArea.html('<div class="wppob-notice wppob-notice-error">Terjadi kesalahan. Silakan coba lagi.</div>');
             button.prop('disabled', false).text('Kirim Saldo');
        });
});



// --- KODE UNTUK FORM TARIK KOMISI ---
$('#wppob-withdraw-form').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    const button = form.find('#wppob-submit-withdrawal');
    const notifArea = $('#wppob-withdraw-notification-area');

    button.prop('disabled', true).text('Memproses Penarikan...');
    notifArea.html('');

    $.post(wppob_frontend_params.ajax_url, form.serialize())
        .done(function(response) {
            if (response.success) {
                notifArea.html('<div class="wppob-notice wppob-notice-success">' + response.data.message + '</div>');
                form.trigger('reset');
                setTimeout(() => window.location.reload(), 2500); // Reload untuk update saldo
            } else {
                notifArea.html('<div class="wppob-notice wppob-notice-error">' + response.data.message + '</div>');
                button.prop('disabled', false).text('Tarik Dana Sekarang');
            }
        })
        .fail(function() {
             notifArea.html('<div class="wppob-notice wppob-notice-error">Terjadi kesalahan koneksi. Silakan coba lagi.</div>');
             button.prop('disabled', false).text('Tarik Dana Sekarang');
        });
});
    
    
})(jQuery);