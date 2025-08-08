/**
 * Internal dependencies
 */
import { TRACKING_DATA_VAR } from '../constants';

export function with_prefix( $action = '' ) {
	return `${ TRACKING_DATA_VAR.prefix }${ $action }`;
}

/**
 * Sends a Conversion API (CAPI) tracking signal to the server using `fetch` with `keepalive`.
 *
 * This utility is generic and can be used to send any event payload to a REST endpoint.
 * Useful for non-blocking tracking events like ViewContent, AddToCart, etc.
 *
 * @param {string} event - The Event name.
 * @param {Object} payload - Key-value data to include in the request body.
 *
 * @since 0.1.0
 */
export function sendCapiEvent( event, payload = {} ) {
	if ( typeof event !== 'string' || ! event || typeof payload !== 'object' ) {
		return;
	}

	fetch( `${ TRACKING_DATA_VAR.ajax_url }`, {
		method: 'POST',
		credentials: 'same-origin',
		keepalive: true,
		body: new URLSearchParams( {
			action: with_prefix( event.toLowerCase() ),
			payload: JSON.stringify( payload ),
			security: TRACKING_DATA_VAR.capi_nonce,
		} ),
	} );
}
