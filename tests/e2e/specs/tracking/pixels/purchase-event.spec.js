/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );
const {
	fillBillingCheckoutBlocks,
} = require( '@woocommerce/e2e-utils-playwright' );

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
import { customer as c, integration } from '../../../config';

let admin = null;
let customer = null;
let orderUrl = '';
const uuid4Regex =
	/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

test.beforeAll( 'Setup contexts', async ( { browser } ) => {
	admin = await browser.newPage( { storageState: process.env.ADMINSTATE } );
	customer = await browser.newPage( {
		storageState: process.env.CUSTOMERSTATE,
	} );

	admin.on( 'dialog', async ( dialog ) => {
		await dialog.accept();
	} );
} );

test.describe( 'PURCHASE event', () => {
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
		test( `[${ theme } theme] Placing order sends event`, async () => {
			await switchTheme( admin, themes[ theme ] );
			await customer.goto( '/checkout' );
			await customer.waitForLoadState( 'domcontentloaded' );

			const editBillingButton = customer.getByRole( 'button', {
				name: 'Edit billing address',
			} );

			if ( await editBillingButton.isVisible() ) {
				await editBillingButton.click();
			}

			await fillBillingCheckoutBlocks( customer, c.billing );
			await customer
				.getByRole( 'checkbox', { name: 'Add a note to your order' } )
				.check();
			await customer
				.getByRole( 'button', { name: 'Place Order' } )
				.click();
			await customer.waitForURL( '**/checkout/order-received/**' );

			orderUrl = await customer.url();

			const events = await customer.evaluate( () => window.snaptr.queue );
			const PURCHASE = findSnaptrEvent( events, 'PURCHASE' );
			expect( PURCHASE ).not.toBe( null );

			const [ , , payload ] = PURCHASE;
			expect( payload.integration ).toBe( integration );
			expect( payload.price ).toBe( '40.00' );
			expect( payload.currency ).toBe( 'USD' );
			expect( payload.event_id ).toMatch( uuid4Regex );
			expect( payload.transaction_id ).toBeGreaterThan( 0 );
			expect( payload.item_ids ).toEqual( [ '10', '11' ] );
			expect( payload.number_items ).toBe( 3 );
			expect( payload ).toHaveProperty( 'item_category' );
		} );

		test( `[${ theme } theme] No event is sent on reload`, async () => {
			await switchTheme( admin, themes[ theme ] );
			await customer.goto( orderUrl );

			await customer.waitForLoadState( 'domcontentloaded' );

			const events = await customer.evaluate( () => window.snaptr.queue );
			const PURCHASE = findSnaptrEvent( events, 'PURCHASE' );
			expect( PURCHASE ).toBe( null );
		} );
	}
} );
