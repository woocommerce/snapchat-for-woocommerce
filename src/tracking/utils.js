/**
 * Registers a callback for Add to Cart button clicks in product loop/catalog views.
 *
 * This includes:
 * - Classic loop `.add_to_cart_button`
 * - Products (Beta) block buttons rendered inside `<woocommerce/product-button>`
 *
 * The callback receives the native DOM `click` event.
 *
 * @since 0.1.0
 *
 * @param {Function} callback - Function to call when a loop add-to-cart button is clicked.
 */
export function onLoopAddToCartClick( callback, eventId ) {
	addEventHook(
		[
			// Classic loop buttons (not variable/grouped)
			'.add_to_cart_button:not(.product_type_variable):not(.product_type_grouped):not(.wc-block-components-product-button__button)',
			// Products (Beta) block fix
			'[data-block-name="woocommerce/product-button"] > .add_to_cart_button:not(.product_type_variable):not(.product_type_grouped)',
		],
		callback
	);
}

/**
 * Registers a callback for Add to Cart button clicks on single product pages.
 *
 * These are typically buttons with the `.single_add_to_cart_button` class.
 * The callback receives the native DOM `click` event.
 *
 * @since 0.1.0
 *
 * @param {Function} callback - Function to call when a single product add-to-cart is clicked.
 */
export function onSingleAddToCartClick( callback ) {
	addEventHook( ['.single_add_to_cart_button'], callback );
}

/**
 * Internal utility to register click event listeners for given selectors.
 *
 * Hooks are attached after DOMContentLoaded to ensure all target elements
 * are available in the document.
 *
 * @param {string[]} selectors - List of CSS selectors to bind to.
 * @param {Function} callback - Function to invoke when matching element is clicked.
 */
function addEventHook( selectors, callback ) {
	document.defaultView.addEventListener( 'DOMContentLoaded', function () {
		selectors.forEach( ( selector ) => {
			document.querySelectorAll( selector ).forEach( ( button ) => {
				button.addEventListener( 'click', callback );
			} );
		} );
	} );
}
