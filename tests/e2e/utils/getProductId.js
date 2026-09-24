/**
 * External dependencies
 */
const axios = require( 'axios' ).default;

/**
 * Gets the ID of a product from its slug using the public WooCommerce Store API.
 *
 * Product IDs depend on how the test environment was seeded, so specs should
 * read them at runtime instead of hardcoding them.
 *
 * @param {string} baseURL - The base URL of the test site.
 * @param {string} slug - The product slug (e.g. 'product-one').
 * @return {Promise<number>} The product ID.
 * @throws {Error} If no product matches the slug.
 */
export async function getProductId( baseURL, slug ) {
	const { data } = await axios.get(
		`${ baseURL }/wp-json/wc/store/v1/products`,
		{ params: { slug } }
	);

	if ( ! data.length ) {
		throw new Error( `Could not find a product with slug "${ slug }"` );
	}

	return data[ 0 ].id;
}
