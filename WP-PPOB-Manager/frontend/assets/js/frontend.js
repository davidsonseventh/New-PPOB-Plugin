(function ($) {
    'use strict';
    $(document).ready(function () {



// --- LOGIKA MODAL PIN TRANSAKSI ---
let transactionData = null; // Untuk menyimpan data form sementara

// Saat tombol 'Beli Sekarang' diklik, jangan langsung submit, tapi buka modal
$('#wppob-purchase-form').on('submit', function (e) {
    e.preventDefault(); // Mencegah form submit
    transactionData = $(this).serialize(); // Simpan data form
    $('#wppob-pin-error').hide();
    $('#wppob-pin-input').val('');
    $('#wppob-pin-modal').fadeIn(200);
    $('#wppob-pin-input').focus();
});

// Saat tombol Batal di modal diklik
$('#wppob-pin-cancel').on('click', function() {
    $('#wppob-pin-modal').fadeOut(200);
});

// Saat tombol Konfirmasi di modal diklik
// GANTI FUNGSI LAMA DENGAN YANG INI

// Saat tombol Konfirmasi di modal diklik
$('#wppob-pin-confirm').on('click', function() {
   
    const pin = $('#wppob-pin-input').val();
    if (pin.length !== 6) {
        $('#wppob-pin-error').text('PIN harus 6 digit.').show();
        return;
    }
    
    
   

    // Tambahkan PIN ke data form yang sudah disimpan
    const fullTransactionData = transactionData + '&transaction_pin=' + pin + '&action=wppob_submit_purchase&nonce=' + wppob_frontend_params.nonce;

    // Sembunyikan modal dan siapkan notifikasi
    $('#wppob-pin-modal').fadeOut(200);
    const button = $('#wppob-submit-purchase');
    const notifArea = $('#wppob-notification-area');

    button.prop('disabled', true).text('Memproses...');
    notifArea.html('<div id="wppob-realtime-status"></div>'); // Siapkan kontainer status

    // Kirim permintaan AJAX
    $.post(wppob_frontend_params.ajax_url, fullTransactionData)
        .done(function (response) {
            if (response.success) {
                // Jika sukses, mulai polling status
                startPolling(response.data.transaction_id);
            } else {
                // --- INI BAGIAN UTAMA PERBAIKAN ---
                let errorMessage = response.data.message;

                // Cek apakah server mengirim flag untuk membuat PIN
                if (response.data.require_pin_setup) {
                    // Dapatkan URL halaman saat ini dan tambahkan parameter tab=security
                    const securityPageUrl = window.location.pathname.split('?')[0] + 'dashboard-saya/?tab=security';
                    // Buat pesan error dengan link ke halaman pembuatan PIN
                    errorMessage = '<strong>' + response.data.message + '</strong><br><a href="' + securityPageUrl + '" style="margin-top:10px; display:inline-block;" class="button">Buat PIN Keamanan Anda Sekarang</a>';

                    // Kosongkan detail pembelian agar tidak membingungkan
                    $('#wppob-purchase-details').slideUp();
                    $('.wppob-product-item').removeClass('selected');
                    $('#wppob-product-id').val('');
                }

                // Tampilkan pesan error (baik yang biasa maupun yang sudah dimodifikasi)
                notifArea.html('<div class="wppob-notice wppob-notice-error">' + errorMessage + '</div>');
                button.prop('disabled', false).text('Beli Sekarang');
            }
        })
        .fail(function () {
             
            notifArea.html('<div class="wppob-notice wppob-notice-error">Terjadi kesalahan koneksi. Silakan coba lagi.</div>');
            button.prop('disabled', false).text('Beli Sekarang');
        });
});




let statusInterval; // Variabel untuk menyimpan interval polling

// Fungsi untuk memulai polling status transaksi
function startPolling(transactionId) {
    const notifArea = $('#wppob-notification-area');
    const statusWrapper = $('#wppob-realtime-status');

    statusWrapper.html('<div class="wppob-status-polling"><span class="spinner is-active"></span> Menunggu Konfirmasi...</div>');

    statusInterval = setInterval(function () {
        $.post(wppob_frontend_params.ajax_url, {
            action: 'wppob_check_transaction_status',
            nonce: wppob_frontend_params.nonce,
            transaction_id: transactionId
        })
        .done(function (response) {
            if (response.success) {
                const status = response.data.status;
                const details = response.data.details || {};

                if (status === 'success' || status === 'failed') {
                    clearInterval(statusInterval); // Hentikan polling

                    let finalHtml = '';
                    if (status === 'success') {
                        // Cek apakah 'tab' sudah ada di URL
                        const currentUrl = new URL(window.location.href);
                        const receiptUrl = currentUrl.pathname + '?tab=dashboard&view_transaction=' + transactionId;
                        finalHtml = '<div class="wppob-status-final-success">' +
                            '<strong>Transaksi Sukses!</strong>' +
                            '<p>SN/Token: ' + (details.sn || 'Tidak ada') + '</p>' +
                            '<a href="' + receiptUrl + '" class="button">Lihat Struk</a>' +
                        '</div>';
                    } else {
                        finalHtml = '<div class="wppob-status-final-error">' +
                            '<strong>Transaksi Gagal</strong>' +
                            '<p>Pesan: ' + (details.message || 'Dana telah dikembalikan.') + '</p>' +
                        '</div>';
                    }
                    statusWrapper.html(finalHtml);
                    $('#wppob-purchase-form').trigger('reset');
                    $('#wppob-purchase-details').slideUp();
                    $('.wppob-product-item').removeClass('selected').show();
                    $('#wppob-submit-purchase').prop('disabled', true).text('Beli Sekarang');
                }
            } else {
                clearInterval(statusInterval);
                statusWrapper.html('<div class="wppob-notice wppob-notice-error">Gagal memeriksa status. Silakan cek di Riwayat Transaksi.</div>');
            }
        });
    }, 5000); // Cek status setiap 5 detik
}


        // --- BAGIAN FORM PEMBELIAN PRODUK ---

       // --- LOGIKA BARU UNTUK FORM PRABAYAR & PASCABAYAR OTOMATIS ---
const purchaseFormWrapper = $('.wppob-frontend-wrap');
if (purchaseFormWrapper.length > 0) {
    const purchaseForm = $('#wppob-purchase-form');
    const isPostpaid = purchaseFormWrapper.data('is-postpaid') === true;
    const customerNoInput = $('#wppob-customer-no');
    const productIdInput = $('#wppob-product-id');
    const submitButton = $('#wppob-submit-purchase');
    const notifArea = $('#wppob-notification-area');
    const purchaseDetails = $('#wppob-purchase-details');
    const spinner = $('#wppob-inquiry-spinner');
    let transactionData = null;
    let debounceTimer;
    

    function checkFormState() {
        const customerNo = customerNoInput.val();
        const productId = productIdInput.val();
        const isReady = customerNo.length > 4 && productId;
        if (submitButton.data('mode') !== 'pay') {
            submitButton.prop('disabled', !isReady);
        }
    }

    function performBillInquiry() {
        const productId = productIdInput.val();
        const customerNo = customerNoInput.val();

        spinner.addClass('is-active');
        submitButton.prop('disabled', true).text('Mengecek...');
        notifArea.html('');

        $.post(wppob_frontend_params.ajax_url, {
            action: 'wppob_check_postpaid_bill',
            nonce: wppob_frontend_params.nonce,
            product_id: productId,
            customer_no: customerNo
        })
        .done(function(response) {
            if (response.success) {
                const detailsHtml = `
                    <h4>Detail Tagihan</h4>
                    <p><strong>Nama:</strong> ${response.data.customer_name}</p>
                    <p><strong>Periode:</strong> ${response.data.period}</p>
                    <p><strong>Detail:</strong> ${response.data.description}</p>
                    <p><strong>Tagihan:</strong> ${response.data.bill_amount}</p>
                    <p><strong>Biaya Admin:</strong> ${response.data.admin_fee}</p>
                    <hr>
                    <p><strong>Total Bayar:</strong> <strong class="wppob-total-price">${response.data.total_payment}</strong></p>
                `;
                purchaseDetails.html(detailsHtml).slideDown();

                submitButton.text('Bayar Sekarang').prop('disabled', false).data('mode', 'pay');
                purchaseForm.find('[name="is_postpaid"], [name="ref_id_pasca"], [name="total_payment_raw"]').remove();
                purchaseForm.append(`<input type="hidden" name="is_postpaid" value="true">`);
                purchaseForm.append(`<input type="hidden" name="ref_id_pasca" value="${response.data.ref_id}">`);
                purchaseForm.append(`<input type="hidden" name="total_payment_raw" value="${response.data.total_payment_raw}">`);
            } else {
                notifArea.html('<div class="wppob-notice wppob-notice-error">' + response.data.message + '</div>');
                resetPostpaidForm();
            }
        })
        .fail(function() {
            notifArea.html('<div class="wppob-notice wppob-notice-error">Koneksi error saat cek tagihan.</div>');
            resetPostpaidForm();
        })
        .always(function() {
            spinner.removeClass('is-active');
        });
    }

    function resetPostpaidForm() {
        purchaseDetails.slideUp().html('');
        submitButton.text('Cek Tagihan').data('mode', 'inquiry');
        purchaseForm.find('[name="is_postpaid"], [name="ref_id_pasca"], [name="total_payment_raw"]').remove();
        checkFormState();
    }

    $('.wppob-product-item').on('click', function () {
        $('.wppob-product-item').removeClass('selected');
        $(this).addClass('selected');
        productIdInput.val($(this).data('product-id'));

        if (isPostpaid) {
            resetPostpaidForm();
            if (customerNoInput.val().length > 4) {
                performBillInquiry();
            }
        } else {
            const productName = $(this).data('product-name');
            const productPrice = $(this).data('product-price');
            purchaseDetails.html(`<p><strong>Produk:</strong> ${productName}</p><p><strong>Harga:</strong> ${productPrice}</p>`).slideDown();
        }
        checkFormState();
    });

    customerNoInput.on('keyup input', function () {
        notifArea.html('');
        if (isPostpaid) {
            clearTimeout(debounceTimer);
            resetPostpaidForm();
            if ($(this).val().length > 4 && productIdInput.val()) {
                debounceTimer = setTimeout(performBillInquiry, 800);
            }
        } else {
             // Logika auto-detect provider untuk prabayar
            const number = $(this).val();
            let detectedProvider = null;
            if (number.length >= 4) {
                const prefix = number.substring(0, 4);
                const providerPrefixes = {
                    'telkomsel': ['0811', '0812', '0813', '0821', '0822', '0823', '0851', '0852', '0853'],
                    'indosat': ['0814', '0815', '0816', '0855', '0856', '0857', '0858'],
                    'xl': ['0817', '0818', '0819', '0859', '0877', '0878'],
                    'axis': ['0831', '0832', '0833', '0838'],
                    'tri': ['0895', '0896', '0897', '0898', '0899'],
                    'smartfren': ['0881', '0882', '0883', '0884', '0885', '0886', '0887', '0888', '0889']
                };
                for (const provider in providerPrefixes) {
                    if (providerPrefixes[provider].includes(prefix)) {
                        detectedProvider = provider;
                        break;
                    }
                }
            }
            $('.wppob-product-item').each(function () {
                const productBrand = $(this).data('brand') || '';
                if (detectedProvider === null) {
                    $(this).show();
                } else {
                    if (productBrand.toLowerCase().includes(detectedProvider)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                }
            });
        }
        checkFormState();
    });

    purchaseForm.on('submit', function (e) {
        e.preventDefault();
        if (isPostpaid && submitButton.data('mode') !== 'pay') {
            performBillInquiry();
            return;
        }
        transactionData = $(this).serialize() + '&action=wppob_submit_purchase&nonce=' + wppob_frontend_params.nonce;
        $('#wppob-pin-error').hide().text('');
        $('#wppob-pin-input').val('');
        $('#wppob-pin-modal').fadeIn(200);
        $('#wppob-pin-input').focus();
    });

    
    
    
    
    // Letakkan disini kode yang dihapus jika nanti terdapat bug
}
        
        
        
            // --- 1. LOGIKA INJECT VOUCHER BERDASARKAN KATEGORI ---
        if (purchaseFormWrapper.length) {
            const categoryName = purchaseFormWrapper.data('category-name');
            const barcodeWrapper = $('#wppob-barcode-wrapper');
            const barcodeInput = $('#wppob-barcode-no');
            const customerNoWrapper = $('#wppob-customer-no').closest('.wppob-form-group'); // Dapatkan wrapper-nya

            // Cek apakah nama kategori mengandung "inject" atau "voucher fisik"
            if (categoryName.includes('inject') || categoryName.includes('voucher fisik')) {
                // Tampilkan input barcode dan sembunyikan input nomor tujuan
                barcodeWrapper.show();
                barcodeInput.prop('required', true);
                customerNoWrapper.hide();
                $('#wppob-customer-no').prop('required', false); // Jadikan tidak wajib diisi
            }
        }
        
        
        

        // --- BAGIAN TOP UP SALDO ---

        function checkTopupFormState() {
            const amount = $('#wppob-topup-amount').val();
            const method = $('#wppob-payment-method-input').val();
            $('#wppob-submit-topup').prop('disabled', !(amount >= 10000 && method));
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



        // --- FUNGSI LAIN (TRANSFER, WITHDRAW, DLL) ---
        // Catatan: Fungsi backend (PHP) untuk ini belum diimplementasikan.
        
        $('#wppob-transfer-bank-form, #wppob-transfer-paypal-form, #wppob-transfer-form, #wppob-withdraw-form').on('submit', function(e) {
            e.preventDefault();
            alert('Fitur ini belum diaktifkan di sisi server.');
        });

    });
    
    // --- LOGIKA FORM BUAT & UBAH PIN ---
$('#wppob-create-pin-form, #wppob-change-pin-form').on('submit', function(e){
    e.preventDefault();
    const form = $(this);
    const button = form.find('button[type="submit"]');
    const notifArea = $('#wppob-pin-notification-area');

    button.prop('disabled', true);
    notifArea.html('');

    $.post(wppob_frontend_params.ajax_url, form.serialize())
        .done(function(response) {
            if (response.success) {
                notifArea.html('<div class="wppob-notice wppob-notice-success">' + response.data.message + '</div>');
                setTimeout(function(){ location.reload(); }, 2000);
            } else {
                notifArea.html('<div class="wppob-notice wppob-notice-error">' + response.data.message + '</div>');
                button.prop('disabled', false);
            }
        });
});



// --- ## KODE BARU UNTUK CEK TAGIHAN PASCABAYAR ## ---

// Cek apakah produk yang ditampilkan adalah pascabayar
const isPostpaid = $('.wppob-frontend-wrap[data-category-name*="pascabayar"], .wppob-frontend-wrap[data-category-name*="tagihan"]').length > 0;

if (isPostpaid) {
    const purchaseForm = $('#wppob-purchase-form');
    const submitButton = $('#wppob-submit-purchase');
    const notifArea = $('#wppob-notification-area');
    const purchaseDetails = $('#wppob-purchase-details');

    // 1. Ubah tombol "Beli Sekarang" menjadi "Cek Tagihan"
    submitButton.text('Cek Tagihan');

    // Sembunyikan detail pembelian awal
    purchaseDetails.hide();

    // 2. Ganti logika submit form
    purchaseForm.off('submit').on('submit', function(e) {
        e.preventDefault();

        // Cek apakah kita sedang dalam mode "Bayar" atau "Cek Tagihan"
        if (submitButton.data('mode') === 'pay') {
            // Jika mode bayar, buka modal PIN seperti biasa
            transactionData = $(this).serialize();
            $('#wppob-pin-modal').fadeIn(200);
            $('#wppob-pin-input').focus();
        } else {
            // Jika mode cek tagihan, jalankan AJAX untuk cek tagihan
            const productId = $('#wppob-product-id').val();
            const customerNo = $('#wppob-customer-no').val();

            if (!productId || !customerNo) {
                alert('Silakan pilih produk dan masukkan nomor pelanggan.');
                return;
            }

            submitButton.prop('disabled', true).text('Mengecek...');
            notifArea.html('');

            $.post(wppob_frontend_params.ajax_url, {
                action: 'wppob_check_postpaid_bill',
                nonce: wppob_frontend_params.nonce,
                product_id: productId,
                customer_no: customerNo
            })
            .done(function(response) {
                if (response.success) {
                    // Tampilkan detail tagihan
                    const detailsHtml = `
                        <h4>Detail Tagihan</h4>
                        <p><strong>Nama Pelanggan:</strong> ${response.data.customer_name}</p>
                        <p><strong>Jumlah Tagihan:</strong> ${response.data.bill_amount}</p>
                        <p><strong>Biaya Admin:</strong> ${response.data.admin_fee}</p>
                        <p><strong>Total Pembayaran:</strong> <strong>${response.data.total_payment}</strong></p>
                    `;
                    purchaseDetails.html(detailsHtml).slideDown();

                    // Ubah tombol menjadi "Bayar Sekarang"
                    submitButton.text('Bayar Sekarang').data('mode', 'pay');

                    // Tambahkan ref_id ke form untuk proses pembayaran
                    purchaseForm.append(`<input type="hidden" name="ref_id_pasca" value="${response.data.ref_id}">`);

                } else {
                    notifArea.html('<div class="wppob-notice wppob-notice-error">' + response.data.message + '</div>');
                }
            })
            .fail(function() {
                notifArea.html('<div class="wppob-notice wppob-notice-error">Koneksi error. Silakan coba lagi.</div>');
            })
            .always(function() {
                 submitButton.prop('disabled', false);
                 if (submitButton.data('mode') !== 'pay') {
                    submitButton.text('Cek Tagihan');
                 }
            });
        }
    });
}

// --- ## KODE TAMBAHAN UNTUK MEMPERBAIKI TOP UP SALDO ## ---

// Fungsi untuk memeriksa status form top up
function checkTopupFormState() {
    const amount = $('#wppob-topup-amount').val();
    const method = $('#wppob-payment-method-input').val();
    // Tombol akan aktif jika jumlah minimal 10000 dan metode pembayaran sudah dipilih
    $('#wppob-submit-topup').prop('disabled', !(amount >= 10000 && method));
}

// Event handler saat metode pembayaran diklik
$('.wppob-payment-method').on('click', function() {
    $('.wppob-payment-method').removeClass('selected');
    $(this).addClass('selected');
    $('#wppob-payment-method-input').val($(this).data('code'));
    checkTopupFormState(); // Periksa status form setiap kali metode dipilih
});

// Event handler saat jumlah top up diisi
$('#wppob-topup-amount').on('keyup input', checkTopupFormState);

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
                // Mengalihkan ke halaman pembayaran
                window.location.href = response.data.redirect_url;
            } else {
                notifArea.html('<div class="wppob-notice wppob-notice-error">' + response.data.message + '</div>');
                button.prop('disabled', false).text('Lanjutkan Pembayaran');
            }
        })
        .fail(function() {
             notifArea.html('<div class="wppob-notice wppob-notice-error">Terjadi kesalahan koneksi. Silakan coba lagi.</div>');
             button.prop('disabled', false).text('Lanjutkan Pembayaran');
        });
});
    
})(jQuery);