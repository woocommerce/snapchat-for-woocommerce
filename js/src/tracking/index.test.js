/**
 * The tracking entry point registers its listeners when it is imported, so each
 * test loads a fresh copy and removes that copy's listeners afterwards.
 */
const addedListeners = [];
const originalAddEventListener = EventTarget.prototype.addEventListener;

beforeEach( () => {
	jest.spyOn( EventTarget.prototype, 'addEventListener' ).mockImplementation(
		function ( type, listener, options ) {
			addedListeners.push( [ this, type, listener, options ] );
			return originalAddEventListener.call(
				this,
				type,
				listener,
				options
			);
		}
	);
} );

afterEach( () => {
	jest.restoreAllMocks();
	addedListeners
		.splice( 0 )
		.forEach( ( [ target, type, listener, options ] ) =>
			target.removeEventListener( type, listener, options )
		);
	delete window.snapchatAdsTrackingData;
	delete window.snaptr;
	document.body.innerHTML = '';
} );

const loadTrackingScript = async ( trackingData ) => {
	window.snapchatAdsTrackingData = trackingData;
	await jest.isolateModulesAsync( async () => {
		await import( './index' );
	} );
	document.dispatchEvent(
		new Event( 'DOMContentLoaded', { bubbles: true } )
	);
};

// A form without the hidden event ID field, as WooCommerce renders for a
// grouped product whose children can't be added directly.
const renderFormWithoutEventIdField = () => {
	document.body.innerHTML = `
		<form class="cart">
			<button type="button" name="add-to-cart" value="10" class="single_add_to_cart_button">Add to cart</button>
		</form>
	`;

	return document.querySelector( '.single_add_to_cart_button' );
};

describe( 'Single add to cart listener', () => {
	test( 'Skips the pixel event when the event ID field is missing', async () => {
		const button = renderFormWithoutEventIdField();
		window.snaptr = jest.fn();
		await loadTrackingScript( {
			is_pixel_enabled: '1',
			event_id_el_name: 'snapchat_event_id',
			pixel_data: {
				currency: 'EUR',
				currency_minor_unit: 2,
				products: { 10: { price: 25 } },
			},
		} );

		button.click();

		expect( window.snaptr ).not.toHaveBeenCalled();
	} );

	test( 'Does not look up the event ID field when the pixel is off', async () => {
		const button = renderFormWithoutEventIdField();
		window.snaptr = jest.fn();
		await loadTrackingScript( { is_pixel_enabled: '' } );

		button.click();

		expect( window.snaptr ).not.toHaveBeenCalled();
	} );
} );
