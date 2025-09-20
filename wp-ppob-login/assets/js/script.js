jQuery(document).ready(function($) {
    // Handle login/register form submission
    $('#wppob-login-form').on('submit', function(e) {
        e.preventDefault();
        var phone = $('#phone').val();
        // Here you would add logic to check if the user exists.
        // For simplicity, we'll assume a new user and show the register form.
        $('#wppob-login-form-container').hide();
        $('#phone_register').val(phone);
        $('#wppob-register-form-container').show();
    });

    // Handle registration form submission
    $('#wppob-register-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post(wppob_login_ajax.ajax_url, {
            action: 'wppob_login_register',
            data: formData
        }, function(response) {
            if (response.success) {
                $('#wppob-register-form-container').hide();
                $('#user_id').val(response.data.user_id);
                $('#wppob-otp-form-container').show();
            } else {
                alert(response.data.message);
            }
        });
    });

    // Handle OTP form submission
    $('#wppob-otp-form').on('submit', function(e) {
        e.preventDefault();
        var otp = $('input[name="otp[]"]').map(function() {
            return $(this).val();
        }).get().join('');
        var userId = $('#user_id').val();
        $.post(wppob_login_ajax.ajax_url, {
            action: 'wppob_verify_otp',
            user_id: userId,
            otp: otp
        }, function(response) {
            if (response.success) {
                $('#wppob-otp-form-container').hide();
                $('#wppob-pin-form-container').show();
            } else {
                alert(response.data.message);
            }
        });
    });

    // Handle PIN form submission
    $('#wppob-pin-form').on('submit', function(e) {
        e.preventDefault();
        var pin = $('#pin').val();
        var confirmPin = $('#confirm_pin').val();
        if (pin !== confirmPin) {
            alert('PIN tidak cocok.');
            return;
        }
        $.post(wppob_login_ajax.ajax_url, {
            action: 'wppob_set_pin',
            pin: pin
        }, function(response) {
            if (response.success) {
                window.location.href = '/'; // Redirect to homepage
            } else {
                alert(response.data.message);
            }
        });
    });
});
