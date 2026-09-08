/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildDateField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

test.describe( 'Date field min date', () => {
	/**
	 * "Disable past dates" must not relax a future min date lead time.
	 */
	test( 'keeps the min date lead time when past dates are disabled', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'delivery_date';

		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Date Min Date Test',
			fields: [
				buildDateField( {
					title: 'Delivery date',
					dataName: fieldId,
					minDate: '+2d',
					pastDates: 'on',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		// Freeze "today" so the rendered month and the lead time are deterministic.
		await page.clock.setFixedTime( new Date( '2026-06-10T12:00:00' ) );

		await page.goto( `/?p=${ product.id }` );

		const dateInput = page.locator(
			`input[name="ppom[fields][${ fieldId }]"]`
		);
		await expect( dateInput ).toBeVisible();

		await dateInput.click();

		const calendar = page.locator( '#ui-datepicker-div' );
		await expect( calendar ).toBeVisible();
		// changeMonth/changeYear render the header as selects.
		await expect( calendar.locator( 'select.ui-datepicker-month' ) ).toHaveValue( '5' );
		await expect( calendar.locator( 'select.ui-datepicker-year' ) ).toHaveValue( '2026' );

		const dayCell = ( day ) =>
			calendar.locator( 'td:not(.ui-datepicker-other-month)', {
				hasText: new RegExp( `^${ day }$` ),
			} );

		// Today and today + 1 are before the +2d lead time.
		await expect( dayCell( 10 ) ).toHaveClass( /ui-state-disabled/ );
		await expect( dayCell( 11 ) ).toHaveClass( /ui-state-disabled/ );

		// Today + 2 is the first selectable date.
		await expect( dayCell( 12 ) ).not.toHaveClass( /ui-state-disabled/ );

		await dayCell( 12 ).locator( 'a' ).click();

		await expect( dateInput ).toHaveValue( '06/12/2026' );
	} );
} );
