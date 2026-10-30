/**
 * External dependencies
 */
const { expect } = require( '@playwright/test' );

/**
 * Gets the ID of a product from its slug using the public WooCommerce Store API.
 *
 * Product IDs depend on how the test environment was seeded, so specs should
 * read them at runtime instead of hardcoding them.
 *
 * @param {import('@playwright/test').APIRequestContext} request - Request context with the test site's baseURL (e.g. `page.request`).
 * @param {string} slug - The product slug (e.g. 'product-one').
 * @return {Promise<number>} The product ID.
 * @throws {Error} If the request fails or no product matches the slug.
 */
export async function getProductId( request, slug ) {
	const response = await request.get( '/wp-json/wc/store/v1/products', {
		params: { slug },
	} );
	await expect( response ).toBeOK();

	const data = await response.json();

	if ( ! Array.isArray( data ) || ! data.length ) {
		throw new Error( `Could not find a product with slug "${ slug }"` );
	}

	return data[ 0 ].id;
}
