/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { API_RESPONSE_CODES } from '~/constants';

export const pagePaths = {
	getStarted: '/snapchat/start',
	onboarding: '/snapchat/setup',
	settings: '/snapchat/settings',
	dashboard: '/snapchat/dashboard',
};

const getStartedPath = pagePaths.getStarted;
const onboardingPath = pagePaths.onboarding;
const settingsPath = pagePaths.settings;
const dashboardPath = pagePaths.dashboard;

export const getGetStartedUrl = () => {
	return getNewPath( null, getStartedPath, null );
};

export const getOnboardingUrl = () => {
	return getNewPath( null, onboardingPath, null );
};

export const getDashboardUrl = ( query = null ) => {
	return getNewPath( query, dashboardPath, null );
};

export const getSettingsUrl = () => {
	return getNewPath( null, settingsPath, null );
};
