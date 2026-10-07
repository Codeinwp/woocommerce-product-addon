/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";

import {
	attachPpomGroupToProducts,
	buildPriceMatrixField,
	buildSelectField,
	createPpomGroup,
	createSimpleProduct,
} from "../fixtures/index.js";

/**
 * With one price matrix per option of a controlling Select, the first price
 * table was built before the condition engine decided which matrix is on
 * screen. Every matrix input still carried the `active` class at that moment,
 * so the collector asked jQuery whether "the active matrix" was hidden, got
 * yes for the set, and dropped the matrix. The page showed the catalog price
 * while the cart charged the matrix price.
 */
function uniqueToken() {
	return `${Date.now()}_${Math.floor(Math.random() * 1e6)}`;
}

test.describe("Conditional price matrices", () => {
	test("initial total prices from the visible matrix, not the catalog price", async ({
		page,
		requestUtils,
	}) => {
		const token = uniqueToken();
		const lookId = `look_${token}`;
		const glossy = { label: "Glossy", value: "glossy" };
		const matte = { label: "Matte", value: "matte" };

		const product = await createSimpleProduct(requestUtils, {
			regular_price: "0.24",
		});

		const matrixFor = (suffix, showWhen, first) =>
			buildPriceMatrixField({
				title: `Tiers ${suffix}`,
				dataName: `matrix_${suffix}_${token}`,
				options: [
					{ option: "1-1", price: first },
					{ option: "2-9", price: "1.80" },
				],
				logic: "on",
				conditions: {
					visibility: "Show",
					bound: "All",
					rules: [
						{
							elements: lookId,
							operators: "is",
							element_values: showWhen,
						},
					],
				},
			});

		const { ppomId } = await createPpomGroup(requestUtils, {
			groupName: `Conditional matrices ${token}`,
			fields: [
				buildSelectField({
					title: "Look",
					dataName: lookId,
					options: [glossy, matte],
				}),
				matrixFor("glossy", glossy.label, "2.02"),
				matrixFor("matte", matte.label, "2.12"),
			],
		});

		await attachPpomGroupToProducts(requestUtils, {
			ppomId,
			productIds: [product.id],
		});

		await page.goto(`/?p=${product.id}`);

		const table = page.locator("#ppom-price-container");

		// The browser preselects the first option, so its matrix is the visible
		// one and its first tier is what the shopper must be quoted.
		await expect(table).toContainText("2.02");
		await expect(table).not.toContainText("0.24");

		// Switching the look must quote the other matrix.
		await page
			.locator(`select[name="ppom[fields][${lookId}]"]`)
			.selectOption({ label: matte.label });

		await expect(table).toContainText("2.12");
	});
});
