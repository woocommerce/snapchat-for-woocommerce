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
	switchTheme,
} from '../../../utils';
import { integration } from '../../../config';

test.describe( 'VIEW_CONTENT event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const themes = getThemes();
	let productId = null;

	test.beforeAll( 'Get product ID', async ( { browser } ) => {
		const page = await browser.newPage();
		// The pixel sends item IDs as numbers for this event.
		productId = await getProductId( page.request, 'product-two' );
		await page.close();
	} );

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

			expect( payload.integration ).toBe( integration );
			expect( payload.price ).toBe( 15 );
			expect( payload.currency ).toBe( 'USD' );
			expect( payload.item_ids ).toContain( productId );
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

			expect( payload.integration ).toBe( integration );
			expect( payload.price ).toBe( 15 );
			expect( payload.currency ).toBe( 'USD' );
			expect( payload.item_ids ).toContain( productId );
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

			expect( payload.integration ).toBe( integration );
			expect( payload.price ).toBe( 15 );
			expect( payload.currency ).toBe( 'USD' );
			expect( payload.item_ids ).toContain( productId );
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
