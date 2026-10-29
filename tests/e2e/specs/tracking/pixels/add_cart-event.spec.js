/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	findSnaptrEvent,
	getProductId,
	getThemes,
	singleAddToCart,
	switchTheme,
} from '../../../utils';
import { integration } from '../../../config';

const anyUuidRegex =
	/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

/**
 * Stops the add-to-cart form from submitting.
 *
 * The Pixel event fires on the button click, but the form submit that follows
 * reloads the page and wipes the mocked `snaptr.queue` before it can be read.
 *
 * @param {import('@playwright/test').Page} page - The Playwright page object.
 */
async function preventCartFormSubmit( page ) {
	await page.evaluate( () => {
		document.addEventListener(
			'submit',
			( event ) => event.preventDefault(),
			true
		);
	} );
}

test.describe( 'ADD_CART event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const themes = getThemes();
	let productId = null;

	test.beforeAll( 'Get product ID', async ( { browser } ) => {
		const page = await browser.newPage();
		productId = await getProductId( page.request, 'product-one' );
		await page.close();
	} );

	for ( const theme in themes ) {
		test( `[${ theme } theme] Shop page`, async ( { page } ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/shop' );

			await page
				.getByRole( 'link', { name: 'Add to cart: “Product Five”' } )
				.or(
					page.getByRole( 'button', {
						name: 'Add to cart: “Product Five”',
					} )
				)
				.click();
			const events = await page.evaluate( () => window.snaptr.queue );
			const ADD_CART = findSnaptrEvent( events, 'ADD_CART' );
			expect( ADD_CART ).not.toBe( null );

			const [ , , payload ] = ADD_CART;

			expect( payload.integration ).toBe( integration );
			expect( payload.price ).toBe( 10 );
			expect( payload.currency ).toBe( 'USD' );
			expect( payload.event_id ).toMatch( anyUuidRegex );
			expect( payload.client_dedup_id ).toMatch( anyUuidRegex );
		} );

		test( `[${ theme } theme] Single product page`, async ( { page } ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/product/product-one' );
			await preventCartFormSubmit( page );

			await singleAddToCart( page, 1 );

			const events = await page.evaluate( () => window.snaptr.queue );
			const ADD_CART = findSnaptrEvent( events, 'ADD_CART' );
			expect( ADD_CART ).not.toBe( null );

			const [ , , payload ] = ADD_CART;

			expect( payload.integration ).toBe( integration );
			expect( payload.currency ).toBe( 'USD' );
			expect( payload.price ).toBe( 10 );
			expect( payload.number_items ).toBe( 1 );
			expect( payload.item_ids ).toContain( productId );
			expect( payload.event_id ).toMatch( anyUuidRegex );
			expect( payload.client_dedup_id ).toMatch( anyUuidRegex );
		} );

		test( `[${ theme } theme] Single product page, variable product`, async ( {
			page,
		} ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/product/variable-product-one' );
			await preventCartFormSubmit( page );

			await page
				.locator( 'form.variations_form select' )
				.selectOption( { label: 'Red' } );
			const variationIdInput = page.locator( '[name=variation_id]' );
			await expect( variationIdInput ).not.toHaveValue( /^0?$/ );
			const variationId = parseInt(
				await variationIdInput.inputValue(),
				10
			);

			await singleAddToCart( page, 1 );

			const events = await page.evaluate( () => window.snaptr.queue );
			const ADD_CART = findSnaptrEvent( events, 'ADD_CART' );
			expect( ADD_CART ).not.toBe( null );

			const [ , , payload ] = ADD_CART;

			expect( payload.integration ).toBe( integration );
			expect( payload.currency ).toBe( 'USD' );
			expect( payload.price ).toBe( 12 );
			expect( payload.item_ids ).toContain( variationId );
			expect( payload.event_id ).toMatch( anyUuidRegex );
			expect( payload.client_dedup_id ).toMatch( anyUuidRegex );
		} );
	}
} );
