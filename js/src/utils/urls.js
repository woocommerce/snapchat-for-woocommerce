/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';

export const pagePaths = {
	getStarted: '/snapchat/start',
	onboarding: '/snapchat/setup',
	settings: '/snapchat/settings',
	dashboard: '/snapchat/dashboard',
};

/**
 * Generates the URL for the "Get Started" page.
 *
 * @return {string} The constructed URL for the Get Started page.
 */
export const getGetStartedUrl = () => {
	return getNewPath( null, pagePaths.getStarted, null );
};

/**
 * Returns the onboarding URL by generating a new path using the onboarding page path.
 *
 * @return {string} The generated onboarding URL.
 */
export const getOnboardingUrl = () => {
	return getNewPath( null, pagePaths.onboarding, null );
};

/**
 * Builds the Snapchat Ads Manager create-campaign URL for an ad account.
 *
 * @param {string} adAccountId The Snapchat ad account ID.
 * @return {string} The create-campaign URL, or an empty string when the ID is missing.
 */
export const getCreateCampaignUrl = ( adAccountId ) => {
	if ( ! adAccountId ) {
		return '';
	}

	return `https://ads.snapchat.com/${ adAccountId }/create-campaign`;
};

/**
 * Generates the dashboard URL with optional query parameters.
 *
 * @param {Object|null} query - Optional query parameters to append to the URL.
 * @return {string} The constructed dashboard URL.
 */
export const getDashboardUrl = ( query = null ) => {
	return getNewPath( query, pagePaths.dashboard, null );
};

/**
 * Returns the URL path for the settings page.
 *
 * @return {string} The constructed settings page URL.
 */
export const getSettingsUrl = () => {
	return getNewPath( null, pagePaths.settings, null );
};
