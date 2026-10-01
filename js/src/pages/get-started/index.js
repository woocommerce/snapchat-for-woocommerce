/**
 * External dependencies
 */
import { getHistory } from '@woocommerce/navigation';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { getReferrerQueryParams } from '~/utils/tracks';
import { getOnboardingUrl, getSettingsUrl } from '~/utils/urls';

const GetStarted = () => {
	const onboardingUrl = getOnboardingUrl();
	const settingsUrl = getSettingsUrl();

	const redirectUrl = sfwData.setupComplete ? settingsUrl : onboardingUrl;
	getHistory().replace(
		addQueryArgs( redirectUrl, getReferrerQueryParams() )
	);
	return null;
};

export default GetStarted;
