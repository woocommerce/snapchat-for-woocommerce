/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import { findSnaptrEvent, getThemes, switchTheme } from '../../../utils';

test.describe( 'PAGE_VIEW event', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	const PAGES = [
		{ url: '/', name: 'Home Page' },
		{ url: '/shop', name: 'Shop Page' },
		{ url: '/product/product-one', name: 'Single Product Page' },
	];

	const themes = getThemes();

	for ( const theme in themes ) {
		for ( const PAGE of PAGES ) {
			test( `[${ theme } theme] ${ PAGE.name }`, async ( { page } ) => {
				await switchTheme( page, themes[ theme ] );
				await page.goto( PAGE.url );
				const events = await page.evaluate( () => window.snaptr.queue );
				const PAGE_VIEW = findSnaptrEvent( events, 'PAGE_VIEW' );
				expect( PAGE_VIEW ).not.toBe( null );
			} );
		}
	}
} );
