/**
 * Deactivation survey modal for the plugin list screen.
 *
 * The collected reason is optional telemetry; the saved redirect target still
 * drives the final plugin deactivation link after the modal flow completes.
 */
'use strict';
jQuery( function ( $ ) {
	const modal = $( '#ppom-deactivate-modal' );
	let deactivateLink = '';

	$( '#the-list' ).on( 'click', 'a.ppom-deactivate-link', function ( e ) {
		e.preventDefault();
		modal.addClass( 'modal-active' );
		deactivateLink = $( this ).attr( 'href' );
		modal
			.find( 'a.dont-bother-me' )
			.attr( 'href', deactivateLink )
			.css( 'float', 'left' );
	} );

	$( '#ppom-deactivate-modal' ).on(
		'click',
		'a.review-and-deactivate',
		function ( e ) {
			e.preventDefault();
			window.open(
				'https://wordpress.org/support/plugin/woocommerce-product-addon/reviews/#new-post'
			);
			window.location.href = deactivateLink;
		}
	);
	modal.on( 'click', 'button.pipe-model-cancel', function ( e ) {
		e.preventDefault();
		modal.removeClass( 'modal-active' );
	} );
	modal.on( 'click', 'input[type="radio"]', function () {
		const parent = $( this ).parents( 'li:first' );
		modal.find( '.reason-input' ).remove();
		const inputType = parent.data( 'type' ),
			inputPlaceholder = parent.data( 'placeholder' );
		if ( 'reviewhtml' === inputType ) {
			var reasonInputHtml =
				'<div class="reviewlink"><a href="#" target="_blank" class="review-and-deactivate">Deactivate and leave a review<span class="xa-pipe-rating-link"> &#9733;&#9733;&#9733;&#9733;&#9733; </span></a></div>';
		} else {
			var reasonInputHtml =
				'<div class="reason-input">' +
				( 'text' === inputType
					? '<input type="text" class="input-text" size="40" />'
					: '<textarea rows="5" cols="45"></textarea>' ) +
				'</div>';
		}
		if ( inputType !== '' ) {
			parent.append( $( reasonInputHtml ) );
			parent
				.find( 'input, textarea' )
				.attr( 'placeholder', inputPlaceholder )
				.focus();
		}
	} );

	modal.on( 'click', 'button.pipe-model-submit', function ( e ) {
		e.preventDefault();
		// Submit the reason asynchronously, then continue to the original
		// deactivation URL regardless of whether the response body is useful.
		const button = $( this );
		if ( button.hasClass( 'disabled' ) ) {
			return;
		}
		const $radio = $( 'input[type="radio"]:checked', modal );
		const $selected_reason = $radio.parents( 'li:first' ),
			$input = $selected_reason.find( 'textarea, input[type="text"]' );

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pipe_submit_uninstall_reason',
				reason_id: 0 === $radio.length ? 'none' : $radio.val(),
				reason_info: 0 !== $input.length ? $input.val().trim() : '',
			},
			beforeSend() {
				button.addClass( 'disabled' );
				button.text( 'Processing...' );
			},
			complete( resp ) {
				window.location.href = deactivateLink;
			},
		} );
	} );
} );
