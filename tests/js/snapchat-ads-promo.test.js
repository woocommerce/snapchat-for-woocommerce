/**
 * External dependencies
 */
import { renderToString } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SnapchatAdsPromo from '~/meta-boxes/order-attribution/snapchat-ads-promo';
import useSnapchatAccountDetails from '~/hooks/useSnapchatAccountDetails';

jest.mock( '~/hooks/useSnapchatAccountDetails', () => jest.fn( () => ( {} ) ) );

jest.mock( '@wordpress/components', () => ( {
	Flex: ( { children } ) => <div>{ children }</div>,
	FlexItem: ( { children } ) => <div>{ children }</div>,
	FlexBlock: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock( '~/components/app-button', () => ( { text, href } ) => (
	<a href={ href }>{ text }</a>
) );

jest.mock( '~/utils/urls', () => ( {
	getOnboardingUrl: () => 'https://example.test/onboarding',
	getCreateCampaignUrl: ( adAccountId ) =>
		adAccountId
			? `https://ads.snapchat.com/${ adAccountId }/create-campaign`
			: '',
} ) );

describe( 'SnapchatAdsPromo', () => {
	afterEach( () => {
		delete window.snapchatAdsMetaBoxData;
		useSnapchatAccountDetails.mockReset();
		useSnapchatAccountDetails.mockReturnValue( {} );
	} );

	it( 'renders the get-started promo when onboarding is incomplete', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: false };

		const html = renderToString( <SnapchatAdsPromo /> );

		expect( html ).toContain( 'Your next customers are on Snapchat' );
		expect( html ).toContain( 'Get started' );
	} );

	it( 'renders the get-started promo when meta box data is absent', () => {
		const html = renderToString( <SnapchatAdsPromo /> );

		expect( html ).toContain( 'Your next customers are on Snapchat' );
	} );

	it( 'renders nothing when onboarded with an active campaign', () => {
		window.snapchatAdsMetaBoxData = {
			onboardingComplete: true,
			hasCampaign: true,
		};

		expect( renderToString( <SnapchatAdsPromo /> ) ).toBe( '' );
	} );

	it( 'renders the create-campaign promo when onboarded without a campaign', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: true };
		useSnapchatAccountDetails.mockReturnValue( { ad_acc_id: 'abc123' } );

		const html = renderToString( <SnapchatAdsPromo /> );

		expect( html ).toContain( 'Get more sales with Snapchat Ads' );
		expect( html ).toContain( 'Create campaign' );
		expect( html ).toContain(
			'href="https://ads.snapchat.com/abc123/create-campaign"'
		);
	} );

	it( 'renders nothing when onboarded without a campaign and no ad account', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: true };

		expect( renderToString( <SnapchatAdsPromo /> ) ).toBe( '' );
	} );
} );
