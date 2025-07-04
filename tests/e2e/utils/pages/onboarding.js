/**
 * Internal dependencies
 */
import MockRequests from '../mock-requests';

/**
 * Set up accounts page object class.
 */
export default class OnboardingPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Close the current page.
	 *
	 * @return {Promise<void>}
	 */
	async closePage() {
		await this.page.close();
	}

	/**
	 * Go to the set up mc page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup',
			{ waitUntil: 'domcontentloaded' }
		);
	}

	/**
	 * Get Snapchat description row.
	 *
	 * @return {import('@playwright/test').Locator} Get Snapchat description row.
	 */
	getSnapchatDescriptionRow() {
		return this.getSnapchatAccountCard().locator(
			'.sfw-account-card__description'
		);
	}

	/**
	 * Get Snapchat combo card connected label.
	 *
	 * @return {import('@playwright/test').Locator} Get Snapchat combo card connected label.
	 */
	getSnapchatComboConnectedLabel() {
		return this.getSnapchatAccountCard().locator(
			'.sfw-connected-icon-label'
		);
	}

	/**
	 * Get "Connect" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Connect" button.
	 */
	getConnectButton() {
		return this.page.getByRole( 'button', {
			name: 'Connect',
			exact: true,
		} );
	}

	/**
	 * Get WordPress account card.
	 *
	 * @return {import('@playwright/test').Locator} Get WordPress account card.
	 */
	getWPAccountCard() {
		return this.page.locator( '.sfw-account-card', {
			hasText: 'WordPress.com',
		} );
	}

	/**
	 * Get Snapchat account card.
	 *
	 * @return {import('@playwright/test').Locator} Get Snapchat account card.
	 */
	getSnapchatAccountCard() {
		return this.page.locator(
			'.sfw-account-card'
		);
	}

	/**
	 * Get "Continue" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Continue" button.
	 */
	getContinueButton() {
		return this.page.getByRole( 'button', {
			name: 'Continue',
			exact: true,
		} );
	}

	/**
	 * Click "Continue" button.
	 *
	 * @return {Promise<void>}
	 */
	async clickContinueButton() {
		const continueButton = this.getContinueButton();
		await continueButton.click();
		await this.page.waitForLoadState( 'domcontentloaded' );
	}

	/**
	 * Get connect to a different Snapchat account button.
	 *
	 * @return {import('@playwright/test').Locator} Get connect to a different Snapchat account button.
	 */
	getConnectDifferentAdsAccountButton() {
		return this.page.getByRole( 'button', {
			name: 'Or, connect to a different Snapchat account',
			exact: true,
		} );
	}

	/**
	 * Mock Google as not connected.
	 */
	async mockSnapchatNotConnected() {
		await this.fulfillSnapchatConnection( {
			active: 'no',
			email: '',
			scope: [],
		} );
	}
}
