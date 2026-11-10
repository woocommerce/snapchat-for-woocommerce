/**
 * Internal dependencies
 */
import { TRACKING_DATA_VAR } from '../constants';
import {
	addToCartClick,
	retrievedVariation,
	singleAddToCartClick,
} from './utils';

jest.mock( '../constants', () => ( { TRACKING_DATA_VAR: {} } ) );

// Replace the contents of the shared object rather than the object itself,
// the same way the page's inline script merges data into the global.
const setTrackingData = ( data ) => {
	Object.keys( TRACKING_DATA_VAR ).forEach(
		( key ) => delete TRACKING_DATA_VAR[ key ]
	);
	Object.assign( TRACKING_DATA_VAR, data );
};

const createCartForm = () => {
	document.body.innerHTML = `
		<form class="cart">
			<input name="quantity" value="2" />
			<button type="button" name="add-to-cart" value="10">Add to cart</button>
		</form>
	`;

	return document.querySelector( '[name=add-to-cart]' );
};

const variation = { variation_id: 11, display_price: 25 };

afterEach( () => {
	delete window.snaptr;
} );

describe( 'retrievedVariation', () => {
	test( 'Records the variation price', () => {
		setTrackingData( { pixel_data: { products: {} } } );

		retrievedVariation( variation );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
		} );
	} );

	test( 'Records the variation price when the page printed no products', () => {
		// PHP encodes an empty products map as an empty array.
		setTrackingData( { pixel_data: { products: [] } } );

		retrievedVariation( variation );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
		} );
	} );

	test( 'Records the variation when pixel data arrives after the script loads', () => {
		setTrackingData( {} );
		Object.assign( TRACKING_DATA_VAR, {
			pixel_data: { products: {} },
		} );

		retrievedVariation( variation );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
		} );
	} );

	test( 'Does nothing when the page has no pixel data', () => {
		setTrackingData( {} );

		expect( () => retrievedVariation( variation ) ).not.toThrow();
		expect( TRACKING_DATA_VAR.pixel_data ).toBeUndefined();
	} );

	test( 'Does nothing when the pixel data has no products', () => {
		setTrackingData( { pixel_data: { currency_minor_unit: 2 } } );

		expect( () => retrievedVariation( variation ) ).not.toThrow();
		expect( TRACKING_DATA_VAR.pixel_data.products ).toBeUndefined();
	} );

	test( 'Does nothing when the variation has no ID', () => {
		setTrackingData( { pixel_data: { products: {} } } );

		retrievedVariation( { display_price: 25 } );

		expect( TRACKING_DATA_VAR.pixel_data.products ).toEqual( {} );
	} );
} );

describe( 'Add to cart clicks without pixel data', () => {
	beforeEach( () => {
		setTrackingData( {} );
		window.snaptr = jest.fn();
	} );

	test( 'singleAddToCartClick does not throw or send an event', () => {
		const button = createCartForm();

		expect( () =>
			singleAddToCartClick( { target: button }, 'event-id' )
		).not.toThrow();
		expect( window.snaptr ).not.toHaveBeenCalled();
	} );

	test( 'addToCartClick does not throw or send an event', () => {
		const event = {
			currentTarget: { dataset: { product_id: '10', quantity: '1' } },
		};

		expect( () => addToCartClick( event, 'event-id' ) ).not.toThrow();
		expect( window.snaptr ).not.toHaveBeenCalled();
	} );
} );

describe( 'singleAddToCartClick with pixel data', () => {
	test( 'Sends ADD_CART with the product, quantity, price, and currency', () => {
		setTrackingData( {
			pixel_data: {
				currency: 'EUR',
				currency_minor_unit: 2,
				products: { 10: { price: 25 } },
			},
		} );
		window.snaptr = jest.fn();
		const button = createCartForm();

		singleAddToCartClick( { target: button }, 'event-id' );

		expect( window.snaptr ).toHaveBeenCalledWith(
			'track',
			'ADD_CART',
			expect.objectContaining( {
				item_ids: [ 10 ],
				number_items: 2,
				price: 25,
				currency: 'EUR',
				event_id: 'event-id',
				client_dedup_id: 'event-id',
			} )
		);
	} );
} );
