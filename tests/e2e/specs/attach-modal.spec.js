/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";
import { createSimpleGroupField } from "../utils";

test.describe("Attach Modal", () => {
	test("attach to products", async ({ page, admin }) => {
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
});
