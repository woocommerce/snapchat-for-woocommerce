import { TRACKING_DATA_VAR } from './constants';
import { onSingleAddToCartClick, onLoopAddToCartClick } from './utils';
import { singleAddToCartClick, addToCartClick } from './pixel/utils';
import { triggerCAPI } from './conversions/utils';

const isPixelEnabled = TRACKING_DATA_VAR.is_pixel_enabled;
const isConversionEnabled = TRACKING_DATA_VAR.is_conversion_enabled;

document.addEventListener( 'DOMContentLoaded', () => {
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
