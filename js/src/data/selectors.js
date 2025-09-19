/**
 * @typedef {Object} JetpackAccount
 * @property {'yes'|'no'} active Whether jetpack is connected.
 * @property {'yes'|'no'} owner Whether the current admin user is the jetpack owner.
 * @property {string|''} email Owner email. Available for jetpack owner.
 * @property {string|''} displayName Owner name. Available for jetpack owner.
 */

/**
 * @typedef {Object} SnapchatAccount
 * @property {'connected'|'disconnected'} status The status of the Snapchat account.
 */

/**
 * @typedef {Object} General
 * @property {string} version The version of the Snapchat for WooCommerce plugin.
 */

/**
 * @typedef {Object} Setup
 * @property {'connected'|'disconnected'} status The setup status.
 * @property {string} step The current setup step.
 */

/**
 * @typedef {Object} SnapchatAccountDetails
 * @property {string} org_id The Snapchat organization ID.
 * @property {string} org_name The name of the Snapchat organization.
 * @property {string} ad_acc_id The Snapchat ad account ID.
 * @property {string} ad_acc_name The name of the Snapchat ad account.
 * @property {string} pixel_id The Snapchat pixel ID.
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
 * @return {SnapchatAccount | null} The Snapchat account data from the state, or null if not set.
 */
export const getSnapchatAccount = ( state ) => {
	return state.accounts.snapchat;
};

/**
 * Retrieves the general settings of the Snapchat for WooCommerce plugin.
 *
 * @param {Object} state - The Redux state object.
 * @return {General} The general settings object containing version and other properties.
 */
export const getGeneral = ( state ) => {
	return state.general;
};

/**
 * Retrieves the Snapchat account details from the state.
 *
 * @param {Object} state - The Redux state object.
 * @return {SnapchatAccountDetails|null} The Snapchat details or null if not set.
 */
export const getSnapchatAccountDetails = ( state ) => {
	return state.snapchat;
};

/**
 * Retrieves the settings state.
 *
 * @param {Object} state - The Redux state.
 * @return {{ capiEnabled: boolean, triggerExport: boolean, lastExportTimeStamp: string, exportFileUrl: string }} The settings object.
 */
export const getSettings = ( state ) => {
	return state.settings;
};
