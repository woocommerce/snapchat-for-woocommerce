/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

const PLUGINS_PAGE_URL = '/wp-admin/plugins.php';

test.describe( 'Snapchat for WooCommerce', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( 'Restore Defaults', async ( { browser } ) => {
		const page = await browser.newPage( { storageState: process.env.ADMINSTATE } );
	} );

	test( 'Woo Dependency', async ( { page } ) => {
		await page.goto( PLUGINS_PAGE_URL );

		await expect(
			await page
				.locator( '[data-slug="woocommerce"]' )
				.locator( '.required-by' )
		).toContainText( 'Required by: Snapchat for WooCommerce', { exact: false } );

	} );

	test( 'Deactivation', async ( { page } ) => {
		await page.goto( PLUGINS_PAGE_URL );

		await page
			.getByRole( 'link', { name: 'Deactivate Snapchat for Woocommerce' } )
			.click();

		await expect(
			await page.getByText( 'Plugin deactivated.' )
		).toBeVisible();
	} );

	test( 'Activation', async ( { page } ) => {
		await page.goto( PLUGINS_PAGE_URL );

		await page
			.getByRole( 'link', { name: 'Activate Snapchat for Woocommerce' } )
			.click();

		await expect(
			await page.getByText( 'Plugin activated.' )
		).toBeVisible();
	} );

	test( '"Snapchat for Woocommerce" menu option', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fmarketing' );
		await expect(
			await page
				.locator( '.wp-submenu' )
				.getByRole( 'link', { name: 'Snapchat for WooCommerce' } )
		).toBeVisible();
	} );
} );
