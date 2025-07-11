/* eslint no-console: 0 */

/**
 * Internal dependencies
 */
import { TRACKING_DATA_VAR } from './constants';
import {
	onSingleAddToCartClick,
	onLoopAddToCartClick,
	hasUserConsent,
	setSnapChatClickId,
} from './utils';
import { singleAddToCartClick, addToCartClick } from './pixel/utils';
import { triggerCAPI } from './conversions/utils';

const isPixelEnabled = TRACKING_DATA_VAR.is_pixel_enabled;
const isConversionEnabled = TRACKING_DATA_VAR.is_conversion_enabled;

/**
 * Immediately sets the ScCid cookie from the `sc_click_id` URL param,
 * but only if the user has granted marketing consent via WP Consent API.
 */
if ( hasUserConsent() ) {
	setSnapChatClickId();
}

document.addEventListener( 'DOMContentLoaded', () => {
	if ( ! hasUserConsent() ) {
		console.info(
			'[Snapchat] Marketing consent denied. Tracking skipped.'
		);
		return;
	}

	onSingleAddToCartClick( ( event ) => {
		const eventId = document.querySelector(
			`[name=${ TRACKING_DATA_VAR.event_id_el_name }]`
		).value;

		if ( isPixelEnabled ) {
			singleAddToCartClick( event, eventId );
		}
	} );

	onLoopAddToCartClick( ( event ) => {
		const eventId = window.crypto.randomUUID();

		if ( isPixelEnabled ) {
			addToCartClick( event, eventId );
		}

		const data = event.currentTarget.dataset;

		if ( isConversionEnabled && data?.product_id ) {
			triggerCAPI( eventId, data.product_id, 1 );
		}
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
	if ( e.detail.marketing === 'allow' ) {
		window.location.reload();
	}
} );
