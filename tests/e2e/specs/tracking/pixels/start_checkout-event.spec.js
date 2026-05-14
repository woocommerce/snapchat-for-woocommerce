/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import {
	findSnaptrEvent,
	getThemes,
	switchTheme,
	singleAddToCart,
	clearCart,
} from '../../../utils';
import { integration } from '../../../config';

let admin = null;
let customer = null;

async function checkoutAssertions( page ) {
	const events = await page.evaluate( () => window.snaptr.queue );
	const START_CHECKOUT = findSnaptrEvent( events, 'START_CHECKOUT' );
	expect( START_CHECKOUT ).not.toBe( null );

	const [ , , payload ] = START_CHECKOUT;

	expect( payload.integration ).toBe( integration );
	expect( payload.price ).toBe( '40.00' );
	expect( payload.currency ).toBe( 'USD' );
	expect( payload.item_ids ).toEqual( [ '10', '11' ] );
}

test.beforeAll( 'Setup contexts', async ( { browser } ) => {
	admin = await browser.newPage( { storageState: process.env.ADMINSTATE } );
	customer = await browser.newPage( {
		storageState: process.env.CUSTOMERSTATE,
	} );

	admin.on( 'dialog', async ( dialog ) => {
		await dialog.accept();
	} );
} );

test.describe( 'START_CHECKOUT event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const themes = getThemes();

	test.beforeEach( 'Setup Cart', async () => {
		await clearCart( admin );
		await customer.goto( '/product/product-one' );
		await singleAddToCart( customer, 1 );

		await customer.goto( '/product/product-two' );
		await singleAddToCart( customer, 2 );
	} );

	test.afterAll( 'Clear Cart at the end', async () => {
		await clearCart( admin );
		await admin.close();
		await customer.close();
	} );

	for ( const theme in themes ) {
		test( `[${ theme } theme] Direct access to Checkout Page sends events`, async () => {
			await switchTheme( admin, themes[ theme ] );
			await customer.goto( '/checkout' );
			await customer.waitForLoadState( 'domcontentloaded' );
			await checkoutAssertions( customer );
		} );

		test( `[${ theme } theme] Backward navigation sends event `, async () => {
			await switchTheme( admin, themes[ theme ] );
			await customer.goto( '/checkout' );
			await customer
				.getByRole( 'link', { name: 'snapchat-for-woocommerce' } )
				.click();
			await customer.goBack();

			await customer.goto( '/checkout' );
			await customer.waitForLoadState( 'domcontentloaded' );
			await checkoutAssertions( customer );
		} );

		test( `[${ theme } theme] Navigating to Checkout Page event sends event `, async () => {
			await switchTheme( admin, themes[ theme ] );
			await customer.goto( '/shop' );

			await customer
				.getByRole( 'link', { name: 'Checkout' } )
				.first()
				.click();
			await customer.waitForLoadState( 'domcontentloaded' );
			await checkoutAssertions( customer );
		} );

		test( `[${ theme } theme] No event is sent on reload`, async () => {
			await switchTheme( admin, themes[ theme ] );
			await customer.goto( '/checkout' );
			await customer.reload();

			await customer.waitForLoadState( 'domcontentloaded' );

			const events = await customer.evaluate( () => window.snaptr.queue );
			const START_CHECKOUT = findSnaptrEvent( events, 'START_CHECKOUT' );
			expect( START_CHECKOUT ).toBe( null );
		} );
	}
} );
