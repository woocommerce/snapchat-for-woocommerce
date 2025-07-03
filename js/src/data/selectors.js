/**
 * @typedef {Object} JetpackAccount
 * @property {'yes'|'no'} active Whether jetpack is connected.
 * @property {'yes'|'no'} owner Whether the current admin user is the jetpack owner.
 * @property {string|''} email Owner email. Available for jetpack owner.
 * @property {string|''} displayName Owner name. Available for jetpack owner.
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
