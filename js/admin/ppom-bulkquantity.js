'use strict';

/**
 * Admin editor for the bulkquantity field type.
 *
 * The table built here is serialized into JSON and stored in the field meta.
 * Frontend scripts later read that JSON to map entered quantities to the
 * matching price/base-price row on the product page.
 *
 * @see ppom_bulkquantity_price_manager in js/ppom.inputs.js
 */

/**
 * Row shape produced by `tableToJSON()` for a bulkquantity matrix.
 *
 * The first two headers are expected to remain "Quantity Range" and
 * "Base Price"; any additional headers represent variation labels whose cell
 * values become the unit prices for that range.
 *
 * @typedef {{
 *   'Quantity Range': string,
 *   'Base Price'?: string
 * }} PPOMBulkQuantityRow
 */
jQuery( function ( $ ) {
	const body = $( 'body' );

	// Encapsulates the table editor so add/edit flows reuse the same validation
	// and input-mask setup before the hidden JSON payload is updated.
	const ppomBQ = {
		// Range inputs are kept as strings like `1-10` because `tableToJSON()`
		// later serializes visible table cells, not hidden numeric inputs.
		setMaskRangeInput() {
			$( '.ppom-bulk-qty-val-picker,.ppom-bulk-qty-val' ).each(
				( i, el ) => {
					const input = $( el );

					if ( input.inputmask( 'hasMaskedValue' ) ) {
						return true;
					}

					input.inputmask( { regex: '[0-9]*-[0-9]*' } );
				}
			);
		},
		/**
		 * Validate the matrix before it is persisted into the hidden JSON field.
		 *
		 * The frontend price resolver assumes ranges are non-overlapping and
		 * ordered as `start-end`; allowing bad data here would make product-page
		 * price lookups ambiguous.
		 *
		 * @param {PPOMBulkQuantityRow[]} formData
		 * @return {boolean}
		 */
		formValidation( formData ) {
			const pattern = new RegExp( '^([0-9]+)-([0-9]+)$' );
			const notification = ( msgSlug, magicValues ) => {
				let msg = ppom_bq.i18n.validation[ msgSlug ];

				for ( const [ key, value ] of Object.entries( magicValues ) ) {
					msg = msg.replace( `{${ key }}`, value );
				}

				alert( msg );
			};

			const globalRanges = [];

			for ( const el of formData ) {
				const range = el[ 'Quantity Range' ];

				if ( ! pattern.test( range ) ) {
					notification( 'invalid_pattern', { range } );
					return false;
				}

				const rangeVals = range.split( '-' );
				const start = parseInt( rangeVals[ 0 ] );
				const end = parseInt( rangeVals[ 1 ] );

				// rule: start value should be lower than the end value
				if ( end < start ) {
					notification( 'end_bigger_than_start', { range } );
					return false;
				}

				// rule: start cannot be equal to end
				if ( start === end ) {
					notification( 'start_cannot_be_equal_with_end', { range } );
					return false;
				}

				// rule: check if there are any intersection with another range
				for ( const anotherRange of globalRanges ) {
					const aStart = parseInt( anotherRange.start );
					const aEnd = parseInt( anotherRange.end );
					const aRange = anotherRange.range;

					if (
						( start >= aStart && start <= aEnd ) ||
						( end >= aStart && end <= aEnd )
					) {
						notification( 'range_intersection', {
							range1: range,
							range2: aRange,
						} );
						return false;
					}
				}

				globalRanges.push( { start, end, range } );
			}

			return true;
		},
		/**
		 * Turn the read-only preview table back into an editable grid.
		 *
		 * The saved state is rendered as plain table text for compactness inside
		 * the builder. Editing reverses that presentation step by replacing each
		 * cell with an input and restoring the row-removal affordance.
		 *
		 * @param {JQuery<HTMLElement>} el
		 * @return {void}
		 */
		showEditForm( el ) {
			const bulk_wrap = el.closest( '.ppom-bulk-quantity-wrapper' );
			bulk_wrap
				.find( 'table' )
				.find( 'tbody tr td' )
				.each( function ( index, el ) {
					const class_name = $( el ).attr( 'id' );
					const td_wrap = $( this );
					const cross_icon =
						'<span class="remove ppom-rm-bulk-qty"><i class="fa fa-times" aria-hidden="true"></i></span>';
					if ( class_name == 'ppom-bulkqty-adjust-cross' ) {
						var input =
							'' +
							cross_icon +
							'<input type="text" class="form-control ppom-bulk-qty-val-picker" value="' +
							$( this ).text() +
							'">';
					} else {
						var input =
							'<input type="text" class="form-control" value="' +
							$( this ).text() +
							'">';
					}

					td_wrap.closest( 'td' ).html( input );
				} );

			// show action
			$( this ).hide();
			bulk_wrap.find( '.ppom-bulk-action-wrap' ).show();
			bulk_wrap.find( '.ppom-save-bulk-json' ).show();

			this.setMaskRangeInput();
		},
	};

	body.ready( function () {
		ppomBQ.setMaskRangeInput();
	} );

	$( document ).on(
		'ppom_new_field_created',
		( e, newField, fieldNo, fieldType ) => {
			if ( fieldType !== 'bulkquantity' ) {
				return;
			}

			ppomBQ.setMaskRangeInput();
		}
	);

	body.on( 'click', 'button.ppom-add-bulk-qty-row', function ( e ) {
		e.preventDefault();

		const main_wrapper = $( this ).closest( '.ppom-slider' );
		const field_index = main_wrapper
			.find( '.ppom-fields-actions' )
			.attr( 'data-field-no' );
		const bulk_div = $( this ).closest( 'div' );
		const bulk_qty_val = bulk_div.find( '.ppom-bulk-qty-val' ).val();
		const table = $( this ).closest( 'div.table-content' ),
			tbody = table.find( 'tbody' ),
			thead = table.find( 'thead' );

		// Clone the last visible row so merchants keep the same number of
		// variation columns while defining the next quantity interval.
		const clon_qty_section = tbody.find( 'tr:last-child' ).clone();
		clon_qty_section
			.find( '.ppom-bulk-qty-val-picker' )
			.val( bulk_qty_val );
		clon_qty_section.appendTo( tbody );

		ppomBQ.setMaskRangeInput();
	} );

	body.on( 'click', 'span.ppom-rm-bulk-qty', function ( e ) {
		e.preventDefault();

		const count = $( this ).closest( 'tbody' ).find( 'tr' ).length;
		if ( count < 2 ) {
			alert( 'sorry! you can not remove more textbox' );
			return;
		}
		$( this ).closest( 'tr' ).remove();
	} );

	body.on( 'click', 'span.ppom-rm-bulk-variation', function ( e ) {
		e.preventDefault();

		const cell = $( this ).closest( 'th' ),
			index = cell.index() + 1;
		cell.closest( 'table' )
			.find( 'th, td' )
			.filter( ':nth-child(' + index + ')' )
			.remove();
	} );

	body.on( 'click', 'button.ppom-add-bulk-variation-col', function ( e ) {
		e.preventDefault();

		const buk_div = $( this ).closest( 'div' );
		const bulk_variation_val = buk_div
			.find( '.ppom-bulk-variation-val' )
			.val();
		// console.log(bulk_variation_val);
		const table = $( this ).closest( 'div.table-content' ).find( 'table' ),
			thead = table.find( 'thead' ),
			lastTheadRow = thead.find( 'tr:last-child' ),
			tbody = table.find( 'tbody' );
		const closest_td = tbody.find( 'td:last-child' );

		// Every added column becomes a new object key when `tableToJSON()`
		// serializes the matrix, so the header text is the persisted identifier.
		$( '<th>', {
			html:
				' <span class="ppom-bulk-variation-meta"> ' +
				bulk_variation_val +
				' </span> <span class="remove ppom-rm-bulk-variation"><i class="fa fa-times" aria-hidden="true"></i></span>',
		} ).appendTo( lastTheadRow );
		$( '<td>', {
			html: '<input type="text" class="form-control" />',
		} ).insertAfter( closest_td );
	} );

	// Convert the editable grid back into the JSON blob later consumed by
	// `ppom_bulkquantity_price_manager()` on the product page.
	$( 'body' ).on( 'click', '.ppom-save-bulk-json', function ( event ) {
		event.preventDefault();

		const bulk_wrap = $( this ).closest( '.ppom-bulk-quantity-wrapper' );
		bulk_wrap
			.find( 'table' )
			.find( 'input' )
			.each( function ( index, el ) {
				const td_wrap = $( this );
				td_wrap.closest( 'td' ).html( td_wrap.val() );
			} );
		const bulkData = bulk_wrap.find( 'table' ).tableToJSON();

		if ( ! ppomBQ.formValidation( bulkData ) ) {
			ppomBQ.showEditForm( $( this ) );
			return;
		}

		bulk_wrap
			.find( '.ppom-saved-bulk-data' )
			.val( JSON.stringify( bulkData ) );

		// hide action
		$( this ).hide();
		bulk_wrap.find( '.ppom-bulk-action-wrap' ).hide();
		bulk_wrap.find( '.ppom-edit-bulk-json' ).show();
	} );

	// Rehydrate the read-only table into inputs so an existing matrix can be edited.
	$( 'body' ).on( 'click', '.ppom-edit-bulk-json', function ( event ) {
		event.preventDefault();
		ppomBQ.showEditForm( $( this ) );
	} );
} );
