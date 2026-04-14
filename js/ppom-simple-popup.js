/*
 * PPOM frontend popup bridge.
 *
 * This wraps a lightweight popup pattern used by some frontend field flows and
 * also bootstraps Tooltipster-based help tooltips when tooltip settings were
 * localized for the current product form.
 *
 * @see ppom_setup_file_upload_input in js/file-upload.js
 * @see imageTooltip in js/image-tooltip.js
 */

'use strict';

( function ( $ ) {
	// Tooltipster settings are localized from PHP per product/form instance so
	// the same script can respect store-specific trigger, width, and color rules.
	const tooltipConfig =
		'undefined' !== typeof ppom_tooltip_vars ? ppom_tooltip_vars : {};
	const tooltip_position = tooltipConfig.ppom_tooltip_position;
	const tooltip_trigger = tooltipConfig.ppom_tooltip_trigger;
	const tooltip_animation = tooltipConfig.ppom_tooltip_animation;
	const tooltip_maxwidth = tooltipConfig.ppom_tooltip_maxwidth;
	const tooltip_borderclr = tooltipConfig.ppom_tooltip_borderclr;
	const tooltip_bgclr = tooltipConfig.ppom_tooltip_bgclr;
	const tooltip_txtclr = tooltipConfig.ppom_tooltip_txtclr;
	const tooltip_interactive =
		tooltipConfig.ppom_tooltip_interactive == 'yes' ? true : false;

	const tooltip_options = {
		contentAsHTML: true,
		animation: tooltip_animation,
		theme: 'ppom_tooltipster-punk',
		interactive: tooltip_interactive,
		trigger: 'custom',
		position: tooltip_position,
		maxWidth: tooltip_maxwidth,
		tooltipBorderColor: tooltip_borderclr,
		tooltipBGColor: tooltip_bgclr,
		tooltipContentColor: tooltip_txtclr,
	};

	if ( tooltip_trigger != 'yes' ) {
		tooltip_options.triggerClose = {
			mouseleave: true,
			originClick: true,
			tap: true,
		};

		tooltip_options.triggerOpen = {
			mouseenter: true,
			tap: true,
		};
	} else {
		tooltip_options.triggerClose = {
			click: true,
			tap: true,
		};

		tooltip_options.triggerOpen = {
			click: true,
			tap: true,
		};
	}
	// Tooltips are optional because some forms load this popup bridge only for
	// modal behavior, while others use it to initialize tooltip-enabled inputs.
	if ( $( '[data-ppom-tooltip~=ppom_tooltip]' ).length > 0 ) {
		$( '[data-ppom-tooltip~=ppom_tooltip]' ).ppom_tooltipster?.(
			tooltip_options
		);
	}

	// Plugin name and prefix
	const pluginName = 'megapopup';
	const prefix = 'ppom-popup';

	// File-upload browse buttons need a refresh after hidden containers become
	// visible inside a popup, otherwise Plupload can calculate stale positions.
	function pluploadRefresh() {
		if ( typeof uploaderInstances !== 'object' ) {
			return;
		}
		const instances = Object.values( uploaderInstances );

		for ( let i = 0; i < instances.length; i++ ) {
			if ( typeof instances[ i ].refresh !== 'function' ) {
				continue;
			}

			instances[ i ].refresh();
		}
	}

	$( document ).on( 'click', '[data-model-id]', function ( e ) {
		e.preventDefault();
		const popup_id = $( this ).attr( 'data-model-id' );

		// Frontend templates point at modal containers with `data-model-id`.
		// Opening through the plugin keeps overlay handling and file-upload
		// refresh logic in one place.
		$( '#' + popup_id ).megapopup( $( this ).data() );

		pluploadRefresh();
	} );

	// jQuery plugin wrapper used by PPOM frontend templates that render inline
	// modal markup and want a consistent open/close contract.
	$.fn[ pluginName ] = function ( options ) {
		const defaults = {
			backgroundclickevent: true,
			popupcloseclass: prefix + '-close-js',
			bodycontroller: prefix + '-open',
		};

		//Extend popup options
		var options = $.extend( {}, defaults, options );

		return this.each( function () {
			// Global Variables
			let modal = $( this ),
				modalBG = $( '.' + prefix + '-bg-controler' );

			// Popup background show
			if ( modalBG.length == 0 ) {
				modalBG = $(
					'<div class="' + prefix + '-bg-controler" />'
				).appendTo( 'body' );
			}

			// Opening only toggles classes/visibility; the popup contents are
			// already rendered by PHP and may include live PPOM inputs/widgets.
			modal.bind( prefix + ':open', function () {
				$( 'body' ).addClass( options.bodycontroller );
				modal.css( { display: 'block' } );
				modalBG.fadeIn();
				modal.animate(
					{
						top: '0px',
						opacity: 1,
					},
					0
				);
			} );

			// Closing keeps the modal in the DOM so reopening does not discard
			// field state entered inside the popup-backed PPOM form controls.
			modal.bind( prefix + ':close', function () {
				$( 'body' ).removeClass( options.bodycontroller );
				modalBG.fadeOut();
				modal.animate(
					{
						top: '0px',
						opacity: 0,
					},
					0,
					function () {
						modal.css( { display: 'none' } );
					}
				);
			} );

			//Open Modal Immediately
			modal.trigger( prefix + ':open' );

			// close popup listner
			$( '.' + options.popupcloseclass ).bind(
				'click.modalEvent',
				function ( e ) {
					modal.trigger( prefix + ':close' );
					e.preventDefault();
				}
			);

			// Close popup on overlay click, but not when clicking inside the modal
			$( '.ppom-enquiry-overlay, .ppom-popup-product-edit-overlay' ).bind(
				'click.modalEvent',
				function ( e ) {
					if (
						! $( e.target ).closest(
							'.ppom-enquiry-modal, .ppom-popup-product-edit-modal'
						).length
					) {
						modal.trigger( prefix + ':close' );
					}
				}
			);

			// disable backgroundclickevent close
			if ( options.backgroundclickevent ) {
				modalBG.css( { cursor: 'pointer' } );
				modalBG.bind( 'click.modalEvent', function () {
					modal.trigger( prefix + ':close' );
				} );
			}
		} );
	};
} )( jQuery );
