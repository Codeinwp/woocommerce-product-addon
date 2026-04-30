import { expect } from "@wordpress/e2e-test-utils-playwright";

/**
 * Pick the field type in the inputs modal.
 *
 * @param {import("@playwright/test").Page} page - The page object to interact with the web page.
 * @param {string} fieldType The field type.
 */
export async function pickFieldTypeInModal(page, fieldType) {
	const dialog = page.getByRole("dialog").first();
	if (await dialog.isVisible().catch(() => false)) {
		const fieldTypeLabels = {
			checkbox: "Checkbox",
			select: "Select",
			text: "Text Input",
		};
		const label = fieldTypeLabels[fieldType] || fieldType;

		await dialog
			.getByRole("button", { name: new RegExp(`^${label}\\b`, "i") })
			.first()
			.click();
		await expect(dialog.getByLabel("Data name")).toBeVisible();
		return;
	}

	await page
		.locator(
			`.ppom-fields-name-model .ppom-field-item[data-field-type="${fieldType}"]`,
		)
		.click();
}

/**
 * Open the Field Edit Modal.
 *
 * @param {import("@playwright/test").Page} page
 * @param {string|number} modelId
 */
export async function openFieldEditModal(page, modelId) {
	await page.locator(`#ppom_sort_id_${modelId} .ppom-edit-field`).click();
	const dialog = page.getByRole("dialog").first();
	if (await dialog.isVisible().catch(() => false)) {
		await expect(dialog).toBeVisible();
	}
}

/**
 * Creates a simple group field in the admin panel.
 *
 * @param {object} admin The admin object to interact with the admin panel.
 * @param {import("@playwright/test").Page} page The page object to interact with the web page.
 * @param {number} [fieldsNumber=2] The number of fields to create in the group.
 * @returns {Promise<{ baseName: string, ppomId: number }>} The created group info.
 */
export async function createSimpleGroupField(admin, page, fieldsNumber = 2) {
	await admin.visitAdminPage("admin.php?page=ppom");

	await page.getByRole("link", { name: "Add New Group" }).click();
	await page
		.locator("#ppom-template-wizard-modal .ppom-template-card--scratch")
		.click();
	await page.waitForURL(/action=new/);
	await page.getByRole("textbox").fill("Test Group Field");

	const randomNumber = Math.floor(Math.random() * 1000);

	for (let i = 1; i <= fieldsNumber; i++) {
		await addNewField(page);
		await pickFieldTypeInModal(page, "text");
		await fillFieldNameAndId(
			page,
			i,
			`test ${randomNumber + i - 1}`,
			`test_${randomNumber + i - 1}`,
		);
		await saveFieldInModal(page, i);
	}

	const usingReactFieldModal =
		(await page.locator(".ppom-react-field-modal-open").count()) > 0;
	if (!usingReactFieldModal) {
		await Promise.all([
			page.waitForURL(/wp-admin\/admin\.php\?page=ppom&productmeta_id=\d+&do_meta=edit/),
			saveFields(page),
		]);
	}
	await expect(
		page.getByRole("button", { name: "Save Fields" }),
	).toBeVisible();

	const url = new URL(page.url());
	const ppomId = Number(url.searchParams.get("productmeta_id"));

	if (!Number.isInteger(ppomId) || ppomId <= 0) {
		throw new Error(`Failed to resolve PPOM id from URL: ${page.url()}`);
	}

	return {
		baseName: `test ${randomNumber}`,
		ppomId,
	};
}

/**
 * Fill the `Title` (fieldName) and `Data name` (fieldId) on field edit modal.
 *
 * @param {import("@playwright/test").Page} page The page.
 * @param {string|number} modelId The input field model id.
 * @param {string} name The field display name.
 * @param {string} id The field ID.
 */
export async function fillFieldNameAndId(page, modelId, name, id) {
	const dialog = page.getByRole("dialog").first();
	if (await dialog.isVisible().catch(() => false)) {
		await dialog.getByLabel("Title").fill(name);
		await dialog.getByLabel("Data name").fill(id);
		return;
	}

	await page
		.locator(`input[name="ppom\\[${modelId}\\]\\[title\\]"]`)
		.fill(name);
	await page
		.locator(`input[name="ppom\\[${modelId}\\]\\[data_name\\]"]`)
		.fill(id);
}

/**
 * Fill the option name and id on field edit modal.
 *
 * @param {import("@playwright/test").Page} page The page.
 * @param {string|number} modelId The model ID.
 * @param {string|number} optionId The option ID.
 * @param {string} name The option display name.
 * @param {string|number} value The option value.
 */
export async function fillOptionNameAndValue(
	page,
	modelId,
	optionId,
	name,
	value,
) {
	const dialog = page.getByRole("dialog").first();
	if (await dialog.isVisible().catch(() => false)) {
		while ((await dialog.locator('input[placeholder="Option"]').count()) <= optionId) {
			await dialog
				.getByRole("button", { name: /Add (your first )?option/i })
				.click();
		}
		await dialog.locator('input[placeholder="Option"]').nth(optionId).fill(name);
		const optionIdInputs = dialog.locator('input[placeholder="Option ID"]');
		if ((await optionIdInputs.count()) > optionId) {
			await optionIdInputs.nth(optionId).fill(value);
		}
		return;
	}

	await page
		.locator(
			`input[name="ppom\\[${modelId}\\]\\[options\\]\\[${optionId}\\]\\[option\\]"]`,
		)
		.fill(name);
	await page
		.locator(
			`input[name="ppom\\[${modelId}\\]\\[options\\]\\[${optionId}\\]\\[id\\]"]`,
		)
		.fill(value);
}

/**
 * Enable the condition in field edit modal.
 *
 * @param {import("@playwright/test").Page} page The page.
 * @param {string|number} The input field model id.
 */
export async function enableConditionsInModal(page, modelId) {
	await page.locator(`input[name="ppom[${modelId}][logic]"] + span`).click();
}

/**
 * Switch to Conditions tab in field edit modal.
 *
 * @param {import("@playwright/test").Page} page The page.
 * @param {string|number} modelId The input field model id.
 */
export async function switchToConditionsModalTab(page, modelId) {
	await page.locator(`#ppom_field_model_${modelId} #condition_tab`).click();
}

/**
 * Switch to Options tab in field edit modal.
 *
 * @param {import("@playwright/test").Page} page The page.
 * @param {string|number} modelId The input field model id.
 */
export async function switchToOptionsModalTab(page, modelId) {
	const dialog = page.getByRole("dialog").first();
	if (await dialog.isVisible().catch(() => false)) {
		const optionsTab = dialog.getByRole("tab", { name: /Options|Add options/i });
		if ((await optionsTab.count()) > 0) {
			await optionsTab.click();
		} else {
			await expect(dialog.getByText(/Add options/i).first()).toBeVisible();
		}
		return;
	}

	await page
		.locator(`#ppom_field_model_${modelId}`)
		.getByText("Add Options", { exact: true })
		.click();
}

/**
 * Add a new option below the last option in the field edit modal.
 *
 * @param {import("@playwright/test").Page} page  The page.
 * @param {string|number} modelId The input field model id.
 */
export async function addNewOptionInModal(page, modelId) {
	const dialog = page.getByRole("dialog").first();
	if (await dialog.isVisible().catch(() => false)) {
		await dialog
			.getByRole("button", { name: /Add (your first )?option/i })
			.click();
		return;
	}

	await page
		.locator(`#ppom_field_model_${modelId} button.ppom-add-option`)
		.last()
		.click();
}

/**
 * Save the fields of group input fields.
 *
 * @param {import("@playwright/test").Page} page The page.
 */
export async function saveFields(page) {
	await page.getByRole("button", { name: "Save Fields" }).click();
}

/**
 * Open the modal for adding a new input field.
 *
 * @param {import("@playwright/test").Page} page The page.
 */
export async function addNewField(page) {
	await page.getByRole("button", { name: "Add field" }).click();
}

/**
 * Save the option field in the field edit modal.
 *
 * @param {import("@playwright/test").Page} page The page.
 * @param {string|number} modelId The input field model id.
 */
export async function saveFieldInModal(page, modelId) {
	const dialog = page.getByRole("dialog").first();
	if (await dialog.isVisible().catch(() => false)) {
		await Promise.all([
			page.waitForEvent("framenavigated", { timeout: 15000 }).catch(() => {}),
			dialog.getByRole("button", { name: "Save" }).click(),
		]);
		await page.waitForLoadState("networkidle").catch(() => {});
		return;
	}

	await page
		.locator(
			`.ppom_sort_id_${modelId} :is(.ppom-add-field, .ppom-update-field)`,
		)
		.click();
}
