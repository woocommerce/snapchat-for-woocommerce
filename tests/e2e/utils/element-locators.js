/**
 * Returns common locators on the setup and settings page.
 */
export default class ElementLocators {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		this.page = page;
	}

	/**
	 * Get a generic account card by its title.
	 *
	 * @param {string} title The title text of the account card (e.g., 'WordPress.com', 'Snapchat').
	 * @return {import('@playwright/test').Locator} The locator for the matching account card.
	 */
	getCard( title = '' ) {
		return this.page.locator( '.sfw-account-card', {
			has: this.page.locator( '.sfw-account-card__title', { hasText: title } ),
		} );
	}

	/**
	 * Get WP account card.
	 *
	 * @return {import('@playwright/test').Locator} Get WP account card.
	 */
	getWPAccountCard() {
		return this.getCard( 'WordPress.com' );
	}

	/**
	 * Get the connect button inside the WordPress.com account card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getWpConnectButton() {
		return this
			.getWPAccountCard()
			.getByRole( 'button', {
				hasText: 'Connect',
			} );
	}

	/**
	 * Get the connected label inside the WordPress.com account card.
	 *
	 * @return {import('@playwright/test').Locator} The connected label locator.
	 */
	getWpConnectedLabel() {
		return this
			.getWPAccountCard()
			.locator(
				'.sfw-connected-icon-label'
			);
	}

	/**
	 * Get Snapchat account card.
	 *
	 * @return {import('@playwright/test').Locator} Get Snapchat account card.
	 */
	getSnapchatAccountCard() {
		return this.getCard( 'Snapchat' );
	}

	/**
	 * Get the connect button inside the Snapchat account card.
	 *
	 * @return {import('@playwright/test').Locator} The connect button locator.
	 */
	getSnapchatConnectButton() {
		return this
			.getSnapchatAccountCard()
			.getByRole( 'button', {
				hasText: 'Connect',
			} );
	}

	/**
	 * Get the connected label inside the Snapchat account card.
	 *
	 * @return {import('@playwright/test').Locator} The connected label locator.
	 */
	getSnapchatConnectedLabel() {
		return this
			.getSnapchatAccountCard()
			.locator(
				'.sfw-connected-icon-label'
			);
	}

	/**
	 * Get the checkbox for enabling Conversions API tracking.
	 *
	 * @return {import('@playwright/test').Locator} The checkbox locator.
	 */
	getCapiCheckbox() {
		return this
			.getCard( 'Conversions API' )
			.getByLabel( 'Enable Conversions API tracking' );
	}
}
