/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";

import {
	addNewField,
	addNewOptionInModal,
	enableConditionsInModal,
	fillFieldNameAndId,
	fillOptionNameAndValue,
	pickFieldTypeInModal,
	saveFieldInModal,
	saveFields,
	switchToConditionsModalTab,
} from "../utils";

test.describe("Conditions", () => {
	/***
	 * Create two select fields (with two options each) and one text field. Display the text field if the second option of the second select is selected.
	 */
	test("conditions with Select input", async ({ page, admin }) => {
		await admin.visitAdminPage("admin.php?page=ppom");

		await page.getByRole("link", { name: "Add New Group" }).click();
		await page
			.getByRole("textbox")
			.fill("Test Group Field with Select Conditions");

		const randomNumber = Math.floor(Math.random() * 1000);
		const numOfInputFields = 2;

		for (let i = 1; i <= numOfInputFields; i++) {
			await page.getByRole("button", { name: "Add field" }).click();
			await pickFieldTypeInModal(page, "select");

			await fillFieldNameAndId(
				page,
				i,
				`Select Input ${randomNumber + i - 1}`,
				`select_test_${randomNumber + i - 1}`,
			);

			await page
				.locator(`#ppom_field_model_${i}`)
				.getByText("Add Options", { exact: true })
				.click();

			await fillOptionNameAndValue(page, i, 0, "Option 1", "option_1");
			await addNewOptionInModal(page, i);
			await fillOptionNameAndValue(page, i, 1, "Option 2", "option_2");

			await saveFieldInModal(page, i);
		}

		await addNewField(page);
		await pickFieldTypeInModal(page, "text");
		await fillFieldNameAndId(
			page,
			numOfInputFields + 1,
			"Output",
			"output_test",
		);

		await saveFields(page);
		await page.waitForLoadState("networkidle");
		await page.reload();

		await page
			.locator(`#ppom_sort_id_${numOfInputFields + 1} .ppom-edit-field`)
			.click();

		await switchToConditionsModalTab(page, numOfInputFields + 1);
		await enableConditionsInModal(page, numOfInputFields + 1);

		await page
			.locator(
				`select[name="ppom\\[${
					numOfInputFields + 1
				}\\]\\[conditions\\]\\[rules\\]\\[0\\]\\[elements\\]"]`,
			)
			.selectOption({ index: 1 });

		await page
			.locator(
				`select[name="ppom\\[${
					numOfInputFields + 1
				}\\]\\[conditions\\]\\[rules\\]\\[0\\]\\[operators\\]"]`,
			)
			.selectOption({ index: 0 });

		await page
			.locator(
				`select[name="ppom\\[${
					numOfInputFields + 1
				}\\]\\[conditions\\]\\[rules\\]\\[0\\]\\[element_values\\]"]`,
			)
			.selectOption({ index: 1 });

		await saveFieldInModal(page, numOfInputFields + 1);
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
		const selectedOption = await productSelector.inputValue();
		console.log("Selected option value:", selectedOption);
		await page.getByRole("button", { name: "Save", exact: true }).click();

		await page.waitForLoadState("networkidle");
		await page.goto(`/?p=${selectedOption}`);

		await expect(page.getByLabel("Output")).toBeHidden();

		await page
			.locator(
				`select[name="ppom[fields][select_test_${randomNumber + 1}]"]`,
			)
			.selectOption({ index: 1 });

		await expect(page.getByLabel("Output")).toBeVisible();
	});
});
