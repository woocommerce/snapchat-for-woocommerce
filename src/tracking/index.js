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
		const products = isPixelEnabled && addToCartClick( event, eventId );
		isConversionEnabled && products?.id && triggerCAPI( eventId, products.id, 1 );
	} );
} );
