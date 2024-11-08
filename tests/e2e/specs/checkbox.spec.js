/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";

import {
    addNewField,
    addNewOptionInModal,
    fillFieldNameAndId,
    fillOptionNameAndValue,
    pickFieldTypeInModal,
    saveFieldInModal,
    saveFields,
} from "../utils";

const CHECKBOX_OPTIONS = {
    CHECKED_OPTION_1: 'yes',
    CHECKED_OPTION_2: 'no',
    UNCHECKED_OPTION_1: 'maybe'
};

test.describe("Checkbox", () => {
    /***
     * Create a simple checkbox with 3 options. Two will be marked as checked by default. Check if the selection is respected on rendering.
     */
    test("check default selected options", async ({ page, admin }) => {
        await admin.visitAdminPage("admin.php?page=ppom");

        await page.getByRole("link", { name: "Add New Group" }).click();
        await page
            .getByRole("textbox")
            .fill("Default Value for Checkbox");

        await addNewField(page);
        await pickFieldTypeInModal(page, "checkbox");

        const modelId = 1;
        const fieldId = `checkbox_test`;

        await fillFieldNameAndId(
            page,
            modelId,
            `Checkbox Default values`,
            fieldId,
        );


        await page.locator(`textarea[name="ppom\\[${modelId}\\]\\[checked\\]"]`)
            .fill(`${CHECKBOX_OPTIONS.CHECKED_OPTION_1}\r\n${CHECKBOX_OPTIONS.CHECKED_OPTION_2}`);

        await page
				.locator(`#ppom_field_model_${modelId}`)
				.getByText("Add Options", { exact: true })
				.click();

        await fillOptionNameAndValue(page, modelId, 0, CHECKBOX_OPTIONS.CHECKED_OPTION_1, CHECKBOX_OPTIONS.CHECKED_OPTION_1);
        await addNewOptionInModal(page, modelId);
        await fillOptionNameAndValue(page, modelId, 1, CHECKBOX_OPTIONS.CHECKED_OPTION_2, CHECKBOX_OPTIONS.CHECKED_OPTION_2);
        await addNewOptionInModal(page, modelId);
        await fillOptionNameAndValue(page, modelId, 2, CHECKBOX_OPTIONS.UNCHECKED_OPTION_1, CHECKBOX_OPTIONS.UNCHECKED_OPTION_1);
        await saveFieldInModal(page, modelId);

        await saveFields(page);
        await page.waitForLoadState("networkidle");
        await page.reload();

        await page.getByText("Attach to Products").click({ force: true });
        await page.waitForLoadState("networkidle");

        const productSelector = page.locator(
            'select[name="ppom-attach-to-products\\[\\]"]',
        );
        await page.waitForLoadState("networkidle");

        await productSelector.selectOption({ index: 0 });
        const selectedProductId = await productSelector.inputValue();
        await page.getByRole("button", { name: "Save", exact: true }).click();

        await page.waitForLoadState("networkidle");
        await page.goto(`/?p=${selectedProductId}`);
        
        await expect(page.locator(`input[name="ppom[fields][${fieldId}][]"][value="${CHECKBOX_OPTIONS.CHECKED_OPTION_1}"]`)).toBeChecked();
        await expect(page.locator(`input[name="ppom[fields][${fieldId}][]"][value="${CHECKBOX_OPTIONS.CHECKED_OPTION_2}"]`)).toBeChecked();
        await expect(page.locator(`input[name="ppom[fields][${fieldId}][]"][value="${CHECKBOX_OPTIONS.UNCHECKED_OPTION_1}"]`)).not.toBeChecked();
    });
});
