/**
 * External dependencies
 */
import { act } from 'react';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SnapchatAdsPromo from '~/meta-boxes/order-attribution/snapchat-ads-promo';
import useSnapchatAccountDetails from '~/hooks/useSnapchatAccountDetails';
import { recordSfwEvent } from '~/utils/tracks';

jest.mock( '@wordpress/components', () => ( {
	Flex: ( { children } ) => <div>{ children }</div>,
	FlexItem: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock( '~/components/app-button', () => ( { text, href } ) => (
	<a href={ href }>{ text }</a>
) );

jest.mock( '~/hooks/useSnapchatAccountDetails', () => jest.fn() );

jest.mock( '~/utils/tracks', () => ( {
	recordSfwEvent: jest.fn(),
	REFERRER_TYPE_IN_PRODUCT_PLACEMENT: 'in_product_placement',
	withReferrer: ( href, placementId ) =>
		`${ href }?referrer_type=in_product_placement&referrer_id=${ placementId }`,
} ) );

jest.mock( '~/utils/urls', () => ( {
	getOnboardingUrl: () => 'https://example.test/onboarding',
	getCreateCampaignUrl: ( adAccountId ) =>
		adAccountId
			? `https://ads.snapchat.com/${ adAccountId }/create-campaign`
			: '',
} ) );

global.IS_REACT_ACT_ENVIRONMENT = true;

let root;

const renderPromo = () => {
	const container = document.createElement( 'div' );
	root = createRoot( container );

	// eslint-disable-next-line testing-library/no-unnecessary-act -- `root.render` is React's, not Testing Library's.
	act( () => {
		root.render( <SnapchatAdsPromo /> );
	} );

	return container.innerHTML;
};

describe( 'SnapchatAdsPromo', () => {
	beforeEach( () => {
		useSnapchatAccountDetails.mockReturnValue( {} );
	} );

	afterEach( () => {
		act( () => root.unmount() );
		delete window.snapchatAdsMetaBoxData;
		jest.clearAllMocks();
	} );

	it( 'renders the get-started promo when onboarding is incomplete', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: false };

		const html = renderPromo();

		expect( html ).toContain( 'Your next customers are on Snapchat' );
		expect( html ).toContain( 'Get started' );
	} );

	it( 'renders the get-started promo when meta box data is absent', () => {
		const html = renderPromo();

		expect( html ).toContain( 'Your next customers are on Snapchat' );
	} );

	it( 'adds the referrer params to the get-started link', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: false };

		const html = renderPromo();

		expect( html ).toContain(
			'https://example.test/onboarding?referrer_type=in_product_placement&amp;referrer_id=order-attribution-meta-box'
		);
	} );

	it( 'renders nothing when onboarded with an active campaign', () => {
		window.snapchatAdsMetaBoxData = {
			onboardingComplete: true,
			hasCampaign: true,
		};
		useSnapchatAccountDetails.mockReturnValue( { ad_acc_id: 'abc123' } );

		expect( renderPromo() ).toBe( '' );
	} );

	it( 'renders the create-campaign promo when onboarded without a campaign', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: true };
		useSnapchatAccountDetails.mockReturnValue( { ad_acc_id: 'abc123' } );

		const html = renderPromo();

		expect( html ).toContain( 'Get more sales with Snapchat Ads' );
		expect( html ).toContain( 'Create campaign' );
		expect( html ).toContain(
			'https://ads.snapchat.com/abc123/create-campaign?referrer_type=in_product_placement&amp;referrer_id=order-attribution-meta-box'
		);
	} );

	it( 'renders nothing when onboarded without a campaign and no ad account', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: true };

		expect( renderPromo() ).toBe( '' );
	} );

	it( 'records the shown event once when a promo renders', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: false };

		renderPromo();

		// eslint-disable-next-line testing-library/no-unnecessary-act -- `root.render` is React's, not Testing Library's.
		act( () => {
			root.render( <SnapchatAdsPromo /> );
		} );

		expect( recordSfwEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordSfwEvent ).toHaveBeenCalledWith( 'sfw_ads_promo_shown', {
			context: 'order-attribution-meta-box',
		} );
	} );

	it( 'does not record the shown event when nothing renders', () => {
		window.snapchatAdsMetaBoxData = {
			onboardingComplete: true,
			hasCampaign: true,
		};

		renderPromo();

		expect( recordSfwEvent ).not.toHaveBeenCalled();
	} );
} );
