/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppSpinner from '~/components/app-spinner';
import StepContent from '~/components/stepper/step-content';
import StepContentHeader from '~/components/stepper/step-content-header';
import StepContentFooter from '~/components/stepper/step-content-footer';
import StepContentActions from '~/components/stepper/step-content-actions';
import Section from '~/components/section';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import WPComAccountCard from '~/components/wpcom-account-card';
import SnapchatComboAccountCard from '~/components/snapchat-combo-account-card';
import './index.scss';

const SetupAccounts = ( props ) => {
	const { onContinue = () => {} } = props;
	const { jetpack } = useJetpackAccount();

	/**
	 * When jetpack is loading, or when Snapchat account is loading,
	 *  we display the AppSpinner.
	 *
	 * The account loading is in sequential manner, one after another.
	 * @todo add snapchat account loading state when available.
	 */
	const isLoadingJetpack = ! jetpack;
	const isJetpackActive = jetpack?.active === 'yes';

	if ( isLoadingJetpack ) {
		return <AppSpinner />;
	}

	const handleSubmitCallback = noop;
	const isContinueButtonDisabled = ! isJetpackActive;
	const isSubmitting = false;

	return (
		<StepContent>
			<StepContentHeader
				title={ __( 'Set up your accounts', 'snapchat-for-woo' ) }
				description={ __(
					'Connect the accounts required to use Snapchat integration.',
					'snapchat-for-woo'
				) }
			/>
			<Section
				className="sfw-wp-snapchat-accounts-section"
				title={ __( 'Connect accounts', 'snapchat-for-woo' ) }
				description={ __(
					'The following accounts are required to use the Snapchat plugin.',
					'snapchat-for-woo'
				) }
			>
				<WPComAccountCard jetpack={ jetpack } />
				<SnapchatComboAccountCard disabled={ ! isJetpackActive } />
			</Section>

			<StepContentFooter>
				<StepContentActions>
					<AppButton
						isPrimary
						disabled={ isContinueButtonDisabled }
						loading={ isSubmitting }
						text={ __( 'Continue', 'snapchat-for-woo' ) }
						onClick={ handleSubmitCallback }
					/>
				</StepContentActions>
			</StepContentFooter>
		</StepContent>
	);
};

export default SetupAccounts;
