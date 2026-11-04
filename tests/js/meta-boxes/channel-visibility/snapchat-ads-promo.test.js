/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SnapchatAdsPromo from '~/meta-boxes/channel-visibility/snapchat-ads-promo';
import usePreference from '~/hooks/usePreference';
import { recordSfwEvent } from '~/utils/tracks';

jest.mock( '~/hooks/usePreference', () => jest.fn() );
jest.mock( '~/utils/tracks', () => ( { recordSfwEvent: jest.fn() } ) );
jest.mock(
	'~/meta-boxes/channel-visibility/channel-visibility-settings',
	() => () => <div>Sync settings</div>
);
jest.mock( '~/meta-boxes/channel-visibility/get-started-cta', () => () => (
	<button>Get started</button>
) );
jest.mock( '~/meta-boxes/channel-visibility/promo-cta', () => () => (
	<button>Promo CTA</button>
) );

describe( 'Channel visibility SnapchatAdsPromo', () => {
	afterEach( () => {
		delete window.snapchatAdsMetaBoxData;
		jest.clearAllMocks();
	} );

	test( 'Records is_dismissed false for the full promo', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: '' };
		usePreference.mockReturnValue( undefined );

		render( <SnapchatAdsPromo /> );

		expect( recordSfwEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordSfwEvent ).toHaveBeenCalledWith( 'sfw_ads_promo_shown', {
			context: 'channel-visibility-meta-box',
			has_onboarded: false,
			is_dismissed: false,
		} );
	} );

	test( 'Records is_dismissed true from the first event when dismissed on an earlier visit', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: '' };
		usePreference.mockReturnValue( true );

		render( <SnapchatAdsPromo /> );

		expect( recordSfwEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordSfwEvent ).toHaveBeenCalledWith( 'sfw_ads_promo_shown', {
			context: 'channel-visibility-meta-box',
			has_onboarded: false,
			is_dismissed: true,
		} );
	} );

	test( 'Does not record a second event when the promo is dismissed during the same page load', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: '' };
		usePreference.mockReturnValue( undefined );

		const { rerender } = render( <SnapchatAdsPromo /> );

		usePreference.mockReturnValue( true );
		rerender( <SnapchatAdsPromo /> );

		expect( recordSfwEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordSfwEvent ).toHaveBeenCalledWith(
			'sfw_ads_promo_shown',
			expect.objectContaining( { is_dismissed: false } )
		);
	} );

	test( 'Does not record the shown event once onboarded', () => {
		window.snapchatAdsMetaBoxData = { onboardingComplete: '1' };
		usePreference.mockReturnValue( undefined );

		render( <SnapchatAdsPromo /> );

		expect( recordSfwEvent ).not.toHaveBeenCalled();
	} );
} );
