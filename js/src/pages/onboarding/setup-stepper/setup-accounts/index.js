/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AppButton from '~/components/app-button';
import AppSpinner from '~/components/app-spinner';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import StepContent from '~/components/stepper/step-content';
import WPComAccountCard from '~/components/wpcom-account-card';
import SnapchatAccountCard from '~/components/snapchat-account-card';
import StepContentHeader from '~/components/stepper/step-content-header';
import StepContentFooter from '~/components/stepper/step-content-footer';
import StepContentActions from '~/components/stepper/step-content-actions';
import './index.scss';

/**
 * When the merchant is onboarded.
 *
 * @event sfw_onboarding_completed
 */

/**
 * @fires sfw_onboarding_completed
 */
const SetupAccounts = ( props ) => {
	const { onContinue = noop } = props;
	const { jetpack } = useJetpackAccount();
	const {
		isConnected: isSnapchatConnected,
		hasFinishedResolution: hasResolvedSnapchatAccount,
	} = useSnapchatAccount();

	/**
	 * When jetpack is loading, or when Snapchat account is loading,
	 *  we display the AppSpinner.
	 *
	 * The account loading is in sequential manner, one after another.
	 * @todo add snapchat account loading state when available.
	 */
	const isLoadingJetpack = ! jetpack;
	const isJetpackActive = jetpack?.active === 'yes';

	if ( isLoadingJetpack || ! hasResolvedSnapchatAccount ) {
		return <AppSpinner />;
	}

	const handleOnClick = () => {
		onContinue();
	};

	const isContinueButtonDisabled = ! isJetpackActive || ! isSnapchatConnected;
	const isSubmitting = false;

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Set up your accounts',
					'snapchat-for-woocommerce'
				) }
				description={ __(
					'Connect the accounts required to use Snapchat integration.',
					'snapchat-for-woocommerce'
				) }
			/>
			<Section
				className="sfw-wp-snapchat-accounts-section"
				title={ __( 'Connect accounts', 'snapchat-for-woocommerce' ) }
				description={ __(
					'The following accounts are required to use the Snapchat plugin.',
					'snapchat-for-woocommerce'
				) }
			>
				<WPComAccountCard jetpack={ jetpack } />
				<SnapchatAccountCard disabled={ ! isJetpackActive } />
			</Section>

			<StepContentFooter>
				<StepContentActions>
					<AppButton
						isPrimary
						disabled={ isContinueButtonDisabled }
						loading={ isSubmitting }
						text={ __( 'Continue', 'snapchat-for-woocommerce' ) }
						onClick={ handleOnClick }
						eventName="sfw_onboarding_completed"
					/>
				</StepContentActions>
			</StepContentFooter>
		</StepContent>
	);
};

export default SetupAccounts;
