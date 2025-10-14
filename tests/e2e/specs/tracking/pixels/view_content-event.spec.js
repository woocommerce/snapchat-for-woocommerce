/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { findSnaptrEvent, getThemes, switchTheme } from '../../../utils';

test.describe( 'VIEW_CONTENT event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const themes = getThemes();

	for ( const theme in themes ) {
		test( `[${ theme } theme] Direct access to Single Product Page sends events`, async ( {
			page,
		} ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/product/product-two' );
			const events = await page.evaluate( () => window.snaptr.queue );
			const VIEW_CONTENT = findSnaptrEvent( events, 'VIEW_CONTENT' );
			expect( VIEW_CONTENT ).not.toBe( null );

			const [ , , payload ] = VIEW_CONTENT;

			expect( payload.integration ).toBe( 'woocommerce-v1' );
			expect( payload.price ).toBe( 15 );
			expect( payload.currency ).toBe( 'USD' );
			expect( payload.item_ids ).toContain( 13 );
		} );

		test( `[${ theme } theme] Backward navigation sends event `, async ( {
			page,
		} ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/product/product-two' );
			await page
				.getByRole( 'link', { name: 'Sample Page' } )
				.first()
				.click();
			await page.goBack();

			const events = await page.evaluate( () => window.snaptr.queue );
			const VIEW_CONTENT = findSnaptrEvent( events, 'VIEW_CONTENT' );
			expect( VIEW_CONTENT ).not.toBe( null );

			const [ , , payload ] = VIEW_CONTENT;

			expect( payload.integration ).toBe( 'woocommerce-v1' );
			expect( payload.price ).toBe( 15 );
			expect( payload.currency ).toBe( 'USD' );
			expect( payload.item_ids ).toContain( 13 );
		} );

		test( `[${ theme } theme] Navigate to Single Product Page event sends event `, async ( {
			page,
		} ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/shop' );
			await page
				.locator( '.woocommerce-loop-product__title', {
					hasText: 'Product Two',
				} )
				.or(
					page.locator( '.wp-block-post-title', {
						hasText: 'Product Two',
					} )
				)
				.click();

			await expect( page.url() ).toContain( '/product/product-two' );
			await page.waitForLoadState( 'domcontentloaded' );

			const events = await page.evaluate( () => window.snaptr.queue );
			const VIEW_CONTENT = findSnaptrEvent( events, 'VIEW_CONTENT' );
			expect( VIEW_CONTENT ).not.toBe( null );

			const [ , , payload ] = VIEW_CONTENT;

			expect( payload.integration ).toBe( 'woocommerce-v1' );
			expect( payload.price ).toBe( 15 );
			expect( payload.currency ).toBe( 'USD' );
			expect( payload.item_ids ).toContain( 13 );
		} );

		test( `[${ theme } theme] No event is sent on reload`, async ( {
			page,
		} ) => {
			await switchTheme( page, themes[ theme ] );
			await page.goto( '/product/product-two' );
			await page.reload();

			const events = await page.evaluate( () => window.snaptr.queue );
			const VIEW_CONTENT = findSnaptrEvent( events, 'VIEW_CONTENT' );
			expect( VIEW_CONTENT ).toBe( null );
		} );
	}
} );
