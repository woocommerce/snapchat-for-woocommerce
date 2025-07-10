/**
 * Internal utility to register click event listeners for given selectors.
 *
 * Hooks are attached after DOMContentLoaded to ensure all target elements
 * are available in the document.
 *
 * @since 0.1.0
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
export function onLoopAddToCartClick( callback ) {
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
	addEventHook( [ '.single_add_to_cart_button' ], callback );
}

/**
 * Check if marketing consent has been granted before running tracking logic.
 *
 * This guard ensures that no tracking (Pixel or Conversion API)
 * is executed if the user has explicitly denied consent for marketing.
 *
 * The check uses the WP Consent API's `wp_has_consent()` method, which returns:
 * - `true` if the user has granted consent for the specified category (e.g., 'marketing')
 * - `false` if the user has explicitly denied it
 *
 * If the `wp_has_consent` function is not defined (e.g., WP Consent API is not installed),
 * the condition assumes consent has been granted (fail-open).
 *
 * @since 0.1.0
 */
export function hasUserConsent() {
	return (
		typeof wp_has_consent !== 'function' || wp_has_consent( 'marketing' )
	);
}

/**
 * Stores the Snapchat Click ID (sc_click_id) in a first-party cookie for use in CAPI events.
 *
 * Snapchat appends the `sc_click_id` query parameter to the landing page URL after an ad click.
 * This value is required for server-side event attribution (CAPI), but only appears on the first
 * page view of a session. To persist it for the rest of the session, we store it in a cookie
 * named `ScCid`.
 *
 * The cookie can later be read in PHP via `$_COOKIE['ScCid']` and included in the user_data
 * payload for Snapchat Conversion API events.
 *
 * ⚠️ This logic is specific to Snapchat's tracking requirements.
 *
 * Cookie Attributes:
 * - `path=/`: Makes the cookie available site-wide
 * - No expiration is set → session cookie (expires when browser closes)
 *
 * @since 0.1.0
 */
export function setSnapChatClickId() {
	const url = new URL( window.location.href );
	const scClickId = url.searchParams.get( 'sc_click_id' );

	if ( scClickId ) {
		document.cookie = `ScCid=${ encodeURIComponent( scClickId ) }; path=/;`;
	}
}
