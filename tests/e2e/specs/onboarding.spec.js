/**
 * External dependencies
 */
import OnboardingPage from '../utils/pages/onboarding';
const { test, expect } = require( '@playwright/test' );

/**
 * @type {import('../utils/pages/onboarding.js').default} onboardingPage
 */
let onboardingPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Onboarding', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		onboardingPage = new OnboardingPage( page );
	} );

	test.afterAll( async () => {
		await onboardingPage.closePage();
	} );

	test( 'Can visit the get started page', async ( { page } ) => {
		const response = await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fstart' );
		expect( response?.status() ).toBe( 200 );
	} );

	test( 'Clicking "Get Started" starts onboarding', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fstart' );
		await page.getByRole( 'link', { name: 'Get Started' } ).click();
		await expect( await page.getByRole( 'heading', { name: 'Set up your accounts' } ) ).toBeVisible();
	} );

	test( 'Back button redirects to Start page', async( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup' );
		await page.locator( '.sfw-stepper-top-bar__back-button' ).click();
		await expect( page ).toHaveURL( '/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fstart' );
	} );

	test( 'Verify WordPress.com connect container', async( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup' );

		const cardBody = page.locator('[data-wp-component="CardBody"]').filter( {
			has: page.getByText( 'WordPress.com' ),
		} );

		expect( cardBody ).toContainText( 'Required to connect with Snapchat', { exact: false } );

		await expect( cardBody.getByRole( 'button', { name: 'Connect' } ) ).toBeEnabled();
	} );

	test( 'Verify Snapchat connect container', async( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup' );

		const cardBody = page.locator('[data-wp-component="CardBody"]').filter( {
			has: page.locator('.sfw-subsection-title', { hasText: 'Snapchat' }),
		} );

		expect( cardBody ).toContainText( 'Connect your Snapchat Business Account to sync your catalog and run Dynamic Ads.', { exact: false } );

		await expect( cardBody.getByRole( 'button', { name: 'Connect' } ) ).not.toBeEnabled();
	} );

	test.describe( 'Connect WordPress.com account', () => {
		test( 'should send an API request to connect Jetpack, and redirect to the returned URL', async ( {
			baseURL,
		} ) => {
			// Mock Jetpack connect.
			await onboardingPage.mockJetpackConnect( baseURL + '/auth_url' );

			await onboardingPage.goto();

			// Click the enabled connect button.
			await page.locator(
				"//button[text()='Connect'][not(@disabled)]"
			).click();

			await page.waitForLoadState( 'domcontentloaded' );

			// Expect the user to be redirected.
			await page.waitForURL( baseURL + '/auth_url' );

			expect( page.url() ).toMatch( baseURL + '/auth_url' );
		} );
	} );

	test.describe( 'Connected WordPress.com account', async () => {
		test.beforeAll( async () => {
			// Mock Snapchat as not connected.
			// When pending even WPORG will not render yet.
			// If not mocked will fail and render nothing,
			// as Jetpack is mocked only on the client-side.
			await onboardingPage.mockSnapchatNotConnected();

			await onboardingPage.goto();
		} );

		test( 'should show the WP.org connection button when not connected', async () => {
			await onboardingPage.mockJetpackNotConnected();
			await onboardingPage.goto();

			await expect(
				page.getByRole( 'heading', { name: 'Set up your accounts' } )
			).toBeVisible();

			const wpAccountCard = onboardingPage.getWPAccountCard();

			await expect( wpAccountCard ).toBeVisible();

			const connectButton = await wpAccountCard.getByRole( 'button', {
				hasText: 'Connect',
			} );

			await expect( connectButton ).toBeVisible();
		} );

		test( 'should show the WP.org connection card when already connected', async () => {
			await onboardingPage.mockJetpackConnected(
				'Test user',
				'jetpack@example.com'
			);
			await onboardingPage.goto();

			await expect(
				page.getByRole( 'heading', { name: 'Set up your accounts' } )
			).toBeVisible();

			await expect(
				page.getByText(
					'Connect your Snapchat Business Account to sync your catalog and run Dynamic Ads.'
				)
			).toBeVisible();

			const wpAccountCard = onboardingPage.getWPAccountCard();

			await expect( wpAccountCard ).toBeVisible();
			await expect( onboardingPage.getSnapchatComboConnectedLabel() ).toBeVisible();
		} );
	} );
} );
