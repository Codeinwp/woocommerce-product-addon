/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Attach Modal', () => {

    async function createSimpleGroupField(admin, page) {
        await admin.visitAdminPage('admin.php?page=ppom');

        await page.getByRole('link', { name: 'Add New Group' }).click();
        await page.getByRole('textbox').fill('Test Group Field');
        await page.getByRole('button', { name: 'Add field' }).click();
        await page.locator('.ppom-fields-name-model .ppom-field-item[data-field-type="text"]').click();

        const randomNumber = Math.floor(Math.random() * 1000);
        await page.locator(`input[name="ppom\\[1\\]\\[title\\]"]`).fill(`test ${randomNumber}`);
        await page.locator(`input[name="ppom\\[1\\]\\[data_name\\]"]`).fill(`test_${randomNumber}`);

        await page.getByRole('button', { name: 'Add Field', exact: true }).click();
        await page.getByRole('button', { name: 'Save Fields' }).click();

		await page.waitForTimeout(1000);

        return `test ${randomNumber}`;
    }

	test( 'attach to products', async({ page, admin }) => {
        const createdFieldName = await createSimpleGroupField( admin, page );
		await admin.visitAdminPage('admin.php?page=ppom');

        const firstRow = page.locator('#ppom-groups-export-form tbody tr').first();
        const ppomId = await firstRow.locator('td').nth(1).innerText();
        await firstRow.getByText('Attach to Products').click();

        const productSelector = page.locator('#ppom-product-form div').filter({ hasText: 'Display on Specific Products' }).first().locator('select[name="ppom-attach-to-products\\[\\]"]');
        await productSelector.selectOption({ index: 0 });
        
        const selectedOption = await productSelector.inputValue();
        console.log('Selected option value:', selectedOption);
        await page.getByRole('button', { name: 'Save' }).click();

        await page.waitForLoadState('networkidle');
        await page.goto(`/?p=${selectedOption}`);

        await expect( page.locator(`.ppom-id-${ppomId}`) ).toBeVisible();
	});
});
