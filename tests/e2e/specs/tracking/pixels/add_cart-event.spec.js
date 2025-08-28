/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { findSnaptrEvent, getThemes, switchTheme } from '../../../utils';

const anyUuidRegex =
	/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

test.describe( 'ADD_CART event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const themes = getThemes();

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

			expect( payload.integration ).toBe( 'woocommerce-v1' );
			expect( payload.price ).toBe( 10 );
			expect( payload.event_id ).toMatch( anyUuidRegex );
			expect( payload.client_dedup_id ).toMatch( anyUuidRegex );
		} );
	}
} );
