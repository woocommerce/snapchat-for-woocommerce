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
	 * @param {Object<string, string>} [query={}] Extra query args, e.g. the args an OAuth return adds.
	 * @return {Promise<void>}
	 */
	async goto( query = {} ) {
		const params = new URLSearchParams( {
			page: 'wc-admin',
			path: '/snapchat/setup',
			...query,
		} );

		await this.page.goto( `/wp-admin/admin.php?${ params }`, {
			waitUntil: 'domcontentloaded',
		} );
	}
}
