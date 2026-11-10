/**
 * Internal dependencies
 */
import { TRACKING_DATA_VAR } from './constants';
import { onFoundVariation } from './utils';

jest.mock( './constants', () => ( { TRACKING_DATA_VAR: {} } ) );

const setTrackingData = ( data ) => {
	Object.keys( TRACKING_DATA_VAR ).forEach(
		( key ) => delete TRACKING_DATA_VAR[ key ]
	);
	Object.assign( TRACKING_DATA_VAR, data );
};

const variation = { variation_id: 11, display_price: 25 };

describe( 'onFoundVariation', () => {
	beforeEach( () => {
		// `wp_localize_script` prints booleans as '1' and ''.
		setTrackingData( {
			is_pixel_enabled: '1',
			pixel_data: { products: {} },
		} );
	} );

	afterEach( () => {
		delete window.wp_has_consent;
	} );

	test( 'Records nothing when the pixel is off', () => {
		TRACKING_DATA_VAR.is_pixel_enabled = '';

		onFoundVariation( variation );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toBeUndefined();
	} );

	test( 'Records nothing when the consent tool reports no marketing consent', () => {
		window.wp_has_consent = jest.fn( () => false );

		onFoundVariation( variation );

		expect( window.wp_has_consent ).toHaveBeenCalledWith( 'marketing' );
		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toBeUndefined();
	} );

	test( 'Records the variation price when no consent tool is installed', () => {
		onFoundVariation( variation );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
		} );
	} );

	test( 'Records the variation price when the consent tool reports consent', () => {
		window.wp_has_consent = jest.fn( () => true );

		onFoundVariation( variation );

		expect( TRACKING_DATA_VAR.pixel_data.products[ 11 ] ).toEqual( {
			price: 25,
		} );
	} );

	test( 'Does nothing when the page has no pixel data', () => {
		delete TRACKING_DATA_VAR.pixel_data;

		expect( () => onFoundVariation( variation ) ).not.toThrow();
		expect( TRACKING_DATA_VAR.pixel_data ).toBeUndefined();
	} );

	test( 'Logs instead of throwing when recording the variation fails', () => {
		const pixelData = {};
		Object.defineProperty( pixelData, 'products', {
			get() {
				throw new Error( 'Unexpected tracking data' );
			},
		} );
		TRACKING_DATA_VAR.pixel_data = pixelData;

		expect( () => onFoundVariation( variation ) ).not.toThrow();
		expect( console ).toHaveErrored();
	} );
} );
