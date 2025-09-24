jQuery(document).ready(function ($) {
    const wrapper = $('#wppob-login-wrapper');
    if (wrapper.length === 0) return;

    // --- TEMPLATES ---
    const otpTemplate = `
        <div id="wppob-step-otp">
            <div class="wppob-form-header">
                <button type="button" class="back-button">&larr;</button>
                <h2>Verifikasi No Hp</h2>
                <p>Kami mengirimkan Kode Verifikasi ke WhatsApp Anda</p>
            </div>
            <form id="wppob-otp-form">
                <div class="wppob-form-group otp-inputs">
                    ${[...Array(6)].map(() => `<input type="tel" class="otp-input" maxlength="1" required>`).join('')}
                </div>
                <input type="hidden" id="otp_user_id">
                <div id="wppob-otp-message-area" class="wppob-message"></div>
                <button type="submit" class="wppob-button">Konfirmasi</button>
            </form>
        </div>`;

    const pinTemplate = `
        <div id="wppob-step-pin">
            <div class="wppob-form-header">
                <h2>Buat PIN Keamanan</h2>
                <p>PIN ini akan digunakan untuk setiap transaksi Anda.</p>
            </div>
            <form id="wppob-pin-form">
                <input type="hidden" id="pin_user_id">
                <div class="wppob-form-group">
                    <label for="pin">Buat PIN Keamanan (6 Digit)</label>
                    <input type="password" id="pin" maxlength="6" inputmode="numeric" required>
                </div>
                <div class="wppob-form-group">
                    <label for="confirm_pin">Konfirmasi PIN Keamanan</label>
                    <input type="password" id="confirm_pin" maxlength="6" inputmode="numeric" required>
                </div>
                <div id="wppob-pin-message-area" class="wppob-message"></div>
                <button type="submit" class="wppob-button">Daftar & Login</button>
            </form>
        </div>`;
    
    // ## TEMPLATE BARU ##: Untuk login dengan PIN
    const pinLoginTemplate = `
        <div id="wppob-step-pin-login">
            <div class="wppob-form-header">
                <button type="button" class="back-button">&larr;</button>
                <h2>Masukkan PIN Anda</h2>
                <p>Untuk keamanan, masukkan 6 digit PIN Anda untuk login.</p>
            </div>
            <form id="wppob-pin-login-form">
                <input type="hidden" id="pin_login_user_id">
                <div class="wppob-form-group">
                    <label for="pin_login">PIN Keamanan</label>
                    <input type="password" id="pin_login" maxlength="6" inputmode="numeric" required autocomplete="off">
                </div>
                <div id="wppob-pin-login-message-area" class="wppob-message"></div>
                <button type="submit" class="wppob-button">Login</button>
            </form>
        </div>`;


    // --- HELPER FUNCTIONS ---
    function showMessage(area, text, isError = true) {
        area.text(text).removeClass('success error').addClass(isError ? 'error' : 'success').show().delay(5000).fadeOut();
    }

    // --- EVENT HANDLERS ---
    wrapper.on('submit', '#wppob-phone-form', function (e) {
        e.preventDefault();
        const btn = $(this).find('button');
        const phone = $('#phone').val().replace(/[^0-9]/g, '');
        showMessage($('#wppob-message-area').hide());
        btn.prop('disabled', true).text('Memeriksa...');
        $.post(wppob_login_params.ajax_url, {
            action: 'wppob_check_phone',
            nonce: wppob_login_params.nonce,
            phone: phone
        }).done(res => {
            if (res.success) {
                if (res.data.action === 'register') {
                    $('#wppob-step-phone').hide();
                    $('#phone_register').val(phone);
                    $('#wppob-step-register').show();
                } else {
                    wrapper.html(otpTemplate);
                    $('#otp_user_id').val(res.data.user_id);
                }
            } else {
                showMessage($('#wppob-message-area'), res.data.message);
            }
        }).fail(() => showMessage($('#wppob-message-area'), 'Koneksi error.'))
          .always(() => btn.prop('disabled', false).text('Lanjut'));
    });

    wrapper.on('submit', '#wppob-register-form', function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        showMessage($('#wppob-register-message-area').hide());
        btn.prop('disabled', true).text('Mendaftar...');
        $.post(wppob_login_params.ajax_url, {
            action: 'wppob_register_user',
            nonce: wppob_login_params.nonce,
            full_name: $('#full_name').val(),
            phone: $('#phone_register').val(),
            email: $('#email').val()
        }).done(res => {
            if (res.success) {
                wrapper.html(otpTemplate);
                $('#otp_user_id').val(res.data.user_id);
            } else {
                showMessage($('#wppob-register-message-area'), res.data.message);
            }
        }).fail(() => showMessage($('#wppob-register-message-area'), 'Koneksi error.'))
          .always(() => btn.prop('disabled', false).text('Lanjutkan'));
    });
    
    // ## LOGIKA DIPERBAIKI ##: Mengarahkan ke form yang benar (buat PIN / login PIN)
    wrapper.on('submit', '#wppob-otp-form', function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        const otp = $('.otp-input').map((_, el) => $(el).val()).get().join('');
        const userId = $('#otp_user_id').val();
        showMessage($('#wppob-otp-message-area').hide());
        btn.prop('disabled', true).text('Memverifikasi...');
        $.post(wppob_login_params.ajax_url, {
            action: 'wppob_verify_otp',
            nonce: wppob_login_params.nonce,
            user_id: userId,
            otp: otp
        }).done(res => {
            if (res.success) {
                if (res.data.has_pin) {
                    // Pengguna lama: tampilkan form login dengan PIN
                    wrapper.html(pinLoginTemplate);
                    $('#pin_login_user_id').val(userId);
                } else {
                    // Pengguna baru: tampilkan form pembuatan PIN
                    wrapper.html(pinTemplate);
                    $('#pin_user_id').val(userId);
                }
            } else {
                showMessage($('#wppob-otp-message-area'), res.data.message);
            }
        }).fail(() => showMessage($('#wppob-otp-message-area'), 'Koneksi error.'))
          .always(() => btn.prop('disabled', false).text('Konfirmasi'));
    });

    wrapper.on('submit', '#wppob-pin-form', function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        const pin = $('#pin').val();
        if (pin !== $('#confirm_pin').val()) {
            showMessage($('#wppob-pin-message-area'), 'Konfirmasi PIN tidak cocok.');
            return;
        }
        showMessage($('#wppob-pin-message-area').hide());
        btn.prop('disabled', true).text('Menyimpan...');
        $.post(wppob_login_params.ajax_url, {
            action: 'wppob_set_pin',
            nonce: wppob_login_params.nonce,
            user_id: $('#pin_user_id').val(),
            pin: pin
        }).done(res => {
            if (res.success) {
                window.location.reload();
            } else {
                 showMessage($('#wppob-pin-message-area'), res.data.message);
            }
        }).fail(() => showMessage($('#wppob-pin-message-area'), 'Koneksi error.'))
          .always(() => btn.prop('disabled', false).text('Daftar & Login'));
    });

    // ## HANDLER BARU ##: Untuk form login dengan PIN
    wrapper.on('submit', '#wppob-pin-login-form', function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        const pin = $('#pin_login').val();
        const userId = $('#pin_login_user_id').val();
        showMessage($('#wppob-pin-login-message-area').hide());
        btn.prop('disabled', true).text('Memeriksa...');
        $.post(wppob_login_params.ajax_url, {
            action: 'wppob_verify_pin_and_login',
            nonce: wppob_login_params.nonce,
            user_id: userId,
            pin: pin
        }).done(res => {
            if (res.success) {
                window.location.reload();
            } else {
                showMessage($('#wppob-pin-login-message-area'), res.data.message);
            }
        }).fail(() => showMessage($('#wppob-pin-login-message-area'), 'Koneksi error.'))
          .always(() => btn.prop('disabled', false).text('Login'));
    });


    // Helper functions
    wrapper.on('keyup', '.otp-input', function(e) {
        if (this.value.length === this.maxLength) $(this).next('.otp-input').focus();
    });
    wrapper.on('click', '.back-button', () => window.location.reload());
});