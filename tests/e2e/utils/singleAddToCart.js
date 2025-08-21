/**
 * Simulates adding a product to the WooCommerce cart from the single product page.
 *
 * This utility is designed for E2E tests where you need to interact with the
 * product page directly and trigger the `Add to cart` action.
 *
 * It fills the product quantity field (default `1`) and clicks the
 * `.single_add_to_cart_button`.
 *
 * @param {import('@playwright/test').Page} page - The Playwright page object.
 * @param {number|string} [quantity='1'] - The quantity to add. Defaults to 1.
 * @throws {Error} If the quantity field or add-to-cart button cannot be found.
 */
export async function singleAddToCart( page, quantity = '1' ) {
	await page.locator( '[name="quantity"]' ).fill( quantity.toString() );
	await page
		.locator( '.single_add_to_cart_button', { hasText: 'Add to cart' } )
		.click();
}
