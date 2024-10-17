/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";
import { createSimpleGroupField } from "../utils";

test.describe("Attach Modal", () => {
	/**
	 * Attach a new group field to the first product in list then check if it is rendered.
	 */
	test("attach to products and check", async ({ page, admin }) => {
		await createSimpleGroupField(admin, page);
		await page.waitForTimeout(500);
		await admin.visitAdminPage("admin.php?page=ppom");

		const firstRow = page
			.locator("#ppom-groups-export-form tbody tr")
			.first();
		const ppomId = await firstRow.locator("td").nth(1).innerText();
		await firstRow.getByText("Attach to Products").click();

		const productSelector = page
			.locator("#ppom-product-form div")
			.filter({ hasText: "Display on Specific Products" })
			.first()
			.locator('select[name="ppom-attach-to-products\\[\\]"]');
		await productSelector.selectOption({ index: 0 });

		const selectedOption = await productSelector.inputValue();
		console.log("Selected option value:", selectedOption);
		await page.getByRole("button", { name: "Save" }).click();

		await page.waitForLoadState("networkidle");
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
	test("attach to multiple categories", async ({ page, admin }) => {
		const categoriesToUse = ["test_cat_1", "test_cat_2"];

		await createSimpleGroupField(admin, page);
		await page.waitForTimeout(500);
		await admin.visitAdminPage("admin.php?page=ppom");

		const firstRow = page
			.locator("#ppom-groups-export-form tbody tr")
			.first();
		const ppomId = await firstRow.locator("td").nth(1).innerText();
		await firstRow.getByText("Attach to Products").click();
		await page.waitForLoadState("networkidle");

		await page.evaluate(() => {
			document.querySelector('#attach-to-categories > div.postbox').classList.remove('closed');
		});

		const categoriesSelector = page.locator(
			'#ppom-product-modal select[name="ppom-attach-to-categories\\[\\]"]',
		);
	
		// NOTE: categories created by `create-prodcuts.sh`
		await categoriesSelector.selectOption(
			categoriesToUse.map((c) => ({ value: c })),
		);
		await page.getByRole("button", { name: "Save" }).click();
		await page.waitForLoadState("networkidle");
		await page.reload();

		for (const cat of categoriesToUse) {
			await page.goto(`/?product_cat=${cat}`);
			await page.locator('a.add_to_cart_button').first().click();

			const elements = page.locator(`.ppom-id-${ppomId}`);
			const count = await elements.count();
			expect(count ).toBeGreaterThan(0);
		}
	});
});
