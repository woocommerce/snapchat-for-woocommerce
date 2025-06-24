import { TRACKING_DATA_VAR } from '../constants';

export const triggerCAPI = async ( eventId, productId, quantity ) => {
	const formData = new FormData();
	formData.append( 'action', TRACKING_DATA_VAR.capi_trigger_action );
	formData.append( 'security', TRACKING_DATA_VAR.capi_nonce );
	formData.append( 'product_id', productId );
	formData.append( 'quantity', quantity );
	formData.append( 'event_id', eventId );

	await fetch( TRACKING_DATA_VAR.ajax_url, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	} );
}
