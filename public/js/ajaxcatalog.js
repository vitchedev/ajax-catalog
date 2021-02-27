/*
 *fields widgets
 */


import 'inputmask/dist/inputmask/inputmask.numeric.extensions.js';
import Inputmask from 'inputmask/dist/inputmask/inputmask.js';
import 'inputmask/dist/inputmask/inputmask.js';

// import 'select2/dist/js/i18n/ru.js';

import Blazy from 'blazy';

widgetsInitiate();

/*
 *function for initiation of widgets
 */
function widgetsInitiate() {

    //https://select2.org/data-sources/ajax
    //http://www.codebyjigs.com/select2-laravel/
    if (document.getElementsByClassName('autocompleteInput').length > 0) {
        $('.autocompleteInput').select2({
            minimumInputLength: 1,
            language: "ru",
            ajax: {
                url: '/f-ajax-autocomplete',
                dataType: 'json',
                method: "POST",
                delay: 200,
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page || 1,
                        table: $(this).data("table"),
                        column: $(this).data("column")
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;

                    return {
                        results: data,
                        pagination: {
                            more: 10 == data.length
                        }
                    };
                }
            },
        });
    }

    //https://www.npmjs.com/package/bootstrap-3-typeahead
    //https://github.com/bassjobsen/Bootstrap-3-Typeahead
    if (document.getElementsByClassName('autocompleteInput2').length > 0) {
        $('.autocompleteInput2').typeahead({
            source: function (query, process) {
                $.post('/f-ajax-autocomplete-2', {
                    q: query,
                    table: this.$element.data("table"),
                    column: this.$element.data("column")
                }).done(function (response) {
                    return process(response);
                });
            },
            minLength: 2,
            autoSelect: false,
            delay: 200,
            items: 15,
        });
    }


    if (document.getElementsByClassName('ajaxDate2').length > 0) {
        $('.ajaxDate2').datetimepicker({
            locale: 'ru',
            format: 'YYYY-MM-DD'
        });
    }

    if (document.getElementsByClassName('ajaxDateTime').length > 0) {
        $('.ajaxDateTime').datetimepicker({
            locale: 'ru',
            format: 'YYYY-MM-DD HH:mm'
        });
    }

    Inputmask("2099-99-99").mask(document.getElementsByClassName("ajaxDate"));

    $('body').on('keydown', '.ajaxDate', function (e) {
        let key = e.which;
        if ((key == "37") || (key == "38") || (key == "39") || (key == "40")) {
            e.preventDefault();
        }
    });

    Inputmask("+38(999)999-99-99").mask(document.getElementsByClassName("ajaxPhone"));

    $('body').on('keydown', '.ajaxPhone', function (e) {
        let key = e.which;
        if ((key == "37") || (key == "38") || (key == "39") || (key == "40")) {
            e.preventDefault();
        }
    });

    if (document.getElementsByClassName('category-filter').length > 0) {

        new Vue({
            el: '.category-filter',
            data: {
                options: categoryValues.options,
                category: categoryValues.active
            },
            methods: {
                catChange() {
                    if (this.category.length == 0) {
                        updatePageOnAjax(categoryValues.defaultUrl);
                    } else {
                        updatePageOnAjax(this.category[this.category.length - 1]);
                    }
                },
            }
        });
    }

    if (document.getElementsByClassName('layout-slider').length > 0) {

        let minPrice = $('.inputformprice').find('input[name="prb"]').val();
        let maxPrice = $('.inputformprice').find('input[name="pre"]').val();
        let disableSlider = false;

        $("#Slider2").ionRangeSlider({
            type: "double",
            min: Math.floor(window.minPrice),
            max: Math.ceil(window.maxPrice),
            from: Math.floor(minPrice),
            to: Math.ceil(maxPrice),
            grid: true,
            step: 1,
            onChange: function (data) {
                $('.inputformprice').find('input[name="prb"]').val(data.from);
                $('.inputformprice').find('input[name="pre"]').val(data.to);
            },
            onFinish: function (data) {
                slider.update({
                    disable: true
                });

                //generate right url on server
                $.post('/f-ajax-field', {
                    name: "prb",
                    url: window.location.pathname,
                    text: $('.inputformprice').find('input[name="prb"]').val()
                }).done(function (data) {
                    $.post('/f-ajax-field', {
                        name: "pre",
                        url: data,
                        text: $('.inputformprice').find('input[name="pre"]').val()
                    }).done(function (data) {
                        //update page
                        updatePageOnAjax(data);
                    });
                });
            },
        });

        let slider = $("#Slider2").data("ionRangeSlider");

        $('body').on('keydown keyup', 'input[name="pre"], input[name="prb"]', function(e){
            if (parseInt($(this).val()) > parseInt($(this).attr('max'))
                && e.keyCode != 46 // delete
                && e.keyCode != 8 // backspace
            ) {
                e.preventDefault();
                $(this).val($(this).attr('max'));
            }

            slider.update({
                from: Math.floor($('.inputformprice').find('input[name="prb"]').val()),
                to: Math.ceil($('.inputformprice').find('input[name="pre"]').val()),
            });
        });

        $('body').on('change', 'input[name="pre"], input[name="prb"]', function(e){
            slider.update({
                disable: true
            });
        });

    }


}

/*
 *show all checkbox button
 */
$('body').on('click', '.showAll', function () {
    //find elements to work with
    let triggerClass = $(this).find('input').attr('id') + '_trigger';

    if ($(this).find('input').is(':checked')) {
        //check checkboxes if showAll is checked
        $('.' + triggerClass).each(function () {
            if (!$(this).is(':checked')) {
                $(this).trigger('click');
            }
        });
    } else {
        //uncheck checkboxes if showAll is unchecked
        $('.' + triggerClass).each(function () {
            if ($(this).is(':checked')) {
                $(this).trigger('click');
            }
        });
    }
});

/*
 *bulk export submit
 */
$('#search_bulk_operations').submit(function (e) {

    e.preventDefault();

    //get values from form
    let values = {};
    $.each($('#search_bulk_operations').serializeArray(), function (i, field) {
        //add only necessary fields
        values[field.name] = field.value;
    });

    let selected = [];
    $('.search_bulk_trigger:checked').each(function () {
        selected.push($(this).attr('value'));
    });

    //what we should do
    let operation = $('#search_bulk_operations option[value=' + values.operation + ']').html();
    //should we confirm our action before continue?
    let confirmation = $('#search_bulk_operations option[value=' + values.operation + ']').hasClass('check');
    //can we process form?
    let check = true;

    //if we should confirm replase our check with users value
    if (confirmation) {
        check = confirm('Вы уверены, что хотите выполнить операцию "' + operation + '"?');
    }

    //if we will process action in js we will prevent form submit to
    if ($('#search_bulk_operations option[value=' + values.operation + ']').hasClass('by_js')) {
        //process open edit links
        $.each(values, function (index, value) {
            if ($.isNumeric(value)) {
                let url = $('#search_item_' + value + ' .' + values.operation).attr('href');
                if (url) {
                    //we need new variable to open multiple windows in chrome
                    let open = window.open(url, value);
                }
            }
        });
        check = false;
    }

    //prevent form submit
    if (check) {

        if (values.operation == 'export') {
            //we can't just download file by ajax, so we pass data throug form

            var $form = $('<form>', {
                action: $(this).attr('action'),
                method: 'post'
            });

            $.each(values, function (key, val) {
                $('<input>').attr({
                    type: "hidden",
                    name: key,
                    value: val
                }).appendTo($form);
            });

            $('<input>').attr({
                type: "hidden",
                name: 'requestUrl',
                value: window.location.pathname
            }).appendTo($form);

            $.each(selected, function (key, val) {
                $('<input>').attr({
                    type: "hidden",
                    name: 'items[]',
                    value: val
                }).appendTo($form);
            });

            $form.appendTo('body').submit();

        } else {
            $.post($(this).attr('action'), {
                operation: values.operation,
                requestUrl: window.location.pathname,
                items: selected
            }).done(function (data) {
                //update page
                location.reload();
            });
        }

    }
});

/*
 *function for page update by ajax
 */
function updatePageOnAjax(url) {

    //get page data from backend
    $.get(url + '/jq', function (data) {
        $.each(data.zones, function (index, value) {
            if (index == 'catalog_page_ajax') {
                $('#' + index).html(value);
            } else {
                $('#' + index).replaceWith(value);
            }
        });

        //update page data from backend
        $.each(data.data_for_js, function (index, value) {
            window[index] = value;
        });

        //update lazy load of images
        var bLazy = new Blazy();

        widgetsInitiate();
    });

    //update page url
    window.history.pushState('page2', 'Title', url);

}

/*
 *sorts handler in ajax catalog
 */
$(document).on('click', '.ajax_link', function (e) {

    //prevent link submit
    e.preventDefault();

    //update page
    if(!$(this).hasClass('processing')) {
        $('.ajax_link').addClass("processing");
        updatePageOnAjax($(this).attr('href'));
        if ($('.catalog-box').offset()) {
            if ($(this).parent().hasClass('more-products')) {
            } else {
                $('body, html').animate({
                    scrollTop: $('.catalog-box').offset().top
                }, 500);
            }

        }
    }

});

/*
 *prevent form submit on enter
 */
$('#catalog_page_ajax').on('submit', '#form_catalog', function (e) {
    e.preventDefault();
});

/*
 *filters handler in ajax catalog
 */
$('#catalog_page_ajax').on('change', '.ajax_filter', function (e) {

    let type = $(this).attr('data-type');
    //check source of the link
    if (type == 'link') {
        //update page
        updatePageOnAjax(this.value);
    } else if (type == 'text') {
        //generate right url on server
        $.post('/f-ajax-field', {
            name: $(this).attr('name'),
            url: window.location.pathname,
            text: this.value
        }).done(function (data) {
            //update page
            updatePageOnAjax(data);
        });
    }

});

//for date picker fields
$('#catalog_page_ajax').on('dp.hide', '.ajax_filter_date', function (e) {
    //check that date really changed
    if (e.target.dataset.default != e.date.format("YYYY-MM-DD")) {
        //generate right url on server
        $.post('/f-ajax-field', {
            name: $(e.target).children("input").prop('name'),
            url: window.location.pathname,
            text: e.date.format("YYYY-MM-DD")
        }).done(function (data) {
            //update page
            updatePageOnAjax(data);
        });
    }
});
$('#catalog_page_ajax').on('dp.change', '.ajax_filter_date', function (e) {
    //check that date  removed manually
    if (!e.date) {
        $.post('/f-ajax-field', {
            name: $(e.target).children("input").prop('name'),
            url: window.location.pathname,
            text: null
        }).done(function (data) {
            //update page
            updatePageOnAjax(data);
        });
    }
});
