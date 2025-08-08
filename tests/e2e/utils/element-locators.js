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
			has: this.page.locator( '.sfw-account-card__title', {
				hasText: title,
			} ),
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
		return this.getWPAccountCard().getByRole( 'button', {
			hasText: 'Connect',
		} );
	}

	/**
	 * Get the connected label inside the WordPress.com account card.
	 *
	 * @return {import('@playwright/test').Locator} The connected label locator.
	 */
	getWpConnectedLabel() {
		return this.getWPAccountCard().locator( '.sfw-connected-icon-label' );
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
		return this.getSnapchatAccountCard().getByRole( 'button', {
			hasText: 'Connect',
		} );
	}

	/**
	 * Get the continue button that navigates to the setup screen after click.
	 *
	 * @return {import('@playwright/test').Locator} The continue button locator.
	 */
	getContinueToSetupButton() {
		return this.page.getByRole( 'button', { name: 'Continue' } );
	}

	/**
	 * Get the modal shown after successful onboarding.
	 *
	 * @return {import('@playwright/test').Locator} The onboarding successful modal.
	 */
	getOnboardingSuccessfulModal() {
		return this.page.locator( '.sfw-onboarding-success-modal', {
			hasText: 'You’ve successfully set up Snapchat for WooCommerce!',
		} );
	}

	/**
	 * Get the modal shown after successful onboarding.
	 *
	 * @return {import('@playwright/test').Locator} The onboarding successful modal.
	 */
	getOnboardingSuccessfulCloseModalButton() {
		return this.getOnboardingSuccessfulModal().getByRole( 'button', {
			name: 'Close',
		} );
	}

	/**
	 * Get the disconnect button inside the Snapchat account card.
	 *
	 * @return {import('@playwright/test').Locator} The disconnect button locator.
	 */
	getSnapchatDisconnectButton() {
		return this.getSnapchatAccountCard().getByRole( 'button', {
			name: 'Disconnect Snapchat account',
		} );
	}

	/**
	 * Get the Snapchat disconnect confirmation modal.
	 *
	 * @return {import('@playwright/test').Locator} The Disconnect confirmation modal.
	 */
	getSnapchatDisconnectModal() {
		return this.page.locator( '.sfw-disconnect-accounts-modal', {
			hasText:
				'I understand that I am disconnecting my Snapchat account from this WooCommerce extension.',
		} );
	}

	/**
	 * Get the Snapchat disconnect confirmation checkbox inside the modal.
	 *
	 * @return {import('@playwright/test').Locator} The Disconnect confirmation modal.
	 */
	getSnapchatDisconnectConfirmCheckbox() {
		return this.getSnapchatDisconnectModal().getByRole( 'checkbox', {
			name: 'Yes, I want to disconnect my Snapchat account',
		} );
	}

	/**
	 * Get the final disconnect button inside the Snapchat account card.
	 *
	 * @return {import('@playwright/test').Locator} The disconnect button locator.
	 */
	getSnapchatFinalDisconnectButton() {
		return this.getSnapchatDisconnectModal().getByRole( 'button', {
			name: 'Disconnect Snapchat Account',
		} );
	}

	/**
	 * Get the connected label inside the Snapchat account card.
	 *
	 * @return {import('@playwright/test').Locator} The connected label locator.
	 */
	getSnapchatConnectedLabel() {
		return this.getSnapchatAccountCard().locator(
			'.sfw-connected-icon-label'
		);
	}

	/**
	 * Get the checkbox for enabling Conversions API tracking.
	 *
	 * @return {import('@playwright/test').Locator} The checkbox locator.
	 */
	getCapiCheckbox() {
		return this.getCard( 'Conversions API' ).getByLabel(
			'Enable Conversions API tracking'
		);
	}
}
