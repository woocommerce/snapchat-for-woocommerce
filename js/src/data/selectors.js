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
 * Retrieves the Snapchat account information.
 * @param {Object} state - The Redux state object.
 * @return {* | null} The Snapchat account data from the state, or null if not set.
 */
export const getSnapchatAccount = ( state ) => {
	return state.accounts.snapchat;
};

/**
 * Retrieves the general settings of the Snapchat for WooCommerce plugin.
 *
 * @param {Object} state - The Redux state object.
 * @return {Object} The general settings object containing version and other properties.
 */
export const getGeneral = ( state ) => {
	return state.general;
};

/**
 * Retrieves the Snapchat account details from the state.
 *
 * @param {Object} state - The Redux state object.
 * @return {Object|null} The Snapchat details or null if not set.
 */
export const getSnapchatAccountDetails = ( state ) => {
	return state.snapchat;
};

/**
 * Retrieves the status of enhanced conversions.
 *
 * @param {Object} state - The Redux state object.
 * @return {boolean} The status of enhanced conversions, true if enabled, false otherwise or null if not set.
 */
export const getEnableEnhancedConversions = ( state ) => {
	return state.enhancedConversions;
};
