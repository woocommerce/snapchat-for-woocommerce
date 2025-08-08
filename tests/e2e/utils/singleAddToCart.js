export async function singleAddToCart( page, quantity = '1' ) {
	await page.locator( '[name="quantity"]' ).fill( quantity.toString() );
	await page
		.locator( '.single_add_to_cart_button', { hasText: 'Add to cart' } )
		.click();
}
