jQuery(document).ready(function($) {
    // Fungsi copy-to-clipboard
    $('body').on('click', '.wplm-copy-btn', function(e) {
        e.preventDefault();
        var target = $(this).data('clipboard-target');
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($(target).text()).select();
        document.execCommand("copy");
        $temp.remove();
        
        var originalText = $(this).text();
        $(this).text('Disalin!');
        setTimeout(() => {
            $(this).text(originalText);
        }, 1500);
    });
});
