/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
import SetupPage from '../utils/pages/setup.js';
import ElementLocators from '../utils/element-locators.js';

/**
 * @type {import('../utils/pages/setup.js').default} setupPage
 */
let setupPage = null;

/**
 * @type {import('../utils/element-locators.js').default} onboardingPage
 */
let locator = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Merchant Onboarding', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setupPage = new SetupPage( page );
		locator = new ElementLocators( page );
	} );

	test.afterAll( async () => {
		await setupPage.closePage();
	} );

	test( 'Initial account card states', async () => {
		setupPage.goto();

		await expect( locator.getWPAccountCard() ).toBeVisible();
		await expect( locator.getWpConnectButton() ).toBeEnabled();

		await expect( locator.getSnapchatAccountCard() ).toBeVisible();
		await expect( locator.getSnapchatConnectButton() ).toBeDisabled();
	} );

	test( 'WP.com connection flow', async ( { baseURL } ) => {
		await setupPage.mockJetpackConnect( `${ baseURL }/auth_url` );
		await setupPage.goto();

		await locator.getWpConnectButton().click();
		await page.waitForLoadState( 'domcontentloaded' );
		await page.waitForURL( `${ baseURL }/auth_url` );
		expect( page.url() ).toMatch( `${ baseURL }/auth_url` );
	} );

	test( 'WP.com connected card state', async () => {
		await setupPage.mockJetpackConnected();
		await setupPage.goto();

		await expect( locator.getWpConnectedLabel() ).toBeVisible();
		await expect( locator.getSnapchatConnectButton() ).toBeEnabled();
	} );

	test( 'Snapchat connection flow', async ( { baseURL } ) => {
		await setupPage.mockJetpackConnected();
		await setupPage.mockSnapchatConnect( `${ baseURL }/snap_auth_url` );
		await setupPage.goto();

		await locator.getSnapchatConnectButton().click();
		await page.waitForLoadState( 'domcontentloaded' );
		await page.waitForURL( `${ baseURL }/snap_auth_url` );
		expect( page.url() ).toMatch( `${ baseURL }/snap_auth_url` );
	} );

	test( 'Snapchat connected card state', async () => {
		await setupPage.mockJetpackConnected();
		await setupPage.mockSnapchatConnection( { status: 'connected' } );
		await setupPage.mockOnboardingSetup( {
			status: 'connected',
			step: 'accounts',
		} );
		await setupPage.goto();

		await expect( locator.getSnapchatConnectedLabel() ).toBeVisible();
	} );

	test( 'Snapchat card details', async () => {
		const payload = {
			org_id: '244753a0-2021-482c-af9b-dd6e7677d562',
			org_name: 'SnapForWooV105',
			ad_acc_id: '89b3e14b-bac9-409e-857c-ab006cd1c96e',
			ad_acc_name: 'SnapForWooV105 Self Service',
			pixel_id: 'fd014a21-2e25-41a8-9e12-de8c9fe512b4',
		};

		await setupPage.mockSnapchatAccount( payload );
		setupPage.goto();

		await expect( locator.getSnapchatAccountCard() ).toContainText(
			'Organization: SnapForWooV105'
		);
		await expect( locator.getSnapchatAccountCard() ).toContainText(
			'Ads Account: SnapForWooV105 Self Service (89b3e14b-bac9-409e-857c-ab006cd1c96e)'
		);
		await expect( locator.getSnapchatAccountCard() ).toContainText(
			'Pixel ID: fd014a21-2e25-41a8-9e12-de8c9fe512b4'
		);
		await expect( locator.getSnapchatConnectedLabel() ).toBeVisible();
	} );
} );
