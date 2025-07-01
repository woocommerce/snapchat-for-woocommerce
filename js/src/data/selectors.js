/**
 * @typedef {Object} JetpackAccount
 * @property {'yes'|'no'} active Whether jetpack is connected.
 * @property {'yes'|'no'} owner Whether the current admin user is the jetpack owner.
 * @property {string|''} email Owner email. Available for jetpack owner.
 * @property {string|''} displayName Owner name. Available for jetpack owner.
 */

/**
 * Selector to retrieve the 'setup' property from the state.
 *
 * @param {Object} state - The Redux state object.
 * @return {*} The 'setup' property from the state.
 */
export const getSetup = ( state ) => {
	return state.setup;
};

/**
 * Select jetpack connection state.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 * @return {JetpackAccount|null} The jetpack connection state. It would return `null` before the data is fetched.
 */
export const getJetpackAccount = ( state ) => {
	return state.accounts.jetpack;
};

/**
 * Retrieves the list of existing Snapchat organizations.
 *
 * @param {Object} state - The Redux state object.
 * @return {Array|null} The array of existing Snapchat organizations, or null if not set.
 */
export const getExistingSnapchatOrganizations = ( state ) => {
	return state.accounts.existing_organizations;
};

/**
 * Retrieves the list of existing Snapchat ads accounts.
 *
 * @param {Object} state - The Redux state object.
 * @param {string} organizationId - The ID of the organization to filter by.
 * @return {Array|null} The array of existing Snapchat ads, or null if not set.
 */
export const getExistingSnapchatAdsAccounts = ( state, organizationId ) => {
	return state.accounts.existing_ads[ organizationId ];
};

/**
 * Retrieves the Snapchat Ads account information.
 *
 * @param {Object} state - The Redux state object.
 * @return {* | null} The Snapchat Ads account data from the state, or null if not set.
 */
export const getSnapchatAdsAccount = ( state ) => {
	return state.accounts.ads;
};

/**
 * Retrieves the Snapchat organization.
 *
 * @param {Object} state - The Redux state object.
 * @return {* | null} The organization associated with the Snapchat Ads account.
 */
export const getSnapchatOrganization = ( state ) => {
	return state.accounts.organization;
};

/**
 * Retrieves the Snapchat account information.
 * @param {Object} state - The Redux state object.
 * @return {* | null} The Snapchat account data from the state, or null if not set.
 */
export const getSnapchatAccount = ( state ) => {
	return state.accounts.snapchat;
};
