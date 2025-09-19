/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';
import { getHistory } from '@woocommerce/navigation';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { getSettingsUrl } from '~/utils/urls';
import { STEP_NAME_KEY_MAP } from './constants';
import { recordStepperChangeEvent } from '~/utils/tracks';
import SetupAccounts from './setup-accounts';

/**
 * @param {Object} props React props
 * @param {string} [props.savedStep] A saved step overriding the current step
 */
const SavedSetupStepper = ( { savedStep } ) => {
	const [ step, setStep ] = useState( savedStep );

	const handleSetupAccountsContinue = () => {
		const settingsUrl = getSettingsUrl();
		getHistory().push(
			addQueryArgs( settingsUrl, {
				onboarding: 'success',
			} )
		);
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
					key: STEP_NAME_KEY_MAP.accounts,
					label: __(
						'Set up your accounts',
						'snapchat-for-woocommerce'
					),
					content: (
						<SetupAccounts
							onContinue={ handleSetupAccountsContinue }
						/>
					),
					onClick: handleStepClick,
				},
			] }
		/>
	);
};

export default SavedSetupStepper;
