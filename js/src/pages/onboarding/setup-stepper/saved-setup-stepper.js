/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SetupAccounts from './setup-accounts';
import SetupPaidAds from './setup-paid-ads';
import stepNameKeyMap from './stepNameKeyMap';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
} from '~/utils/tracks';

/**
 * @param {Object} props React props
 * @param {string} [props.savedStep] A saved step overriding the current step
 */
const SavedSetupStepper = ( { savedStep } ) => {
	const [ step, setStep ] = useState( savedStep );

	/**
	 * Handles "onContinue" callback to set the current step and record event tracking.
	 *
	 * @param {string} to The next step to go to.
	 */
	const continueStep = ( to ) => {
		const from = step;

		recordStepContinueEvent( 'sfw_setup_mc', from, to );
		setStep( to );
	};

	const handleSetupAccountsContinue = () => {
		continueStep( stepNameKeyMap.paid_ads );
	};

	const handleStepClick = ( stepKey ) => {
		// Only allow going back to the previous steps.
		if ( Number( stepKey ) < Number( step ) ) {
			recordStepperChangeEvent( 'sfw_setup_mc', stepKey );
			setStep( stepKey );
		}
	};

	return (
		<Stepper
			className="sfw-setup-stepper"
			currentStep={ step }
			steps={ [
				{
					key: stepNameKeyMap.accounts,
					label: __( 'Set up your accounts', 'snapchat-for-woo' ),
					content: (
						<SetupAccounts
							onContinue={ handleSetupAccountsContinue }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: stepNameKeyMap.paid_ads,
					label: __( 'Create a campaign', 'snapchat-for-woo' ),
					content: <SetupPaidAds />,
					onClick: handleStepClick,
				},
			] }
		/>
	);
};

export default SavedSetupStepper;
