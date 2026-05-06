/**
 * WordPress dependencies
 */
import { test, expect } from "@wordpress/e2e-test-utils-playwright";

import { buildPalettesField } from "../fixtures/fields.js";
import { setPpomLicenseFixture } from "../fixtures/license.js";
import { createPpomGroup, createSimpleTextGroup } from "../fixtures/ppom.js";
import { saveFields } from "../utils.js";

async function visitReactModalGroup(admin, ppomId) {
	await admin.visitAdminPage(
		`admin.php?page=ppom&productmeta_id=${ppomId}&do_meta=edit&ppom_react_modal=1`,
	);
}

async function openFirstFieldReactModal(page) {
	await page.locator("#ppom_sort_id_1 .ppom-edit-field").click();
	const dialog = page.getByRole("dialog").first();
	await expect(dialog).toBeVisible();
	return dialog;
}

async function openAdvancedSettings(dialog) {
	const toggle = dialog.getByText(/Show advanced settings/i);
	if ((await toggle.count()) > 0) {
		await toggle.click();
	}
}

async function optionValues(dialog) {
	return dialog
		.locator('input[placeholder="Option"]')
		.evaluateAll((inputs) => inputs.map((input) => input.value));
}

async function setReloadProbe(page) {
	const token = `react-modal-${Date.now()}`;
	await page.evaluate((value) => {
		window.__ppomReactModalReloadProbe = value;
	}, token);
	return token;
}

async function expectNoReloadSinceProbe(page, token) {
	await expect
		.poll(() =>
			page.evaluate(() => window.__ppomReactModalReloadProbe || ""),
		)
		.toBe(token);
}

async function pickTextField(dialog) {
	await dialog.getByRole("tab", { name: "Text" }).click();
	await dialog.locator('button[aria-label^="Text Input"]').last().click();
}

async function fillTextFieldBasics(dialog, { title, dataName }) {
	await dialog.getByLabel("Title").fill(title);
	await dialog.getByLabel("Data name").fill(dataName);
}

test.describe("React field modal (opt-in)", () => {
	test("picker entry opens FieldTypePicker (Add field)", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		await expect(
			page.locator('button[data-modal-id="ppom_fields_model_id"]'),
		).toHaveCount(0);

		const addFieldButton = page.getByRole("button", {
			name: "Add field",
			exact: true,
		});
		await expect(addFieldButton).toHaveClass(/button-primary/);
		await addFieldButton.click();

		const dialog = page.getByRole("dialog").first();
		await expect(dialog).toBeVisible();

		await expect(dialog.getByRole("tab", { name: "Text" })).toBeVisible();
		await dialog.getByRole("tab", { name: "Text" }).click();
		await expect(
			dialog.getByRole("button", { name: /Text Input/i }).first(),
		).toBeVisible();
	});

	test("per-field edit button opens sidebar without inner Add field CTA", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		const dialog = await openFirstFieldReactModal(page);
		await expect(
			dialog.getByRole("button", { name: "Add field", exact: true }),
		).toHaveCount(0);
	});

	test("per-field edit button shows Settings tab for definition-driven text field", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		const dialog = await openFirstFieldReactModal(page);
		await expect(
			dialog.getByRole("tab", { name: /Settings/i }),
		).toBeVisible();
	});

	test("opening an existing field can close without confirmation when unchanged", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		const dialog = await openFirstFieldReactModal(page);
		await dialog.getByRole("button", { name: "Cancel" }).click();

		await expect(dialog).toBeHidden();
	});

	test("editing an existing field requires close confirmation", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		const dialog = await openFirstFieldReactModal(page);
		await dialog.getByLabel("Title").fill("Changed React modal title");
		await dialog.getByRole("button", { name: "Cancel" }).click();

		await expect(dialog).toBeVisible();
		await expect(
			dialog.getByRole("button", { name: "Confirm" }),
		).toBeVisible();
	});

	test("selecting a new field type can close without confirmation when unchanged", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		await page
			.getByRole("button", { name: "Add field", exact: true })
			.click();
		const dialog = page.getByRole("dialog").first();
		await expect(dialog).toBeVisible();

		await dialog.getByRole("tab", { name: "Text" }).click();
		await dialog.locator('button[aria-label^="Text Input"]').last().click();
		await dialog.getByRole("button", { name: "Cancel" }).click();

		await expect(dialog).toBeHidden();
	});

	test("editing a newly selected field requires close confirmation", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		await page
			.getByRole("button", { name: "Add field", exact: true })
			.click();
		const dialog = page.getByRole("dialog").first();
		await expect(dialog).toBeVisible();

		await dialog.getByRole("tab", { name: "Text" }).click();
		await dialog.locator('button[aria-label^="Text Input"]').last().click();
		await dialog.getByLabel("Title").fill("New edited field");
		await dialog.getByRole("button", { name: "Cancel" }).click();

		await expect(dialog).toBeVisible();
		await expect(
			dialog.getByRole("button", { name: "Confirm" }),
		).toBeVisible();
	});

	test("matrix-style options reorder, add, and remove", async ({
		page,
		admin,
		requestUtils,
	}) => {
		await setPpomLicenseFixture(requestUtils, {
			valid: true,
			plan: 3,
			proInstalled: true,
		});

		const { ppomId } = await createPpomGroup(requestUtils, {
			groupName: "React modal matrix options",
			fields: [
				buildPalettesField({
					title: "Palette Options",
					dataName: "palette_options",
					options: [
						{
							option: "Small",
							price: "10",
							label: "Small label",
							id: "small",
						},
						{
							option: "Large",
							price: "20",
							label: "Large label",
							id: "large",
						},
					],
				}),
			],
		});

		await visitReactModalGroup(admin, ppomId);

		let dialog = await openFirstFieldReactModal(page);
		await openAdvancedSettings(dialog);

		await expect(dialog.locator('input[placeholder="Option"]')).toHaveCount(
			2,
		);
		expect(await optionValues(dialog)).toEqual(["Small", "Large"]);

		const dragHandles = dialog.getByRole("button", {
			name: "Drag to reorder",
		});
		await dragHandles.nth(1).focus();
		await page.keyboard.press("ArrowUp");
		expect(await optionValues(dialog)).toEqual(["Large", "Small"]);

		await dialog.getByRole("button", { name: "Add option" }).click();
		await expect(dialog.locator('input[placeholder="Option"]')).toHaveCount(
			3,
		);
		await dialog.locator('input[placeholder="Option"]').nth(2).click();
		await page.keyboard.type("Medium");
		await expect(
			dialog.locator('input[placeholder="Option"]').nth(2),
		).toHaveValue("Medium");

		await dialog.getByRole("button", { name: "Remove" }).first().click();
		expect(await optionValues(dialog)).toEqual(["Small", "Medium"]);
	});

	test("new field is created with a slugified-title data name plus random suffix", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		await page
			.getByRole("button", { name: "Add field", exact: true })
			.click();
		const dialog = page.getByRole("dialog").first();
		await expect(dialog).toBeVisible();

		await pickTextField(dialog);

		const titleValue = await dialog.getByLabel("Title").inputValue();
		const dataNameValue = await dialog.getByLabel("Data name").inputValue();

		const expectedSlug = titleValue
			.toLowerCase()
			.replace(/[^a-z0-9]+/g, "_")
			.replace(/^_+|_+$/g, "");
		expect(dataNameValue).toMatch(
			new RegExp(`^${expectedSlug}_[a-z0-9]{1,6}$`),
		);
	});

	test("clicking save with prefilled data name passes validation without manual entry", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		await page
			.getByRole("button", { name: "Add field", exact: true })
			.click();
		const dialog = page.getByRole("dialog").first();
		await expect(dialog).toBeVisible();

		await pickTextField(dialog);

		await dialog.getByLabel("Title").fill("Direct Save Title");
		await dialog.getByRole("button", { name: "Add Field" }).click();

		await expect(
			dialog.getByText(/Data Name must be required/i),
		).toHaveCount(0);
		await expect(dialog).toBeHidden();
		await expect(
			page.locator(".ppom_field_table .ppom_meta_field_title", {
				hasText: "Direct Save Title",
			}),
		).toBeVisible();
	});

	test("adding a text field stages the row without reloading", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);
		const probe = await setReloadProbe(page);

		await page
			.getByRole("button", { name: "Add field", exact: true })
			.click();
		const dialog = page.getByRole("dialog").first();
		await expect(dialog).toBeVisible();

		await pickTextField(dialog);
		await fillTextFieldBasics(dialog, {
			title: "React staged text",
			dataName: "react_staged_text",
		});
		await dialog.getByRole("button", { name: "Add Field" }).click();

		await expect(dialog).toBeHidden();
		await expect(
			page.locator(".ppom_field_table .ppom_meta_field_title", {
				hasText: "React staged text",
			}),
		).toBeVisible();
		await expectNoReloadSinceProbe(page, probe);
	});

	test("editing an existing field stages the row without reloading", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);
		const probe = await setReloadProbe(page);

		const dialog = await openFirstFieldReactModal(page);
		await dialog.getByLabel("Title").fill("React staged edit");
		await dialog.getByRole("button", { name: "Update Field" }).click();

		await expect(dialog).toBeHidden();
		await expect(
			page.locator("#ppom_sort_id_1 .ppom_meta_field_title"),
		).toHaveText("React staged edit");
		await expectNoReloadSinceProbe(page, probe);
	});

	test("Save Fields persists staged modal changes through the classic submit", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		const dialog = await openFirstFieldReactModal(page);
		await dialog.getByLabel("Title").fill("React persisted edit");
		await dialog.getByRole("button", { name: "Update Field" }).click();
		await expect(dialog).toBeHidden();

		page.once("dialog", (browserDialog) => browserDialog.accept());
		const reloaded = page.waitForEvent("load");
		await saveFields(page);
		await reloaded;

		await visitReactModalGroup(admin, ppomId);
		await expect(
			page.locator("#ppom_sort_id_1 .ppom_meta_field_title"),
		).toHaveText("React persisted edit");
	});

	test("canceling dirty edits does not stage row changes", async ({
		page,
		admin,
		requestUtils,
	}) => {
		const { ppomId } = await createSimpleTextGroup(requestUtils, {
			fieldsNumber: 1,
		});

		await visitReactModalGroup(admin, ppomId);

		const originalTitle = await page
			.locator("#ppom_sort_id_1 .ppom_meta_field_title")
			.textContent();
		const dialog = await openFirstFieldReactModal(page);
		await dialog.getByLabel("Title").fill("React canceled edit");
		await dialog.getByRole("button", { name: "Cancel" }).click();
		await expect(
			dialog.getByRole("button", { name: "Confirm" }),
		).toBeVisible();
		await dialog.getByRole("button", { name: "Confirm" }).click();

		await expect(dialog).toBeHidden();
		await expect(
			page.locator("#ppom_sort_id_1 .ppom_meta_field_title"),
		).toHaveText(originalTitle?.trim() || "");
	});
});
