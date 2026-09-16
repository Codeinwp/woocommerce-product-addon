// @ts-check

/**
 * PPOM Conditional Version 2
 *
 * This is the current conditional-logic engine used on the product page. PHP
 * renders rule metadata into `data-cond-*` attributes; this file evaluates
 * those rules and emits the shared `ppom_field_hidden` / `ppom_field_shown`
 * events that pricing, uploads, validation, and default restoration rely on.
 *
 * @see populate_conditional_elements in js/admin/ppom-admin.js
 * @see ppom_fields_hidden_conditionally
 * @see ppom_update_option_prices in js/price/ppom-price.js
 */

/** @type {string[]} */
let ppom_hidden_fields = [];

/**
 * @typedef {Object} PPOMConditionCompareArgs
 * @property {string|string[]|undefined|null} valueToCompare
 * @property {string|undefined} selectOptionToCompare
 * @property {string|undefined} constantValueToCompare
 * @property {{to: string, from: string}} betweenValueInterval
 * @property {string} operator
 */

jQuery( function ( $ ) {
	// Replay the initial field state after the product form has rendered so
	// defaults, preselected options, and conditionally hidden fields line up.
	setTimeout( function () {
		$( 'form.cart' )
			.find(
				'select option:selected, input[type="radio"]:checked, input[type="checkbox"]:checked'
			)
			.each( function ( i, field ) {
				if (
					$( field )
						.closest( 'div.ppom-field-wrapper' )
						.hasClass( 'ppom-c-hide' )
				) {
					return;
				}

				const data_name = $( field ).data( 'data_name' );
				ppom_check_conditions(
					data_name,
					function ( element_dataname, event_type, $scope ) {
						// console.log(data_name, event_type);
						$.event.trigger( {
							type: event_type,
							field: element_dataname,
							scope: $scope,
							time: new Date(),
						} );
					},
					$( field ).closest( '.ppom-wrapper' )
				);
			} );

		$( 'form.cart' )
			.find( 'div.ppom-c-show' )
			.each( function ( i, field ) {
				const data_name = $( field ).data( 'data_name' );
				ppom_check_conditions(
					data_name,
					function ( element_dataname, event_type, $scope ) {
						$.event.trigger( {
							type: event_type,
							field: element_dataname,
							scope: $scope,
							time: new Date(),
						} );
					},
					$( field ).closest( '.ppom-wrapper' )
				);
			} );

		$( 'form.cart' )
			.find( 'div.ppom-c-hide' )
			.each( function ( i, field ) {
				const data_name = $( field ).data( 'data_name' );
				$.event.trigger( {
					type: 'ppom_field_hidden',
					field: data_name,
					scope: $( field ).closest( '.ppom-wrapper' ),
					time: new Date(),
				} );
			} );
	}, 100 );

	// $('form.cart').on('change', 'select, input[type="radio"], input[type="checkbox"]', function(ev) {

	/**
	 * Re-evaluate any condition tree that depends on the changed form control.
	 *
	 * @param {HTMLInputElement|HTMLSelectElement} modifiedElement
	 * @return {void}
	 */
	function trigger_check_conditions( modifiedElement ) {
		const data_name = modifiedElement.dataset?.data_name;
		// Scope to the changed control's own product form so a control
		// sharing a data_name with another PPOM form on the same page
		// cannot read or toggle that other form's fields.
		const $scope = jQuery( modifiedElement ).closest( '.ppom-wrapper' );
		ppom_check_conditions( data_name, ( element_dataname, event_type, $scope ) => {
			$.event.trigger( {
				type: event_type,
				field: element_dataname,
				scope: $scope,
				time: new Date(),
			} );
		}, $scope );
	}

	$( '.ppom-wrapper' ).on(
		'change',
		'select, input:radio, input:checkbox, input[type="date"]',
		function ( _e ) {
			trigger_check_conditions( this );
		}
	);

	$( '.ppom-wrapper' ).on(
		'keyup',
		'input:text, input[type="number"], input[type="email"]',
		function ( _e ) {
			trigger_check_conditions( this );
		}
	);

	$( document ).on( 'ppom_hidden_fields_updated', function ( e ) {
		ppom_fields_hidden_conditionally();
	} );

	$( document ).on( 'ppom_field_hidden', function ( e ) {
		// console.log(e.field)

		// Resolve the originating form from the triggering event so a
		// data_name shared by another PPOM form on the same page doesn't
		// reset or re-evaluate that other form's fields (issue #735).
		const $scope = e.scope && e.scope.length ? e.scope : $( document );
		const product_id = $scope.find( '[name="ppom_product_id"]' ).val();

		const element_type = ppom_get_field_type_by_id( e.field, product_id );
		switch ( element_type ) {
			case 'select':
				$scope
					.find( 'select[name="ppom[fields][' + e.field + ']"]' )
					.val( '' );
				break;

			case 'multiple_select':
				var selector = $scope.find(
					'select[name="ppom[fields][' + e.field + '][]"]'
				);
				var selected_value = selector.val();
				var selected_options = selector.find( 'option:selected' );

				jQuery.each(
					selected_options,
					function ( index, default_selected ) {
						const option_id =
							jQuery( default_selected ).attr( 'data-option_id' );
						const the_id =
							'ppom-multipleselect-' + e.field + '-' + option_id;

						$scope.find( '#' + the_id ).remove();
					}
				);

				if ( selected_value ) {
					selector.val( null ).trigger( 'change' );
				}

				break;

			case 'checkbox':
				$scope
					.find( 'input[name="ppom[fields][' + e.field + '][]"]' )
					.prop( 'checked', false );
				break;

			case 'radio':
				$scope
					.find( 'input[name="ppom[fields][' + e.field + ']"]' )
					.prop( 'checked', false );
				break;

			case 'file':
				$scope
					.find( '#filelist-' + e.field )
					.find( '.u_i_c_box' )
					.remove();
				break;

			case 'palettes':
			case 'image':
				$scope
					.find( 'input[name="ppom[fields][' + e.field + '][]"]' )
					.prop( 'checked', false );
				break;

			case 'imageselect':
				var the_id = 'ppom-imageselect' + e.field;
				$scope.find( '#' + the_id ).remove();
				break;

			case 'quantityoption':
				$scope.find( '#' + e.field ).val( '' );
				var the_id = 'ppom-quantityoption-rm' + e.field;
				$scope.find( '#' + the_id ).remove();
				break;

			case 'pricematrix':
				$scope
					.find( `input[data-dataname="ppom[fields][${ e.field }]"]` )
					.removeClass( 'active' );
				break;

			case 'quantities':
				$scope
					.find( `input[name^="ppom[fields][${ e.field }]"]` )
					.val( '' );
				break;

			case 'fixedprice':
				// if select type is radio
				$scope
					.find( 'input[name="ppom[fields][' + e.field + ']"]' )
					.prop( 'checked', false );
				// if select type is select
				$scope
					.find( 'select[name="ppom[fields][' + e.field + ']"]' )
					.val( '' );
				break;

			default:
				// Reset text/textarea/date/email etc types
				$scope.find( '#' + e.field ).val( '' );
				break;
		}

		$.event.trigger( {
			type: 'ppom_hidden_fields_updated',
			field: e.field,
			scope: $scope,
			time: new Date(),
		} );

		ppom_check_conditions(
			e.field,
			function ( element_dataname, event_type, $scope ) {
				// console.log(`${element_dataname} ===> ${event_type}`);
				$.event.trigger( {
					type: event_type,
					field: element_dataname,
					scope: $scope,
					time: new Date(),
				} );
			},
			$scope
		);
	} );

	/*$(document).on('ppom_field_shown', function(e) {

        console.log(`shown event ${e.field}`);
        ppom_check_conditions(e.field);
    });*/

	$( document ).on( 'ppom_field_shown', function ( e ) {
		ppom_fields_hidden_conditionally();

		// Resolve the originating form the same way ppom_field_hidden does,
		// so restoring defaults for one PPOM form can't read or touch
		// another form's fields sharing the same data_name (issue #735).
		const $scope = e.scope && e.scope.length ? e.scope : $( document );
		const product_id = $scope.find( '[name="ppom_product_id"]' ).val();

		// Set checked/selected again
		ppom_set_default_option( e.field, $scope );

		ppom_check_conditions(
			e.field,
			function ( element_dataname, event_type, $scope ) {
				// console.log(`${element_dataname} ===> ${event_type}`);
				$.event.trigger( {
					type: event_type,
					field: element_dataname,
					scope: $scope,
					time: new Date(),
				} );
			},
			$scope
		);

		const field_meta = ppom_get_field_meta_by_id( e.field, product_id );

		// Apply FileAPI to DOM
		// PPOM version 22.0 has issue, commenting it so far by Najeeb April 4, 2021
		// if (field_meta.type === 'file' || field_meta.type === 'cropper') {
		//     ppom_setup_file_upload_input(field_meta);
		// }

		// Price Matrix
		if ( field_meta.type == 'pricematrix' ) {
			// Resettin
			$scope.find( '.ppom_pricematrix' ).removeClass( 'active' );

			// Set Active
			const classname = '.' + field_meta.data_name;
			// console.log(field_meta.data_name, jQuery(`input[data-dataname="ppom[fields][${field_meta.data_name}]"]`));
			$scope
				.find( `input[data-dataname="ppom[fields][${ field_meta.data_name }]"]` )
				.addClass( 'active' );
			// $(classname).find('.ppom_pricematrix').addClass('active')
		}

		//Imageselect (Image dropdown)
		if ( field_meta.type === 'imageselect' ) {
			const dd_selector = 'ppom_imageselect_' + field_meta.data_name;
			const ddData = $scope.find( '#' + dd_selector ).data( 'ppom_ddslick' );
			const image_replace = field_meta.image_replace
				? field_meta.image_replace
				: 'off';

			ppom_create_hidden_input( ddData );
			ppom_update_option_prices();
			setTimeout( function () {
				ppom_image_selection( ddData, image_replace );
			}, 100 );
			// $('#'+dd_selector).ddslick('select', {index: 0 });
		}

		// Multiple Select Addon
		if ( field_meta.type === 'multiple_select' ) {
			const selector = $scope.find(
				'select[name="ppom[fields][' + field_meta.data_name + '][]"]'
			);
			const selected_value = selector.val();
			const default_value = field_meta.selected;

			if ( selected_value === null && default_value ) {
				const selected_opt_arr = default_value.split( ',' );

				selector.val( selected_opt_arr ).trigger( 'change' );

				const selected_options = selector.find( 'option:selected' );
				jQuery.each(
					selected_options,
					function ( index, default_selected ) {
						const option_id =
							jQuery( default_selected ).attr( 'data-option_id' );
						const option_label =
							jQuery( default_selected ).attr(
								'data-optionlabel'
							);
						const option_price =
							jQuery( default_selected ).attr(
								'data-optionprice'
							);

						ppom_multiple_select_create_hidden_input(
							field_meta.data_name,
							option_id,
							option_price,
							option_label,
							field_meta.title
						);
					}
				);
			}
		}
	} );

	ppom_fields_hidden_conditionally();
} );

function ppom_check_conditions( data_name, callback, $scope ) {
	// Each `.ppom-cond-*` node describes one target field and its dependencies.
	// We evaluate all rules for that target, then notify the rest of the stack
	// through shared PPOM events instead of mutating unrelated features directly.
	//
	// $scope confines the target-node and value lookups to one product's
	// `.ppom-wrapper`; without it, two PPOM forms on the same page that reuse
	// a data_name read and toggle each other's fields (issue #735).
	$scope = $scope && $scope.length ? $scope : jQuery( document );
	let is_matched = false;
	let event_type, element_data_name;

	$scope.find( `div.ppom-cond-${ data_name }` ).each( function () {
		// return this.data('cond-val1').match(/\w*-Back/);
		// console.log(jQuery(this));
		const total_cond = parseInt( jQuery( this ).data( 'cond-total' ) );
		const binding = jQuery( this ).data( `cond-bind` );
		const visibility = jQuery( this ).data( `cond-visibility` );
		element_data_name = jQuery( this ).data( 'data_name' );

		let matched = 0;
		const matched_conditions = [];
		const cond_elements = [];
		for ( let t = 1; t <= total_cond; t++ ) {
			const targetFieldToCompare = jQuery( this )
				.data( `cond-input${ t }` )
				?.toString()
				?.toLowerCase();
			const targetFieldValue =
				ppom_get_element_value( targetFieldToCompare, $scope );

			const selectOptionValue = jQuery( this )
				.data( `cond-val${ t }` )
				?.toString();
			const operator = jQuery( this ).data( `cond-operator${ t }` );
			const constantValue = jQuery( this )
				.data( `cond-constant-val-${ t }` )
				?.toString();
			const betweenValueTo = jQuery( this ).data(
				`cond-between-to-${ t }`
			);
			const betweenValueFrom = jQuery( this ).data(
				`cond-between-from-${ t }`
			);

			is_matched = ppom_compare_values( {
				valueToCompare: targetFieldValue,
				selectOptionToCompare: selectOptionValue,
				constantValueToCompare: constantValue,
				betweenValueInterval: {
					from: betweenValueFrom,
					to: betweenValueTo,
				},
				operator,
			} );

			if ( is_matched ) {
				matched = ++matched;
				cond_elements.push( targetFieldToCompare );
			}

			matched_conditions[ element_data_name ] = matched;

			event_type =
				visibility === 'hide'
					? 'ppom_field_hidden'
					: 'ppom_field_shown';
			// console.log(`${t} ***** ${element_data_name} total_cond ${total_cond} == matched ${matched} ==> ${matched_conditions[element_data_name]} ==> visibility ${event_type}`);

			if ( matched_conditions[ element_data_name ] > 0 && binding === 'Any' ) {
				const $wrapper = jQuery( this );
				if ( visibility !== 'hide' ) {
					$wrapper.removeClass( function ( _index, className ) {
						return ( className.match(/\bppom-locked-\S+/g) || [] ).join( ' ' );
					} );
				} else {
 					$wrapper.addClass( `ppom-locked-${ data_name }` );
 				}

				if ( visibility === 'hide' ) {
 					$wrapper.addClass( 'ppom-c-hide' ).removeClass( 'ppom-c-show' );
 				} else {
 					$wrapper.removeClass( 'ppom-c-hide' );
 				}

				if ( typeof callback === 'function' ) {
					callback( element_data_name, event_type, $scope );
				}
			} else if ( matched_conditions[ element_data_name ] == total_cond && binding === 'All') {
				// remove/add locked classes for all dependent fields
				cond_elements.forEach( ( cond_dataname ) => {
					if ( visibility === 'hide' ) {
						jQuery( this )
							.addClass(
								`ppom-locked-${ cond_dataname } ppom-c-hide`
							)
							.removeClass( 'ppom-c-show' );
					} else {
						jQuery( this ).removeClass(
							`ppom-locked-${ cond_dataname } ppom-c-hide`
						);
					}
				} );

				if ( typeof callback === 'function' ) {
					callback( element_data_name, event_type, $scope );
				}
			} else if (
				! is_matched ||
				matched_conditions[ element_data_name ] !== total_cond
			) {
				if ( binding === 'Any' ) {
					const classes = ((jQuery(this).attr("class") || "").match(/\bppom-cond-[^\s]+/g,) || [])
								.map((cls) => cls.replace("ppom-cond-", "ppom-locked-"))
								.join(" ");

					if ( visibility === 'hide' ) {
						jQuery( this ).removeClass(
							`${ classes } ppom-c-hide`
						);
					} else {
						jQuery( this ).addClass(
							`${ classes } ppom-c-hide`
						);
					}
				} else if ( binding === 'All' ) {
					if ( visibility === 'hide' ) {
						event_type = 'ppom_field_shown';
						jQuery( this ).removeClass(
							`ppom-locked-${ data_name } ppom-c-hide`
						);
					} else {
						event_type = 'ppom_field_hidden';
						jQuery( this ).addClass(
							`ppom-locked-${ data_name } ppom-c-hide`
						);
					}
				}

				if ( typeof callback === 'function' ) {
					callback( element_data_name, event_type, $scope );
				}
			} else {
				jQuery( this ).removeClass(
					`ppom-locked-${ data_name } ppom-c-hide`
				);
				// console.log('event_type', event_type);

				if ( typeof callback === 'function' ) {
					callback( element_data_name, event_type, $scope );
				}
			}
		}
	} );
}

function ppom_get_input_dom_type( data_name, $scope ) {
	$scope = $scope && $scope.length ? $scope : jQuery( document );
	// const field_obj = jQuery(`input[name="ppom[fields][${data_name}]"], input[name="ppom[fields][${data_name}[]]"], select[name="ppom[fields][${data_name}]"]`);
	const field_obj = $scope.find( `.ppom-input[data-data_name="${ data_name }"]` );
	return field_obj.closest( '.ppom-field-wrapper' ).data( 'type' );
}

// Normalize values across PPOM field types so condition operators can stay
// unaware of the exact DOM structure used by each input renderer.
//
// $scope confines every lookup to one product's `.ppom-wrapper`, defaulting
// to the whole document for callers that don't have a specific form in mind.
function ppom_get_element_value( data_name, $scope ) {
	$scope = $scope && $scope.length ? $scope : jQuery( document );
	const ppom_type = ppom_get_input_dom_type( data_name, $scope );
	let element_value = '';
	const value_found_cb = [];

	switch ( ppom_type ) {
		case 'switcher':
		case 'radio':
			element_value = $scope.find(
				`.ppom-input[data-data_name="${ data_name }"]:checked`
			).val();
			break;
		case 'palettes':
		case 'checkbox':
			$scope.find(
				'input[name="ppom[fields][' + data_name + '][]"]:checked'
			).each( function ( i ) {
				value_found_cb[ i ] = jQuery( this ).val();
			} );
			break;
		case 'image':
		case 'conditional_meta':
			element_value = $scope.find(
				`.ppom-input[data-data_name="${ data_name }"]:checked`
			).data( 'label' );
			break;
		case 'imageselect':
			element_value = $scope.find(
				`.ppom-input[data-data_name="${ data_name }"]:checked`
			).data( 'label' );
			break;
		case 'fixedprice':
			var render_type = $scope.find( `.ppom-input-${ data_name }` ).attr(
				'data-input'
			);
			if ( render_type == 'radio' ) {
				element_value = $scope.find(
					`.ppom-input[data-data_name="${ data_name }"]:checked`
				).val();
			} else {
				element_value = $scope.find(
					`.ppom-input[data-data_name="${ data_name }"]`
				).val();
			}
			break;

		default:
			element_value = $scope.find(
				`.ppom-input[data-data_name="${ data_name }"]`
			).val();
	}

	if ( ppom_type === 'checkbox' || ppom_type === 'palettes' ) {
		// console.log(value_found_cb);
		return value_found_cb;
	}

	return element_value;
}

/**
 * Compares values based on the provided operator.
 *
 * @param {PPOMConditionCompareArgs} args - Comparison parameters taken from a
 * rendered condition rule and the current state of its target field.
 * @return {boolean} - The result of the comparison.
 */
function ppom_compare_values( args ) {
	const {
		valueToCompare,
		selectOptionToCompare,
		constantValueToCompare,
		operator,
		betweenValueInterval,
	} = args;
	let result = false;
	switch ( operator ) {
		case 'is':
			if ( Array.isArray( valueToCompare ) ) {
				result = valueToCompare.includes( selectOptionToCompare );
			} else {
				result = valueToCompare === selectOptionToCompare;
				if ( ! selectOptionToCompare && constantValueToCompare ) {
					result = valueToCompare === constantValueToCompare;
				}
			}
			break;

		case 'not':
			if ( Array.isArray( valueToCompare ) ) {
				result = ! valueToCompare.includes( selectOptionToCompare );
			} else {
				result = valueToCompare !== selectOptionToCompare;
				if ( ! selectOptionToCompare && constantValueToCompare ) {
					result = valueToCompare !== constantValueToCompare;
				}
			}
			break;

		case 'greater than':
			result =
				parseFloat( valueToCompare ) >
				parseFloat( selectOptionToCompare );
			if ( ! selectOptionToCompare && constantValueToCompare ) {
				result =
					parseFloat( valueToCompare ) >
					parseFloat( constantValueToCompare );
			}
			break;

		case 'less than':
			result =
				parseFloat( valueToCompare ) <
				parseFloat( selectOptionToCompare );
			if ( ! selectOptionToCompare && constantValueToCompare ) {
				result =
					parseFloat( valueToCompare ) <
					parseFloat( constantValueToCompare );
			}
			break;

		case 'any':
			result =
				valueToCompare !== undefined &&
				valueToCompare !== null &&
				valueToCompare !== '';
			break;

		case 'empty':
			result =
				valueToCompare === undefined ||
				valueToCompare === null ||
				valueToCompare === '';
			break;

		case 'between':
			result =
				parseFloat( valueToCompare ) >=
					parseFloat( betweenValueInterval.from ) &&
				parseFloat( valueToCompare ) <=
					parseFloat( betweenValueInterval.to );
			break;

		case 'number-multiplier':
			result =
				parseFloat( valueToCompare ) %
					parseFloat( constantValueToCompare ) ===
				0;
			break;

		case 'even-number':
			result = parseFloat( valueToCompare ) % 2 === 0;
			break;

		case 'odd-number':
			result = parseFloat( valueToCompare ) % 2 !== 0;
			break;

		case 'contains':
			result = valueToCompare?.includes( constantValueToCompare );
			break;

		case 'not contains':
			result = ! valueToCompare?.includes( constantValueToCompare );
			break;

		case 'regex':
			if ( typeof constantValueToCompare === 'string' ) {
				const [ _, pattern, flags ] =
					constantValueToCompare.split( '/' );
				const regex = new RegExp(
					pattern || constantValueToCompare,
					flags
				);
				result = regex.test( valueToCompare );
			}
			break;

		default:
		// code
	}

	// console.log(`matching ${v1} ${operator} ${v2}`);
	return result;
}

function ppom_set_default_option( field_id, $scope ) {
	// When a field becomes visible again, restore its default state the same way
	// the original PHP renderer would have populated it on first page load.
	//
	// $scope confines every id/name lookup below to one product's
	// `.ppom-wrapper`: PPOM renders field ids from the bare data_name with no
	// per-product suffix, so two PPOM forms on one page have literally
	// duplicate DOM ids, and an unscoped `#id` lookup silently resolves to
	// whichever form's element happens to be first in the document (issue
	// #735).
	$scope = $scope && $scope.length ? $scope : jQuery( document );
	const product_id = $scope.find( '[name="ppom_product_id"]' ).val();

	const field = ppom_get_field_meta_by_id( field_id, product_id );

	switch ( field.type ) {
		// Check if field is
		case 'switcher':
		case 'radio':
			jQuery.each( field.options, function ( label, options ) {
				const opt_id =
					product_id + '-' + field.data_name + '-' + options.id;
				// console.log('optio nid ', opt_id);

				if ( options.option == field.selected ) {
					$scope
						.find( '#' + opt_id )
						.prop( 'checked', true )
						.trigger( 'change' );
				}
			} );
			break;

		case 'select': {
			// The hidden-field reset calls val('') on a select with no ''
			// option, which leaves selectedIndex at -1 and makes val()
			// return null — so a cleared select must be detected via both.
			const $select = $scope.find( '#' + field.data_name );
			const current_value = $select.val();

			if ( null === current_value || '' === current_value ) {
				$select.val( field.selected );

				// No configured default (or it no longer matches an option):
				// fall back to the first option, matching the state the PHP
				// renderer produces on initial page load.
				if ( null === $select.val() ) {
					$select.prop( 'selectedIndex', 0 );
				}
			}
			break;
		}

		case 'image':
			jQuery.each( field.images, function ( index, img ) {
				if ( img.title == field.selected ) {
					$scope
						.find( '#' + field.data_name + '-' + img.id )
						.prop( 'checked', true );
				}
			} );
			break;

		case 'checkbox':
			jQuery.each( field.options, function ( label, options ) {
				const opt_id =
					product_id + '-' + field.data_name + '-' + options.id;

				// Imported metas can lack the `checked` key entirely.
				const default_checked = ( field.checked || '' ).split( '\r\n' );
				if ( jQuery.inArray( options.option, default_checked ) > -1 ) {
					$scope.find( '#' + opt_id ).prop( 'checked', true );
				}
			} );
			break;

		case 'quantities':
			jQuery.each( field.options, function ( label, options ) {
				//console.log(options);
				if ( options.default === '' ) {
					return;
				}
				const opt_id =
					product_id + '-' + field.data_name + '-' + options.id;
				$scope
					.find( '#' + opt_id )
					.val( options.default )
					.trigger( 'change' );
			} );
			break;

		case 'text':
		case 'date':
		case 'number':
			if ( '' === $scope.find( '#' + field.data_name ).val() ) {
				$scope.find( '#' + field.data_name ).val( field.default_value );
			}
			break;
	}
}

// Mirror the current hidden field list into the hidden input consumed by PHP.
function ppom_fields_hidden_conditionally() {
	// Reset
	ppom_hidden_fields = [];
	// jQuery(`.ppom-field-wrapper.ppom-c-hide`).filter(function() {

	//     const data_name = jQuery(this).data('data_name');
	//     jQuery(`#${data_name}`).prop('required', false);
	//     // console.log(data_name);
	//     ppom_hidden_fields.push(data_name);
	// });
	// console.log("Condionally Hidden", ppom_hidden_fields);
	// jQuery("#conditionally_hidden").val(ppom_hidden_fields);

	// Use the actual visual state: per-source `ppom-locked-*` classes go
	// stale when rules span multiple source fields, but `ppom-c-hide` always
	// reflects what the customer sees.
	jQuery( `.ppom-field-wrapper.ppom-c-hide` ).each( function ( i, h ) {
		ppom_hidden_fields.push( jQuery( h ).data( 'data_name' ) );
	} );
	jQuery( '#conditionally_hidden' ).val( ppom_hidden_fields );
	// console.log(ppom_hidden_fields);
}
