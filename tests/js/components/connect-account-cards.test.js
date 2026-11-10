/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ConnectWPComAccountCard from '~/components/wpcom-account-card/connect-wpcom-account-card';
import ConnectSnapchatAccountCard from '~/components/snapchat-account-card/connect-snapchat-account-card';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { getReferrerQueryParams } from '~/utils/tracks';

jest.mock( '~/constants', () => ( { sfwData: { setupComplete: false } } ) );
jest.mock( '~/hooks/useApiFetchCallback', () => jest.fn() );
jest.mock( '~/hooks/useDispatchCoreNotices', () => () => ( {
	createNotice: jest.fn(),
} ) );
jest.mock( '~/hooks/useUpsertSnapchatConfig', () => () => ( {
	upsertSnapchatConfig: jest.fn(),
	loading: false,
} ) );
jest.mock( '~/utils/tracks', () => ( { getReferrerQueryParams: jest.fn() } ) );
jest.mock( '~/components/account-card', () => ( {
	__esModule: true,
	default: () => null,
	APPEARANCE: {},
} ) );
jest.mock( '~/components/app-button', () => () => null );

const REFERRER = {
	referrer_type: 'in_product_placement',
	referrer_id: 'order-attribution-meta-box',
};

/**
 * Returns the REST path the card passed to `useApiFetchCallback`.
 *
 * @return {string} The connect request path.
 */
function getConnectPath() {
	return useApiFetchCallback.mock.calls[ 0 ][ 0 ].path;
}

describe.each( [
	[ 'ConnectWPComAccountCard', ConnectWPComAccountCard, '/jetpack/connect' ],
	[
		'ConnectSnapchatAccountCard',
		ConnectSnapchatAccountCard,
		'/snapchat/connect',
	],
] )( '%s', ( name, Card, endpoint ) => {
	beforeEach( () => {
		useApiFetchCallback.mockReturnValue( [ jest.fn(), {} ] );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'forwards the placement referrer to the connect request', () => {
		getReferrerQueryParams.mockReturnValue( REFERRER );
		render( <Card /> );

		const path = getConnectPath();
		expect( path ).toContain( endpoint );
		expect( path ).toContain( 'next_page_name=setup-snapchat' );
		expect( path ).toContain( 'referrer_type=in_product_placement' );
		expect( path ).toContain( 'referrer_id=order-attribution-meta-box' );
	} );

	test( 'sends no referrer params when the flow was not referred', () => {
		getReferrerQueryParams.mockReturnValue( {} );
		render( <Card /> );

		expect( getConnectPath() ).not.toContain( 'referrer_' );
	} );
} );
