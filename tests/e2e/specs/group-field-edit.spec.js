/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";

import {
	addNewOptionInModal,
	createSimpleGroupField,
	fillFieldNameAndId,
	fillOptionNameAndValue,
	saveFieldInModal,
	saveFields,
    switchToOptionsModalTab,
} from "../utils";

test.describe("Group Fields Edit", () => {
	async function optionIdValues(page, modelId) {
		return page.$$eval(
			`#ppom_field_model_${modelId} ul.ppom-options-sortable li.ui-sortable-handle input[data-metatype="id"]`,
			(inputId) => inputId.map((i) => i.value),
		);
	}

	async function openLegacyFieldEditModal(page, modelId) {
		await page.evaluate((id) => {
			window.jQuery(".ppom-modal-box, .ppom-modal-overlay").hide();
			window.jQuery(`#ppom_field_model_${id}`).show();
		}, modelId);
		await expect(page.locator(`#ppom_field_model_${modelId}`)).toBeVisible();
	}

	/**
	 * Create two input fields and change their order then save and check.
	 */
	test("change fields order on saving", async ({ page, admin }) => {
		await createSimpleGroupField(admin, page);

		const fieldIds = await page.$$eval("td.ppom_meta_field_id", (tds) =>
			tds.map((td) => td.innerText),
		);

		// Move the last row into the first position using HTML manipulation.
		await page.evaluate(() => {
			const tbody = document.querySelector("tbody.ui-sortable");
			const rows = Array.from(tbody.querySelectorAll("tr"));
			if (rows.length > 1) {
				const lastRow = rows[rows.length - 1];
				tbody.insertBefore(lastRow, rows[0]);
			}
		});

		await saveFields(page);

		const newOrderFieldIds = await page.$$eval(
			"td.ppom_meta_field_id",
			(tds) => tds.map((td) => td.innerText),
		);

		expect(newOrderFieldIds).not.toEqual(fieldIds);
	});

	/**
	 * Create a select input with two option. Save then change their order and check again.
	 */
    test("change select option order on saving", async ({ page, admin }) => {
        await admin.visitAdminPage("admin.php?page=ppom");

		await page.getByRole("link", { name: "Add New Group" }).click();
		await page
			.getByRole("textbox")
			.fill("Change Select Option order");

        await page
            .locator('.ppom-fields-name-model .ppom-field-item[data-field-type="select"]')
            .dispatchEvent("click");

        const modelId = 1;

        await fillFieldNameAndId(
            page,
            modelId,
            `Select Input`,
            `select_test`,
        );

        await switchToOptionsModalTab(page, modelId);

        await fillOptionNameAndValue(page, modelId, 0, "Option 1", "option_1");
        await addNewOptionInModal(page, modelId);
        await fillOptionNameAndValue(page, modelId, 1, "Option 2", "option_2");

        const initialOrderOptionsIds = await optionIdValues(page, modelId);

        await saveFieldInModal(page, modelId);
        await saveFields(page);

        await page.waitForLoadState("networkidle");
		await page.reload();

        await openLegacyFieldEditModal(page, modelId);
        await switchToOptionsModalTab(page, modelId);

        // Move the last row into the first position using HTML manipulation.
        await page.evaluate((modelId) => {
            const tbody = document.querySelector(`#ppom_field_model_${modelId} ul.ppom-options-sortable`);

            const rows = Array.from(tbody.querySelectorAll("li.ui-sortable-handle"));
            if (rows.length > 1) {
                const lastRow = rows[rows.length - 1];
                tbody.insertBefore(lastRow, rows[0]);
            }
        }, modelId);

        await saveFieldInModal(page, modelId);
        await saveFields(page);

        await page.waitForLoadState("networkidle");
		await page.reload();

        await openLegacyFieldEditModal(page, modelId);
        await switchToOptionsModalTab(page, modelId);

        const newOrderOptionsIds = await optionIdValues(page, modelId);

		expect(newOrderOptionsIds).not.toEqual(initialOrderOptionsIds);
    });
});
