const { expect } = require( '@playwright/test' );

export async function clearCart( page ) {
	await page.goto( '/wp-admin/admin.php?page=wc-status&tab=tools' );
	await page.locator( 'input[form="form_clear_sessions"]' ).click();
	await expect(
		page.getByText( 'Deleted all active sessions' )
	).toBeVisible();
}
