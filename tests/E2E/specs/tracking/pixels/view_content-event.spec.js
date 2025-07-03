/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { findSnaptrEvent } from '../../../utils';

test.describe( 'VIEW_CONTENT event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( `Single Product Page`, async ( { page } ) => {
		await page.goto( '/product/product-two' );
		const events = await page.evaluate( () => window.snaptr.queue );
		const VIEW_CONTENT = findSnaptrEvent( events, 'VIEW_CONTENT' );
		expect( VIEW_CONTENT ).not.toBe( null );

		const payload = VIEW_CONTENT[2];

		expect( payload.price ).toBe( 10 );
		expect( payload.currency ).toBe( 'USD' );
		expect( payload.item_ids ).toContain( 11 )
	} );
} );
