/**
 * Internal dependencies
 */
import { TRACKING_DATA_VAR } from './constants';
import { SnapchatEvent } from './pixel/events';
import { sendPixelEvent } from './pixel/utils';
import { sendCapiEvent } from './conversions/utils';

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

/**
 * Determines whether the current page load is a fresh navigation
 * (e.g., from a link, address bar, or redirect) and not a reload.
 *
 * Uses the Performance Navigation API (Level 2 where supported) to inspect
 * the navigation type. Falls back to legacy `performance.navigation.type`
 * if needed.
 *
 * Returns `true` only for:
 * - 'navigate' (link click, address bar entry, redirect)
 * - 'back_forward' (history traversal – optional, still counts as non-reload)
 *
 * Returns `false` for:
 * - 'reload' (user manually reloaded the page)
 *
 * @since 0.1.0
 *
 * @return {boolean} Whether the page load was a fresh visit.
 */
export function isFreshPageVisit() {
	if ( typeof performance === 'undefined' ) {
		return true;
	}

	if ( performance.getEntriesByType ) {
		const entries = performance.getEntriesByType( 'navigation' );

		if ( entries.length > 0 ) {
			const type = entries[ 0 ].type;
			return type === 'navigate' || type === 'back_forward';
		}
	}

	// Fallback for older browsers (0 = TYPE_NAVIGATE)
	return performance.navigation?.type === 0;
}

/**
 * Fires a Snapchat `VIEW_CONTENT` event when a user lands on a single product page.
 *
 * This method is designed to be called on Single Product pages.
 * It ensures the event is only fired only on fresh navigations — such as arriving
 * via a link click, redirect, or back/forward traversal — and not on manual page reloads
 * to inflating analytics or triggering duplicate events.
 *
 * A unique `eventId` is generated and included in both the Pixel and Conversions API payloads
 * to support deduplication.
 *
 * @since 0.1.0
 *
 * @return {void}
 */
export const onSingleProductPageVisit = () => {
	if ( isFreshPageVisit() && TRACKING_DATA_VAR.VIEW_CONTENT ) {
		const eventId = window.crypto.randomUUID();

		const eventData = {
			...TRACKING_DATA_VAR.VIEW_CONTENT,
			event_id: eventId,
			client_dedup_id: eventId,
		};

		if ( TRACKING_DATA_VAR.is_pixel_enabled ) {
			sendPixelEvent( SnapchatEvent.VIEW_CONTENT, eventData );
		}

		if ( TRACKING_DATA_VAR.is_conversion_enabled ) {
			sendCapiEvent( SnapchatEvent.VIEW_CONTENT, eventData );
		}
	}
};

/**
 * Fires a Snapchat `START_CHECKOUT` event when a user reaches the Checkout page.
 *
 * This method is designed to be called on the Checkout page.
 * It ensures the event is only fired only on fresh navigations — such as arriving
 * via a link click, redirect, or back/forward traversal — and not on manual page reloads
 * to inflating analytics or triggering duplicate events.
 *
 * A unique `eventId` is generated and included in both the Pixel and Conversions API payloads
 * to support deduplication.
 *
 * @since 0.1.0
 *
 * @return {void}
 */
export const onCheckoutPageVisit = () => {
	if ( isFreshPageVisit() && TRACKING_DATA_VAR.START_CHECKOUT ) {
		const eventId = window.crypto.randomUUID();

		const eventData = {
			...TRACKING_DATA_VAR.START_CHECKOUT,
			event_id: eventId,
			client_dedup_id: eventId,
		};

		if ( TRACKING_DATA_VAR.is_pixel_enabled ) {
			sendPixelEvent( SnapchatEvent.START_CHECKOUT, eventData );
		}

		if ( TRACKING_DATA_VAR.is_conversion_enabled ) {
			sendCapiEvent( SnapchatEvent.START_CHECKOUT, eventData );
		}
	}
};

/**
 * Fires a Snapchat `PAGE_VIEW` event when a user visits any page on the site.
 *
 * This method is designed to run on all frontend pages where general page view tracking
 * is required — including content, category, and landing pages.
 *
 * It ensures the event is only fired only on fresh navigations — such as arriving
 * via a link click, redirect, or back/forward traversal — and not on manual page reloads
 * to inflating analytics or triggering duplicate events.
 *
 * @since 0.1.0
 *
 * @return {void}
 */
export const onPageView = () => {
	if ( isFreshPageVisit() && TRACKING_DATA_VAR.PAGE_VIEW ) {
		const eventId = window.crypto.randomUUID();

		const eventData = {
			event_id: eventId,
			client_dedup_id: eventId,
		};

		if ( TRACKING_DATA_VAR.is_pixel_enabled ) {
			sendPixelEvent( SnapchatEvent.PAGE_VIEW, eventData );
		}

		if ( TRACKING_DATA_VAR.is_conversion_enabled ) {
			sendCapiEvent( SnapchatEvent.PAGE_VIEW, eventData );
		}
	}
};
