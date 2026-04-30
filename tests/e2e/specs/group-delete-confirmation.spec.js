/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";
import { createSimpleTextGroup } from "../fixtures/index.js";

test.describe("Group Delete Confirmation Dialog", () => {
	function getVisibleGroupRows(page) {
		return page.locator(
			"#ppom-meta-table tbody tr:has(input.ppom_product_checkbox)"
		);
	}

	function getGroupRowById(page, ppomId) {
		return page.locator(
			`#ppom-meta-table tbody tr:has(input.ppom_product_checkbox[value="${ppomId}"])`
		);
	}

	async function searchGroupsTable(page, searchTerm) {
		const searchInput = page.locator(
			'#ppom-meta-table_filter input[type="search"]'
		);

		await expect(searchInput).toBeVisible();
		await searchInput.fill(String(searchTerm));
	}

	async function createVisibleGroups(requestUtils, page, admin, count) {
		const groupNamePrefix = `Delete Dialog ${Date.now()}`;

		for (let index = 1; index <= count; index++) {
			await createSimpleTextGroup(requestUtils, {
				groupName: `${groupNamePrefix} ${index}`,
			});
		}

		await admin.visitAdminPage("admin.php?page=ppom");
		await searchGroupsTable(page, groupNamePrefix);
		await expect(getVisibleGroupRows(page)).toHaveCount(count);

		return groupNamePrefix;
	}

	async function getVisibleGroupData(row) {
		const checkbox = row.locator("input.ppom_product_checkbox");

		return {
			id: await checkbox.getAttribute("value"),
			name: await checkbox.getAttribute("data-name"),
		};
	}

	async function selectVisibleGroupRow(row) {
		await row.locator("td.ppom-checkboxe-style label").click();
		await expect(row.locator("input.ppom_product_checkbox")).toBeChecked();
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
		requestUtils,
	}) => {
		await createVisibleGroups(requestUtils, page, admin, 1);
		const row = getVisibleGroupRows(page).first();
		const group = await getVisibleGroupData(row);

		// Select the checkbox for our group
		await selectVisibleGroupRow(row);

		// Trigger bulk delete
		await triggerBulkDelete(page);

		// Wait for confirmation popup
		const popup = await waitForPopup(page);

		// Verify the popup title contains the group name
		const popupTitle = popup.locator(".ppom-popup-title");
		await expect(popupTitle).toBeVisible();

		const titleText = await popupTitle.textContent();

		expect(titleText).toContain(group.name);

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
		requestUtils,
	}) => {
		await createVisibleGroups(requestUtils, page, admin, 3);
		const rows = getVisibleGroupRows(page);
		const groups = [
			await getVisibleGroupData(rows.nth(0)),
			await getVisibleGroupData(rows.nth(1)),
			await getVisibleGroupData(rows.nth(2)),
		];

		// Select checkboxes for all three groups
		await selectVisibleGroupRow(rows.nth(0));
		await selectVisibleGroupRow(rows.nth(1));
		await selectVisibleGroupRow(rows.nth(2));

		// Trigger bulk delete
		await triggerBulkDelete(page);

		// Wait for confirmation popup
		const popup = await waitForPopup(page);

		// Verify the popup title contains all group names
		const popupTitle = popup.locator(".ppom-popup-title");
		await expect(popupTitle).toBeVisible();

		const titleText = await popupTitle.textContent();

		for (const group of groups) {
			expect(titleText).toContain(group.name);
		}

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
		requestUtils,
	}) => {
		await createVisibleGroups(requestUtils, page, admin, 1);
		const groupRow = getVisibleGroupRows(page).first();
		const group = await getVisibleGroupData(groupRow);

		// Verify the group exists in the table
		await expect(groupRow).toBeVisible();

		// Select the checkbox for our group
		await selectVisibleGroupRow(groupRow);

		// Trigger bulk delete
		await triggerBulkDelete(page);

		// Wait for confirmation popup and verify it shows the group name
		const popup = await waitForPopup(page);
		const popupTitle = popup.locator(".ppom-popup-title");
		await expect(popupTitle).toContainText(group.name);

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
		await Promise.all([
			page.waitForURL(/admin\.php\?page=ppom/, {
				waitUntil: "domcontentloaded",
			}),
			confirmDeletion(page),
		]);

		// Verify the group is no longer in the table
		await searchGroupsTable(page, group.id);
		await expect(getGroupRowById(page, group.id)).not.toBeVisible();
	});

	/**
	 * Delete single group using delete link (not bulk action)
	 */
	test("displays group name when deleting via single delete link", async ({
		page,
		admin,
		requestUtils,
	}) => {
		await createVisibleGroups(requestUtils, page, admin, 1);
		const row = getVisibleGroupRows(page).first();
		const group = await getVisibleGroupData(row);

		// Find and click the delete link for this specific group
		const deleteLink = row.locator("a.ppom-delete-single-product");
		await expect(deleteLink).toBeVisible();
		await deleteLink.click();

		// Wait for confirmation popup
		const popup = await waitForPopup(page);

		// Verify the popup title contains the group name
		const popupTitle = popup.locator(".ppom-popup-title");
		await expect(popupTitle).toBeVisible();

		const titleText = await popupTitle.textContent();

		// The confirmation should contain the group name
		expect(titleText).toContain(group.name);

		await cancelDeletion(page);
	});
});
