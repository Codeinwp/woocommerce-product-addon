/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";
import { createSimpleGroupField } from "../utils";

test.describe("Group Delete Confirmation Dialog", () => {
	/**
	 * Helper to get the bulk-select checkbox input for a specific group.
	 *
	 * `MetaGroupsListTable::column_cb()` renders the input alongside a
	 * sibling `<label class="screen-reader-text">`, so we target the input
	 * directly — Playwright's `.check()` / `.click()` handle visibility.
	 */
	function getGroupCheckbox(page, ppomId) {
		return page.locator(`input.ppom_product_checkbox[value="${ppomId}"]`);
	}

	/**
	 * Helper to trigger bulk delete action via the WP_List_Table toolbar.
	 *
	 * The list table renders two bulk-action selects (top + bottom). We pick
	 * the top select and submit through its paired Apply button, which is
	 * what fires the JS confirmation popup.
	 */
	async function triggerBulkDelete(page) {
		await page.locator("#bulk-action-selector-top").selectOption("delete");
		await page.locator("#doaction").click();
	}

	/**
	 * Helper to wait for and get the popup dialog
	 */
	async function waitForPopup(page) {
		await page.waitForSelector(".ppom-popup-overlay", { state: "visible" });
		return page.locator(".ppom-popup-overlay");
	}

	/**
	 * Helper to confirm deletion in popup
	 */
	async function confirmDeletion(page) {
		await page.locator(".ppom-popup-overlay .ppom-btn-confirm").click();
	}

	async function cancelDeletion(page) {
		await page.locator(".ppom-popup-overlay .ppom-btn-cancel").click();
	}

	/**
	 * Delete single group and verify group name in confirmation dialog
	 */
	test("displays single group name in delete confirmation dialog", async ({
		page,
		admin,
	}) => {
		const { baseName, ppomId } = await createSimpleGroupField(admin, page);

		// Navigate to the groups list
		// Sort by id desc so the just-created group lands on page 1 of the
		// paginated WP_List_Table (default 50 per page, ascending by id).
		await admin.visitAdminPage(
			"admin.php?page=ppom&orderby=productmeta_id&order=desc"
		);

		// Select the checkbox for our group
		const checkbox = getGroupCheckbox(page, ppomId);
		await checkbox.click();

		// Trigger bulk delete
		await triggerBulkDelete(page);

		// Wait for confirmation popup
		const popup = await waitForPopup(page);

		// Verify the popup title contains the group name
		const popupTitle = popup.locator(".ppom-popup-title");
		await expect(popupTitle).toBeVisible();

		const titleText = await popupTitle.textContent();

		expect(titleText).toContain("Test Group Field");

		// Verify it's formatted correctly (should replace %s with the group name)
		expect(titleText).not.toContain("%s");

		await cancelDeletion(page);
	});

	/**
	 * Delete multiple groups and verify all group names in confirmation dialog
	 */
	test("displays multiple group names in delete confirmation dialog", async ({
		page,
		admin,
	}) => {
		// Create three groups
		const group1 = await createSimpleGroupField(admin, page);
		const group2 = await createSimpleGroupField(admin, page);
		const group3 = await createSimpleGroupField(admin, page);

		// Navigate to the groups list
		// Sort by id desc so the just-created group lands on page 1 of the
		// paginated WP_List_Table (default 50 per page, ascending by id).
		await admin.visitAdminPage(
			"admin.php?page=ppom&orderby=productmeta_id&order=desc"
		);

		// Select checkboxes for all three groups
		await getGroupCheckbox(page, group1.ppomId).check();
		await getGroupCheckbox(page, group2.ppomId).check();
		await getGroupCheckbox(page, group3.ppomId).check();

		// Trigger bulk delete
		await triggerBulkDelete(page);

		// Wait for confirmation popup
		const popup = await waitForPopup(page);

		// Verify the popup title contains all group names
		const popupTitle = popup.locator(".ppom-popup-title");
		await expect(popupTitle).toBeVisible();

		const titleText = await popupTitle.textContent();

		// All three group names should be in the confirmation (all are "Test Group Field")
		expect(titleText).toContain("Test Group Field");

		// Should be comma-separated.
		expect(titleText).toMatch(/,/);

		await cancelDeletion(page);
	});

	/**
	 * Confirm deletion and verify groups are removed
	 */
	test("successfully deletes selected groups after confirmation", async ({
		page,
		admin,
	}) => {
		const { baseName, ppomId } = await createSimpleGroupField(admin, page);

		// Navigate to the groups list
		// Sort by id desc so the just-created group lands on page 1 of the
		// paginated WP_List_Table (default 50 per page, ascending by id).
		await admin.visitAdminPage(
			"admin.php?page=ppom&orderby=productmeta_id&order=desc"
		);

		// Verify the group exists in the table
		const groupRow = page.locator(`tr td:has-text("${ppomId}")`);
		await expect(groupRow).toBeVisible();

		// Select the checkbox for our group
		await getGroupCheckbox(page, ppomId).check();

		// Trigger bulk delete
		await triggerBulkDelete(page);

		// Wait for confirmation popup and verify it shows the group name
		const popup = await waitForPopup(page);
		const popupTitle = popup.locator(".ppom-popup-title");
		await expect(popupTitle).toContainText("Test Group Field");

		// Confirm the deletion. The WP_List_Table form submits to the server,
		// which deletes the groups and redirects back with `ppom_deleted=N`.
		await Promise.all([
			page.waitForURL(/ppom_deleted=/),
			confirmDeletion(page),
		]);

		// Verify the group is no longer in the table
		await expect(groupRow).not.toBeVisible();
	});

	/**
	 * Delete single group using delete link (not bulk action)
	 */
	test("displays group name when deleting via single delete link", async ({
		page,
		admin,
	}) => {
		const { baseName, ppomId } = await createSimpleGroupField(admin, page);

		// Navigate to the groups list
		// Sort by id desc so the just-created group lands on page 1 of the
		// paginated WP_List_Table (default 50 per page, ascending by id).
		await admin.visitAdminPage(
			"admin.php?page=ppom&orderby=productmeta_id&order=desc"
		);

		// The Delete row-action is rendered inside a `.row-actions` block that
		// WP_List_Table hides until the row is hovered, so reveal it first.
		const row = page.locator(
			`tr:has(input.ppom_product_checkbox[value="${ppomId}"])`
		);
		await row.hover();

		const deleteLink = row.locator(
			`a.ppom-delete-single-product[data-product-id="${ppomId}"]`
		);
		await deleteLink.click();

		// Wait for confirmation popup
		const popup = await waitForPopup(page);

		// Verify the popup title contains the group name
		const popupTitle = popup.locator(".ppom-popup-title");
		await expect(popupTitle).toBeVisible();

		const titleText = await popupTitle.textContent();

		// The confirmation should contain the group name
		expect(titleText).toContain("Test Group Field");

		await cancelDeletion(page);
	});
});
