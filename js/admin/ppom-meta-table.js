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
	 * Initialize Select2 on the product search dropdowns in the "attach to products" modal.
	 *
	 * @return {void}
	 */
	function initAttachSelects() {
		const attachSelects = $( '.ppom-attach-container-item select' );

		if ( typeof $.fn.select2 === 'function' ) {
			attachSelects.select2();
		}
	}

	const append_overlay_modal =
		"<div class='ppom-modal-overlay ppom-js-modal-close'></div>";

	// Load the product-assignment UI lazily so the heavy modal table is fetched
	// only when the merchant asks to attach a group to products. Delegated from
	// `body` so it works in both the field groups list and the field editor.
	$( 'body' ).on(
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
				$( '#' + model_id ).fadeIn();
				$( '#attach-to-products input' ).focus();
			} );
		}
	);

	$( 'body' ).on( 'click', 'a.ppom-delete-single-product', function ( e ) {
		e.preventDefault();
		const productmeta_id = $( this ).attr( 'data-product-id' );
		let title = window?.ppom_vars?.i18n.popup.deleteGroup;
		const productName = $( this ).data( 'name' );
		title = productName ? title.replace( '%s', productName ) : window?.ppom_vars?.i18n.popup.confirmTitle;

		window?.ppomPopup?.open( {
			title: title,
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
								: window?.ppom_vars?.i18n?.popup
										?.errorTitle ?? 'Error';
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
				title: title,
				onConfirmation: () => {
					$form.data( 'ppomConfirmed', true );
					$form.trigger( 'submit' );
				},
			} );
		}
	);
} );
