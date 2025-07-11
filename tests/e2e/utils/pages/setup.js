/**
 * Internal dependencies
 */
import MockRequests from '../mock-requests';

/**
 * Set up setup page object class.
 */
export default class SetupPage extends MockRequests {
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
	 * Go to the setup page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup',
			{ waitUntil: 'domcontentloaded' }
		);
	}
}
