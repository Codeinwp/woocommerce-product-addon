'use strict';

/**
 * AJAX product-form validation before WooCommerce submits add-to-cart.
 *
 * The server still owns validation. This script performs a preflight request so
 * PPOM can show validation notices inline without losing the current form state.
 */
jQuery( function ( $ ) {
	//   console.log('loaded cart');

	let ppom_cart_validated = false;

	if ( $.blockUI !== undefined ) {
		$.blockUI.defaults.message = '';
	}

	$( 'form.cart' ).on( 'submit', function ( e ) {
		if ( ppom_cart_validated ) {
			return true;
		}

		// Stop the first native submit, ask PHP to validate the serialized PPOM
		// payload, then re-trigger add-to-cart only after the response is clean.
		e.preventDefault();

		// Removing validation div
		$( '.ppom-ajax-validation' ).remove();
		$( 'form.cart' ).block();

		let data = $( this ).serialize();
		data = data + '&action=ppom_ajax_validation';
		data = data + '&ppom_nonce=' + ppom_input_vars.ppom_validate_nonce;

		$.post( ppom_input_vars.ajaxurl, data, function ( notices ) {
			$( 'form.cart' ).unblock();
			if ( notices.status == 'error' ) {
				const show_notice = $( '<div/>' )
					.addClass(
						'woocommerce-notices-wrapper ppom-ajax-validation'
					)
					.css( 'clear', 'both' )
					.css( 'margin-top', '5px' )
					.html( notices.message )
					.appendTo( 'form.cart' );
			} else {
				ppom_cart_validated = true;
				$( 'button[name="add-to-cart"]' ).trigger( 'click' );
			}
		} );
	} );
} );
