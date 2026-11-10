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
	delete window.jQuery;
	delete window.wp_has_consent;
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

// Stands in for jQuery so the test can trigger the handlers the script binds
// with `jQuery( document ).on( event, selector, handler )`.
const captureJQueryHandlers = () => {
	const handlers = {};
	window.jQuery = jest.fn( () => ( {
		on: ( eventName, selector, handler ) => {
			handlers[ eventName ] = handler;
		},
	} ) );

	return handlers;
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

	test( 'Sends the pixel event with the ID from the event ID field', async () => {
		const button = renderFormWithoutEventIdField();
		button.form.insertAdjacentHTML(
			'beforeend',
			'<input type="hidden" name="snapchat_event_id" value="abc">'
		);
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

		expect( window.snaptr ).toHaveBeenCalledWith(
			'track',
			'ADD_CART',
			expect.objectContaining( {
				item_ids: [ 10 ],
				event_id: 'abc',
				client_dedup_id: 'abc',
			} )
		);
	} );

	test( 'Does not look up the event ID field when the pixel is off', async () => {
		const button = renderFormWithoutEventIdField();
		window.snaptr = jest.fn();
		await loadTrackingScript( { is_pixel_enabled: '' } );

		button.click();

		expect( window.snaptr ).not.toHaveBeenCalled();
	} );
} );

describe( 'Variation listener', () => {
	const variation = { variation_id: 11, display_price: 25 };

	test( 'Records the selected variation price', async () => {
		const handlers = captureJQueryHandlers();
		const trackingData = {
			is_pixel_enabled: '1',
			pixel_data: { products: {} },
		};
		await loadTrackingScript( trackingData );

		handlers.found_variation( new Event( 'found_variation' ), variation );

		expect( trackingData.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
		} );
	} );

	test( 'Records nothing without marketing consent', async () => {
		const handlers = captureJQueryHandlers();
		window.wp_has_consent = jest.fn( () => false );
		const trackingData = {
			is_pixel_enabled: '1',
			pixel_data: { products: {} },
		};
		await loadTrackingScript( trackingData );

		handlers.found_variation( new Event( 'found_variation' ), variation );

		expect( trackingData.pixel_data.products[ 11 ] ).toBeUndefined();
		expect( console ).toHaveInformed();
	} );

	test( 'Does not throw when recording the variation fails', async () => {
		const handlers = captureJQueryHandlers();
		const pixelData = {};
		Object.defineProperty( pixelData, 'products', {
			get() {
				throw new Error( 'Unexpected tracking data' );
			},
		} );
		await loadTrackingScript( {
			is_pixel_enabled: '1',
			pixel_data: pixelData,
		} );

		expect( () =>
			handlers.found_variation(
				new Event( 'found_variation' ),
				variation
			)
		).not.toThrow();
		expect( console ).toHaveErrored();
	} );
} );
