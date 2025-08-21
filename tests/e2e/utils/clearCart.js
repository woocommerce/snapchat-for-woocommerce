const { expect } = require( '@playwright/test' );

/**
 * Clears the WooCommerce cart and active sessions via the WordPress Admin Tools page.
 *
 * This utility navigates to the WooCommerce > Status > Tools screen, clicks the
 * "Clear all sessions" tool (`form_clear_sessions`), and verifies that the
 * confirmation message is displayed (`Deleted all active sessions`).
 *
 * Useful in E2E tests to guarantee a clean state between runs by removing any
 * lingering cart or session data.
 *
 * @param {import('@playwright/test').Page} page - The Playwright page object.
 */
export async function clearCart( page ) {
	await page.goto( '/wp-admin/admin.php?page=wc-status&tab=tools' );
	await page.locator( 'input[form="form_clear_sessions"]' ).click();
	await expect(
		page.getByText( 'Deleted all active sessions' )
	).toBeVisible();
}
