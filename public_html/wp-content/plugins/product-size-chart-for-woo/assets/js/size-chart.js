(function ($) {
    'use strict';
    $(document).ready(function () {
        $('.pscw-option-select2:not(.pscw-option-select2-init)').each(function () {
            let select = $(this);
            let close_on_select = false, min_input = 2, placeholder = select.data('placeholder_select2'), action = '', type_select2 = select.data('type_select2');
            switch (type_select2) {
                case 'products':
                    action = 'pscw_search_product';
                    break;
            }
            select.addClass('pscw-option-select2-init').select2(select2_params(placeholder, action, close_on_select, min_input));
        });
        $('.vi-ui.checkbox:not(.pscw-checkbox-init)').addClass('pscw-checkbox-init').off().checkbox();

        $('#assign-all-product').on('change', function () {
            if ($(this).prop('checked')){
                $(this).parent().find('#'+$(this).attr('id')+'-val').val('1');
                $('.pscw-'+$(this).attr('id')+'-class').hide();
            }else {
                $(this).parent().find('#'+$(this).attr('id')+'-val').val('');
                $('.pscw-'+$(this).attr('id')+'-class').show() ;
            }
        }).trigger('change');
    });
    function select2_params(placeholder, action, close_on_select, min_input) {
        let result = {
            closeOnSelect: close_on_select,
            placeholder: placeholder,
            cache: true
        };
        if (action) {
            result['minimumInputLength'] = min_input;
            result['escapeMarkup'] = function (markup) {
                return markup;
            };
            result['ajax'] = {
                url: "admin-ajax.php?action=" + action,
                dataType: 'json',
                type: "GET",
                quietMillis: 50,
                delay: 250,
                data: function (params) {
                    return {
                        key_search: params.term,
                        nonce: $('#woo_sc_nonce').val(),
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: false
            };
        }
        return result;
    }
    return;
    if (ViPscw === undefined) {
        var ViPscw = {};
    }
    jQuery(document).ready(function ($) {
        "use strict";

        const initPscwValue = (element, type) => {
            const termOptions = (placeholder, taxonomy) => {
                return {
                    width: '100%',
                    minimumInputLength: 1,
                    placeholder: placeholder,
                    allowClear: true,
                    ajax: {
                        type: 'post',
                        url: VicPscwParams.ajaxUrl,
                        data: function (params) {
                            let query = {
                                taxonomy: taxonomy,
                                key_search: params.term,
                                action: 'pscw_search_term',
                                nonce: VicPscwParams.nonce,
                            };
                            return query;
                        },
                        processResults: function (data) {
                            return data ? data : {results: []};
                        }
                    }
                };
            };

            switch (type) {
                case 'products':
                    $(element).select2({
                        width: '100%',
                        minimumInputLength: 3,
                        placeholder: 'Product name...',
                        allowClear: true,
                        ajax: {
                            type: 'post',
                            url: VicPscwParams.ajaxUrl,
                            data: function (params) {
                                let query = {
                                    key_search: params.term,
                                    action: 'pscw_search_product',
                                    nonce: VicPscwParams.nonce,
                                };
                                return query;
                            },
                            processResults: function (data) {
                                return data ? data : {results: []};
                            }
                        }
                    });
                    break;
                case 'product_type':
                    $(element).select2(termOptions('Product type...', 'product_type'));
                    break;
                case 'product_visibility':
                    $(element).select2(termOptions('Product visibility...', 'product_visibility'));
                    break;
                case 'product_cat':
                    $(element).select2(termOptions('Product categories...', 'product_cat'));
                    break;
                case 'product_tag':
                    $(element).select2(termOptions('Product tags...', 'product_tag'));
                    break;
                case 'shipping_class':
                    $(element).select2(termOptions('Product shipping class...', 'product_shipping_class'));
                    break;
            }
        };

        ViPscw.sizeChart = {
            init() {
                this.count = 0;
                const container = $("#pscw_configure");

                container.on("change", this.change.bind(this));
                this.load();
            },

            load() {

                initPscwValue("#pscw_assign_products", "products");
                initPscwValue("#pscw_assign_product_cat", "product_cat");
                $("#pscw_assign").select2({
                    width: '100%',
                    minimumResultsForSearch: 'Infinity'
                });
            },

            change(e) {
                let selectedElement = e.target;
                switch (selectedElement.classList[0]) {
                    case "pscw_assign":
                        let val = $(selectedElement).val();
                        if (val !== 'none' || val !== 'all') {
                            $(".pscw_assign_pane.active").removeClass("active");
                            $(`.pscw_assign_pane[data-option="${val}"]`).addClass("active");

                        }
                        break;
                }
            },

        }

        ViPscw.sizeChart.init();
    });
}(jQuery));