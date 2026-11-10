/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { addBaseEventProperties, withReferrer } from '~/utils/tracks';

jest.mock( '@woocommerce/navigation', () => ( {
	getQuery: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
	queueRecordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	select: () => ( {
		getGeneral: () => ( { version: '1.0.0', adAccountId: '' } ),
	} ),
} ) );

jest.mock( '~/constants', () => ( {
	sfwData: { slug: 'snapwoo', sandboxMode: false },
} ) );

jest.mock( '~/data', () => ( { STORE_KEY: 'test-store' } ) );

describe( 'withReferrer', () => {
	it( 'appends the in-product placement referrer params', () => {
		expect(
			withReferrer(
				'admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup',
				'order-attribution-meta-box'
			)
		).toBe(
			'admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup&referrer_type=in_product_placement&referrer_id=order-attribution-meta-box'
		);
	} );
} );

describe( 'addBaseEventProperties', () => {
	it( 'carries the referrer params over from the current URL', () => {
		getQuery.mockReturnValue( {
			path: '/snapchat/setup',
			referrer_type: 'in_product_placement',
			referrer_id: 'channel-visibility-meta-box',
		} );

		expect( addBaseEventProperties( { context: 'setup' } ) ).toEqual( {
			context: 'setup',
			referrer_type: 'in_product_placement',
			referrer_id: 'channel-visibility-meta-box',
			snapwoo_version: '1.0.0',
		} );
	} );

	it( 'keeps explicit referrer properties over the URL ones', () => {
		getQuery.mockReturnValue( {
			referrer_type: 'in_product_placement',
			referrer_id: 'channel-visibility-meta-box',
		} );

		expect(
			addBaseEventProperties( {
				referrer_type: 'in_product_placement',
				referrer_id: 'order-attribution-meta-box',
			} )
		).toMatchObject( { referrer_id: 'order-attribution-meta-box' } );
	} );

	it( 'omits the referrer properties when the URL has none', () => {
		getQuery.mockReturnValue( { path: '/snapchat/setup' } );

		expect( addBaseEventProperties( {} ) ).toEqual( {
			snapwoo_version: '1.0.0',
		} );
	} );
} );
