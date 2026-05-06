'use strict';

/**
 * Shows/hides PPOM field groups based on the selected WooCommerce variation.
 */
jQuery( function ( $ ) {
	/**
	 * Parses the comma-separated variation IDs from a group's data attribute.
	 * Returns an empty array when no restrictions are set (group is unrestricted).
	 *
	 * @param {jQuery} $group - A `.ppom-variation-rule-group` element.
	 * @return {number[]} Parsed variation IDs.
	 */
	function parseAllowedVariations( $group ) {
		const raw = String( $group.attr( 'data-ppom-allowed-variations' ) || '' );
		return raw
			.split( ',' )
			.map( ( id ) => parseInt( id, 10 ) )
			.filter( ( id ) => id > 0 );
	}

	/**
	 * Disables or re-enables all form inputs inside a field group.
	 * Only touches inputs that were disabled by this script (tracked via
	 * `ppomVariationDisabled` data key) so we don't interfere with inputs
	 * that were already disabled for other reasons.
	 *
	 * @param {jQuery}  $group   - A `.ppom-variation-rule-group` element.
	 * @param {boolean} disabled - True to disable, false to restore.
	 */
	function setInputsDisabled( $group, disabled ) {
		$group.find( ':input' ).each( function () {
			const $input = $( this );

			if ( disabled ) {
				if ( ! $input.prop( 'disabled' ) ) {
					$input.data( 'ppomVariationDisabled', true );
					$input.prop( 'disabled', true );
				}
				return;
			}

			if ( $input.data( 'ppomVariationDisabled' ) ) {
				$input.prop( 'disabled', false );
				$input.removeData( 'ppomVariationDisabled' );
			}
		} );
	}

	/**
	 * Adds or removes the `ppom-c-hide` class on all field wrappers inside a group.
	 * This prevents PPOM's conditional-logic engine from evaluating or revealing
	 * fields that belong to a hidden variation group. Uses the same tracking approach
	 * as setInputsDisabled to only affect fields we hid ourselves.
	 *
	 * @param {jQuery}  $group - A `.ppom-variation-rule-group` element.
	 * @param {boolean} hidden - True to hide fields, false to restore.
	 */
	function setConditionHiddenClass( $group, hidden ) {
		$group.find( '.ppom-field-wrapper' ).each( function () {
			const $field = $( this );

			if ( hidden ) {
				if ( ! $field.hasClass( 'ppom-c-hide' ) ) {
					$field.data( 'ppomVariationHidden', true );
					$field.addClass( 'ppom-c-hide' );
				}
				return;
			}

			if ( $field.data( 'ppomVariationHidden' ) ) {
				$field.removeClass( 'ppom-c-hide' );
				$field.removeData( 'ppomVariationHidden' );
			}
		} );
	}

	/**
	 * Fires ppom_field_shown / ppom_field_hidden once per field inside the group,
	 * matching the payload shape (`{ field: <data_name> }`) that PPOM's conditional
	 * logic, validator, and file-upload listeners expect. Without the payload those
	 * listeners read `e.field` as undefined and either no-op or throw on subsequent
	 * `field_meta.type` access.
	 *
	 * @param {jQuery} $group    - A `.ppom-variation-rule-group` element.
	 * @param {string} eventType - 'ppom_field_shown' or 'ppom_field_hidden'.
	 */
	function triggerFieldEvents( $group, eventType ) {
		$group.find( '.ppom-field-wrapper' ).each( function () {
			const dataName = $( this ).attr( 'data-data_name' );
			if ( ! dataName ) {
				return;
			}
			$.event.trigger( {
				type: eventType,
				field: dataName,
				time: new Date(),
			} );
		} );
	}

	/**
	 * Shows or hides a single variation-restricted group.
	 * Handles visibility, input disabled state, conditional-logic class,
	 * accessibility, and fires ppom_field_shown/hidden events.
	 *
	 * @param {jQuery}  $group - A `.ppom-variation-rule-group` element.
	 * @param {boolean} active - True to show, false to hide.
	 */
	function setGroupActive( $group, active ) {
		if ( active ) {
			$group.show().attr( 'aria-hidden', 'false' );
			setInputsDisabled( $group, false );
			setConditionHiddenClass( $group, false );
			triggerFieldEvents( $group, 'ppom_field_shown' );
		} else {
			$group.hide().attr( 'aria-hidden', 'true' );
			setInputsDisabled( $group, true );
			setConditionHiddenClass( $group, true );
			triggerFieldEvents( $group, 'ppom_field_hidden' );
		}
	}

	/**
	 * Iterates all variation-restricted groups on the page and activates the ones
	 * matching the given variation ID. Unrestricted groups (no data attribute)
	 * are always shown. Triggers a price recalculation after toggling.
	 *
	 * @param {number|string} variationId - The selected WooCommerce variation ID, or 0 if none.
	 */
	function refreshVariationGroups( variationId ) {
		const selectedVariationId = parseInt( variationId, 10 ) || 0;

		$( '.ppom-variation-rule-group' ).each( function () {
			const $group = $( this );
			const allowedVariationIds = parseAllowedVariations( $group );

			if ( allowedVariationIds.length === 0 ) {
				setGroupActive( $group, true );
				return;
			}

			setGroupActive(
				$group,
				selectedVariationId > 0 &&
					allowedVariationIds.includes( selectedVariationId )
			);
		} );

		if ( typeof window.ppom_update_option_prices === 'function' ) {
			window.ppom_update_option_prices();
		} else if ( typeof ppom_update_option_prices === 'function' ) {
			ppom_update_option_prices();
		}
	}

	$( document.body )
		.on( 'show_variation', 'form.variations_form', function ( event, variation ) {
			refreshVariationGroups( variation?.variation_id );
		} )
		.on( 'hide_variation reset_data', 'form.variations_form', function () {
			refreshVariationGroups( 0 );
		} );

	const currentVariationId = $( 'form.variations_form input[name="variation_id"]' ).val();
	refreshVariationGroups( currentVariationId );
} );
