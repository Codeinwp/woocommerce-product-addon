/**
 * Pick the field type in the inputs modal.
 * 
 * @param {import("@playwright/test").Page} page - The page object to interact with the web page.
 * @param {string} fieldType The field type.
 */
export async function pickFieldTypeInModal( page, fieldType ) {
    await page.locator(`.ppom-fields-name-model .ppom-field-item[data-field-type="${fieldType}"]`).click();
}

/**
 * Creates a simple group field in the admin panel.
 *
 * @param {object} admin The admin object to interact with the admin panel.
 * @param {import("@playwright/test").Page} page The page object to interact with the web page.
 * @param {number} [fieldsNumber=2] The number of fields to create in the group.
 * @returns {Promise<string>} The base name of the created fields.
 */
export async function createSimpleGroupField(admin, page, fieldsNumber = 2) {
    
    await admin.visitAdminPage('admin.php?page=ppom');

    await page.getByRole('link', { name: 'Add New Group' }).click();
    await page.getByRole('textbox').fill('Test Group Field');

    const randomNumber = Math.floor(Math.random() * 1000);

    for (let i = 1; i <= fieldsNumber; i++) {
        await addNewField(page);
        await pickFieldTypeInModal(page, 'text');
        await fillFieldNameAndId(page, i, `test ${randomNumber + i - 1}`, `test_${randomNumber + i - 1}`)
        await saveFieldInModal(page, i);
    }

    await saveFields(page);
    return `test ${randomNumber}`;
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
    await page.locator(`input[name="ppom\\[${modelId}\\]\\[title\\]"]`).fill(name);
    await page.locator(`input[name="ppom\\[${modelId}\\]\\[data_name\\]"]`).fill(id);
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
export async function fillOptionNameAndValue(page, modelId, optionId, name, value) {
    await page.locator(`input[name="ppom\\[${modelId}\\]\\[options\\]\\[${optionId}\\]\\[option\\]"]`).fill(name);
    await page.locator(`input[name="ppom\\[${modelId}\\]\\[options\\]\\[${optionId}\\]\\[id\\]"]`).fill(value);
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
 * Open the field edit modal.
 * 
 * @param {import("@playwright/test").Page} page The page.
 * @param {string|number} modelId The input field model id.
 */
export async function switchToConditionsModalTab(page, modelId) {
    await page.locator(`#ppom_field_model_${modelId} #condition_tab`).click();
}

/**
 * Add a new option below the last option in the field edit modal.
 * 
 * @param {import("@playwright/test").Page} page  The page.
 * @param {string|number} modelId The input field model id.
 */
export async function addNewOptionInModal(page, modelId) {
    await page.locator(`#ppom_field_model_${modelId} button.ppom-add-option`).last().click();
}

/**
 * Save the fields of group input fields.
 * 
 * @param {import("@playwright/test").Page} page The page. 
 */
export async function saveFields(page) {
    await page.getByRole('button', { name: 'Save Fields' }).click();
}

/**
 * Open the modal for adding a new input field.
 * 
 * @param {import("@playwright/test").Page} page The page. 
 */
export async function addNewField(page) {
    await page.getByRole('button', { name: 'Add field' }).click();
}

/**
 * Save the option field in the field edit modal.
 * 
 * @param {import("@playwright/test").Page} page The page.
 * @param {string|number} modelId The input field model id.
 */
export async function saveFieldInModal(page, modelId) {
    await page.locator(`.ppom_sort_id_${modelId} :is(.ppom-add-field, .ppom-update-field)`).click();
}