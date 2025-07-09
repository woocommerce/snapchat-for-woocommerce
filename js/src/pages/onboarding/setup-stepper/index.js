/**
 * Internal dependencies
 */
import { STEP_NAME_KEY_MAP } from './constants';
import AppSpinner from '~/components/app-spinner';
import SavedSetupStepper from './saved-setup-stepper';
import useSetup from '~/hooks/useSetup';

const SetupStepper = () => {
	const { hasFinishedResolution, data: sfwSetup } = useSetup();

	if ( ! hasFinishedResolution && ! sfwSetup ) {
		return <AppSpinner />;
	}

	if ( hasFinishedResolution && ! sfwSetup ) {
		// this means error occurred, we just need to return null here,
		// wp-data actions will display an error snackbar at the bottom of the page.
		return null;
	}

	const { step } = sfwSetup;

	return <SavedSetupStepper savedStep={ STEP_NAME_KEY_MAP[ step ] } />;
};

export default SetupStepper;
