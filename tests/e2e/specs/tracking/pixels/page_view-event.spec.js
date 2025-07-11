/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { findSnaptrEvent } from '../../../utils';

test.describe( 'PAGE_VIEW event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const PAGES = [
		{ url: '/', name: 'Home Page' },
		{ url: '/shop', name: 'Shop Page' },
		{ url: '/product/product-one', name: 'Single Product Page' },
	];

	for ( const PAGE of PAGES ) {
		test( `${ PAGE.name }`, async ( { page } ) => {
			await page.goto( PAGE.url );
			const events = await page.evaluate( () => window.snaptr.queue );
			const PAGE_VIEW = findSnaptrEvent( events, 'PAGE_VIEW' );
			expect( PAGE_VIEW ).not.toBe( null );
		} );
	}
} );
