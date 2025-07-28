/**
 * Switches the active WordPress theme using the E2E test REST endpoint.
 *
 * Requires the `snapchat-e2e/v1/switch-theme` endpoint to be available
 * and the `E2E_CONTEXT` constant defined on the server.
 *
 * @param {import('@playwright/test').Page} page - The Playwright page object.
 * @param {string} themeSlug - The slug of the theme to activate (e.g. 'twentytwentyfour').
 * @throws {Error} If the request fails or the response indicates an error.
 */
export async function switchTheme( page, themeSlug ) {
	const response = await page.request.post( '/wp-json/snapchat-e2e/v1/switch-theme', {
		data: {
			theme: themeSlug,
		},
		headers: {
			'Content-Type': 'application/json',
		},
	} );

	if ( !response.ok() ) {
		const errorBody = await response.text();
		throw new Error( `Failed to switch theme. HTTP ${ response.status() }: ${ errorBody }` );
	}

	const result = await response.json();

	if ( ! result.success ) {
		throw new Error( `Theme switch failed: ${ result.error || 'Unknown error' }` );
	}
}

/**
 * Returns a mapping of theme type labels to their corresponding theme slugs.
 *
 * Used in E2E tests to programmatically switch between classic and block themes.
 *
 * @returns {Object} An object where keys are theme types (e.g. 'classic', 'block')
 *                   and values are corresponding WordPress theme slugs.
 */
export function getThemes() {
	return {
		'classic': 'storefront',
		'block': 'twentytwentyfive',
	};
}
