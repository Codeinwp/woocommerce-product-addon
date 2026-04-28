/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";

import {
	attachPpomGroupToProducts,
	createSimpleProduct,
	createSimpleTextGroup,
} from "../fixtures/index.js";

test.describe("Field Group enable/disable toggle", () => {
	/**
	 * Click the per-row toggle on the Field Groups list and wait for the
	 * server response so the cached row is invalidated before we re-render.
	 *
	 * @param {import("@playwright/test").Page} page
	 * @param {number} ppomId
	 */
	async function clickToggleAndWait(page, ppomId) {
		const responsePromise = page.waitForResponse(
			(response) =>
				response.url().includes("admin-ajax.php") &&
				response.request().method() === "POST" &&
				response.request().postData()?.includes("ppom_toggle_meta_disabled"),
		);
		// The <input> is visually hidden (position:absolute; opacity:0), so
		// click the visible <label> wrapper — that flips the checkbox naturally.
		await page.locator(`.ppom-toggle[data-ppom-id="${ppomId}"]`).click();
		const response = await responsePromise;
		const json = await response.json();
		expect(json.status).toBe("success");
		return json;
	}

	/**
	 * Per-row toggle: disabling a group must hide its fields on the
	 * attached product without touching the attachment, and re-enabling
	 * must restore the form.
	 */
	test("disable hides form on frontend, re-enable restores it", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const product = await createSimpleProduct(requestUtils, {
			name: "Toggle Target Product",
		});
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			groupName: "Toggle Group",
			fieldsNumber: 1,
		});
		await attachPpomGroupToProducts(requestUtils, {
			ppomId,
			productIds: [product.id],
		});

		// Sanity: form renders before toggling.
		await page.goto(`/?p=${product.id}`);
		await expect(page.locator(`.ppom-id-${ppomId}`).first()).toBeVisible();

		// Toggle off via the list table.
		// Sort by id desc so newly created groups appear on page 1 of the
		// paginated WP_List_Table (default 50 per page, ascending by id).
		await admin.visitAdminPage(
			"admin.php?page=ppom&orderby=productmeta_id&order=desc",
		);
		const toggleInput = page.locator(
			`.ppom-toggle[data-ppom-id="${ppomId}"] .ppom-toggle-input`,
		);
		await expect(toggleInput).toBeChecked();
		const disableResp = await clickToggleAndWait(page, ppomId);
		expect(disableResp.disabled).toBe(true);
		await expect(toggleInput).not.toBeChecked();

		// Frontend should no longer render the group.
		await page.goto(`/?p=${product.id}`);
		await expect(page.locator(`.ppom-id-${ppomId}`)).toHaveCount(0);

		// Toggle back on.
		// Sort by id desc so newly created groups appear on page 1 of the
		// paginated WP_List_Table (default 50 per page, ascending by id).
		await admin.visitAdminPage(
			"admin.php?page=ppom&orderby=productmeta_id&order=desc",
		);
		await expect(toggleInput).not.toBeChecked();
		const enableResp = await clickToggleAndWait(page, ppomId);
		expect(enableResp.disabled).toBe(false);
		await expect(toggleInput).toBeChecked();

		// Frontend renders the group again — attachment was preserved.
		await page.goto(`/?p=${product.id}`);
		await expect(page.locator(`.ppom-id-${ppomId}`).first()).toBeVisible();
	});

	/**
	 * Bulk "Disable" hides the form for all selected groups in one shot.
	 */
	test("bulk disable hides selected groups on frontend", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const product = await createSimpleProduct(requestUtils, {
			name: "Bulk Toggle Product",
		});
		const groupA = await createSimpleTextGroup(requestUtils, {
			groupName: "Bulk Group A",
			fieldsNumber: 1,
		});
		const groupB = await createSimpleTextGroup(requestUtils, {
			groupName: "Bulk Group B",
			fieldsNumber: 1,
		});
		// Attach both groups to the same product (Pro path); free build will
		// keep only the latest. Either way, the chosen group must disappear
		// once it's bulk-disabled, which is what we assert below.
		await attachPpomGroupToProducts(requestUtils, {
			ppomId: groupA.ppomId,
			productIds: [product.id],
		});
		await attachPpomGroupToProducts(requestUtils, {
			ppomId: groupB.ppomId,
			productIds: [product.id],
		});

		// Sort by id desc so newly created groups appear on page 1 of the
		// paginated WP_List_Table (default 50 per page, ascending by id).
		await admin.visitAdminPage(
			"admin.php?page=ppom&orderby=productmeta_id&order=desc",
		);

		// Select both rows by their checkbox ids (column_cb in the list table).
		await page.locator(`#cb-select-${groupA.ppomId}`).check();
		await page.locator(`#cb-select-${groupB.ppomId}`).check();

		// Choose "Disable" in the top bulk action dropdown and submit.
		await page
			.locator('.ppom-existing-meta-wrapper select[name="action"]')
			.selectOption("disable");
		await page
			.locator('.ppom-existing-meta-wrapper #doaction')
			.click();

		// After redirect, both rows should render as off.
		await expect(
			page.locator(
				`.ppom-toggle[data-ppom-id="${groupA.ppomId}"] .ppom-toggle-input`,
			),
		).not.toBeChecked();
		await expect(
			page.locator(
				`.ppom-toggle[data-ppom-id="${groupB.ppomId}"] .ppom-toggle-input`,
			),
		).not.toBeChecked();

		// Frontend: neither group should render any field block.
		await page.goto(`/?p=${product.id}`);
		await expect(page.locator(`.ppom-id-${groupA.ppomId}`)).toHaveCount(0);
		await expect(page.locator(`.ppom-id-${groupB.ppomId}`)).toHaveCount(0);
	});
});
