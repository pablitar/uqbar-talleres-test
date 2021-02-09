(function ($) {
    'use strict';
    $(document).ready(function () {
        var BOS_API_Admin = {
            init: function () {

                var $body = $('body');

                // Retrieve feedback posts when an approriate action is taken
                $body.on('change', '#badgeos_settings_rest_api_enable', BOS_API_Admin.badgeos_enable_disable_api);
                $('#_badgeos_restapi_user').autocomplete({
                    source: function (request, response) {
                        $.getJSON(ajaxurl, {
                            q: BOS_API_Admin.extractLast(request.term), action: 'badgeos-get-users-list'
                        }, response);
                    },
                    multiselect: false,
                    search: function () {
                        // custom minLength
                        var term = BOS_API_Admin.extractLast(this.value);
                        if (term.length < 3) {
                            return false;
                        }
                    },
                    focus: function () {
                        // prevent value inserted on focus
                        return false;
                    },
                    change: function (event, ui) {
                        var auto_field = jQuery(this);
                        if (auto_field.val() == '') {
                            var dep_field = auto_field.data('fieldname');
                            var dep_field_type = auto_field.data('type');
                            if (dep_field_type == 'autocomplete') {
                                jQuery('#' + dep_field).val('');
                            }
                        }
                    },
                    select: function (event, ui) {

                        var auto_field = jQuery(this);

                        var dep_field = auto_field.data('fieldname');
                        var dep_field_type = auto_field.data('type');
                        if (dep_field_type == 'autocomplete') {

                            if (ui.item.value != '')
                                jQuery('#' + dep_field).val(ui.item.value);
                            else
                                jQuery('#' + dep_field).val(ui.item.id);
                        }

                        this.value = ui.item.label;
                        return false;
                    }
                });
            },
            badgeos_enable_disable_api: function () {

                if ($('#badgeos_settings_rest_api_enable').val() == 'yes') {
                    $('.badgeos_hide_if_api_disabled').css('display', 'block');
                } else {
                    $('.badgeos_hide_if_api_disabled').css('display', 'none');
                }
            },
            split: function (val) {
                return val.split(/,\s*/);
            },
            extractLast: function (term) {
                return BOS_API_Admin.split(term).pop();
            }
        };

        BOS_API_Admin.init();
        BOS_API_Admin.badgeos_enable_disable_api();
    });
})(jQuery);