/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { createSimpleGroupField } from '../utils';

test.describe( 'Group Fields Edit', () => {
	test( 'change fields order', async({ page, admin }) => {
        await createSimpleGroupField( admin, page );
        await page.waitForTimeout(500);
		await admin.visitAdminPage('admin.php?page=ppom');

        const firstRow = page.locator('#ppom-groups-export-form tbody tr').first();
        const ppomId = await firstRow.locator('td').nth(1).innerText();

        await admin.visitAdminPage(`admin.php?page=ppom&productmeta_id=${ppomId}&do_meta=edit`);
        
        const fieldIds = await page.$$eval('td.ppom_meta_field_id', tds => tds.map(td => td.innerText));
        
        // Move the last row into the first position using HTML manipulation.
        await page.evaluate(() => {
            const tbody = document.querySelector('tbody.ui-sortable');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            if ( rows.length > 1 ) {
                const lastRow = rows[rows.length - 1];
                tbody.insertBefore(lastRow, rows[0]);
            }
        });

        await page.getByRole('button', { name: 'Save Fields' }).click();

        const newOrderFieldIds = await page.$$eval('td.ppom_meta_field_id', tds => tds.map(td => td.innerText));
        
        expect(newOrderFieldIds).not.toEqual(fieldIds);
	});
});