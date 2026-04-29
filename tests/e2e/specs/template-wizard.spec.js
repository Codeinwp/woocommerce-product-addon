/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";

const MODAL = "#ppom-template-wizard-modal";

async function openWizard( page ) {
	await page
		.locator( '[data-modal-id="ppom-template-wizard-modal"]' )
		.first()
		.click();
	await expect( page.locator( MODAL ) ).toBeVisible();
}

test.describe( "Template Library Wizard", () => {
	test( "opens from the toolbar and lists Free + Pro templates", async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( "admin.php?page=ppom" );

		// Wizard markup is on the page but hidden by default.
		const modal = page.locator( MODAL );
		await expect( modal ).toBeHidden();

		await openWizard( page );

		// Title and start-from-scratch tile.
		await expect(
			modal.getByRole( "heading", { name: "Set Up Product Fields" } )
		).toBeVisible();
		await expect(
			modal.locator( ".ppom-template-card--scratch" )
		).toBeVisible();

		// At least the two known free templates render with their titles.
		await expect(
			modal.locator(
				'.ppom-template-card--free .ppom-template-tile[data-template="food-customiser"]'
			)
		).toBeVisible();
		await expect(
			modal.locator(
				'.ppom-template-card--free .ppom-template-tile[data-template="personalised-gift"]'
			)
		).toBeVisible();

		// Pro section is rendered with locked tiles when Pro plugin is not active.
		await expect(
			modal.locator( ".ppom-template-divider__label", {
				hasText: "Pro templates",
			} )
		).toBeVisible();
		await expect(
			modal.locator( ".ppom-template-card--pro" ).first()
		).toHaveClass( /ppom-template-card--locked/ );
		await expect(
			modal.locator( ".ppom-template-card__pro-badge" ).first()
		).toHaveText( "PRO" );
	} );

	test( "Cancel button hides the wizard", async ( { page, admin } ) => {
		await admin.visitAdminPage( "admin.php?page=ppom" );
		await openWizard( page );

		await page
			.locator( MODAL )
			.getByRole( "button", { name: "Cancel" } )
			.click();

		await expect( page.locator( MODAL ) ).toBeHidden();
	} );

	test( '"Start from scratch" navigates to the new-group editor', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( "admin.php?page=ppom" );
		await openWizard( page );

		await Promise.all( [
			page.waitForURL( /action=new/ ),
			page.locator( MODAL + " .ppom-template-card--scratch" ).click(),
		] );

		// Editor renders the empty fields form.
		await expect(
			page.getByRole( "button", { name: "Save Fields" } )
		).toBeVisible();
		await expect(
			page.getByRole( "button", { name: "Add field" } )
		).toBeVisible();
	} );

	test( "imports a free template and lands on the editor with the imported fields", async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( "admin.php?page=ppom" );
		await openWizard( page );

		// personalised-gift defines 5 fields with these data_names.
		const expectedDataNames = [
			"personalisation_text",
			"font",
			"delivery_date",
			"gift_wrapping",
			"card_message",
		];

		await Promise.all( [
			page.waitForURL(
				/wp-admin\/admin\.php\?page=ppom&productmeta_id=\d+&do_meta=edit/
			),
			page
				.locator(
					MODAL +
						' .ppom-template-tile[data-template="personalised-gift"]'
				)
				.click(),
		] );

		await expect(
			page.getByRole( "button", { name: "Save Fields" } )
		).toBeVisible();

		// Group name should match the template's group_name.
		await expect(
			page.locator( 'input[name="productmeta_name"]' )
		).toHaveValue( "Personalised Gift" );

		// Each templated field is present in the summary table by data_name.
		const renderedDataNames = await page.$$eval(
			"td.ppom_meta_field_id",
			( tds ) => tds.map( ( td ) => td.innerText.trim() )
		);

		expect( renderedDataNames ).toHaveLength( expectedDataNames.length );
		for ( const dataName of expectedDataNames ) {
			expect( renderedDataNames ).toContain( dataName );
		}
	} );

	test( "locked Pro tile does not import and exposes the Upgrade link", async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( "admin.php?page=ppom" );
		await openWizard( page );

		const modal = page.locator( MODAL );
		const lockedTile = modal
			.locator( ".ppom-template-card--locked" )
			.first();

		// Inline upgrade link is visible inside the locked card.
		const upgradeLink = lockedTile.locator(
			"a.ppom-template-card__upgrade"
		);
		await expect( upgradeLink ).toBeVisible();
		await expect( upgradeLink ).toHaveAttribute( "target", "_blank" );
		const href = await upgradeLink.getAttribute( "href" );
		expect( href ).toBeTruthy();
		expect( href ).not.toBe( "" );

		// Capture any AJAX import attempt so we can assert it does NOT fire.
		let importRequested = false;
		page.on( "request", ( request ) => {
			if (
				request.method() === "POST" &&
				request.url().includes( "admin-ajax.php" )
			) {
				const body = request.postData() ?? "";
				if ( body.includes( "action=ppom_import_template" ) ) {
					importRequested = true;
				}
			}
		} );

		// Click the locked tile itself (not the upgrade link).
		await lockedTile.locator( ".ppom-template-tile" ).click();

		// Give the JS handler a chance to run if it were going to.
		await page.waitForTimeout( 250 );

		expect( importRequested ).toBe( false );

		// We're still on the listing page with the modal open.
		await expect( page ).toHaveURL( /page=ppom/ );
		await expect( modal ).toBeVisible();
	} );
} );
