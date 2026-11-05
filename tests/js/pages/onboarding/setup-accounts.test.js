/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SetupAccounts from '~/pages/onboarding/setup-stepper/setup-accounts';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import useSnapchatAccountDetails from '~/hooks/useSnapchatAccountDetails';
import { recordSfwEvent } from '~/utils/tracks';

jest.mock( '@woocommerce/components', () => ( {
	Spinner: () => null,
} ) );
jest.mock( '~/hooks/useJetpackAccount', () => jest.fn() );
jest.mock( '~/hooks/useSnapchatAccount', () => jest.fn() );
jest.mock( '~/hooks/useSnapchatAccountDetails', () => jest.fn() );
jest.mock( '~/utils/tracks', () => ( { recordSfwEvent: jest.fn() } ) );
jest.mock( '~/components/wpcom-account-card', () => () => null );
jest.mock( '~/components/snapchat-account-card', () => () => null );

/**
 * Mocks the hooks for a merchant with Jetpack and Snapchat connected.
 *
 * @param {Object} accountDetails Return value for `useSnapchatAccountDetails()`.
 */
function mockConnected( accountDetails ) {
	useJetpackAccount.mockReturnValue( { jetpack: { active: 'yes' } } );
	useSnapchatAccount.mockReturnValue( {
		isConnected: true,
		hasFinishedResolution: true,
	} );
	useSnapchatAccountDetails.mockReturnValue( accountDetails );
}

const getContinueButton = () =>
	screen.getByRole( 'button', { name: /Continue/ } );

describe( 'SetupAccounts', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Continue stays disabled while account details are loading', () => {
		mockConnected( { hasFinishedResolution: false } );
		render( <SetupAccounts /> );

		expect( getContinueButton() ).toBeDisabled();
	} );

	test.each( [ true, false, null ] )(
		'sfw_onboarding_completed reports has_active_campaign: %p once account details have loaded',
		( hasActiveCampaign ) => {
			const onContinue = jest.fn();
			mockConnected( {
				has_active_campaign: hasActiveCampaign,
				hasFinishedResolution: true,
			} );
			render( <SetupAccounts onContinue={ onContinue } /> );

			fireEvent.click( getContinueButton() );

			expect( recordSfwEvent ).toHaveBeenCalledWith(
				'sfw_onboarding_completed',
				{ has_active_campaign: hasActiveCampaign }
			);
			expect( onContinue ).toHaveBeenCalled();
		}
	);

	test( 'has_active_campaign defaults to null when account details have no campaign state', () => {
		mockConnected( { hasFinishedResolution: true } );
		render( <SetupAccounts /> );

		fireEvent.click( getContinueButton() );

		expect( recordSfwEvent ).toHaveBeenCalledWith(
			'sfw_onboarding_completed',
			{ has_active_campaign: null }
		);
	} );
} );
