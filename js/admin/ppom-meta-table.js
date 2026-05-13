'use strict';

/**
 * Admin list screen actions for saved PPOM field groups.
 *
 * The table itself is rendered server-side by `\PPOM\Admin\MetaGroupsListTable`
 * (a `WP_List_Table` subclass), which handles search, sort, pagination, and
 * bulk-action submission natively. This file only adds the interactions that
 * sit on top: the "attach to products" modal trigger, the per-row AJAX delete,
 * and a confirmation dialog for the Delete bulk action.
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
					results: Array.isArray( data?.results ) ? data.results : [],
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
	function initSelect2Search(
		$el,
		nonce,
		placeholder,
		action,
		fallback,
		dropdownParent
	) {
		const isLocked =
			$el.closest( '.ppom-fields-status-disabled' ).length > 0;

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

		// Clear the typed search keyword after each selection while keeping the dropdown open
		// (closeOnSelect:false) so additional items can be picked without retyping or stale filtering.
		$el.on( 'select2:select', function () {
			const instance = $el.data( 'select2' );
			const $search =
				instance && instance.dropdown && instance.dropdown.$search
					? instance.dropdown.$search
					: ( dropdownParent || $( document.body ) ).find(
							'.select2-search__field'
					  );
			if ( $search && $search.length ) {
				$search.val( '' );
				$search.trigger( 'input.select2' );
			}
		} );

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
		const attachNonce = $(
			'#ppom-product-form [name="ppom_attached_nonce"]'
		).val();
		const parent = $( '#ppom-product-modal' );
		const vars = window?.ppom_vars?.attach;

		if ( typeof $.fn.select2 !== 'function' ) {
			return;
		}

		initSelect2Search(
			productSelect,
			attachNonce,
			vars?.productsPlaceholder,
			vars?.searchAction,
			'ppom_search_products',
			parent
		);
		initSelect2Search(
			variationSelect,
			attachNonce,
			vars?.variationsPlaceholder,
			vars?.searchVariationsAction,
			'ppom_search_variations',
			parent
		);
		initSelect2Search(
			categorySelect,
			attachNonce,
			vars?.categoriesPlaceholder,
			vars?.searchCategoriesAction,
			'ppom_search_categories',
			parent
		);
		initSelect2Search(
			tagSelect,
			attachNonce,
			vars?.tagsPlaceholder,
			vars?.searchTagsAction,
			'ppom_search_tags',
			parent
		);
	}

	// Initialize Select2 on inline attach selects (field-group editor page).
	( function initInlineAttachSelects() {
		const $container = $( '.ppom-inline-attach-container' );
		if ( ! $container.length || typeof $.fn.select2 !== 'function' ) {
			return;
		}

		const attachNonce = $container
			.find( '[name="ppom_attached_nonce"]' )
			.val();
		const productSelect = $container.find(
			'#attach-to-products .ppom-attach'
		);
		const variationSelect = $container.find(
			'#attach-to-variations .ppom-attach'
		);
		const categorySelect = $container.find(
			'#attach-to-categories .ppom-attach'
		);
		const tagSelect = $container.find( '#attach-to-tags .ppom-attach' );
		const vars = window?.ppom_vars?.attach;

		initSelect2Search(
			productSelect,
			attachNonce,
			vars?.productsPlaceholder,
			vars?.searchAction,
			'ppom_search_products'
		);
		initSelect2Search(
			variationSelect,
			attachNonce,
			vars?.variationsPlaceholder,
			vars?.searchVariationsAction,
			'ppom_search_variations'
		);
		initSelect2Search(
			categorySelect,
			attachNonce,
			vars?.categoriesPlaceholder,
			vars?.searchCategoriesAction,
			'ppom_search_categories'
		);
		initSelect2Search(
			tagSelect,
			attachNonce,
			vars?.tagsPlaceholder,
			vars?.searchTagsAction,
			'ppom_search_tags'
		);
	} )();

	const append_overlay_modal =
		"<div class='ppom-modal-overlay ppom-js-modal-close'></div>";

	// Load the product-assignment UI lazily so the heavy modal table is fetched
	// only when the merchant asks to attach a group to products. Delegated from
	// `body` so it works in both the field groups list and the field editor.
	$( 'body' ).on( 'click', 'a.ppom-products-modal', function ( e ) {
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
	} );

	$( 'body' ).on( 'click', 'a.ppom-delete-single-product', function ( e ) {
		e.preventDefault();
		const productmeta_id = $( this ).attr( 'data-product-id' );
		let title = window?.ppom_vars?.i18n.popup.deleteGroup;
		const productName = $( this ).data( 'name' );
		title = productName
			? title.replace( '%s', productName )
			: window?.ppom_vars?.i18n.popup.confirmTitle;

		window?.ppomPopup?.open( {
			title,
			onConfirmation: () => {
				const $link = $( '#del-file-' + productmeta_id );
				const originalHtml = $link.html();
				$link.html( '<img src="' + ppom_vars.loader + '">' );

				const data = {
					action: 'ppom_delete_meta',
					productmeta_id,
					ppom_meta_nonce: $( '#ppom_meta_nonce' ).val(),
				};

				$.post( ajaxurl, data, function ( resp ) {
					if ( resp.status === 'success' ) {
						window?.ppomPopup?.open( {
							title: window?.ppom_vars?.i18n.popup.finishTitle,
							hideCloseBtn: true,
							onConfirmation: () => location.reload(),
							onClose: () => location.reload(),
						} );
					} else {
						$link.html( originalHtml );
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

	// Per-row enable/disable toggle. Sends an AJAX request to flip
	// `productmeta_disabled`; product attachments and field schema are
	// untouched, so this is a pure visibility switch on the frontend.
	$( 'body' ).on(
		'change',
		'.ppom-existing-meta-wrapper .onoffswitch .onoffswitch-checkbox',
		function () {
			const $input = $( this );
			const $wrap = $input.closest( '.onoffswitch' );
			const ppomId = $wrap.data( 'ppomId' );
			const disabled = ! $input.is( ':checked' );

			$wrap.addClass( 'onoffswitch--busy' );
			$input.prop( 'disabled', true );

			$.post(
				ajaxurl,
				{
					action: 'ppom_toggle_meta_disabled',
					productmeta_id: ppomId,
					disabled: disabled ? '1' : '0',
					ppom_meta_nonce: $( '#ppom_meta_nonce' ).val(),
				},
				function ( resp ) {
					$wrap.removeClass( 'onoffswitch--busy' );
					$input.prop( 'disabled', false );

					if ( ! resp || resp.status !== 'success' ) {
						// Roll back the visual state on error.
						$input.prop( 'checked', ! $input.is( ':checked' ) );
						const message =
							resp && resp.message
								? resp.message
								: window?.ppom_vars?.i18n?.popup?.errorTitle ??
								  'Error';
						window?.ppomPopup?.open( {
							title: message,
							hideCloseBtn: true,
						} );
					}
				}
			).fail( function () {
				$wrap.removeClass( 'onoffswitch--busy' );
				$input.prop( 'disabled', false );
				$input.prop( 'checked', ! $input.is( ':checked' ) );
				window?.ppomPopup?.open( {
					title:
						window?.ppom_vars?.i18n?.errorOccurred ??
						window?.ppom_vars?.i18n?.popup?.errorTitle ??
						'Error',
					hideCloseBtn: true,
				} );
			} );
		}
	);

	// Confirmation popup for the native WP_List_Table "Delete" bulk action.
	// `WP_List_Table` exposes both top (`action`) and bottom (`action2`)
	// dropdowns; either may carry the chosen action.
	$( '.ppom-existing-meta-wrapper form[method="post"]' ).on(
		'submit',
		function ( e ) {
			const $form = $( this );

			if ( $form.data( 'ppomConfirmed' ) === true ) {
				return;
			}

			const top = $form.find( 'select[name="action"]' ).val();
			const bottom = $form.find( 'select[name="action2"]' ).val();
			const chosen =
				top && top !== '-1'
					? top
					: bottom && bottom !== '-1'
					? bottom
					: '';

			// Show the upsell modal on free version.
			if ( chosen === 'export' && $( '#ppom-export-upsell' ).length ) {
				e.preventDefault();
				$( '#ppom-export-upsell' ).fadeIn();
				return;
			}

			if ( chosen !== 'delete' ) {
				return;
			}

			e.preventDefault();

			const checkedNames = $form
				.find( '.ppom_product_checkbox:checked' )
				.map( function () {
					return this.dataset.name && this.dataset.name.trim() !== ''
						? this.dataset.name
						: this.value;
				} )
				.get()
				.join( ', ' );

			const deleteGroupTpl = window?.ppom_vars?.i18n?.popup?.deleteGroup;
			const title =
				checkedNames && deleteGroupTpl
					? deleteGroupTpl.replace( '%s', checkedNames )
					: window?.ppom_vars?.i18n?.popup?.confirmTitle;

			window?.ppomPopup?.open( {
				title,
				onConfirmation: () => {
					$form.data( 'ppomConfirmed', true );
					$form.trigger( 'submit' );
				},
			} );
		}
	);
} );
