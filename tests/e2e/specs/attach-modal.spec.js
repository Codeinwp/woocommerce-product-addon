/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";
import {
	createProductCategory,
	createProductTag,
	createSimpleProduct,
	createSimpleProducts,
	getPpomAttachRowMeta,
	setPpomLicenseFixture,
} from "../fixtures/index.js";
import {
	addNewField,
	createSimpleGroupField,
	fillFieldNameAndId,
	getInlineAttachContainer,
	getInlineAttachSelect,
	pickFieldTypeInModal,
	saveFieldInModal,
	saveFields,
	saveInlineAttach,
	searchAndSelectInlineProduct,
	selectInlineAttachTerms,
} from "../utils";

test.describe("Inline Attach", () => {
	/**
	 * Attach a new group to a searched product in a larger catalog and
	 * verify it renders only on that product.
	 */
	test("searches 25 products and attaches the selected match", async ({ page, admin, requestUtils }) => {
		const suffix = Date.now();
		const products = await createSimpleProducts(
			requestUtils,
			Array.from({ length: 25 }, (_, index) => {
				const productNumber = String(index + 1).padStart(2, "0");

				return {
					name: `PPOM Search Product ${suffix} ${productNumber}`,
				};
			}),
		);
		const targetProduct = products[22];
		const controlProduct = products[0];

		const { ppomId } = await createSimpleGroupField(admin, page);
		await expect(getInlineAttachContainer(page)).toBeVisible();

		await searchAndSelectInlineProduct(page, targetProduct.name);
		await saveInlineAttach(page);

		await page.goto(`/?p=${targetProduct.id}`);

		const elements = page.locator(`.ppom-id-${ppomId}`);
		const count = await elements.count();
		for (let i = 0; i < count; i++) {
			await expect(elements.nth(i)).toBeVisible();
		}

		await page.goto(`/?p=${controlProduct.id}`);
		await expect(page.locator(`.ppom-id-${ppomId}`)).toHaveCount(0);
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
					name: `Inline Attach Product ${index + 1}`,
					categories: [{ id: category.id }],
				}),
			),
		);

		const { ppomId } = await createSimpleGroupField(admin, page);
		await expect(getInlineAttachContainer(page)).toBeVisible();

		await selectInlineAttachTerms(
			page,
			"categories",
			categoriesToUse.map((category) => ({
				value: category.slug,
				label: category.name,
			})),
		);
		await saveInlineAttach(page);

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
	 * Workflow (not "a product tagged then untagged"):
	 * 1. Seed a WooCommerce product tag so the inline attach UI can list it.
	 * 2. Create a PPOM field group, then under "Display in Specific Product Tags"
	 *    choose that tag — this writes `productmeta_tags` on the PPOM row.
	 * 3. Save the field group form, then read the PPOM row from the DB to
	 *    confirm the slug was stored.
	 * 4. Clear the multiselect (`selectOption([])` = merchant deselected every
	 *    tag), save again.
	 * 5. DB must be empty and after a reload the UI must show no selected
	 *    options (tag must not "come back").
	 */
	test("detach all product tags persists (ppom-pro#625)", async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await setPpomLicenseFixture( requestUtils, { valid: true, plan: 1 } );

		// --- Setup: catalog tag term + PPOM group (no product is tagged here) ---
		const tag = await createProductTag( requestUtils );
		const { ppomId } = await createSimpleGroupField( admin, page );

		// --- Round 1: attach group to that catalog tag; save; verify DB ---
		await expect( getInlineAttachSelect( page, "tags" ) ).toBeAttached();
		await selectInlineAttachTerms( page, "tags", [
			{ value: tag.slug, label: tag.name },
		] );
		await saveInlineAttach( page );

		let row = await getPpomAttachRowMeta( requestUtils, { ppomId } );
		expect( row.productmeta_tags ).toContain( tag.slug );

		// --- Round 2: merchant clears all tag rules; save; DB must stay empty
		// (bug was: old tags reappeared). Round 1's selected slug is already
		// rendered as `<option selected>` post-reload, so no injection here.
		await selectInlineAttachTerms( page, "tags", [] );
		await saveInlineAttach( page );

		row = await getPpomAttachRowMeta( requestUtils, { ppomId } );
		expect( row.productmeta_tags ).toBe( "" );

		// --- Round 3: reload page; UI must match DB (no tag still selected) ---
		await page.reload();
		await expect(
			getInlineAttachSelect( page, "tags" ).locator( "option:checked" ),
		).toHaveCount( 0 );
	} );

	/**
	 * The inline attach UI should appear only after the group exists.
	 *
	 * `templates/admin/ppom-fields.php` gates the render with
	 * `$is_edit_screen && ! $is_new_group`, so a brand-new group should not
	 * render the container, and the same page after the first save should.
	 */
	test("inline attach UI visibility on Group Edit page", async ({ page, admin }) => {
		await admin.visitAdminPage("admin.php?page=ppom");

		await page.getByRole("link", { name: "Add New Group" }).click();
		await page.getByRole("textbox").fill("Test Inline Attach visibility");

		await expect( getInlineAttachContainer(page) ).toHaveCount(0);

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

		await page.waitForURL(/&productmeta_id=\d+&do_meta=edit/);
		await expect( getInlineAttachContainer(page) ).toBeVisible();
	});
});
