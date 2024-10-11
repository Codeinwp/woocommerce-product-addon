/**
 * Creates a simple group field in the admin panel.
 *
 * @param {object} admin - The admin object to interact with the admin panel.
 * @param {object} page - The page object to interact with the web page.
 * @param {number} [fieldsNumber=2] - The number of fields to create in the group.
 * @returns {Promise<string>} - The base name of the created fields.
 */
export async function createSimpleGroupField(admin, page, fieldsNumber = 2) {
    
    await admin.visitAdminPage('admin.php?page=ppom');

    await page.getByRole('link', { name: 'Add New Group' }).click();
    await page.getByRole('textbox').fill('Test Group Field');

    const randomNumber = Math.floor(Math.random() * 1000);

    for (let i = 1; i <= fieldsNumber; i++) {
        await page.getByRole('button', { name: 'Add field' }).click();
        await page.locator('.ppom-fields-name-model .ppom-field-item[data-field-type="text"]').click();
        await page.locator(`input[name="ppom\\[${i}\\]\\[title\\]"]`).fill(`test ${randomNumber + i - 1}`);
        await page.locator(`input[name="ppom\\[${i}\\]\\[data_name\\]"]`).fill(`test_${randomNumber + i - 1}`);
        await page.getByRole('button', { name: 'Add Field', exact: true }).click();
    }

    await page.getByRole('button', { name: 'Save Fields' }).click();

    await page.waitForTimeout(1000);

    return `test ${randomNumber}`;
}