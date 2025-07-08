/**
 * External dependencies
 */
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { getOnboardingUrl, getSettingsUrl } from '~/utils/urls';

const GetStarted = () => {
	const onboardingUrl = getOnboardingUrl();
	const settingsUrl = getSettingsUrl();

	const redirectUrl = sfwData.setupComplete ? settingsUrl : onboardingUrl;
	getHistory().replace( redirectUrl );
	return null;
};

export default GetStarted;
