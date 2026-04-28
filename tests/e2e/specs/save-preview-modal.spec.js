/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";
import { createSimpleProduct } from "../fixtures/index.js";
import { createSimpleGroupField } from "../utils";

test.describe("Save & Preview Modal", () => {
	async function saveAttachModal(page) {
		page.once("dialog", (dialog) => dialog.accept());
		await page
			.locator("#ppom-product-form")
			.getByRole("button", { name: "Save", exact: true })
			.click();
		await page.locator("#ppom-product-modal").waitFor({ state: "hidden" });
	}

	async function createGroupOrSkip(admin, page, fieldsCount = 1) {
		try {
			return await createSimpleGroupField(admin, page, fieldsCount);
		} catch (error) {
			const message = error instanceof Error ? error.message : String(error);
			if (message.includes("Add New Group")) {
				test.skip(
					true,
					"PPOM admin page is not accessible in this environment (likely 403)."
				);
			}
			throw error;
		}
	}

	test("save and preview stays disabled with tooltip when no product is eligible", async ({
		admin,
		page,
	}) => {
		const { ppomId } = await createGroupOrSkip(admin, page, 1);

		await admin.visitAdminPage(
			`admin.php?page=ppom&productmeta_id=${ ppomId }&do_meta=edit`
		);

		const previewButton = page.locator(".ppom-save-and-preview");
		const tooltipAnchor = page.locator(".ppom-save-preview-tooltip-anchor");

		await expect(previewButton).toBeDisabled();
		await expect.poll(
			async () => await tooltipAnchor.getAttribute("title"),
		).toContain(
			"Attach this group to at least one product, category, or tag to enable preview.",
		);
	});

	test("save and preview opens modal with iframe for attached products", async ({
		admin,
		page,
		requestUtils,
	}) => {
		const product = await createSimpleProduct(requestUtils, {
			name: `Preview Product ${Date.now()}`,
		});
		const { ppomId } = await createGroupOrSkip(admin, page, 1);

		await admin.visitAdminPage(
			`admin.php?page=ppom&productmeta_id=${ ppomId }&do_meta=edit`
		);

		await page.locator(".ppom-products-modal").click();
		await page.locator("#ppom-product-form").waitFor({ state: "visible" });

		const productSelector = page
			.locator("#ppom-product-form div")
			.filter({ hasText: "Display on Specific Products" })
			.first()
			.locator('select[name="ppom-attach-to-products\\[\\]"]');

		await productSelector.selectOption(String(product.id));
		await saveAttachModal(page);

		const previewButton = page.locator(".ppom-save-and-preview");
		await expect(previewButton).toBeEnabled({ timeout: 15000 });

		await previewButton.click();

		const previewModal = page.locator("#ppom-live-preview-modal");
		const previewIframe = page.locator("#ppom-live-preview-iframe");
		const previewSelect = page.locator("#ppom-preview-product-select");

		await expect(previewModal).toBeVisible();
		await expect(previewIframe).toBeVisible();
		await expect.poll(async () => await previewIframe.getAttribute("src")).toBeTruthy();
		await expect(previewSelect).toHaveValue(String(product.id));
	});
});
