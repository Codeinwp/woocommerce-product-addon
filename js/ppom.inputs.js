/**
 * Frontend field bootstrapper for PPOM inputs.
 *
 * PHP localizes each field definition into `ppom_input_vars.ppom_inputs`; this
 * file hydrates those definitions into live widgets on the product page. It is
 * the bridge between rendered markup and the other frontend subsystems such as
 * pricing, conditional logic, and file uploads.
 *
 * @see ppom_update_option_prices in js/price/ppom-price.js
 * @see ppom_update_option_prices in js/price/ppom-price-v2.js
 * @see ppom_check_conditions in js/ppom-conditions-v2.js
 * @see ppom_setup_file_upload_input in js/file-upload.js
 */

/**
 * Minimal shape of a localized PPOM field as consumed by the frontend scripts.
 *
 * PHP sends richer metadata, but these are the keys repeatedly used across the
 * field bootstrapper, price engines, and condition/upload helpers.
 *
 * @typedef {{
 *   data_name: string,
 *   type: string,
 *   title?: string,
 *   field_type?: string,
 *   input_mask?: string,
 *   discount_type?: string,
 *   options?: Array<Object> | string,
 *   images?: Array<Object>,
 *   audio?: Array<Object>
 * }} PPOMLocalizedFieldMeta
 */

'use strict';

/* global ppom_input_vars */

// Runtime caches shared by bulk quantity and price-matrix widgets.
const ppom_bulkquantity_meta = [];
let ppom_pricematrix_discount_type = '';

jQuery( function ( $ ) {
	// Boot the product-form behaviors once WooCommerce and PPOM markup exist.
	// Tooltip Init
	// $('.ppom-tooltip').powerTip({
	//     placement: 'n',
	//     smartPlacement: true,
	//     mouseOnToPopup: true
	// });

	// Remove Emoji from text input
	// $('.ppom-wrapper').on('input keyup', 'input[type="text"]', function(e) {

	//     const input_val = $(this).val();
	//     const new_val = input_val.replace(/([\u2700-\u27BF]|[\uE000-\uF8FF]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|[\u2011-\u26FF]|\uD83E[\uDD10-\uDDFF])/g, '');
	//     $(this).val(new_val);
	// });

	// $('[data-toggle="tooltip"]').tooltip({container:'body', trigger:'hover'});
	const wc_cart_button = jQuery( 'form.cart' ).find(
		'button[name="add-to-cart"]'
	);
	const wc_cart_form = jQuery( 'form.cart' );

	// Measure
	$( '.ppom-measure' ).on( 'change', '.ppom-measure-unit', function ( e ) {
		e.preventDefault();
		// console.log($(this).text());

		$( this )
			.closest( '.ppom-measure' )
			.find( '.ppom-measure-input' )
			.trigger( 'change' );
	} );

	// Disable ajax add to cart
	wc_cart_button.removeClass( 'ajax_add_to_cart' );

	// Range slider updated
	$( document ).on( 'ppom_range_slider_updated', function ( e ) {
		// console.log(wc_product_qty);
		$( 'form.cart' ).find( 'input[name="quantity"]' ).val( e.qty );
		// wc_product_qty.val(e.qty);
		ppom_update_option_prices();
	} );

	// move modals to body bottom
	if ( $( '.ppom-modals' ).length > 0 ) {
		$( '.ppom-modals' ).appendTo( 'body' );
	}

	ppom_init_js_for_ppom_fields( ppom_input_vars.ppom_inputs );
} );

/**
 * Hydrate each localized field definition with the JS behavior its type needs.
 *
 * @param {PPOMLocalizedFieldMeta[]} ppom_fields
 * @return {void}
 */
function ppom_init_js_for_ppom_fields( ppom_fields ) {
	if ( ppom_input_vars.sp_force_display_block === 'on' ) {
		// Fixed the form button issue
		if ( ppom_fields && ppom_fields.length > 0 ) {
			const css_type = jQuery( 'form.cart' ).css( 'display' );
			if ( css_type === 'flex' ) {
				jQuery( 'form.cart' ).addClass( 'ppom-flex-controller' );
			}
		}
	}

	jQuery.each( ppom_fields, function ( index, input ) {
		const InputSelector = jQuery( '#' + input.data_name );

		// Applying JS on inputs
		switch ( input.type ) {
			// masking
			case 'text':
				if ( input.input_mask == undefined || input.input_mask == '' ) {
					break;
				}
				InputSelector.inputmask();
				if (
					input.type === 'text' &&
					input.input_mask !== '' &&
					input.use_regex !== 'on'
				) {
					InputSelector.inputmask( input.input_mask );
				}
				break;

			// only allow numbers and periods in number fields
			case 'number':
				InputSelector.on( 'keydown keyup keypress', function ( event ) {
					if (
						event.key === 'Backspace' ||
						event.key === 'Delete' ||
						event.key === 'Tab' ||
						( event.ctrlKey === true && event.key === 'a' ) ||
						( event.ctrlKey === true && event.key === 'x' ) ||
						( event.ctrlKey === true &&
							event.key === 'Backspace' ) ||
						( event.which >= 48 && event.which <= 57 ) ||
						( event.which >= 96 && event.which <= 105 ) ||
						( event.key === '.' &&
							$( this ).val().indexOf( '.' ) <= 1 )
					) {
						// think happy thoughts :-)
					} else {
						event.preventDefault();
					}
				} ).on( 'focus blur', function () {
					if ( typeof InputSelector.attr( 'max' ) !== 'undefined' ) {
						if (
							parseFloat( InputSelector.val() ) >
							parseFloat( InputSelector.attr( 'max' ) )
						) {
							InputSelector.val( InputSelector.attr( 'max' ) );
						}
					}
				} );
				break;

			case 'date':
				if ( input.jquery_dp === 'on' ) {
					InputSelector.datepicker( 'destroy' );
					InputSelector.datepicker( {
						changeMonth: true,
						changeYear: true,
						dateFormat: input.date_formats.ppom_js_stripSlashes(),
						yearRange: input.year_range,
					} );

					if (
						typeof input.min_date !== 'undefined' &&
						input.min_date.trim().length > 0
					) {
						const min_date = input.min_date.trim();
						InputSelector.datepicker(
							'option',
							'minDate',
							min_date
						);
					}

					if ( typeof input.past_dates !== 'undefined' ) {
						if ( input.past_dates === 'on' ) {
							InputSelector.datepicker( 'option', 'minDate', 0 );
						}
					}

					if ( typeof input.max_date !== 'undefined' ) {
						const max_date = input.max_date.trim();
						InputSelector.datepicker(
							'option',
							'maxDate',
							max_date
						);
					}

					if (
						typeof input.no_weekends !== 'undefined' &&
						input.no_weekends === 'on'
					) {
						InputSelector.datepicker(
							'option',
							'beforeShowDay',
							jQuery.datepicker.noWeekends
						);
					}

					if (
						typeof input.default_value !== 'undefined' &&
						input.default_value.trim().length > 0
					) {
						const default_date = input.default_value.trim();
						InputSelector.datepicker(
							'option',
							'defaultDate',
							default_date
						);
						InputSelector.datepicker( 'setDate', default_date );
					}

					if (
						typeof input.first_day_of_week !== 'undefined' &&
						input.first_day_of_week.trim().length > 0
					) {
						const first_day_of_week =
							input.first_day_of_week.trim();
						InputSelector.datepicker(
							'option',
							'firstDay',
							first_day_of_week
						);
					}
				}
				break;

			case 'image':
				var img_id = input.data_name;
				// Image Tooltip
				if (
					input.show_popup === 'on' &&
					! ppom_input_vars.is_mobile
				) {
					jQuery( '.ppom-zoom-' + img_id ).imageTooltip();
				}

				document
					.querySelectorAll( '.ppom-image-select' )
					.forEach( ( /** @type{HTMLDivElement} */ container ) => {
						/**
						 * @type {HTMLInputElement[]} Holds the selected images.
						 */
						const selectedImgs = [];

						/**
						 * @type {HTMLInputElement[]} The available images to select.
						 */
						const imgsInput = Array.from(
							container.querySelectorAll(
								'.ppom-input.pre_upload_image input'
							)
						);

						for ( const imgInput of imgsInput ) {
							const multiple =
								imgInput.dataset.allowMultiple || false;
							const maxImgSelection = parseInt(
								imgInput.dataset?.maxSelection ?? '-1'
							);

							imgInput.addEventListener( 'click', ( e ) => {
								if ( ! e.target.checked ) {
									return;
								}

								if ( ! multiple ) {
									// Uncheck other inputs.
									imgsInput
										.filter( ( i ) => i !== imgInput )
										.forEach( ( i ) => {
											i.checked = false;
										} );
								} else if ( 0 < maxImgSelection ) {
									// Uncheck oldest checked image.
									selectedImgs.push( imgInput );
									while (
										selectedImgs.length > maxImgSelection
									) {
										const oldestImgSelected =
											selectedImgs.shift();
										if ( oldestImgSelected ) {
											oldestImgSelected.checked = false;
										}
									}
								}
							} );
						}
					} );

				// Data Tooltip
				// $(".pre_upload_image").tooltip({container: 'body'});
				break;
			// date_range
			case 'daterange':
				InputSelector.daterangepicker( {
					autoApply: input.auto_apply == 'on' ? true : false,
					locale: {
						format:
							input.date_formats !== ''
								? input.date_formats
								: 'YYYY-MM-DD',
					},
					showDropdowns: input.drop_down == 'on' ? true : false,
					showWeekNumbers: input.show_weeks == 'on' ? true : false,
					timePicker: input.time_picker == 'on' ? true : false,
					timePickerIncrement:
						input.tp_increment !== ''
							? parseInt( input.tp_increment )
							: '',
					timePicker24Hour: input.tp_24hours == 'on' ? true : false,
					timePickerSeconds: input.tp_seconds == 'on' ? true : false,
					drops: input.open_style !== '' ? input.open_style : 'down',
					startDate:
						input.start_date == '' ? false : input.start_date,
					endDate: input.end_date == '' ? false : input.end_date,
					minDate: input.min_date == '' ? false : input.min_date,
					maxDate: input.max_date == '' ? false : input.max_date,
				} );
				break;

			// color: iris
			case 'color':
				InputSelector.css( 'background-color', input.default_color );
				var iris_options = {
					palettes: ppom_get_palette_setting( input ),
					hide: input.show_onload == 'on' ? false : true,
					color: input.default_color,
					mode:
						input.palettes_mode != '' ? input.palettes_mode : 'hsv',
					width:
						input.palettes_width != '' ? input.palettes_width : 200,
					change( event, ui ) {
						InputSelector.css(
							'background-color',
							ui.color.toString()
						);
						InputSelector.css( 'color', '#fff' );

						// Getting Color Code for update price
						InputSelector.val( ui.color.toString() );
						if ( typeof ppomPrice !== 'undefined' ) {
							ppomPrice.init();
						}
					},
				};

				InputSelector.iris( iris_options );

				// Following script is added to close picker
				// when color is picked
				jQuery( document ).click( function ( e ) {
					if (
						! jQuery( e.target ).is(
							'.ppom-input.color, .iris-picker, .iris-picker-inner'
						)
					) {
						jQuery( '.ppom-input.color' ).iris( 'hide' );
						return e;
					}
				} );

				jQuery( '.ppom-input.color' ).click( function ( event ) {
					jQuery( '.ppom-input.color' ).iris( 'hide' );
					jQuery( this ).iris( 'show' );
					return event;
				} );
				break;

			// Palettes
			case 'palettes':
				const max_selected =
					parseInt( input.max_selected ) || undefined;
				if ( ! max_selected ) {
					break;
				}

				jQuery( document ).on(
					'click',
					`.ppom-palettes-${ input.data_name } input.ppom-input`,
					function ( e ) {
						if (
							jQuery(
								`.ppom-palettes-${ input.data_name } input.ppom-input:checked`
							).length > max_selected
						) {
							alert(
								`You can only select a maximum of ${ max_selected } ${ input.title } colors`
							);
							e.preventDefault();
							//   return false;
						}
					}
				);
				break;
			// Bulk quantity
			case 'bulkquantity':
				setTimeout( function () {
					jQuery( '.quantity.buttons_added' ).hide();
				}, 50 );
				jQuery( 'form.cart' ).find( '.quantity' ).hide();

				// setting formatter
				/*if ($('form.cart').closest('div').find('.price').length > 0){
                	wc_price_DOM = $('form.cart').closest('div').find('.price');
                }*/

				ppom_bulkquantity_meta[ input.data_name ] = input.options;

				var min_quantity_value = jQuery(
					`.ppom-bulkquantity-qty.${ input.data_name }`
				).val();

				// Starting value
				ppom_bulkquantity_price_manager(
					min_quantity_value,
					input.data_name
				);
				break;

			case 'pricematrix':
				ppom_pricematrix_discount_type = input.discount_type;

				if (
					input.show_slider === 'on' &&
					jQuery( '.ppom-range-slide' ).length > 0
				) {
					const slider = new Slider( '.ppom-range-slide', {
						formatter( value ) {
							jQuery.event.trigger( {
								type: 'ppom_range_slider_updated',
								qty: value,
								time: new Date(),
							} );
							return ppom_input_vars.text_quantity + ': ' + value;
						},
					} );
				}

				jQuery( '.ppom-range-bs-slider' ).on( 'change', function ( e ) {
					jQuery.event.trigger( {
						type: 'ppom_range_slider_updated',
						qty: jQuery( this ).val(),
						time: new Date(),
					} );
				} );
				break;
			case 'quantities':
				var enable_plusminus = input.enable_plusminus;
				var field_selectot = jQuery( '.ppom-input-' + input.data_name );
				if ( enable_plusminus == 'on' ) {
					jQuery( '.ppom-quantity', field_selectot ).niceNumber();
				}
				break;
		}
	} );
}

function ppom_get_palette_setting( input ) {
	let palettes_setting = false;
	// first check if palettes is on
	if ( input.show_palettes === 'on' ) {
		palettes_setting = true;
	}
	if ( palettes_setting && input.palettes_colors !== '' ) {
		palettes_setting = input.palettes_colors.split( ',' );
	}

	return palettes_setting;
}

/**
 * Shared field lookup used by pricing, conditions, and file upload helpers.
 *
 * @param {string} field_id
 * @return {string}
 */
function ppom_get_field_type_by_id( field_id ) {
	let field_type = '';
	jQuery.each( ppom_input_vars.ppom_inputs, function ( i, field ) {
		if ( field.data_name === field_id ) {
			field_type = field.field_type;
		}
	} );

	return field_type;
}

/**
 * Return the full localized field definition for a given data_name.
 *
 * @param {string} field_id
 * @return {PPOMLocalizedFieldMeta|string}
 */
function ppom_get_field_meta_by_id( field_id ) {
	let field_meta = '';
	jQuery.each( ppom_input_vars.ppom_inputs, function ( i, field ) {
		if ( field.data_name === field_id ) {
			field_meta = field;
		}
	} );

	return field_meta;
}

/**
 * Some flows need all fields of a given type, for example cropper/file setup.
 *
 * @param {string} type
 * @return {PPOMLocalizedFieldMeta[]}
 */
function ppom_get_field_meta_by_type( type ) {
	const field_meta = Array();
	jQuery.each( ppom_input_vars.ppom_inputs, function ( i, field ) {
		if ( field.type === type ) {
			field_meta.push( field );
		}
	} );

	return field_meta;
}

// Keep the range and number UIs for bulkquantity in sync before recalculating.
function ppom_bq_qty_changed( qty, data_name, context ) {
	if ( context === 'range' ) {
		// update the quantity too.
		jQuery( `.ppom-bulkquantity-qty.${ data_name }` ).val( qty );
	} else if ( context === 'number' ) {
		// update slider too.
		jQuery( `input[type=range].${ data_name }` ).val( qty );
	}

	ppom_bulkquantity_price_manager( qty, data_name );
}

// Resolve the active bulkquantity row into price/base-price attributes expected
// by the legacy and modern price preview engines.
function ppom_bulkquantity_price_manager( quantity, data_name ) {
	let ppom_base_price = 0;
	jQuery.each(
		JSON.parse( ppom_bulkquantity_meta[ data_name ] ),
		function ( idx, obj ) {
			const qty_range = obj[ 'Quantity Range' ].split( '-' );
			const qty_range_from = qty_range[ 0 ];
			const qty_range_to = qty_range[ 1 ];

			if (
				quantity >= parseInt( qty_range_from ) &&
				quantity <= parseInt( qty_range_to )
			) {
				// Setting Initial Price to 0 and taking base price
				ppom_base_price =
					obj[ 'Base Price' ] == undefined ||
					obj[ 'Base Price' ] == ''
						? 0
						: obj[ 'Base Price' ];
				jQuery(
					`.ppom-bulkquantity-options.${ data_name } option:selected`
				).attr( 'data-baseprice', ppom_base_price );

				// Taking selected variation price
				const variation = jQuery( '.ppom-bulkquantity-options' ).val();
				const var_price = obj[ variation ];
				jQuery(
					`.ppom-bulkquantity-options.${ data_name } option:selected`
				).attr( 'data-price', var_price );

				return false;
			}
		}
	);

	ppom_update_option_prices();
}

String.prototype.ppom_js_stripSlashes = function () {
	return this.replace( /\\(.)/gm, '$1' );
};
