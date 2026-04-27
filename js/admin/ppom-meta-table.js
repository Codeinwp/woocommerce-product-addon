'use strict';

/**
 * Admin list screen actions for saved PPOM field groups.
 *
 * This file wires DataTables, bulk actions, and the "attach to products" modal
 * around the server-side group list rendered by PHP.
 *
 * @see window.ppomPopup in js/popup.js
 */
jQuery( function ( $ ) {
	/**
	 * Build a Select2 AJAX config for a taxonomy search endpoint.
	 *
	 * @param {string} action    The AJAX action name.
	 * @param {string} nonce     The nonce value.
	 * @return {Object} Select2 ajax config.
	 */
	function taxonomyAjaxConfig( action, nonce ) {
		return {
			url: ajaxurl,
			dataType: 'json',
			delay: 300,
			cache: true,
			data: function ( params ) {
				return {
					action: action,
					ppom_attached_nonce: nonce,
					q: params.term || '',
					page: params.page || 1,
				};
			},
			processResults: function ( data ) {
				return {
					results: Array.isArray( data?.results )
						? data.results
						: [],
					pagination: {
						more: Boolean( data?.pagination?.more ),
					},
				};
			},
		};
	}

	/**
	 * Initialise a single Select2 search dropdown.
	 *
	 * @param {jQuery}           $el          Select element.
	 * @param {string}           nonce        Attach nonce value.
	 * @param {string}           placeholder  Placeholder text.
	 * @param {string}           action       AJAX action name (or override key).
	 * @param {string}           fallback     Default action when override is empty.
	 * @param {jQuery|undefined} dropdownParent Optional parent for the dropdown.
	 * @return {void}
	 */
	function initSelect2Search( $el, nonce, placeholder, action, fallback, dropdownParent ) {
		const isLocked = $el.closest( '.ppom-fields-status-disabled' ).length > 0;

		const config = {
			width: '100%',
			closeOnSelect: false,
			minimumInputLength: 0,
			placeholder,
			ajax: taxonomyAjaxConfig( action || fallback, nonce ),
		};

		if ( dropdownParent ) {
			config.dropdownParent = dropdownParent;
		}

		$el.select2( config );

		if ( isLocked ) {
			$el.prop( 'disabled', true ).trigger( 'change' );
		}
	}

	/**
	 * Initialize Select2 on the product, category, and tag search dropdowns
	 * in the "attach to products" modal.
	 *
	 * @return {void}
	 */
	function initAttachSelects() {
		const productSelect = $( '#attach-to-products .ppom-attach' );
		const variationSelect = $( '#attach-to-variations .ppom-attach' );
		const categorySelect = $( '#attach-to-categories .ppom-attach' );
		const tagSelect = $( '#attach-to-tags .ppom-attach' );
		const attachNonce = $( '#ppom-product-form [name="ppom_attached_nonce"]' )
			.val();
		const parent = $( '#ppom-product-modal' );
		const vars = window?.ppom_vars?.attach;

		if ( typeof $.fn.select2 !== 'function' ) {
			return;
		}

		initSelect2Search( productSelect, attachNonce, vars?.productsPlaceholder, vars?.searchAction, 'ppom_search_products', parent );
		initSelect2Search( variationSelect, attachNonce, vars?.variationsPlaceholder, vars?.searchVariationsAction, 'ppom_search_variations', parent );
		initSelect2Search( categorySelect, attachNonce, vars?.categoriesPlaceholder, vars?.searchCategoriesAction, 'ppom_search_categories', parent );
		initSelect2Search( tagSelect, attachNonce, vars?.tagsPlaceholder, vars?.searchTagsAction, 'ppom_search_tags', parent );
	}

	// Initialize Select2 on inline attach selects (field-group editor page).
	( function initInlineAttachSelects() {
		const $container = $( '.ppom-inline-attach-container' );
		if ( ! $container.length || typeof $.fn.select2 !== 'function' ) {
			return;
		}

		const attachNonce = $container.find( '[name="ppom_attached_nonce"]' ).val();
		const productSelect = $container.find( '#attach-to-products .ppom-attach' );
		const variationSelect = $container.find( '#attach-to-variations .ppom-attach' );
		const categorySelect = $container.find( '#attach-to-categories .ppom-attach' );
		const tagSelect = $container.find( '#attach-to-tags .ppom-attach' );
		const vars = window?.ppom_vars?.attach;

		initSelect2Search( productSelect, attachNonce, vars?.productsPlaceholder, vars?.searchAction, 'ppom_search_products' );
		initSelect2Search( variationSelect, attachNonce, vars?.variationsPlaceholder, vars?.searchVariationsAction, 'ppom_search_variations' );
		initSelect2Search( categorySelect, attachNonce, vars?.categoriesPlaceholder, vars?.searchCategoriesAction, 'ppom_search_categories' );
		initSelect2Search( tagSelect, attachNonce, vars?.tagsPlaceholder, vars?.searchTagsAction, 'ppom_search_tags' );
	} )();

	// DataTables provides the searchable/sortable shell, while PPOM injects its
	// own action toolbar into the custom `ppom-toolbar` slot defined in `dom`.
	$( '#ppom-meta-table' ).DataTable( {
		pageLength: 50,
		dom: 'f<"ppom-toolbar"><"top">rt<"bottom">lpi',
	} );
	const append_overlay_modal =
		"<div class='ppom-modal-overlay ppom-js-modal-close'></div>";

	// Bulk delete is confirmation-driven because it removes saved field groups,
	// not just the rows in the current DataTable view.
	/**
	 * Delete multiple saved PPOM groups after an explicit confirmation step.
	 *
	 * @param {number[]} checkedProducts_ids
	 * @return {void}
	 */
	function deleteSelectedProducts( checkedProducts_ids ) {
		window?.ppomPopup?.open( {
			title: window?.ppom_vars?.i18n.popup.confirmTitle,
			onConfirmation: () => {
				$( '#ppom_delete_selected_products_btn' ).html( 'Deleting...' );

				const data = {
					action: 'ppom_delete_selected_meta',
					productmeta_ids: checkedProducts_ids,
					ppom_meta_nonce: $( '#ppom_meta_nonce' ).val(),
				};

				$.post( ajaxurl, data, function ( resp ) {
					$( '#ppom_delete_selected_products_btn' ).html( 'Delete' );
					if ( resp ) {
						window?.ppomPopup?.open( {
							title: window?.ppom_vars?.i18n.popup.finishTitle,
							hideCloseBtn: true,
							onConfirmation: () => location.reload(),
							onClose: () => location.reload(),
						} );
					} else {
						window?.ppomPopup?.open( {
							title: window.ppom_vars.i18n.popup.errorTitle,
							text: resp,
							hideCloseBtn: true,
						} );
					}
				} );
			},
		} );
	}

	$( '.ppom_product_checkbox' ).on( 'click', function ( event ) {
		const checkboxProducts = $( '.ppom_product_checkbox' )
			.map( function () {
				return this.value;
			} )
			.get();

		const checkedProducts = $( '.ppom_product_checkbox:checked' )
			.map( function () {
				return this.value;
			} )
			.get();

		if ( checkboxProducts.length == checkedProducts.length ) {
			$(
				'#ppom-all-select-products-head-btn, #ppom-all-select-products-foot-btn'
			).prop( 'checked', true );
		} else {
			$(
				'#ppom-all-select-products-head-btn, #ppom-all-select-products-foot-btn'
			).prop( 'checked', false );
		}

		$( '#selected_products_count' ).html();
		$( '#selected_products_count' ).html( checkedProducts.length );
	} );
	$(
		'#ppom-all-select-products-head-btn, #ppom-all-select-products-foot-btn'
	).on( 'click', function ( event ) {
		$( '#ppom-meta-table input:checkbox' )
			.not( this )
			.prop( 'checked', this.checked );
		const checkedProducts = $( '.ppom_product_checkbox:checked' )
			.map( function () {
				return this.value;
			} )
			.get();
		$( '#selected_products_count' ).html();
		$( '#selected_products_count' ).html( checkedProducts.length );
	} );

	// Load the product-assignment UI lazily so the heavy modal table is fetched
	// only when the merchant asks to attach a group to products.
	$( '#ppom-meta-table_wrapper, .ppom-basic-setting-section' ).on(
		'click',
		'a.ppom-products-modal',
		function ( e ) {
			e.preventDefault();

			$( '.ppom-table' ).DataTable();
			const ppom_id = $( this ).data( 'ppom_id' );
			const get_url =
				ajaxurl + '?action=ppom_get_products&ppom_id=' + ppom_id;
			const model_id = $( this ).attr( 'data-formmodal-id' );

			$.get( get_url, function ( html ) {
				$( '#ppom-product-modal .ppom-modal-body' ).html( html );
				initAttachSelects();
				$( '#ppom_id' ).val( ppom_id );
				$( 'body' ).append( append_overlay_modal );
				$( '#' + model_id ).fadeIn( function () {
					const productSelect = $( '#attach-to-products .ppom-attach' );

					if ( productSelect.length ) {
						productSelect.select2( 'open' );
					}
				} );
			} );
		}
	);

	$( 'body' ).on( 'click', 'a.ppom-delete-single-product', function ( e ) {
		e.preventDefault();
		const productmeta_id = $( this ).attr( 'data-product-id' );

		window?.ppomPopup?.open( {
			title: window?.ppom_vars?.i18n.popup.confirmTitle,
			onConfirmation: () => {
				$( '#del-file-' + productmeta_id ).html(
					'<img src="' + ppom_vars.loader + '">'
				);

				const data = {
					action: 'ppom_delete_meta',
					productmeta_id,
					ppom_meta_nonce: $( '#ppom_meta_nonce' ).val(),
				};

				$.post( ajaxurl, data, function ( resp ) {
					$( '#del-file-' + productmeta_id ).html(
						'<span class="dashicons dashicons-no"></span>'
					);
					if ( resp.status === 'success' ) {
						window?.ppomPopup?.open( {
							title: window?.ppom_vars?.i18n.popup.finishTitle,
							hideCloseBtn: true,
							onConfirmation: () => location.reload(),
							onClose: () => location.reload(),
						} );
					} else {
						window?.ppomPopup?.open( {
							title: window.ppom_vars.i18n.popup.errorTitle,
							text: resp.message,
							hideCloseBtn: true,
						} );
					}
				} );
			},
		} );
	} );

	$( document ).on( 'change', '#ppom-bulk-actions', function () {
		const type = $( this ).val();

		const checkedProducts_ids = $( '.ppom_product_checkbox:checked' )
			.map( function () {
				return parseInt( this.value );
			} )
			.get();

		if ( ! ( checkedProducts_ids.length > 0 ) ) {
			window?.ppomPopup?.open( {
				title: window?.ppom_vars?.i18n.popup.confirmTitle,
				type: 'error',
				hideCloseBtn: true,
			} );
			return;
		}

		// Only one action runs per selection. Resetting the select afterwards
		// prevents DataTables redraws from accidentally replaying the last action.
		if ( 'delete' === type ) {
			deleteSelectedProducts( checkedProducts_ids );
		} else if ( 'export' === type ) {
			$( '#ppom-groups-export-form' ).submit();
		}

		$( this ).val( -1 );
	} );

	// Import/export are always surfaced in the toolbar so the locked Pro state
	// is visible even on Free installs; the markup changes between enabled and
	// disabled variants based on the localized license flag.
	const exportOption =
		ppom_vars.ppomProActivated === 'yes'
			? `<option value="export">${ ppom_vars.i18n.exportLabel }</option>`
			: `<option disabled value="export">${ ppom_vars.i18n.exportLockedLabel }</option>`;

	const importBtn = `<a class="btn btn-secondary btn-sm ml-4 ppom-import-export-btn" href=""><span class="dashicons dashicons-${
		ppom_vars.ppomProActivated === 'yes' ? 'download' : 'lock'
	}"></span>${ ppom_vars.i18n.importLabel }</a>`;

	const bulkActions = `<select id="ppom-bulk-actions">
			<option value="-1">${ ppom_vars.i18n.bulkActionsLabel }</option>
			<option value="delete">${ ppom_vars.i18n.deleteLabel }</option>
			${ exportOption }
		</select>`;

	const btn = `<a class="btn btn-success btn-sm float-right mr-4" href="${ ppom_vars.i18n.addGroupUrl }"><span class="dashicons dashicons-plus"></span>${ ppom_vars.i18n.addGroupLabel }</a>`;

	// DataTables creates the placeholder container, then PPOM injects the
	// toolbar HTML after initialization so the controls stay inside the table UI.
	$( 'div.ppom-toolbar' ).html(
		`<div class="">${ bulkActions } ${ importBtn } <span id="ppom-toolbar-extra"></span> ${ btn }</div>`
	);
} );
