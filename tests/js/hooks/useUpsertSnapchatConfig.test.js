/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useUpsertSnapchatConfig from '~/hooks/useUpsertSnapchatConfig';
import { getReferrerQueryParams } from '~/utils/tracks';

const ONBOARDING_URL = 'admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup';

jest.mock( '@woocommerce/navigation', () => {
	const replace = jest.fn();
	return { getHistory: () => ( { replace } ) };
} );
jest.mock( '~/data', () => ( {
	useAppDispatch: () => ( {
		fetchSnapchatAccount: jest.fn(),
		fetchSetup: jest.fn(),
		invalidateResolution: jest.fn(),
	} ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback', () => () => [ jest.fn() ] );
jest.mock( '~/hooks/useDispatchCoreNotices', () => () => ( {
	createNotice: jest.fn(),
} ) );
jest.mock( '~/utils/urls', () => ( {
	getOnboardingUrl: () => 'admin.php?page=wc-admin&path=%2Fsnapchat%2Fsetup',
} ) );
jest.mock( '~/utils/tracks', () => ( { getReferrerQueryParams: jest.fn() } ) );

/**
 * Runs the upsert and returns the URL the hook navigated to.
 *
 * @return {Promise<string>} The URL passed to `getHistory().replace()`.
 */
async function runUpsert() {
	const { result } = renderHook( () =>
		useUpsertSnapchatConfig( 'config-id', 'products-token' )
	);

	await act( () => result.current.upsertSnapchatConfig() );

	return getHistory().replace.mock.calls[ 0 ][ 0 ];
}

describe( 'useUpsertSnapchatConfig', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'keeps the placement referrer when cleaning up the URL', async () => {
		getReferrerQueryParams.mockReturnValue( {
			referrer_type: 'in_product_placement',
			referrer_id: 'channel-visibility-meta-box',
		} );

		expect( await runUpsert() ).toBe(
			`${ ONBOARDING_URL }&referrer_type=in_product_placement&referrer_id=channel-visibility-meta-box`
		);
	} );

	test( 'returns to the bare onboarding URL when the flow was not referred', async () => {
		getReferrerQueryParams.mockReturnValue( {} );

		expect( await runUpsert() ).toBe( ONBOARDING_URL );
	} );
} );
