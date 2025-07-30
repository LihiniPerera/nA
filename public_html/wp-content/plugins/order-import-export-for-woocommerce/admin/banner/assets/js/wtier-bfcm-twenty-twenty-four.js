(function ($) {
    'use strict';

    $(function () {
        var wtier_bfcm_twenty_twenty_four_banner = {
            init: function () { 
                var data_obj = {
                    _wpnonce: wtier_bfcm_twenty_twenty_four_banner_js_params.nonce,
                    action: wtier_bfcm_twenty_twenty_four_banner_js_params.action,
                    wtier_bfcm_twenty_twenty_four_banner_action_type: '',
                };

                $(document).on('click', '.wtier-bfcm-banner-2024 .bfcm_cta_button', function (e) { 
                    e.preventDefault(); 
                    var elm = $(this);
                    window.open(wtier_bfcm_twenty_twenty_four_banner_js_params.cta_link, '_blank'); 
                    elm.parents('.wtier-bfcm-banner-2024').hide();
                    data_obj['wtier_bfcm_twenty_twenty_four_banner_action_type'] = 3; // Clicked the button.
                    
                    $.ajax({
                        url: wtier_bfcm_twenty_twenty_four_banner_js_params.ajax_url,
                        data: data_obj,
                        type: 'POST'
                    });
                }).on('click', '.wtier-bfcm-banner-2024 .notice-dismiss', function(e) {
                    e.preventDefault();
                    data_obj['wtier_bfcm_twenty_twenty_four_banner_action_type'] = 2; // Closed by user
                    
                    $.ajax({
                        url: wtier_bfcm_twenty_twenty_four_banner_js_params.ajax_url,
                        data: data_obj,
                        type: 'POST',
                    });
                });
            }
        };
        wtier_bfcm_twenty_twenty_four_banner.init();
    });

})(jQuery);