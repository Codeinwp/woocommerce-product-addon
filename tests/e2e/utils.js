import { expect } from "@wordpress/e2e-test-utils-playwright";

/**
 * Pick the field type in the inputs modal.
 *
 * @param {import("@playwright/test").Page} page - The page object to interact with the web page.
 * @param {string} fieldType The field type.
 */
export async function pickFieldTypeInModal(page, fieldType) {
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

	await Promise.all([
		page.waitForURL(/wp-admin\/admin\.php\?page=ppom&productmeta_id=\d+&do_meta=edit/),
		saveFields(page),
	]);
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
	await page
		.locator(
			`.ppom_sort_id_${modelId} :is(.ppom-add-field, .ppom-update-field)`,
		)
		.click();
}

/**
 * Returns the inline attach container locator on the field-group edit page.
 *
 * The inline attach container holds the "Display on Specific Products /
 * Categories / Tags / Variations" selects rendered directly inside the
 * Basic Settings → General tab (replacing the legacy `#ppom-product-modal`).
 *
 * @param {import("@playwright/test").Page} page The page.
 * @returns {import("@playwright/test").Locator}
 */
export function getInlineAttachContainer(page) {
	return page.locator(".ppom-inline-attach-container");
}

/**
 * Returns the underlying native `<select>` for one of the inline attach
 * targets. Useful for `selectOption()` calls that bypass the select2 UI.
 *
 * @param {import("@playwright/test").Page} page The page.
 * @param {"products"|"categories"|"tags"|"variations"} kind Target kind.
 * @returns {import("@playwright/test").Locator}
 */
export function getInlineAttachSelect(page, kind) {
	return getInlineAttachContainer(page).locator(
		`select[name="ppom-attach-to-${kind}[]"]`,
	);
}

/**
 * Select one or more inline attach values by their underlying value.
 *
 * The category and tag selects are populated via select2 ajax search, so
 * only currently-saved values are pre-rendered as `<option>` children.
 * `selectOption` cannot pick an option that does not exist in the native
 * select, so we append it first — which mirrors what select2 does for
 * ajax-backed selects when the user picks a result.
 *
 * Pass an empty array to deselect everything (no injection needed).
 *
 * @param {import("@playwright/test").Page} page The page.
 * @param {"products"|"categories"|"tags"|"variations"} kind Target kind.
 * @param {Array<{value: string, label?: string}>} terms Options to select.
 */
export async function selectInlineAttachTerms(page, kind, terms) {
	const select = getInlineAttachSelect(page, kind);

	if (terms.length === 0) {
		await select.selectOption([]);
		return;
	}

	await select.evaluate((el, items) => {
		for (const item of items) {
			if (el.querySelector(`option[value="${CSS.escape(item.value)}"]`)) {
				continue;
			}
			const opt = document.createElement("option");
			opt.value = item.value;
			opt.textContent = item.label ?? item.value;
			el.appendChild(opt);
		}
	}, terms);

	await select.selectOption(terms.map((term) => ({ value: term.value })));
}

/**
 * Submit the field-group edit form, persisting the inline attach selections.
 *
 * Inline attach values are part of the same form as the field rows. The
 * "Save Fields" submit is intercepted by the admin JS, which POSTs the
 * payload via `fetch` and then calls `window.location.reload()` on success.
 * The URL does not change on update, so we wait for the next `load` event
 * instead of `waitForURL` — `waitForURL` short-circuits to `waitForLoadState`
 * when the URL already matches and would return before the reload happens.
 *
 * @param {import("@playwright/test").Page} page The page.
 */
export async function saveInlineAttach(page) {
	page.once("dialog", (dialog) => dialog.accept());
	const reloaded = page.waitForEvent("load");
	await saveFields(page);
	await reloaded;
	await expect(getInlineAttachContainer(page)).toBeVisible();
}

/**
 * Search for a product by name in the inline "Display on Specific Products"
 * select2 control and click the matching result.
 *
 * Waits on the `ppom_search_products` admin-ajax response keyed to the typed
 * query so the assertion against the rendered option is deterministic.
 *
 * @param {import("@playwright/test").Page} page The page.
 * @param {string} productName Exact product name to type and match against.
 */
export async function searchAndSelectInlineProduct(page, productName) {
	const container = getInlineAttachContainer(page);
	const selection = container.locator(
		"#attach-to-products .select2-selection--multiple",
	);
	const searchResponsePromise = page.waitForResponse((response) => {
		const url = new URL(response.url());

		return (
			url.pathname.endsWith("/wp-admin/admin-ajax.php") &&
			url.searchParams.get("action") === "ppom_search_products" &&
			url.searchParams.get("q") === productName
		);
	});

	await selection.click();
	await page.keyboard.type(productName);

	const searchResponse = await searchResponsePromise;
	expect(searchResponse.ok()).toBeTruthy();
	expect(searchResponse.status()).toBe(200);

	const resultOption = page
		.locator(".select2-results__option[aria-selected]")
		.filter({ hasText: productName });

	await expect(resultOption).toHaveCount(1);
	await resultOption.click();
	await expect(
		container.locator("#attach-to-products .select2-selection__choice"),
	).toContainText(productName);
}
