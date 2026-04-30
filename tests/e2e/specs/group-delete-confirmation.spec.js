/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";
import { createSimpleGroupField } from "../utils";

test.describe("Group Delete Confirmation Dialog", () => {
	/**
	 * Helper to get checkbox for a specific group
	 */
	function getGroupCheckbox(page, ppomId) {
		return page.locator(`input.ppom_product_checkbox[value="${ppomId}"]`).locator('xpath=ancestor::label');
	}

	async function filterGroupsById(page, ppomIds) {
		const ids = (Array.isArray(ppomIds) ? ppomIds : [ppomIds]).map(String);
		await page.evaluate((groupIds) => {
			const table = window.jQuery("#ppom-meta-table").DataTable();
			table.search(groupIds.join("|"), true, false).draw();
		}, ids);
		await expect(
			page.locator(`input.ppom_product_checkbox[value="${ids[0]}"]`)
		).toBeVisible();
	}

	/**
	 * Helper to trigger bulk delete action
	 */
	async function triggerBulkDelete(page) {
		await page.locator("#ppom-bulk-actions").selectOption("delete");
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
		await admin.visitAdminPage("admin.php?page=ppom");
		await filterGroupsById(page, ppomId);

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
		await admin.visitAdminPage("admin.php?page=ppom");
		await filterGroupsById(page, [group1.ppomId, group2.ppomId, group3.ppomId]);

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
		await admin.visitAdminPage("admin.php?page=ppom");
		await filterGroupsById(page, ppomId);

		// Verify the group exists in the table
		const groupRow = page.locator(`input.ppom_product_checkbox[value="${ppomId}"]`).locator("xpath=ancestor::tr");
		await expect(groupRow).toBeVisible();

		// Select the checkbox for our group
		await getGroupCheckbox(page, ppomId).check();

		// Trigger bulk delete
		await triggerBulkDelete(page);

		// Wait for confirmation popup and verify it shows the group name
		const popup = await waitForPopup(page);
		const popupTitle = popup.locator(".ppom-popup-title");
		await expect(popupTitle).toContainText("Test Group Field");

		// Confirm the deletion
		await confirmDeletion(page);

		// Wait for success confirmation popup
		await page.waitForSelector(".ppom-popup-overlay", { state: "visible" });
		const successPopup = page.locator(".ppom-popup-overlay");
		const successTitle = successPopup.locator(".ppom-popup-title");

		// Verify success message appears
		await expect(successTitle).toBeVisible();
		const successText = await successTitle.textContent();

		expect(successText).toEqual("Done");
		await confirmDeletion(page);

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
		await admin.visitAdminPage("admin.php?page=ppom");
		await filterGroupsById(page, ppomId);

		// Find and click the delete link for this specific group
		const deleteLink = page.locator(
			`a.ppom-delete-single-product[data-product-id="${ppomId}"]`
		);
		await deleteLink.waitFor({ state: "visible" });
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
