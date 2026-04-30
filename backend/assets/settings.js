"use strict";
jQuery(function($) {

    /**
        CSS Loader
    **/
    $("#nmsf-page-loader").hide();
    $("#nmsf-page").show();


    /**
        Submit Form Data
    **/
    $("form#mainform, .nmsf-form-js").submit(function(e) {
        e.preventDefault();

        jQuery('.nmsf-wrapper').block({
            message: null,
            overlayCSS: {
                background: "#fff",
                opacity: .6,
            }
        });

        var data = $(this).serialize();

        $.post(ajaxurl, data, function(resp) {

            jQuery('.nmsf-wrapper').unblock();

            window.createNotification({
                closeOnClick: true,
                displayCloseButton: false,
                positionClass: 'nfc-bottom-right',
                showDuration: 2000,
                theme: resp.status
            })({
                title: '',
                message: resp.message
            });

        }, 'json');

    });


    /**
        WP Colorpicker
    **/
    $('.nmsf-wp-colorpicker').wpColorPicker();


    /**
        Active First Settings Panel 
    **/
    $('.nmsf-panels-content').each(function(index, item) {
        $(item).find('.nmsf-panel-handler:first').prop('checked', true);
    });


    /**
        Settings Panel Tab
    **/
    $(document).on("click", ".nmsf-tabs-content div", function() {
        var tab_id = $(this).attr('data-tab-id');

        if (!$(this).is("active")) {

            $(".nmsf-tabs-content div").removeClass("active");
            $(".nmsf-panels-content").removeClass("active");

            $(this).addClass("active");
            $(".nmsf-panels-area").find("div[data-panel-id=" + tab_id + "]").addClass("active");
        }
    });

    /**
        Add Conditional Settings Fields
    **/
    var ruleset = $.deps.createRuleset();

    $('.nmsf-panel-conditional-field').each(function(index, elem) {
        var conditions = $(elem).attr('data-conditions');
        conditions = JSON.parse(conditions);
        var conditional_rule = ruleset;
        var $this = $(this);

        $.each(conditions, function(index, elements) {

            var element = elements[0];
            var operator = elements[1];
            var input_val = elements[2].join(',');

            conditional_rule = conditional_rule.createRule('[data-rule-id="' + element + '"]', operator, input_val);
            conditional_rule.include($this);
        });
    });

    ruleset.install({ log: false });


    /**
        ToolTip Init
    **/
    $('.nmsf-tooltip').ppom_tooltipster({
        interactive: true,
        theme: 'nmsf_tooltipster-punk',
        tooltipBorderColor: '#32334a',
        tooltipBGColor: '#32334a'
    });


    /**
        Video Popup Init
    **/
    $(".nmsf-ref-video-popup").videoPopup();


    /**
        Select2 Init
    **/
    $('.nmsf-multiselect-js').select2();

    /**
     * Disabled submit button.
     */
    $(document).on('click', '.nmsf-panels-content-inner .nmsf-label', function(){
        var isLocked = $(this).hasClass('ppom-is-locked-section');
        $(this)
        .parents('.nmsf-panels-area')
        .find('.woocommerce-save-button')
        .attr('disabled', isLocked);
    });

    const permissionField = $('#ppom_permission_mfields');

    $(document).ready(function(){
        if( permissionField.val().length === 0 ) {
            permissionField.val(['administrator']);
            permissionField.trigger('change');
        }

        permissionField.on('select2:unselecting', function(e){
            if( typeof e.params.args === 'undefined' ) {
                return;
            }

            const element = $(e.params.args.data.element);
            if( element.prop('value') === 'administrator' ) {
                alert(nmsf_vars.administrator_role_cannot_be_removed);
                e.preventDefault();
            }
        });
    });

    /**
     * Toggle password visibility.
     */
    $(document).on('click', '.ppom-eye-toggle', function () {
        var targetId = $(this).data('target');
        var $input   = $('#' + targetId);
        var reveal   = $input.attr('type') === 'password';
        $input.attr('type', reveal ? 'text' : 'password');
        $(this).find('.dashicons')
            .toggleClass('dashicons-visibility', !reveal)
            .toggleClass('dashicons-hidden',     reveal);
    });

    /**
     * Copy to clipboard and show feedback.
     * @param {object} $btn 
     */
    function ppomCopyFeedback($btn) {
        var $icon = $btn.find('.dashicons');
        if ($icon.length) {
            $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes');
            $btn.addClass('ppom-copied');
            setTimeout(function () {
                $icon.removeClass('dashicons-yes').addClass('dashicons-clipboard');
                $btn.removeClass('ppom-copied');
            }, 2000);
        } else {
            var orig = $btn.text();
            $btn.addClass('ppom-copied').text(nmsf_vars.copied);
            setTimeout(function () { $btn.removeClass('ppom-copied').text(orig); }, 2000);
        }
    }

    /**
     * Execute copy command for the given text.
     *
     * @param {string} text 
     * @returns {boolean}
     */
    function ppomExecCopy(text) {
        var $tmp = $('<textarea>').css({ position: 'fixed', top: 0, left: '-9999px', opacity: 0 }).val(text);
        $('body').append($tmp);
        $tmp[0].focus();
        $tmp[0].select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) {}
        $tmp.remove();
        return ok;
    }

    /**
     * Copy to clipboard on button click with fallback and feedback.
     */
    $(document).on('click', '.ppom-copy-btn', function () {
        var $btn     = $(this);
        var targetId = $btn.data('target');
        var $target  = $('#' + targetId);
        var text     = ($target.is('input') ? $target.val() : $target.text()).trim();
        if (!text) { return; }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text)
                .then(function () { ppomCopyFeedback($btn); })
                .catch(function () { if (ppomExecCopy(text)) { ppomCopyFeedback($btn); } });
        } else if (ppomExecCopy(text)) {
            ppomCopyFeedback($btn);
        }
    });

    /**
     * Build card structure by grouping rows between section headers and adding toggle buttons.
     */
    function ppomBuildCards() {
        $('.nmsf-panel-table.form-table').each(function () {
            let $tbody = $(this).find('> tbody');
            if (!$tbody.length) { $tbody = $(this); }
            const $rows = $tbody.children('tr');
            let firstCard = true;
            $rows.each(function () {
                const $row = $(this);
                const isSectionRow = $row.children('td.nmsf-section-type').length > 0;

                if (isSectionRow) {
                    if (!firstCard) {
                        $('<tr class="ppom-card-spacer-row"><td colspan="2"></td></tr>').insertBefore($row);
                    }
                    firstCard = false;
                }
            });

            /**
             * Add toggle buttons to section header rows and hide body rows by default.
             * Also add a class to header rows without body rows for styling.
             */
            $tbody.find('.ppom-card-header-row').each(function () {
                const $next = $(this).next();
                if (!$next.length || $next.hasClass('ppom-card-header-row') || $next.hasClass('ppom-card-spacer-row')) {
                    $(this).addClass('ppom-card-no-body');
                }
            });
        });
    }

    ppomBuildCards();

    /**
     * Toggle card body rows on header click and update aria-expanded attribute for accessibility.
     */
    $(document).on('click', '.ppom-card-toggle', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var expanded = $btn.attr('aria-expanded') === 'true';
        $btn.attr('aria-expanded', expanded ? 'false' : 'true');
        var $headerRow = $btn.closest('tr');
        $headerRow.nextUntil('.ppom-card-header-row, .ppom-card-spacer-row').toggle(!expanded);
    });

    /**
     * Toggle conditional rule status badge on checkbox change.
     */
    $(document).on('change', 'input[type="checkbox"][data-rule-id]', function () {
        var fieldId = $(this).data('rule-id');
        var $badge  = $('.ppom-status-badge[data-field-id="' + fieldId + '"]');
        if (!$badge.length) { return; }
        var active = $(this).is(':checked');
        $badge
            .removeClass('ppom-badge-active ppom-badge-inactive')
            .addClass(active ? 'ppom-badge-active' : 'ppom-badge-inactive')
            .text(active ? nmsf_vars.active : nmsf_vars.inactive);
    });
});
