/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";

import {
	attachPpomGroupToProducts,
	createSimpleProduct,
	createSimpleTextGroup,
	deletePpomGroupRows,
	getProductPpomAssignment,
	setPpomGroupReadFailure,
} from "../fixtures/index.js";

/**
 * Issue #679: a transient failure of the field-group read on a front-end
 * product load must not permanently delete the product's `_product_meta_id`
 * assignment. Only a confirmed absence of the group rows may clean it up.
 *
 * @see https://github.com/Codeinwp/woocommerce-product-addon/issues/679
 */
test.describe("PPOM assignment vs transient field-group read failures", () => {
	test.afterEach(async ({ requestUtils }) => {
		// Never leak the failure simulation into other specs.
		await setPpomGroupReadFailure(requestUtils, { enabled: false });
	});

	test("assignment survives a failed group read and the form self-heals", async ({
		page,
		requestUtils,
	}) => {
		const product = await createSimpleProduct(requestUtils, {
			name: "Transient Failure Product",
		});
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			groupName: "Transient Failure Group",
			fieldsNumber: 1,
		});
		await attachPpomGroupToProducts(requestUtils, {
			ppomId,
			productIds: [product.id],
		});

		// Sanity: the form renders while reads are healthy.
		await page.goto(`/?p=${product.id}`);
		await expect(page.locator(`.ppom-id-${ppomId}`).first()).toBeVisible();

		// Simulate the transient DB failure and load the product page once —
		// this single storefront request used to delete the assignment.
		await setPpomGroupReadFailure(requestUtils, { enabled: true });
		await page.goto(`/?p=${product.id}`);
		await expect(page.locator(`.ppom-id-${ppomId}`)).toHaveCount(0);

		// Reads recover.
		await setPpomGroupReadFailure(requestUtils, { enabled: false });

		// The stored assignment must have survived the failed read…
		const assignment = await getProductPpomAssignment(requestUtils, {
			productId: product.id,
		});
		expect(assignment.exists).toBe(true);
		expect(assignment.meta_ids).toContain(ppomId);

		// …and the form must come back without any admin intervention.
		await page.goto(`/?p=${product.id}`);
		await expect(page.locator(`.ppom-id-${ppomId}`).first()).toBeVisible();
	});

	test("deleting a group cleans its product assignment at deletion time", async ({
		page,
		requestUtils,
	}) => {
		const product = await createSimpleProduct(requestUtils, {
			name: "Stale Cleanup Product",
		});
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			groupName: "Stale Cleanup Group",
			fieldsNumber: 1,
		});
		await attachPpomGroupToProducts(requestUtils, {
			ppomId,
			productIds: [product.id],
		});

		// Sanity: the form renders before the group row disappears.
		await page.goto(`/?p=${product.id}`);
		await expect(page.locator(`.ppom-id-${ppomId}`).first()).toBeVisible();

		// Deleting the group row cleans the direct assignment immediately.
		await deletePpomGroupRows(requestUtils, { ppomIds: [ppomId] });
		const assignment = await getProductPpomAssignment(requestUtils, {
			productId: product.id,
		});
		expect(assignment.exists).toBe(false);
		expect(assignment.meta_ids).toEqual([]);

		// Storefront resolution stays read-only and simply renders no fields.
		await page.goto(`/?p=${product.id}`);
		await expect(page.locator(`.ppom-id-${ppomId}`)).toHaveCount(0);
	});
});
