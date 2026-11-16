/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";

test.describe("Support Hub", () => {
	test("Help launcher shows on the PPOM Fields screen only", async ({
		admin,
		page,
	}) => {
		await admin.visitAdminPage("admin.php?page=ppom");
		await expect(page.locator(".ti-support-launcher")).toBeVisible();

		await admin.visitAdminPage("edit.php");
		// The SDK prints this container in admin_footer whenever the hub loads on a screen.
		await expect(page.locator("#ti-support-hub")).toHaveCount(0);
	});
});
