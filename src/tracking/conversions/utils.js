/**
 * Internal Dependencies.
 */
import { TRACKING_DATA_VAR } from '../constants';

/**
 * Sends a Snapchat Conversion API (CAPI) event to the WordPress server via AJAX.
 *
 * This function triggers a server-side tracking event by submitting an async POST
 * request to the configured WordPress AJAX endpoint (`admin-ajax.php`). It sends
 * the product information and a unique event ID, which can then be used on the
 * server to dispatch a Snapchat CAPI request.
 *
 * @since 0.1.0
 *
 * Server-side, this is expected to trigger a callback hooked into `wp_ajax_{action}`
 * that constructs and sends the actual Snapchat CAPI payload using `UserIdentifier::get_user_data()`
 * and other context.
 *
 * ⚠️ This implementation is specific to Snapchat for WooCommerce and should be re-considered
 * before reusing for other ad platforms without modifications.
 *
 * @param {string} eventId Unique UUID used to deduplicate Pixel and CAPI events
 * @param {string|number} productId WooCommerce product ID
 * @param {number} quantity Quantity of the product being added to cart
 */
export const triggerCAPI = async ( eventId, productId, quantity ) => {
	const formData = new FormData();
	formData.append( 'action', TRACKING_DATA_VAR.capi_trigger_action );
	formData.append( 'security', TRACKING_DATA_VAR.capi_nonce );
	formData.append( 'product_id', productId );
	formData.append( 'quantity', quantity );
	formData.append( 'event_id', eventId );

	await fetch( wc_add_to_cart_params.ajax_url, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	} );
}
