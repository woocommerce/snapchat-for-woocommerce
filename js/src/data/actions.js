/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { API_NAMESPACE, STORE_KEY } from './constants';
import { handleApiError } from '~/utils/handleError';
import TYPES from './action-types';

/**
 * Creates an action to receive a Jetpack account.
 *
 * @param {Object} account - The Jetpack account object to be received.
 * @return {Object} Action object with type `TYPES.RECEIVE_ACCOUNTS_JETPACK` and the account payload.
 */
export function receiveJetpackAccount( account ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_JETPACK,
		account,
	};
}

/**
 * Creates an action to receive a Snapchat account.
 *
 * @param {Object} snapchatAccount - The Snapchat account data to be received.
 * @return {Object} Action object with type RECEIVE_SNAPCHAT_ACCOUNT and the Snapchat account payload.
 */
export function receiveSnapchatAccount( snapchatAccount ) {
	return {
		type: TYPES.RECEIVE_SNAPCHAT_ACCOUNT,
		snapchatAccount,
	};
}

/**
 * Creates an action to receive the status of conversions tracking.
 *
 * @param {boolean} status - The status of conversions tracking, true if enabled, false otherwise.
 * @return {Object} Action object with type RECEIVE_TRACK_CONVERSIONS_STATUS.
 */
export function receiveTrackConversionsStatus( status ) {
	return {
		type: TYPES.RECEIVE_TRACK_CONVERSIONS_STATUS,
		status,
	};
}

/**
 * Creates an action to receive the setup data.
 *
 * @param {Object} setup - The setup data to be received.
 * @return {Object} Action object with type RECEIVE_SETUP and the setup data.
 */
export function receiveSetup( setup ) {
	return {
		type: TYPES.RECEIVE_SETUP,
		setup,
	};
}

/**
 * Creates an action to receive the settings data from the API.
 *
 * @param {Object} settings - Settings object, e.g., { trackConversions: boolean, triggerExport: boolean }.
 * @return {Object} Action object.
 */
export function receiveSettings( settings ) {
	return {
		type: TYPES.RECEIVE_SETTINGS,
		settings,
	};
}

/**
 * Creates an action to receive Snapchat account details.
 *
 * @param {Object} snapchatAccountDetails - The Snapchat account details to be received.
 * @return {Object} Action object with type RECEIVE_SNAPCHAT_ACCOUNT_DETAILS and the Snapchat account details.
 */
export function receiveSnapchatAccountDetails( snapchatAccountDetails ) {
	return {
		type: TYPES.RECEIVE_SNAPCHAT_ACCOUNT_DETAILS,
		snapchatAccountDetails,
	};
}

/**
 * Update the conversions tracking status.
 *
 * @param {boolean} status The status of the conversions tracking.
 * @return {Object} Action object to update the conversions tracking status.
 */
export async function updateTrackConversionsStatus( status ) {
	try {
		await apiFetch( {
			path: `${ API_NAMESPACE }/snapchat/settings`,
			method: 'POST',
			data: {
				capi_enabled: status,
			},
		} );

		return receiveTrackConversionsStatus( status );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error updating the conversions tracking status.',
				'snapchat-for-woo'
			)
		);
		throw error;
	}
}

/**
 * Updates one or more settings on the server.
 *
 * @param {Object} updatedSettings - Partial settings to update, e.g. { trackConversions: true }.
 * @return {Function} Action object to update settings locally.
 */
export async function updateSettings( updatedSettings ) {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/snapchat/settings`,
			method: 'POST',
			data: {
				// Convert settings keys to match REST keys
				capi_enabled: updatedSettings.trackConversions,
			},
		} );

		return receiveSettings( {
			trackConversions: Boolean( response.capi_enabled ),
		} );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error updating the settings.',
				'snapchat-for-woo'
			)
		);
		throw error;
	}
}

/**
 * Fetches the Snapchat account information from the API and dispatches the result.
 *
 * @function fetchSnapchatAccount
 * @return {Promise<void>} Resolves when the account information has been fetched and dispatched.
 */
export async function fetchSnapchatAccount() {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/snapchat/connection`,
		} );

		dispatch( STORE_KEY ).receiveSnapchatAccount( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading Snapchat account info.',
				'snapchat-for-woo'
			)
		);
	}
}

/**
 * Fetches the Snapchat setup information from the API and dispatches the result.
 *
 * @function fetchSetup
 * @return {Promise<void>} Resolves when the setup information has been fetched and dispatched.
 */
export async function fetchSetup() {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/snapchat/setup`,
		} );

		dispatch( STORE_KEY ).receiveSetup( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading Snapchat setup.',
				'snapchat-for-woo'
			)
		);
	}
}

/**
 * Disconnect the connected Snapchat account.
 *
 * @param {boolean} [invalidateRelatedState=false] Whether to invalidate related state in wp-data store.
 * @throws Will throw an error if the request failed.
 */
export async function disconnectSnapchatAccount(
	invalidateRelatedState = false
) {
	try {
		await apiFetch( {
			path: `${ API_NAMESPACE }/snapchat/connection`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DISCONNECT_ACCOUNTS_SNAPCHAT,
			invalidateRelatedState,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'Unable to disconnect your Snapchat account.',
				'snapchat-for-woo'
			)
		);
		throw error;
	}
}
