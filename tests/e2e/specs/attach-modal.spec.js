/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";
import {
	createProductCategory,
	createProductTag,
	createSimpleProduct,
	getPpomAttachRowMeta,
	setPpomLicenseFixture,
} from "../fixtures/index.js";
import { addNewField, createSimpleGroupField, fillFieldNameAndId, pickFieldTypeInModal, saveFieldInModal, saveFields } from "../utils";

test.describe("Attach Modal", () => {
	async function saveAttachModal(page) {
		page.once("dialog", (dialog) => dialog.accept());
		await page
			.locator("#ppom-product-form")
			.getByRole("button", { name: "Save", exact: true })
			.click();
		await page.locator("#ppom-product-modal").waitFor({ state: "hidden" });
	}

	/**
	 * Attach a new group field to the first product in list then check if it is rendered.
	 */
	test("attach to products and check", async ({ page, admin, requestUtils }) => {
		const product = await createSimpleProduct(requestUtils);

		const { ppomId } = await createSimpleGroupField(admin, page);
		await page.locator(".ppom-products-modal").click();
		await page.locator("#ppom-product-form").waitFor({ state: "visible" });

		const productSelector = page
			.locator("#ppom-product-form div")
			.filter({ hasText: "Display on Specific Products" })
			.first()
			.locator('select[name="ppom-attach-to-products\\[\\]"]');
		await productSelector.selectOption(String(product.id));

		const selectedOption = await productSelector.inputValue();
		await saveAttachModal(page);
		await page.goto(`/?p=${selectedOption}`);

		const elements = page.locator(`.ppom-id-${ppomId}`);
		const count = await elements.count();
		for (let i = 0; i < count; i++) {
			await expect(elements.nth(i)).toBeVisible();
		}
	});

	/**
	 * Attach a new group to multiple categories then check.
	 */
	test("attach to multiple categories", async ({ page, admin, requestUtils }) => {
		const categoriesToUse = await Promise.all([
			createProductCategory(requestUtils),
			createProductCategory(requestUtils),
		]);
		const productsToCheck = await Promise.all(
			categoriesToUse.map((category, index) =>
				createSimpleProduct(requestUtils, {
					name: `Attach Modal Product ${index + 1}`,
					categories: [{ id: category.id }],
				}),
			),
		);

		const { ppomId } = await createSimpleGroupField(admin, page);
		await page.locator(".ppom-products-modal").click();
		await page.locator("#ppom-product-form").waitFor({ state: "visible" });

		await page.evaluate(() => {
			document.querySelector('#attach-to-categories > div.postbox').classList.remove('closed');
		});

		const categoriesSelector = page.locator(
			'#ppom-product-modal select[name="ppom-attach-to-categories\\[\\]"]',
		);

		await categoriesSelector.selectOption(
			categoriesToUse.map((category) => ({ value: category.slug })),
		);
		await saveAttachModal(page);

		for (const product of productsToCheck) {
			await page.goto(`/?p=${product.id}`);

			const elements = page.locator(`.ppom-id-${ppomId}`);
			const count = await elements.count();
			expect(count).toBeGreaterThan(0);
		}
	});

	/**
	 * Regression for ppom-pro#625: clearing tag attachment rules must persist.
	 *
	 * Workflow (not “a product tagged then untagged”):
	 * 1. Seed a WooCommerce product tag so the attach modal can list it (same idea as seeding categories).
	 * 2. Create a PPOM field group, open “Attach to Products”, and under “Display in Specific Product Tags”
	 *    choose that tag — this writes `productmeta_tags` on the PPOM row (show group when catalog products have the tag).
	 * 3. Save via the real modal form, then read the PPOM row from the DB (bootstrap) to confirm the slug was stored.
	 * 4. Reopen the modal, clear the multiselect (`selectOption([])` = merchant deselected every tag), save again.
	 * 5. DB must be empty and the UI must show no selected options after reopening (tag must not “come back”).
	 *
	 * `page.evaluate` only expands the Tags postbox (WP admin starts it collapsed); it does not edit tag data.
	 */
	test("detach all product tags from attach modal persists (ppom-pro#625)", async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await setPpomLicenseFixture( requestUtils, { valid: true, plan: 1 } );

		// --- Setup: catalog tag term + PPOM group (no product is tagged here) ---
		const tag = await createProductTag( requestUtils );
		const { ppomId } = await createSimpleGroupField( admin, page );

		// --- Round 1: attach PPOM group to that catalog tag; save; verify DB ---
		await page.locator( ".ppom-products-modal" ).click();
		await page.locator( "#ppom-product-form" ).waitFor( { state: "visible" } );

		// Uncollapse the Tags section (mirrors the categories test; not related to removing tags).
		await page.evaluate( () => {
			document
				.querySelector( "#attach-to-tags > div.postbox" )
				?.classList.remove( "closed" );
		} );

		const tagsSelect = page.locator(
			'#ppom-product-modal select[name="ppom-attach-to-tags\\[\\]"]',
		);
		await tagsSelect.waitFor( { state: "visible" } );
		await tagsSelect.selectOption( [ tag.slug ] );
		await saveAttachModal( page );

		let row = await getPpomAttachRowMeta( requestUtils, { ppomId } );
		expect( row.productmeta_tags ).toContain( tag.slug );

		// --- Round 2: merchant clears all tag rules; save; DB must stay empty (bug was: old tags reappeared) ---
		await page.locator( ".ppom-products-modal" ).click();
		await page.locator( "#ppom-product-form" ).waitFor( { state: "visible" } );
		await page.evaluate( () => {
			document
				.querySelector( "#attach-to-tags > div.postbox" )
				?.classList.remove( "closed" );
		} );
		await tagsSelect.selectOption( [] );
		await saveAttachModal( page );

		row = await getPpomAttachRowMeta( requestUtils, { ppomId } );
		expect( row.productmeta_tags ).toBe( "" );

		// --- Round 3: reopen modal; UI must match DB (no tag still selected) ---
		await page.locator( ".ppom-products-modal" ).click();
		await page.locator( "#ppom-product-form" ).waitFor( { state: "visible" } );
		await page.evaluate( () => {
			document
				.querySelector( "#attach-to-tags > div.postbox" )
				?.classList.remove( "closed" );
		} );
		await expect( tagsSelect.locator( "option:checked" ) ).toHaveCount( 0 );
	} );

	/**
	 * Attach Modal should be visible only for existing PPOM fields.
	 */
	test("attach modal visibility on Group Edit page", async ({ page, admin }) => {
		await admin.visitAdminPage("admin.php?page=ppom");

		await page.getByRole("link", { name: "Add New Group" }).click();
		await page.getByRole("textbox").fill("Test Attach Modal visibility");

		await expect( page.locator('[data-formmodal-id="ppom-product-modal"]') ).toBeHidden();

		await addNewField(page);
		await pickFieldTypeInModal(page, "text");
		await fillFieldNameAndId(
			page,
			1,
			`Test`,
			`test`,
		);
		await saveFieldInModal(page, 1);
		await saveFields(page);

		await page.waitForLoadState("networkidle");
		await page.reload();

		await expect( page.locator('[data-formmodal-id="ppom-product-modal"]') ).toBeVisible();
	});
});
