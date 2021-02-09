(function ($) {
    'use strict';
    $(document).ready(function () {
        var BOS_API_Front = {
            init: function () {
                $('#frm_badge_restapi_submit').on('submit', function () {
                    var data = $(this).serialize();
                    jQuery.post(BosAPIVars.ajax_url, $(this).serialize(), function (response) {
                        location.reload();
                    });
                    return false;
                });
            }
        };

        BOS_API_Front.init();
    });
})(jQuery);