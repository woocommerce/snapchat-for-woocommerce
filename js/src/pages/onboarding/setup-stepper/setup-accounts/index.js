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
import useSnapchatAccountDetails from '~/hooks/useSnapchatAccountDetails';
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
 * @property {boolean|null} has_active_campaign Whether the connected Snapchat ad account already has a campaign, or null if the check could not be completed.
 */

/**
 * @fires sfw_onboarding_completed with `{ has_active_campaign: true }`, `{ has_active_campaign: false }`, or `{ has_active_campaign: null }` when the campaign check could not be completed.
 */
const SetupAccounts = ( props ) => {
	const { onContinue = noop } = props;
	const { jetpack } = useJetpackAccount();
	const {
		isConnected: isSnapchatConnected,
		hasFinishedResolution: hasResolvedSnapchatAccount,
	} = useSnapchatAccount();
	const {
		has_active_campaign: hasActiveCampaign = null,
		hasFinishedResolution: hasResolvedAccountDetails,
	} = useSnapchatAccountDetails();

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

	// Account details are refetched after connecting. Wait for them so
	// `sfw_onboarding_completed` reports the real campaign state rather than
	// a placeholder.
	const isLoadingAccountDetails =
		isSnapchatConnected && ! hasResolvedAccountDetails;

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
						loading={ isLoadingAccountDetails }
						text={ __( 'Continue', 'snapchat-for-woocommerce' ) }
						onClick={ handleOnClick }
						eventName="sfw_onboarding_completed"
						eventProps={ {
							has_active_campaign: hasActiveCampaign,
						} }
					/>
				</StepContentActions>
			</StepContentFooter>
		</StepContent>
	);
};

export default SetupAccounts;
