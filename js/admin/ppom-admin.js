/* global ppom_vars */
"use strict";

const FIELD_COMPATIBLE_WITH_SELECT_OPTIONS = [ 'select', 'radio', 'switcher', 'image', 'conditional_meta' ];
const FIELDS_COMPATIBLE_WITH_TEXT = [ 'text', 'textarea', 'date', 'email' ]
const FIELDS_COMPATIBLE_WITH_NUMBERS = [ ...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS, 'number' ];

const OPERATOR_COMPARISON_VALUE_FIELD_TYPE = {
    'select': [...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS, 'checkbox', 'imageselect'],
}
const COMPARISON_VALUE_CAN_USE_SELECT = [ 'is', 'not', 'greater than', 'less than' ];
const HIDE_COMPARISON_INPUT_FIELD = ['any', 'empty', 'odd-number', 'even-number'];

const OPERATORS_FIELD_COMPATIBILITY = {
    'is': [...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS, ...FIELDS_COMPATIBLE_WITH_TEXT, ...FIELDS_COMPATIBLE_WITH_NUMBERS, 'checkbox', 'imageselect'],
    'not': [...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS, ...FIELDS_COMPATIBLE_WITH_TEXT, ...FIELDS_COMPATIBLE_WITH_NUMBERS, 'checkbox', 'imageselect'],
    'greater than': FIELDS_COMPATIBLE_WITH_NUMBERS,
    'less than': FIELDS_COMPATIBLE_WITH_NUMBERS,
    'even-number': FIELDS_COMPATIBLE_WITH_NUMBERS,
    'odd-number': FIELDS_COMPATIBLE_WITH_NUMBERS,
    'between': FIELDS_COMPATIBLE_WITH_NUMBERS,
    'number-multiplier': FIELDS_COMPATIBLE_WITH_NUMBERS,
    'any': [...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS, ...FIELDS_COMPATIBLE_WITH_TEXT, ...FIELDS_COMPATIBLE_WITH_NUMBERS],
    'empty': [...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS, ...FIELDS_COMPATIBLE_WITH_TEXT, ...FIELDS_COMPATIBLE_WITH_NUMBERS],
    'contains': [...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS, ...FIELDS_COMPATIBLE_WITH_TEXT],
    'not-contains': [...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS, ...FIELDS_COMPATIBLE_WITH_TEXT],
    'regex': [...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS, ...FIELDS_COMPATIBLE_WITH_TEXT]
}

const proOperatorOptionsToLock = new Set();

/**
 * An array to store available condition targets.
 * 
 * @type {Array<{fieldLabel?: string, fieldId?: string, fieldType?: string, canUse: boolean}>}
 */
const availableConditionTargets = [];

jQuery(function($) {

    var loader = new ImageLoader(ppom_vars.loader);
    // define your 'onreadystatechange'
    loader.loadEvent = function(url, imageAsDom) {

        $("#ppom-pre-loading").hide();
        $(".ppom-admin-wrap").show();
    }
    loader.load();

    /*********************************
     *       PPOM Form Design JS       *
     **********************************/


    /*-------------------------------------------------------

        ------ Its Include Following Function -----

        1- Submit PPOM Form Fields
        2- Hide And Show Import & Export & Product Meta blocks
        3- Get Last Field Index
        4- Show And Hide Visibility Role Field
        5- Remove Unsaved Fields
        6- Check And Uncheck All Fields
        7- Remove Check Fields
        8- On Fields Options Handle Add Option Last
        9- Edit Existing Fields
        10- Add New Fields
        11- Update Existing Fields
        12- Clone New Fields
        13- Clone Existing Fields
        14- Saving PPOM IDs In Existing Meta File
        15- Open Product Modal In Existing Meta File (removed)
        16- Handle Fields Tabs
        17- Handle Media Images Of Following Inputs Types
        18- Add Fields Conditions
        19- Add Fields Options
        20- Auto Generate Option IDs
        21- Create Field data_name By Thier Title
        22- Fields Sortable
        23- Fields Option Sortable
        24- Fields Dataname Must Be Required
        25- Fields Add Option Index Controle Funtion
        26- Fields Add Condition Index Controle Function
        27- Get All Fields Title On Condition Element Value After Click On Condition Tab
        28- validate API WooCommerce Product
    ------------------------------------------------------------*/


    /**
        PPOM Model
    **/
    var append_overly_model = ("<div class='ppom-modal-overlay ppom-js-modal-close'></div>");

    $(document).on('click', '[data-modal-id]:not(.ppom-is-pro-field)', function(e) {
        e.preventDefault();
        $("body").append(append_overly_model);
        var modalBox = $(this).attr('data-modal-id');
        lockedDataName = false;
        $('#' + modalBox).fadeIn();
    });

    ppom_close_popup();

    function ppom_close_popup() {

        $(".ppom-js-modal-close, .ppom-modal-overlay").click(function(e) {

            var target = $(e.target);
            if (target.hasClass("ppom-modal-overlay")) {
                return false;
            }
            $(".ppom-modal-box, .ppom-modal-overlay").fadeOut('fast', function() {
                $(".ppom-modal-overlay").remove();
            });

        });
    }


    $('.ppom-color-picker-init').wpColorPicker();


    /**
        1- Submit PPOM Form Fields
    **/
    $(".ppom-save-fields-meta").on('submit', function(e) {
        e.preventDefault();

        jQuery(".ppom-meta-save-notice").html('<img src="' + ppom_vars.loader + '">').show();

        $('.ppom-unsave-data').remove();

        const formData = new FormData();
        const ppomFields = new URLSearchParams();
        
        /*
            NOTE: since the request is to big for small values of `max_input_vars`, we will send the PPOM fields as a single string.
            
            INFO: some parts of the code use `\r\n` as delimiter for arrays in textarea. `serializeArray` respect this convention while native JS Form value access sanitize it to just `\n`.
        */
        $( this ).serializeArray().forEach(({ value, name }) => {
            if ( name.startsWith('ppom[') && typeof value === 'string' ) {
                ppomFields.append( name, value );
            } else {
                formData.append(name, value);
            }
        });

        formData.append('ppom', ppomFields.toString());
       
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(resp => {
            const bg_color = resp.status == 'success' ? '#4e694859' : '#ee8b94';
            jQuery(".ppom-meta-save-notice").html(resp.message).css({ 'background-color': bg_color, 'padding': '8px', 'border-left': '5px solid #008c00' });
            if (resp.status == 'success') {
                if (resp.redirect_to != '') {
                    window.location = resp.redirect_to;
                } else {
                    window.location.reload();
                }
            }
        })
        .catch(() => {
            jQuery(".ppom-meta-save-notice")
                .html( ppom_vars.i18n.errorOccurred )
                .css({ 'background-color': '#ee8b94', 'padding': '8px', 'border-left': '5px solid #c00' });
        });
    });


    /**
        2- Hide And Show Import & Export & Product Meta blocks
    **/
    $('.ppom-import-export-btn').on('click', function(event) {
        event.preventDefault();
        if ( $(".ppom-import-export-block").length === 0 ) {
            $('#ppom-import-upsell').fadeIn();
            return;
        }
        $('.ppom-more-plugins-block').hide();
        $(".ppom-import-export-block").show();
        $(".ppom-product-meta-block").hide();
    });

    $('.ppom-cancle-import-export-btn').on('click', function(event) {
        event.preventDefault();
        $('.ppom-more-plugins-block').show();
        $(".ppom-import-export-block").hide();
        $(".ppom-product-meta-block").show();
    });


    /**
        3- Get Last Field Index
    **/
    var field_no = $('#field_index').val();


    /**
        4- Show And Hide Visibility Role Field
    **/
    $('.ppom-slider').find('[data-meta-id="visibility_role"]').removeClass('ppom_handle_fields_tab').hide();
    $('.ppom_save_fields_model .ppom-slider').each(function(i, div) {
        var visibility_value = $(div).find('[data-meta-id="visibility"] select').val();
        if (visibility_value == 'roles') {
            $(div).find('[data-meta-id="visibility_role"]').show();
        }
    });
    $(document).on('change', '[data-meta-id="visibility"] select', function(e) {
        e.preventDefault();

        var div = $(this).closest('.ppom-slider');
        var visibility_value = $(this).val();
        // console.log(visibility_value);
        if (visibility_value == 'roles') {
            div.find('[data-meta-id="visibility_role"]').show();
        }
        else {
            div.find('[data-meta-id="visibility_role"]').hide();
        }
    });


    /**
        5- Remove Unsaved Fields
    **/
    $(document).on('click', '.ppom-close-fields', function(event) {
        event.preventDefault();

        $(this).closest('.ppom-slider').addClass('ppom-unsave-data');
    });


    /**
        6- Check And Uncheck All Fields
    **/
    $('.ppom-main-field-wrapper').on('change', '.onoffswitch-checkbox', function(event) {
        var div = $(this).closest('div');
        if ($(this).prop('checked')) {
            div.find('input[type="hidden"]').val('on');
        }
        else {
            div.find('input[type="hidden"]').val('off');
        }
    });

    $('.ppom-main-field-wrapper').on('click', '.ppom-check-all-field input', function(event) {
        if ($(this).prop('checked')) {
            $('.ppom_field_table .ppom-checkboxe-style input[type="checkbox"]').prop('checked', true);
        }
        else {
            $('.ppom_field_table .ppom-checkboxe-style input[type="checkbox"]').prop('checked', false);
        }
    });
    $('.ppom-main-field-wrapper').on('change', '.ppom_field_table tbody .ppom-checkboxe-style input[type="checkbox"]', function(event) {
        if ($('.ppom_field_table tbody .ppom-checkboxe-style input[type="checkbox"]:checked').length == $('.ppom_field_table tbody .ppom-checkboxe-style input[type="checkbox"]').length) {
            $('.ppom-check-all-field input').prop('checked', true);
        }
        else {
            $('.ppom-check-all-field input').prop('checked', false);
        }
    });


    /**
        7- Remove Check Fields
    **/
    $('.ppom-main-field-wrapper').on('click', '.ppom_remove_field', function(e) {
        e.preventDefault();
      
        const inputsToRemove = document.querySelectorAll('.ppom_field_table .ppom-check-one-field input:checked');

        if ( inputsToRemove.length > 0 ) {
            window?.ppomPopup?.open({
                title: window?.ppom_vars?.i18n.popup.confirmTitle,
                onConfirmation: () => {
                    inputsToRemove.forEach(( meta_field ) => {
                        const field_id = meta_field.value;
                        meta_field.closest(`.row_no_${field_id}`)?.remove();
                        document.querySelector(`#ppom_field_model_${field_id}`)?.remove();
                    });
                }
            })
        }
        else {
            window?.ppomPopup?.open({
                title: window.ppom_vars.i18n.popup.checkFieldTitle,
                type: "error",
                hideCloseBtn: true
            })
        }
    });

    /**
        9- Edit Existing Fields
    **/
    var lockedDataName = false;
    $(document).on('click', '.ppom-edit-field:not(.ppom-is-pro-field)', function(event) {
        event.preventDefault();

        var the_id = $(this).attr('id');
        $('#ppom_field_model_' + the_id).find('.ppom-close-checker').removeClass('ppom-close-fields');
        lockedDataName = true;
    });


    /**
        10- Add New Fields
    **/
    $(document).on('click', '.ppom-add-field', function(event) {
        event.preventDefault();

        var $this = $(this);
        var ui = ppom_required_data_name($this, 'new');
        if (ui == false) {
            return;
        }

        var copy_model_id = $(this).attr('data-copy-model-id');
        var id = $(this).attr('data-field-index');
        id = Number(id);
        // console.log(id);

        var field_title = $('#ppom_field_model_' + id + '').find('.ppom-modal-body .ppom-fields-actions').attr('data-table-id');
        var data_name = $('#ppom_field_model_' + id + '').find('[data-meta-id="data_name"] input').val();
        var title = $('#ppom_field_model_' + id + '').find('[data-meta-id="title"] input').val();
        var placeholder = $('#ppom_field_model_' + id + '').find('[data-meta-id="placeholder"] input').val();
        var required = $('#ppom_field_model_' + id + '').find('[data-meta-id="required"] input').prop('checked');
        var type = $(this).attr('data-field-type');

        // console.log(field_title);

        if (required == true) {
            var _ok = ppom_vars.i18n.yes;
        }
        else {
            _ok = ppom_vars.i18n.no;
        }
        if (placeholder == null) {
            placeholder = '-';
        }

        var html = '<tr class="row_no_' + id + ' ui-sortable-handle" id="ppom_sort_id_' + id + '">';
        html += '<td class="ppom-sortable-handle"><i class="fa fa-arrows" aria-hidden="true"></i></td>';
        html += '<td class="ppom-check-one-field ppom-checkboxe-style">';
        html += '<label>';
        html += '<input type="checkbox" value="' + id + '">';
        html += '<span></span>';
        html += '</label>';
        html += '</td>';

        html += '<td>';
        html += '<div class="onoffswitch">';
        html += '<input checked type="checkbox" name="ppom[' + id + '][status]" class="onoffswitch-checkbox" id="ppom-onoffswitch-' + id + '" tabindex="0">';
        html += '<label class="onoffswitch-label" for="ppom-onoffswitch-' + id + '">';
        html += '<span class="onoffswitch-inner"></span>';
        html += '<span class="onoffswitch-switch"></span>';
        html += '</label>';
        html += '</div>';
        html += '</td>';

        // html += '<td class="ppom-check-one-field"><input type="checkbox" value="'+id+'"></td>';
        html += '<td class="ppom_meta_field_id">' + data_name + '</td>';
        html += '<td class="ppom_meta_field_type">' + type + '</td>';
        html += '<td class="ppom_meta_field_title">' + title + '</td>';
        html += '<td class="ppom_meta_field_plchlder">' + placeholder + '</td>';
        html += '<td class="ppom_meta_field_req">' + _ok + '</td>';
        html += '<td>';
        html += '<button class="ppom_copy_field btn" id="' + id + '" data-field-type="' + field_title + '" style="margin-right: 4px;"><i class="fa fa-clone" aria-hidden="true"></i></button>';
        html += '<button class="ppom-edit-field btn" id="' + id + '" data-modal-id="ppom_field_model_' + id + '"><i class="fa fa-pencil" aria-hidden="true"></i></button>';
        html += '</td>';
        html += '</tr>';
        html = $.parseHTML(html);
        // console.log(copy_model_id);
        if (copy_model_id != '' && copy_model_id != undefined) {
            $(html).find('.ppom_field_table tbody').end().insertAfter('#ppom_sort_id_' + copy_model_id + '');
        }
        else {
            $(html).appendTo('.ppom_field_table tbody');
        }

        $(".ppom-modal-box, .ppom-modal-overlay").fadeOut('fast', function() {
            $(".ppom-modal-overlay").remove();
        });

        $(this).removeClass('ppom-add-field').addClass('ppom-update-field');
        $(this).html( ppom_vars.i18n.updatedField );

    });


    /**
        11- Update Existing Fields
    **/
    $(document).on('click', '.ppom-update-field', function(event) {
        event.preventDefault();

        var id = $(this).attr('data-field-index');
        id = Number(id);

        var $this = $(this);
        var ui = ppom_required_data_name($this, 'update');

        if (ui == false) {
            return;
        }

        var data_name = $('#ppom_field_model_' + id + '').find('[data-meta-id="data_name"] input').val();
        var title = $('#ppom_field_model_' + id + '').find('[data-meta-id="title"] input').val();
        var placeholder = $('#ppom_field_model_' + id + '').find('[data-meta-id="placeholder"] input').val();
        var required = $('#ppom_field_model_' + id + '').find('[data-meta-id="required"] input').prop('checked');
        var type = $(this).attr('data-field-type');

        if (required == true) {
            var _ok = ppom_vars.i18n.yes;
        }
        else {
            _ok = ppom_vars.i18n.no;
        }

        var row = $('.ppom_field_table tbody').find('.row_no_' + id);

        row.find(".ppom_meta_field_title").text(title);
        row.find(".ppom_meta_field_id").text(data_name);
        row.find(".ppom_meta_field_type").text(type);
        row.find(".ppom_meta_field_plchlder").text(placeholder);
        row.find(".ppom_meta_field_req").text(_ok);

        $(".ppom-modal-box, .ppom-modal-overlay").fadeOut('fast', function() {
            $(".ppom-modal-overlay").remove();
        });
    });


    /**
        12- Clone New Fields
    **/
    var option_index = 0;
    $(document).on('click', '.ppom_select_field', function(event) {
        if( $(this).hasClass('ppom-locked-field') ) {
            return;
        }

        event.preventDefault();

        $('#ppom_fields_model_id').find('.ppom-js-modal-close').trigger('click');

        var field_type = $(this).data('field-type');
        var clone_new_field = $(".ppom-field-" + field_type + ":last").clone();

        // field attr name apply on all fields meta with ppom-meta-field class
        clone_new_field.find('.ppom-meta-field').each(function(i, meta_field) {
            var field_name = 'ppom[' + field_no + '][' + $(meta_field).attr('data-metatype') + ']';
            $(meta_field).attr('name', field_name);
        });

        // fields options sortable
        clone_new_field.find(".ppom-options-sortable").sortable();

        // add fields index in data-field-no
        clone_new_field.find(".ppom-fields-actions").attr('data-field-no', field_no);

        // fields conditions handle name attr
        clone_new_field.find('.ppom-condition-visible-bound').each(function(i, meta_field) {
            var field_name = 'ppom[' + field_no + '][conditions][' + $(meta_field).attr('data-metatype') + ']';
            $(meta_field).attr('name', field_name);
        });

        clone_new_field.find('.ppom-fields-actions [data-meta-id="visibility_role"]').hide();

        var field_model_id = 'ppom_field_model_' + field_no + '';

        clone_new_field.find('.ppom_save_fields_model')
        .end()
        .appendTo('.ppom_save_fields_model')
        .attr('id', field_model_id)
        .find( '.ppom-tabs-header label.ppom-tabs-label' )
        .each( function( index, item ) {
            var tabId = $(item).attr('id');
            var hasTabOptions = $( '#' + field_model_id, document )?.find( '.ppom_handle_' + tabId )?.length > 0;
            if ( ! hasTabOptions ) {
                $(item).hide();
            }
        } );
        clone_new_field.find('.ppom-field-checker').attr('data-field-index', field_no);
        clone_new_field.find('.ppom-field-checker').addClass('ppom-add-fields-js-action');

        // var color_picker_input = clone_new_field.find('.ppom-color-picker-init').clone();
        // clone_new_field.find('.ppom-color-picker-cloner').html(color_picker_input);
        // clone_new_field.find('.ppom-color-picker-init').wpColorPicker();
        // $('.ppom-color-picker-init').wpColorPicker();
        // $('.ppom-color-picker-init').wpColorPicker();

        // $('.ppom-color-picker-init').wpColorPicker('destroy');


        clone_new_field.addClass('ppom_sort_id_' + field_no + '');
        var field_index = field_no;

        // handle multiple options
        var ppom_option_type = '';
        var option_selector = clone_new_field.find('.ppom-option-keys');
        var option_controller = clone_new_field.find('.ppom-fields-option');
        var add_cond_selector = clone_new_field.find('.ppom-conditional-keys');

        // for address addon
        var address_selector = clone_new_field.find('.ppom-checkout-field');
        var address_table_id = clone_new_field.find('.ppom_address_table');
        ppom_create_address_index(address_selector, field_index, address_table_id);

        var wpcolor_selector = clone_new_field.find('.ppom-color-picker-cloner');
        ppom_wp_color_handler(wpcolor_selector, field_index, option_index);

        ppom_create_option_index(option_selector, field_index, option_index, ppom_option_type);
        ppom_option_controller(option_controller, field_index, option_index, ppom_option_type);
        ppom_add_condition_set_index(add_cond_selector, field_index, field_type, option_index);

        // popup fields on model
        ppom_close_popup();
        $('#ppom_field_model_' + field_no + '').fadeIn();

        $( document ).trigger( 'ppom_new_field_created', [ clone_new_field, field_no, field_type ] );

        field_no++;
    });


    /**
        13- Clone Existing Fields
    **/
    var copy_no = 0;
    $('.ppom-main-field-wrapper').on('click', '.ppom_copy_field:not(.ppom-is-pro-field)', function(e) {
        e.preventDefault();

        var model_id_no = $(this).attr('id');

        var field_type = $(this).data('field-type');
        // console.log(model_id_no);

        var clone_new_field = $('.ppom_save_fields_model #ppom_field_model_' + model_id_no + '').clone(true);
        var dataTitleField = clone_new_field.find( '[data-metatype="title"]' );
        var dataNameField = clone_new_field.find( '[data-metatype="data_name"]' );
        var dataNameOldVal = dataNameField.val();
        var duplicateFields = jQuery(document).find('.ppom_field_table .ppom_meta_field_title:contains(' + dataTitleField.val() + ')');
        var dataNameNewVal = '';
        var reg = '/_copy_/';
        if ( duplicateFields && duplicateFields.length >= 1 ) {
            dataNameOldVal = dataNameOldVal.split( '_copy_' ).shift();
            dataNameNewVal = dataNameOldVal + '_copy_' + duplicateFields.length++;
        } else {
            dataNameNewVal = dataNameOldVal + '_copy';
        }
        dataNameField.val( dataNameNewVal );
        // clone_new_field.find('.ppom_save_fields_model').end().appendTo('.ppom_save_fields_model').attr('id','ppom_field_model_'+field_no+'');
        clone_new_field.find('.ppom_save_fields_model').end().insertAfter('#ppom_field_model_' + model_id_no + '').attr('id', 'ppom_field_model_' + field_no + '');
        clone_new_field.find('.ppom-add-fields-js-action').attr('data-field-index', field_no);
        clone_new_field.find('.ppom-close-fields').attr('data-field-index', field_no);
        clone_new_field.find('.ppom-js-modal-close').addClass('ppom-close-fields');
        clone_new_field.find('.ppom-add-fields-js-action').removeClass('ppom-update-field');
        clone_new_field.find('.ppom-add-fields-js-action').attr('data-copy-model-id', model_id_no);
        clone_new_field.find('.ppom-add-fields-js-action').addClass('ppom-add-field');
        clone_new_field.find('.ppom-add-fields-js-action').addClass('ppom-insertafter-field');
        clone_new_field.find('.ppom-add-fields-js-action').html('Add Field');
        clone_new_field.removeClass('ppom_sort_id_' + model_id_no + '');
        clone_new_field.addClass('ppom_sort_id_' + field_no + '');

        // field attr name apply on all fields meta with ppom-meta-field class
        clone_new_field.find('.ppom-meta-field').each(function(i, meta_field) {
            var field_name = 'ppom[' + field_no + '][' + $(meta_field).attr('data-metatype') + ']';
            $(meta_field).attr('name', field_name);
        });

        // fields options sortable
        clone_new_field.find(".ppom-options-sortable").sortable();

        // add fields index in data-field-no
        clone_new_field.find(".ppom-fields-actions").attr('data-field-no', field_no);

        // fields conditions handle name attr
        clone_new_field.find('.ppom-condition-visible-bound').each(function(i, meta_field) {
            var field_name = 'ppom[' + field_no + '][conditions][' + $(meta_field).attr('data-metatype') + ']';
            $(meta_field).attr('name', field_name);
        });

        clone_new_field.find('.ppom-fields-actions [data-meta-id="visibility_role"]').hide();


        var field_index = field_no;

        // handle multiple options
        var ppom_option_type = 'ppom_copy_option';
        var option_selector = clone_new_field.find('.ppom-option-keys');
        var add_cond_selector = clone_new_field.find('.ppom-conditional-keys');
        var eventcalendar_selector = clone_new_field.find('.ppom-eventcalendar-field');
        var image_option_selector = clone_new_field.find('[data-table-id="image"] .data-options, [data-table-id="imageselect"] .data-options');

        // reset option to one
        // clone_new_field.find('[data-table-id="image"] .data-options').remove();
        clone_new_field.find('[data-table-id="audio"] .pre-upload-box li').remove();
        // clone_new_field.find('[data-table-id="imageselect"] .pre-upload-box li').remove();
        // clone_new_field.find('.data-options').not(':last').remove();
        clone_new_field.find('.webcontact-rules').not(':last').remove();

        // set existing conditions meta
        $(clone_new_field).find('select[data-metatype="elements"]').each(function(i, condition_element) {

            var existing_value1 = $(condition_element).attr("data-existingvalue");
            if ($.trim(existing_value1) !== '') {
                jQuery(condition_element).val(existing_value1);
            }
        });
        $(clone_new_field).find('select[data-metatype="element_values"]').each(function(i, condition_element) {

            var div = $(this).closest('.webcontact-rules');
            var existing_value1 = $(condition_element).attr("data-existingvalue");

            if ($.trim(existing_value1) !== '') {
                jQuery(condition_element).val(existing_value1);
            }
        });

        var wpcolor_selector = clone_new_field.find('.ppom-color-picker-cloner');
        ppom_wp_color_handler(wpcolor_selector, field_index, option_index);

        ppom_create_option_index(option_selector, field_index, option_index, ppom_option_type);
        var option_controller = clone_new_field.find('.ppom-fields-option');

        ppom_option_controller(option_controller, field_index, option_index, ppom_option_type);
        ppom_add_condition_set_index(add_cond_selector, field_index, field_type, option_index);

        // for eventcalendar changing index
        ppom_eventcalendar_set_index(eventcalendar_selector, field_index);

        // set index for all images fields
        image_option_selector.find('input').each(function(img_index, img_meta) {

            var opt_in = $(img_meta).attr('data-opt-index');
            var field_name = 'ppom[' + field_index + '][images][' + opt_in + '][' + $(img_meta).attr('data-metatype') + ']';
            $(img_meta).attr('name', field_name);
        });

        // popup fields on model
        $("body").append(append_overly_model);
        ppom_close_popup();
        $('#ppom_field_model_' + field_no + '').fadeIn();

        field_no++;
    });


    /**
        14- Saving PPOM IDs In Existing Meta File
    **/

    /**
     * @type {HTMLFormElement}
     */
    const popupForm = document.querySelector('#ppom-product-form');
    popupForm?.addEventListener('submit', (e) => {
        e.preventDefault();

        const formContent = new FormData( document.querySelector('#ppom-product-form') );

        formContent.append( 'action','ppom_attach_ppoms' );
        formContent.append( 'ppom_id', $("#ppom_id").val() );

        fetch( ajaxurl, {
            method: 'POST',
            body: formContent
        })
            .then(response => response.json())
            .then(resp => {
                alert(resp.message);

                const openModalBtn = document.querySelector('.ppom-products-modal');
                if ( openModalBtn && openModalBtn.dataset?.reload ) {
                    window.location.reload();
                } else {
                    document.querySelector('.ppom-js-modal-close').dispatchEvent(new Event('click'));
                }
            })
            .catch(error => console.error('Error:', error));
    })

    /**
        16- Handle Fields Tabs
    **/
    $('.ppom-control-all-fields-tabs').hide();
    $('.ppom_handle_fields_tab').show();
    $(document).on('click', '.ppom-tabs-label', function() {

        var id = $(this).attr('id');
        var selectedTab = $(this).parent();
        var fields_wrap = selectedTab.parent();
        selectedTab.find('.ppom-tabs-label').removeClass('ppom-active-tab');
        $(this).addClass('ppom-active-tab');
        var content_box = fields_wrap.find('.ppom-control-all-fields-tabs');
        content_box.hide();

        const handler = fields_wrap.find('.ppom_handle_' + id);
        handler.fadeIn(200);

        $(fields_wrap).trigger('ppom_fields_tab_changed', [id, handler]);
    });


    /**
        17- Handle Media Images Of Following Inputs Types
            17.1- Pre-Images Type
            17.2- Audio Type
            17.3- Imageselect Type
    **/
    var $uploaded_image_container;
    $(document).on('click', '.ppom-pre-upload-image-btn', function(e) {
        e.preventDefault();

        var meta_type = $(this).attr('data-metatype');
        $uploaded_image_container = $(this).closest('div');
        var image_append = $uploaded_image_container.find('ul');
        var option_index = parseInt($uploaded_image_container.find('#ppom-meta-opt-index').val());
        var main_wrapper = $(this).closest('.ppom-slider');
        var field_index = main_wrapper.find('.ppom-fields-actions').attr('data-field-no');
        var price_placeholder = ppom_vars.i18n.pricePlaceholder;

        var wp_media_type = 'image';
        if (meta_type == 'audio') {
            wp_media_type = 'audio,video';
        }

        var button = $(this),
            custom_uploader = wp.media({
                title: ppom_vars.i18n.choseFile,
                library: {
                    type: wp_media_type
                },
                button: {
                    text: ppom_vars.i18n.upload
                },
                multiple: true
            }).on('select', function() {

                var attachments = custom_uploader.state().get('selection').toJSON();

                attachments.map((meta, index) => {
                    // console.log(meta);
                    var fileurl = meta.url;
                    var fileid = meta.id;
                    var filename = meta.filename;
                    var file_title = meta.title;


                    var img_icon = '<img width="60" src="' + fileurl + '" style="width: 34px;">';
                    var url_field = '<input placeholder="url" type="text" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][url]" class="form-control" data-opt-index="' + option_index + '" data-metatype="url">';

                    if (meta.type !== 'image') {
                        var img_icon = '<img width="60" src="' + meta.icon + '" style="width: 34px;">';
                        url_field = '';
                    }

                    var price_metatype = 'price';
                    var stock_metatype = 'stock';
                    var stock_placeholder = ppom_vars.i18n.stock;

                    // Set name key for imageselect addon
                    if (meta_type == 'imageselect') {
                        var class_name = 'data-options ui-sortable-handle';
                        var condidtion_attr = 'image_options';
                        meta_type = 'images';
                        price_placeholder = 'Price';
                        url_field = '<input placeholder="Description" type="text" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][description]" class="form-control" data-opt-index="' + option_index + '" data-metatype="description">';
                    }
                    else if (meta_type == 'images') {
                        var class_name = 'data-options ui-sortable-handle';
                        var condidtion_attr = 'image_options';
                    }
                    else if (meta_type == 'conditional_meta') {
                        meta_type = 'images';
                        var class_name = 'data-options ui-sortable-handle';
                        var condidtion_attr = 'image_options';
                        price_placeholder = ppom_vars.i18n.metaIds;
                        price_metatype = 'meta_id';
                    }
                    else {
                        var class_name = '';
                        var condidtion_attr = '';
                    }

                    if (fileurl) {
                        var image_box = '';
                        image_box += '<li class="' + class_name + '" data-condition-type="' + condidtion_attr + '">';
                        image_box += '<span class="dashicons dashicons-move" style="margin-bottom: 7px;margin-top: 2px;"></span>';
                        image_box += '<span class="ppom-uploader-img-title"></span>';
                        image_box += '<div style="display: flex;">';
                        image_box += '<div class="ppom-uploader-img-center">';
                        image_box += img_icon;
                        image_box += '</div>';
                        image_box += '<input type="hidden" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][link]" value="' + fileurl + '" data-opt-index="' + option_index + '" data-metatype="link">';
                        image_box += '<input type="hidden" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][id]" value="' + fileid + '" data-opt-index="' + option_index + '" data-metatype="id">';
                        image_box += '<input type="text" placeholder="Title" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][title]" class="form-control ppom-image-option-title" data-opt-index="' + option_index + '" data-metatype="title" value="' + file_title + '">';
                        image_box += '<input class="form-control" type="text" placeholder="' + price_placeholder + '" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][' + price_metatype + ']" class="form-control" data-opt-index="' + option_index + '" data-metatype="' + price_metatype + '">';

                        if (meta_type != 'audio') {
                            image_box += '<input class="form-control" type="text" placeholder="' + stock_placeholder + '" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][' + stock_metatype + ']" class="form-control" data-opt-index="' + option_index + '" data-metatype="' + stock_metatype + '">';
                        }

                        image_box += url_field;
                        image_box += '<button class="btn btn-danger ppom-pre-upload-delete" style="height: 35px;"><i class="fa fa-times" aria-hidden="true"></i></button>';
                        image_box += '</div>';
                        image_box += '</li>';

                        $(image_box).appendTo(image_append);

                        option_index++;
                    }

                });

                $uploaded_image_container.find('#ppom-meta-opt-index').val(option_index);

            }).open();
    });

    var $uploaded_image_container;
    $(document).on('click', '.ppom-pre-upload-image-btnsd', function(e) {

        e.preventDefault();
        var meta_type = $(this).attr('data-metatype');
        $uploaded_image_container = $(this).closest('div');
        var image_append = $uploaded_image_container.find('ul');
        var option_index = parseInt($uploaded_image_container.find('#ppom-meta-opt-index').val());
        $uploaded_image_container.find('#ppom-meta-opt-index').val(option_index + 1);
        var main_wrapper = $(this).closest('.ppom-slider');
        var field_index = main_wrapper.find('.ppom-fields-actions').attr('data-field-no');
        var price_placeholder = ppom_vars.i18n.pricePlaceholder;
        wp.media.editor.send.attachment = function(props, attachment) {
            // console.log(attachment);
            var existing_images;
            var fileurl = attachment.url;
            var fileid = attachment.id;
            var img_icon = '<img width="60" src="' + fileurl + '" style="width: 34px;">';
            var url_field = '<input placeholder="url" type="text" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][url]" class="form-control" data-opt-index="' + option_index + '" data-metatype="url">';

            if (attachment.type !== 'image') {
                var img_icon = '<img width="60" src="' + attachment.icon + '" style="width: 34px;">';
                url_field = '';
            }

            var price_metatype = 'price';
            var stock_metatype = 'stock';
            var stock_placeholder = ppom_vars.i18n.stock;

            // Set name key for imageselect addon
            if (meta_type == 'imageselect') {
                var class_name = 'data-options ui-sortable-handle';
                var condidtion_attr = 'image_options';
                meta_type = 'images';
                price_placeholder = 'Price';
                url_field = '<input placeholder="Description" type="text" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][description]" class="form-control" data-opt-index="' + option_index + '" data-metatype="description">';
            }
            else if (meta_type == 'images') {
                var class_name = 'data-options ui-sortable-handle';
                var condidtion_attr = 'image_options';
            }
            else if (meta_type == 'conditional_meta') {
                meta_type = 'images';
                var class_name = 'data-options ui-sortable-handle';
                var condidtion_attr = 'image_options';
                price_placeholder = ppom_vars.i18n.metaIds;
                price_metatype = 'meta_id';
            }
            else {
                var class_name = '';
                var condidtion_attr = '';
            }

            if (fileurl) {
                var image_box = '';
                image_box += '<li class="' + class_name + '" data-condition-type="' + condidtion_attr + '">';
                image_box += '<span class="dashicons dashicons-move" style="margin-bottom: 7px;margin-top: 2px;"></span>';
                image_box += '<span class="ppom-uploader-img-title"></span>';
                image_box += '<div style="display: flex;">';
                image_box += '<div class="ppom-uploader-img-center">';
                image_box += img_icon;
                image_box += '</div>';
                image_box += '<input type="hidden" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][link]" value="' + fileurl + '" data-opt-index="' + option_index + '" data-metatype="link">';
                image_box += '<input type="hidden" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][id]" value="' + fileid + '" data-opt-index="' + option_index + '" data-metatype="id">';
                image_box += '<input type="text" placeholder="Title" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][title]" class="form-control ppom-image-option-title" data-opt-index="' + option_index + '" data-metatype="title">';
                image_box += '<input class="form-control" type="text" placeholder="' + price_placeholder + '" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][' + price_metatype + ']" class="form-control" data-opt-index="' + option_index + '" data-metatype="' + price_metatype + '">';
                image_box += '<input class="form-control" type="text" placeholder="' + stock_placeholder + '" name="ppom[' + field_index + '][' + meta_type + '][' + option_index + '][' + stock_metatype + ']" class="form-control" data-opt-index="' + option_index + '" data-metatype="' + stock_metatype + '">';
                image_box += url_field;
                image_box += '<button class="btn btn-danger ppom-pre-upload-delete" style="height: 35px;"><i class="fa fa-times" aria-hidden="true"></i></button>';
                image_box += '</div>';
                image_box += '</li>';

                $(image_box).appendTo(image_append);
            }
        }

        wp.media.editor.open(this);

        return false;
    });
    $(document).on('click', '.ppom-pre-upload-delete', function(e) {

        e.preventDefault();
        $(this).closest('li').remove();
    });


    /**
        18- Add Fields Conditions
    **/
    $(document).on('click', '.ppom-add-rule', function(e) {

        e.preventDefault();

        var div = $(this).parents('.form-group');
        var option_index = parseInt(div.find('.ppom-condition-last-id').val());
        div.find('.ppom-condition-last-id').val(option_index + 1);

        var field_index = div.parents('.ppom-slider').find('.ppom-fields-actions').attr('data-field-no');
        var cloneElement = $('.webcontact-rules:last', div);
        var condition_clone = cloneElement.clone();

        var append_item = div.find('.ppom-condition-clone-js');
        condition_clone.find(append_item).end().appendTo(append_item);
        
        ppom_rename_rules_name();

        var field_type = '';
        var add_cond_selector = condition_clone.find('.ppom-conditional-keys');
        ppom_add_condition_set_index(add_cond_selector, field_index, field_type, option_index);

        if ( $(this).next('.ppom-remove-rule')?.length > 0 ) {
            $(this).remove();
            return;
        }
        $('.ppom-add-rule', cloneElement)
        .removeClass('ppom-add-rule').addClass('ppom-remove-rule')
        .removeClass('btn-success').addClass('btn-danger')
        .html('<i class="fa fa-minus" aria-hidden="true"></i>');

        // Last row.
        var lastRow = jQuery('.webcontact-rules:visible:last');
        if ( lastRow?.find('.ppom-remove-rule')?.length === 0 ) {
            var cloneButton = lastRow?.find('.ppom-add-rule').clone();
            cloneButton
            .removeClass('ppom-add-rule').addClass('ppom-remove-rule ml-1')
            .removeClass('btn-success').addClass('btn-danger')
            .html('<i class="fa fa-minus" aria-hidden="true"></i>');
            cloneButton.insertAfter(lastRow?.find('.ppom-add-rule'));
        }

    }).on('click', '.ppom-remove-rule', function(e) {
        var removeButton = $(this );
        if (removeButton.parents('.ppom-condition-clone-js')?.find('.webcontact-rules')?.length === 1) {
            removeButton
            .parents('.ppom-condition-clone-js')
            .find('.webcontact-rules')
            .find('select')
            .val('')
            .attr( 'selected', false )
            .prop( 'selected', false );
            $(this).remove();
            ppom_rename_rules_name();
            return false;
        }
        removeButton.parents('.webcontact-rules:first').remove();
        // Last row.
        var lastRow = jQuery('.webcontact-rules:visible:last');
        if ( lastRow?.find('.ppom-add-rule')?.length === 0 ) {
            var cloneButton = lastRow?.find('.ppom-remove-rule').clone();
            cloneButton
            .removeClass('ppom-remove-rule').addClass('ppom-add-rule')
            .removeClass('btn-danger').addClass('btn-success')
            .html('<i class="fa fa-plus" aria-hidden="true"></i>');
            cloneButton.insertBefore(lastRow?.find('.ppom-remove-rule'));
        }
        ppom_rename_rules_name();
        e.preventDefault();
        return false;
    });


    /**
        19- Add Fields Options
    **/
    $(document).on('click', '.ppom-add-option', function(e) {

        e.preventDefault();

        var main_wrapper = $(this).closest('.ppom-slider');
        var ppom_option_type = 'ppom_new_option';

        var li = $(this).closest('li');
        var ul = li.closest('ul');
        var clone_item = li.clone();

        clone_item.find(ul).end().appendTo(ul);

        var option_index = parseInt(ul.find('#ppom-meta-opt-index').val());
        ul.find('#ppom-meta-opt-index').val(option_index + 1);
        // console.log(option_index);

        var field_index = main_wrapper.find('.ppom-fields-actions').attr('data-field-no');
        var option_selector = clone_item.find('.ppom-option-keys');
        var option_controller = clone_item.find('.ppom-fields-option');

        ppom_option_controller(option_controller, field_index, option_index, ppom_option_type);
        ppom_create_option_index(option_selector, field_index, option_index, ppom_option_type);

        // $('.ppom-slider').find('.data-options:not(:last) .ppom-add-option')
        // .removeClass('ppom-add-option').addClass('ppom-remove-option')
        // .removeClass('btn-success').addClass('btn-danger')
        // .html('<i class="fa fa-minus" aria-hidden="true"></i>');
    }).on('click', '.ppom-remove-option', function(e) {

        var selector_btn = $(this).closest('.ppom-slider');
        var option_num = selector_btn.find('.data-options').length;

        if (option_num > 1) {
            $(this).parents('.data-options:first').remove();
        }
        else {
            alert( ppom_vars.i18n.cannotRemoveMoreOption );
        }

        e.preventDefault();
        return false;
    });


    /**
        20- Auto Generate Option IDs
    **/
    $(document).on('keyup', '.option-title', function() {

        var closes_id = $(this).closest('li').find('.option-id');
        var option_id = $(this).val().replace(/[^A-Z0-9]/ig, "_");
        option_id = option_id.toLowerCase();
        $(closes_id).val(option_id);
    });


    /**
        21- Create Field data_name By Thier Title
    **/
    $(document).on('keyup', '[data-meta-id="title"] input[type="text"]', function() {

        /**
         * If auto generated data name starts with the "_", that causes the order item meta is recognized as
         * "hidden" in {prefix}_woocommerce_order_ittemmeta table order_by WC Core. To prevent that, add "f" char
         * to beginning of the dataname as auto.
         *
         * With the prefix, all fields that will be created from now on; will be shown on order thank you page anymore.
         *
         * "f" is a randomly chosen character.
         */
        const START_CHAR_DISALLOW_BEING_HIDDEN_FIELD = 'f';

        var $this = $(this);
        var field_id = $this.val().toLowerCase().replace(/[^A-Za-z\d]/g, '_');

        field_id = field_id.charAt(0) === '_' ? `${START_CHAR_DISALLOW_BEING_HIDDEN_FIELD}${field_id}` : field_id;

        var selector = $this.closest('.ppom-slider');

        var wp_field = selector.find('.ppom-fields-actions').attr('data-table-id');
        if (wp_field == 'shipping_fields' || wp_field == 'billing_fields') {
            return;
        }
        if ( true === lockedDataName ) {
            return;
        }
        selector.find('[data-meta-id="data_name"] input[type="text"]:not([readonly])').val(field_id);
    });


    /**
        22- Fields Sortable
    **/
    function insertAt(parent, element, index, dir) {
        var el = parent.children().eq(index);

        element[dir == 'top' ? 'insertBefore' : 'insertAfter'](el);
    }
    $(".ppom_field_table tbody").sortable({
        stop: function(evt, ui) {

            let parent = $('.ppom_save_fields_model'),
                el = parent.find('.' + ui.item.attr('id')),
                dir = 'top';
            if (ui.offset.top > ui.originalPosition.top) {
                dir = 'bottom';
            }
            insertAt(parent, el, ui.item.index(), dir);
        }
    });


    /**
        23- Fields Option Sortable
    **/
    $(".ppom-options-sortable").sortable();

    $("ul.ppom-options-container").sortable({
        revert: true
    });


    /**
        24- Fields Dataname Must Be Required
    **/
    function ppom_required_data_name($this, context) {
        var selector = $this.closest('.ppom-slider');
        var data_name = selector.find('[data-meta-id="data_name"] input[type="text"]').val();
        var savedDataName = selector.find('[data-metatype="data_name"]').val();
        var allDataName = $(document).find( 'table.ppom_field_table td.ppom_meta_field_id' ).map(function(){
            var metaFieldId = $.trim(jQuery(this).text());
            if ( $this.hasClass( 'ppom-update-field' ) && data_name === metaFieldId ) {
                return '';
            }
            return metaFieldId;
        }).get();
        if (data_name == '') {
            var msg = ppom_vars.i18n.dataNameRequired;
            var is_ok = false;
        } else if (('new'===context || ( 'update'===context && savedDataName !== data_name ) ) && $.inArray(data_name, allDataName) != -1) {
            var msg = ppom_vars.i18n.dataNameExists;
            var is_ok = false;
        }
        else {
            msg = '';
            is_ok = true;
        }
        selector.find('.ppom-req-field-id').html(msg);
        return is_ok;
    }


    /**
        WP Color Picker Controller
    **/
    function ppom_wp_color_handler(wpcolor_selector, field_index, option_index) {

        wpcolor_selector.each(function(i, meta_field) {
            var color_picker_input = $(meta_field).find('.ppom-color-picker-init').clone();
            $(meta_field).html(color_picker_input);
            color_picker_input.wpColorPicker();
        });
    }


    /**
        25- Fields Add Option Index Controle Funtion
    **/
    function ppom_create_option_index(option_selector, field_index, option_index, ppom_option_type) {

        option_selector.each(function(i, meta_field) {


            if (ppom_option_type == 'ppom_copy_option') {
                var opt_in = $(meta_field).attr('data-opt-index');
                if (opt_in !== undefined) {
                    option_index = opt_in;
                }
            }
            $(meta_field).attr('data-opt-index', option_index);


            var field_name = 'ppom[' + field_index + '][options][' + option_index + '][' + $(meta_field).attr('data-metatype') + ']';
            $(meta_field).attr('name', field_name);
        });
    }


    function ppom_option_controller(option_selector, field_index, option_index, ppom_option_type) {

        option_selector.each(function(i, meta_field) {

            // console.log(ppom_option_type);
            if (ppom_option_type == 'ppom_copy_option') {
                var opt_in = $(meta_field).attr('data-opt-index');
                if (opt_in !== undefined) {
                    option_index = opt_in;
                }
            }
            $(meta_field).attr('data-opt-index', option_index);


            var field_name = 'ppom[' + field_index + '][' + $(meta_field).attr('data-optiontype') + '][' + option_index + '][' + $(meta_field).attr('data-metatype') + ']';
            $(meta_field).attr('name', field_name);
        });
    }


    /**
        26- Fields Add Condition Index Controle Function
    **/
    function ppom_add_condition_set_index(add_c_selector, opt_field_no, field_type, opt_no) {
        add_c_selector.each(function(i, meta_field) {
            // var field_name = 'ppom['+field_no+']['+$(meta_field).attr('data-metatype')+']';
            var field_name = 'ppom[' + opt_field_no + '][conditions][rules][' + opt_no + '][' + $(meta_field).attr('data-metatype') + ']';
            $(meta_field).attr('name', field_name);
        });
    }

    // address addon
    function ppom_create_address_index(address_selector, field_index, address_table_id) {
        address_selector.each(function(i, meta_field) {
            var field_id = $(meta_field).attr('data-fieldtype');
            var core_field_type = $(address_table_id).attr('data-addresstype');
            var field_name = 'ppom[' + field_index + '][' + core_field_type + '][' + field_id + '][' + $(meta_field).attr('data-metatype') + ']';
            $(meta_field).attr('name', field_name);
        });
    }


    // eventcalendar inputs changing
    function ppom_eventcalendar_set_index(add_c_selector, opt_field_no) {

        add_c_selector.each(function(i, meta_field) {

            var date = $(meta_field).attr('data-date');
            var field_name = 'ppom[' + opt_field_no + '][calendar][' + date + '][' + $(meta_field).attr('data-metatype') + ']';
            $(meta_field).attr('name', field_name);
        });
    }

    // Rename rules name.
    function ppom_rename_rules_name() {
        $('.webcontact-rules:visible' ).each(function( index, item ){
            $(this).attr('id', 'rule-box-' + parseInt(index + 1))
            .find('label')
            .text(function(i,txt) {
                return txt.replace(/\d+/, parseInt(index + 1));
            });
        });
    }

    /**
     * Filter the operator options list based on the target type field.
     * 
     * @param {string?} fieldType The PPOM field type.
     * @param {HTMLSelectElement} operatorSelectField The select input for condition operator.
     * @returns 
     */
    function toggleOperatorFieldByTargetType( fieldType, operatorSelectField ) {
        if ( ! operatorSelectField ) {
            return;
        }

        let shouldHideSelectInput = true;
        const currentValue = operatorSelectField?.value;

        operatorSelectField.querySelectorAll('optgroup').forEach( optgroup => {
            let shouldHideGroup = true;
            optgroup.querySelectorAll('option').forEach( option => {
                const isAvailable = option?.value && OPERATORS_FIELD_COMPATIBILITY[option.value] ? OPERATORS_FIELD_COMPATIBILITY[option.value].includes(fieldType) : option?.value;
                if ( shouldHideGroup && isAvailable ) {
                    shouldHideGroup = false;
                }

                if ( option.value === currentValue && !isAvailable ) {
                    operatorSelectField.value = 'any'; // NOTE: Default to 'any' if the current select value is unavailable.
                }

                option.classList.toggle('ppom-hide-element', !isAvailable );
            });

            if ( ! shouldHideGroup ) {
                shouldHideSelectInput = false;
            }

            optgroup.classList.toggle( 'ppom-hide-element', shouldHideGroup );
        });

        operatorSelectField.classList.toggle( 'ppom-invisible-element', shouldHideSelectInput );
        tryToggleConditionInputFields( operatorSelectField );
    }

    /**
        27- Refresh the condition comparison option list for PPOM field target that are of type select.
    **/
    function updateTargetComparisonValueSelect( targetSelect, conditionContainer, initialSelectedValue ) {
        /** @type {string?} */
        const targetElementNameToPullOptions = targetSelect.value;

        /** @type {HTMLDivElement?} */
        conditionContainer ??= targetSelect.closest('.webcontact-rules');
        const targetSelectOptions = conditionContainer?.querySelector('select[data-metatype="element_values"]');

        if ( !conditionContainer || !targetSelectOptions ) {
            return;
        }

        document.querySelectorAll('.ppom-slider').forEach(sliderItem => {

            const targetElementFieldId = sliderItem.querySelector('input[data-metatype="data_name"]')?.value;
            if ( targetElementFieldId !== targetElementNameToPullOptions ) {
                return;
            }

            const operatorsInput = conditionContainer.querySelector('[data-metatype="operators"]');
            if ( ! operatorsInput ) {
                return;
            }
            
            // Reset the options lists based on the new selection.
            const newOptions = [];

            sliderItem.querySelectorAll('.data-options').forEach(/** @type {HTMLDivElement} */conditionValueContainer => {
                const condition_type = conditionValueContainer.getAttribute('data-condition-type');

                const conditionValueId = conditionValueContainer
                    .querySelector(
                        condition_type === 'simple_options' 
                        ? 'input[data-metatype="option"]' 
                        : '.ppom-image-option-title'
                    )?.value?.trim();

                if ( ! conditionValueId ) {
                    return;
                }

                const optionElement = document.createElement('option');
                optionElement.value = ppom_escape_html(conditionValueId);
                optionElement.textContent = conditionValueId;
                
                newOptions.push( optionElement );
            });
            targetSelectOptions.replaceChildren(...newOptions);
        });

        if ( initialSelectedValue ) {
            targetSelectOptions.value = initialSelectedValue;
        }
    }
    /**
     * Toggle the visibility for input fields type based on operator current value.
     * 
     * @param {HTMLSelectElement?} conditionOperatorInput 
     * @returns 
     */
    function tryToggleConditionInputFields( conditionOperatorInput ) {
        if ( ! conditionOperatorInput ) {
            return;
        }

        const selectedOperator = conditionOperatorInput?.value;

         /**
         * @type {HTMLDivElement|null}
         */
        const container = conditionOperatorInput?.closest('.webcontact-rules');
        if ( !container) {
            return;
        }

        /**
         * @type {HTMLSelectElement|null}
         */
        const conditionTargetSelectOptionsInput = container.querySelector( 'select[data-metatype="element_values"]' );

        /**
         * @type {HTMLInputElement|null}
         */
        const conditionConstantInput = container.querySelector( '[data-metatype="element_constant"]' );

        /**
         * @type {HTMLSelectElement|null}
         */
        const conditionTargetSelectInput = container.querySelector( '[data-metatype="elements"]' );
        if ( !conditionConstantInput || !conditionTargetSelectInput ) {
            return;
        }
   
        /**
         * @type {HTMLDivElement|null}
         */
        const betweenInputs = container.querySelector('.ppom-between-input-container');
       
        let shouldHideSelectInput = false;
        let shouldHideTextInput = false;
        let shouldHideBetweenInputs = false;
        let shouldHideUpsell = true;
        
        if ( proOperatorOptionsToLock.has( selectedOperator ) ) {
            shouldHideSelectInput = true;
            shouldHideTextInput = true;
            shouldHideBetweenInputs = true;
            shouldHideUpsell = false;
        }
        else if ( 'between' === selectedOperator ) {
            shouldHideSelectInput = true;
            shouldHideTextInput = true;
            shouldHideBetweenInputs = false;
        }
        else if ( HIDE_COMPARISON_INPUT_FIELD.includes( selectedOperator ) ) {
            shouldHideSelectInput = true;
            shouldHideTextInput = true;
            shouldHideBetweenInputs = true;
        } else {
            shouldHideSelectInput = true;
            shouldHideBetweenInputs = true;

            /**
             * @type {HTMLOptionElement|null}
             */
            const targetFieldTypeInput = conditionTargetSelectInput.querySelector(`option[value="${conditionTargetSelectInput.value}"]`);
            if (
                conditionTargetSelectOptionsInput &&
                COMPARISON_VALUE_CAN_USE_SELECT.includes( selectedOperator ) &&
                targetFieldTypeInput?.dataset?.fieldtype &&
                OPERATOR_COMPARISON_VALUE_FIELD_TYPE['select'].includes( targetFieldTypeInput.dataset.fieldtype )
            ) {
                shouldHideTextInput = true;
                shouldHideSelectInput = false;
            }
        }

        if ( shouldHideSelectInput && shouldHideTextInput && shouldHideBetweenInputs && shouldHideUpsell ) {
            conditionConstantInput.parentNode?.classList.add('ppom-invisible-element'); // NOTE: Make the entire container visible to preserve the space.
        } else {
            conditionTargetSelectOptionsInput?.classList.toggle("ppom-hide-element", shouldHideSelectInput );
            conditionConstantInput.classList.toggle("ppom-hide-element", shouldHideTextInput );
            betweenInputs?.classList.toggle("ppom-hide-element", shouldHideBetweenInputs );
            container.querySelector('.ppom-upsell-condition')?.classList.toggle("ppom-hide-element", shouldHideUpsell);

            conditionConstantInput.parentNode?.classList.remove('ppom-invisible-element');
        }
    }
     
     // Apply actions on initialization based on operator value.
    document.querySelectorAll('select[data-metatype="operators"]').forEach( conditionOperatorInput => {
        tryToggleConditionInputFields( conditionOperatorInput );
    });

    // Apply actions when operator value changes.
    document.addEventListener('change', function (e) {
        if ( ! e.target.matches('select[data-metatype="operators"]') ) {
            return;
        }
       
        e.preventDefault();
        tryToggleConditionInputFields( e.target );
    });

    $(document).on('change', '[data-meta-id="conditions"] select[data-metatype="element_values"]', function(e) {
        e.preventDefault();

        var element_values = $(this).val();
        $(this).attr('data-existingvalue', element_values);
    });

    $(document).on('click', '.ppom-condition-tab-js', function(e) {
        e.preventDefault();
        populate_conditional_elements();
    });

    /**
     * Populate the condition target select with eligible options based on the operator.
     * 
     * @param {HTMLSelectElement?} selectInput 
     * @param {string?} conditionOperator
     * @param {string[]} excludeIds
     * @returns 
     */
    function populate_condition_target( selectInput, conditionOperator, excludeIds = [] ) {
        if ( !selectInput ) {
            return;
        }

        const newOptions = availableConditionTargets
            .filter( ({ fieldId, canUse }) => canUse && !excludeIds.includes( fieldId) )
            .map( target => {
               
                const option = document.createElement('option');
                option.value = target.fieldId;
                option.textContent = target.fieldLabel;
                option.dataset.fieldtype = target.fieldType;
                
                return option;
            });

        selectInput.replaceChildren( ...newOptions );
    }

    function findFieldTypeById( fieldId ) {
        if ( !fieldId ) {
            return undefined;
        }

        for ( const target of availableConditionTargets ) {
            if ( target.fieldId === fieldId ) {
                return target.fieldType;
            }
        }

        return undefined;
    }

    function can_use_field_type( fieldType ) {
        if ( ! fieldType?.length ) {
            return false;
        }

        for ( const operatorCompatibleFields of Object.values( OPERATORS_FIELD_COMPATIBILITY ) ) {
            if ( operatorCompatibleFields.includes( fieldType ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Populate the condition target select with eligible options based on the operator on initialization and value change.
     * 
     */
    function populate_conditional_elements() {

        // Get all available PPOM fields.
        availableConditionTargets.splice(0, availableConditionTargets.length);
        document.querySelectorAll(".ppom-slider").forEach(item => {
            const fieldLabel = item.querySelector('input[data-metatype="title"]')?.value;
            const fieldId = item.querySelector('input[data-metatype="data_name"]')?.value?.trim();
            const fieldType = item.querySelector('input[data-metatype="type"]')?.value;
            const canUse = can_use_field_type( fieldType );

            if ( !fieldLabel || !fieldId || !fieldType ) {
                return;
            }

            availableConditionTargets.push({ fieldLabel, fieldId, fieldType, canUse });
        });
      
        // Change the target options for all the rules.
        document.querySelectorAll(".ppom-slider").forEach(item => {
            if ( ! item.id ) {
                return;
            }
            const conditionContainers = item.querySelector('div[data-meta-id="conditions"]')?.querySelectorAll('.webcontact-rules');
            
            conditionContainers?.forEach(conditionContainer => {
                const conditionTargetsSelect = conditionContainer.querySelector('[data-metatype="elements"]');
                if ( ! conditionTargetsSelect ) {
                    return;
                }

                const conditionOperatorSelect = conditionContainer.querySelector('[data-metatype="operators"]');
                const fieldId = item.querySelector('input[data-metatype="data_name"]')?.value?.trim();

                populate_condition_target( conditionTargetsSelect, conditionOperatorSelect?.value, [fieldId] );

                if ( conditionTargetsSelect?.dataset?.existingvalue ) {
                    conditionTargetsSelect.value = conditionTargetsSelect?.dataset?.existingvalue;
                }
                
                // NOTE: Get all the locked operators. Unlock them to be eligible to show the upsell.
                conditionOperatorSelect?.querySelectorAll( 'option' ).forEach( option => {
                    if ( ! option.disabled ) {
                        return;
                    }

                    proOperatorOptionsToLock.add( option.value );
                    option.disabled = false;
                });
               
                toggleOperatorFieldByTargetType( findFieldTypeById( conditionTargetsSelect?.value ), conditionOperatorSelect  ); 
                
                const optionsInput = conditionContainer.querySelector('[data-metatype="element_values"]');

                updateTargetComparisonValueSelect( 
                    conditionTargetsSelect,
                    conditionContainer,
                    optionsInput?.dataset?.existingvalue
                );
            });
        });
    }

    /**
     * Update the values of the operators selector and the comparison fields.
     * 
     * NOTE: We are using a global listener since some node are dinamically created/cloned.
     */
    document.addEventListener('change', function(e) {
        if ( ! e.target.matches('select[data-metatype="elements"]') ) {
            return;
        }

        e.preventDefault();
        const conditionContainer = e.target.closest('.webcontact-rules');
        const conditionOperatorSelect = conditionContainer?.querySelector('[data-metatype="operators"]');
        if ( ! conditionContainer || ! conditionOperatorSelect ) {
            return;
        }
        
        toggleOperatorFieldByTargetType( findFieldTypeById(e.target?.value), conditionOperatorSelect  );
        updateTargetComparisonValueSelect( e.target, conditionContainer );

        const optionsInput = conditionContainer.querySelector('[data-metatype="element_values"]');
        const constantInput = conditionContainer.querySelector('[data-metatype="element_constant"]');
        
        // Reset values.
        if ( constantInput ) {
            constantInput.value = '';
        }

        if ( optionsInput ) {
            optionsInput.value = '';
        }
    });
    
    /**
        28- validate API WooCommerce Product
    **/
    function validate_api_wooproduct(form) {

        jQuery(form).find("#nm-sending-api").html(
            '<img src="' + nm_personalizedproduct_vars.doing + '">');

        var data = jQuery(form).serialize();
        data = data + '&action=nm_personalizedproduct_validate_api';

        jQuery.post(ajaxurl, data, function(resp) {

            //console.log(resp);
            jQuery(form).find("#nm-sending-api").html(resp.message);
            if (resp.status == 'success') {
                window.location.reload(true);
            }
        }, 'json');


        return false;
    }


    function ppom_escape_html(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    $(document).on('ppom_fields_tab_changed', 'div.row.ppom-tabs', (e, id, tab)=>{
        if( ppom_vars.i18n.freemiumCFRTab !== id ) {
            return;
        }

        if( tab.find('.freemium-cfr-content').length > 0 ) {
            return;
        }

        $(`<div class="form-group">${ppom_vars.i18n.freemiumCFRContent}</div>`).insertAfter( tab.find('.form-group') );
    });

    const toggleHandler = {
        setDisabledFields: function(jQueryDP){
            const on = jQueryDP.is(':checked');
            const slider = jQueryDP.parents('.ppom-slider');

            const JQUERY_DP_FIELD_MTYPES = [
                'min_date',
                'max_date',
                'date_formats',
                'default_value',
                'first_day_of_week',
                'year_range',
                'no_weekends',
                'past_dates'
            ];

            JQUERY_DP_FIELD_MTYPES.forEach(function(type){
                slider.find('*[data-metatype="'+type+'"]').prop('disabled', !on);
            });
        },
        activateHandler: function() {
            toggleHandler.setDisabledFields($(this));
        }
    }

    $('input[data-metatype="jquery_dp"]').change(toggleHandler.activateHandler);

    $( document ).on( 'ppom_new_field_created', function(e, clone_new_field) {
        $(clone_new_field).find('input[data-metatype="jquery_dp"]').change(toggleHandler.activateHandler);
    } );

    // Unsaved form exit confirmation.
    var unsaved = false;
    $( '.ppom-main-field-wrapper :input' ).change(function () {
        if ( $( this ).parents( '.ppom-checkboxe-style' )?.length > 0 ) {
            unsaved = false;
            return;
        }    
        unsaved = true;
    });
    $( document ).on( 'click', '.ppom-submit-btn input.btn, button.ppom_copy_field, button.ppom-add-fields-js-action, button.ppom-js-modal-close', function() {
        if ( $(this).hasClass('ppom_copy_field') || $(this).hasClass( 'ppom-add-fields-js-action' ) ) {
            unsaved = true;
            return;
        }
        unsaved = false;
    } );
    window.addEventListener( 'beforeunload', function( e ) {
        if ( unsaved ) {
          e.preventDefault();
          e.returnValue = '';
      }
    });

    $( document ).on( 'ppom_fields_tab_changed', function(e, id, tab) {
        if ( 'condition_tab' !== id ) {
            return;
        }

        if ( ! $('input[data-metatype="logic"]', tab?.first() )?.is(':checked') ) {
            tab?.last()?.addClass( 'ppom-disabled-overlay' );
        }
    } );

    $( document ).on( 'change', 'input[data-metatype="logic"]:visible', function() {
        $(this)
        .parents('.ppom_handle_condition_tab')
        .next('.ppom_handle_condition_tab')
        .toggleClass('ppom-disabled-overlay');
    } );

    $( document ).on( 'click', 'button.ppom-edit-field.ppom-is-pro-field, button.ppom_copy_field.ppom-is-pro-field', function() {
        $('#ppom-lock-fields-upsell').fadeIn();
       return false;
    } );

    $(document).ready(function(){
        $('.ppom-slider').each(function(i, item){
            const itemEl = $(item);
            const value = itemEl.find(
                'input[data-metatype="data_name"]').val();

            const type = itemEl.find('input[data-metatype="type"]').val();

            if( $.trim( value ) === '' || type !== 'date' ) {
                return;
            }

            toggleHandler.setDisabledFields(itemEl.find('input[data-metatype="jquery_dp"]'));
        });
    })
    $(document).on('click', '.postbox-header', function() {
        var postbox = $(this).closest('.postbox');
        postbox.toggleClass('closed');
    });
});
document.querySelectorAll('.ppom-modal-shortcuts a')?.forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        // Get the href attribute and use it as the ID to display the corresponding section
        const targetId = this.getAttribute('href');
        if (targetId === '#all') {
            // Show all sections
            document.querySelectorAll('.ppom-fields-section').forEach(section => {
                section.style.display = 'block';
            });
        } else {
            // Hide all sections with the class 'ppom-fields-section'
            document.querySelectorAll('.ppom-fields-section').forEach(section => {
                section.style.display = 'none';
            });

            const targetSection = document.querySelector(targetId + '-ppom-fields');
            if (targetSection) {
                targetSection.style.display = 'block';
            }
        }
    });
});
/**
 * Search Field for Add Field modal.
 */
document.querySelector('input[name="ppom-search-field"]')?.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    const sections = document.querySelectorAll('.ppom-fields-section');

    sections.forEach(section => {
        const buttons = section.querySelectorAll('.ppom-field-item');
        let hasVisibleButton = false;

        buttons.forEach(button => {
            const text = button.textContent.toLowerCase();

            if ( text.includes( query ) ) {
                button.style.display = 'flex';
                hasVisibleButton = true;
            } else {
                button.style.display = 'none';
            }
        });

        // If no buttons in this section are visible, hide the section
        if ( hasVisibleButton ) {
            section.style.display = 'flex';
        } else {
            section.style.display = 'none';
        }
    });
});