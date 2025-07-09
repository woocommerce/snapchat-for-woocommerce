/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './constants';
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
 * Fetches the Snapchat account information from the API and dispatches the result.
 *
 * @async
 * @function fetchSnapchatAccount
 * @param {Object} param0 - The function parameters.
 * @param {Function} param0.dispatch - The dispatch function to send actions.
 * @return {Promise<void>} Resolves when the account information has been fetched and dispatched.
 */
export async function fetchSnapchatAccount( { dispatch } ) {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/snapchat/connection`,
		} );

		console.log( 'Snapchat account response:', response );
		dispatch( receiveSnapchatAccount( response ) );
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
