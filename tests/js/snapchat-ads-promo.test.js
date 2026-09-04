/**
 * External dependencies
 */
import { renderToString } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SnapchatAdsPromo from '~/meta-boxes/order-attribution/snapchat-ads-promo';

jest.mock( '~/components/app-button', () => ( { text } ) => (
	<button>{ text }</button>
) );

jest.mock( '~/utils/urls', () => ( {
	getOnboardingUrl: () => 'https://example.test/onboarding',
} ) );

describe( 'SnapchatAdsPromo', () => {
	afterEach( () => {
		delete window.snapchatAdsMetaBoxData;
	} );

	it( 'renders nothing when onboarding is complete', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: true };

		expect( renderToString( <SnapchatAdsPromo /> ) ).toBe( '' );
	} );

	it( 'renders the promo when onboarding is incomplete', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: false };

		const html = renderToString( <SnapchatAdsPromo /> );

		expect( html ).toContain( 'Your next customers are on Snapchat' );
		expect( html ).toContain( 'Get started' );
	} );

	it( 'renders the promo when meta box data is absent', () => {
		const html = renderToString( <SnapchatAdsPromo /> );

		expect( html ).toContain( 'Your next customers are on Snapchat' );
	} );
} );
