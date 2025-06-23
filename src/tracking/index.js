import { TRACKING_DATA_VAR } from './constants';
import { onSingleAddToCartClick, onLoopAddToCartClick } from './utils';
import { singleAddToCartClick, addToCartClick } from './pixel/utils';
import { triggerCAPI } from './conversions/utils';

const isPixelEnabled = TRACKING_DATA_VAR.is_pixel_enabled;
const isConversionEnabled = TRACKING_DATA_VAR.is_conversion_enabled;

document.addEventListener( 'DOMContentLoaded', () => {
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
	 */
	const hasConsent = 'function' !== typeof wp_has_consent || wp_has_consent( 'marketing' );

	if ( ! hasConsent ) {
		console.info( '[Snapchat] Marketing consent denied. Tracking skipped.' );
		return;
	}

	onSingleAddToCartClick( ( event ) => {
		const eventId = document.querySelector( `[name=${ TRACKING_DATA_VAR.event_id_el_name }]` ).value;
		isPixelEnabled && singleAddToCartClick( event, eventId );
	} );

	onLoopAddToCartClick( ( event ) => {
		const eventId = window.crypto.randomUUID();
		isPixelEnabled && addToCartClick( event, eventId );
		const data = event.currentTarget.dataset;
		isConversionEnabled && data?.product_id && triggerCAPI( eventId, data.product_id, 1 );
	} );
} );

/**
 * Listen for changes in user consent categories via WP Consent API.
 *
 * The 'wp_listen_for_consent_change' event is dispatched when a consent category
 * is updated through wp_set_consent(). This is useful for dynamically responding
 * to a user's interaction with a cookie banner or similar interface.
 *
 * This specific implementation checks if the user has just allowed 'marketing' consent,
 * and if so, reloads the page to ensure that any tracking scripts gated behind
 * wp_has_consent('marketing') are properly executed.
 */
document.addEventListener( 'wp_listen_for_consent_change', ( e ) => {
	if ( 'allow' === e.detail.marketing ) {
		window.location.reload();
	}
} );
