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
import {
	fetchSetup,
	fetchSnapchatAccount,
	receiveJetpackAccount,
	receiveSnapchatAccountDetails,
	receiveTrackConversionsStatus,
} from './actions';

/**
 * Asynchronous thunk action creator to fetch Jetpack account connection status.
 *
 * Dispatches the received Jetpack account information to the store.
 * Handles API errors gracefully and displays a localized error message if needed.
 *
 * @return {Function} Thunk function that accepts Redux's dispatch.
 */
export function getJetpackAccount() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/jetpack/connected`,
			} );

			dispatch( receiveJetpackAccount( response ) );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error loading Jetpack account info.',
					'snapchat-for-woo'
				)
			);
		}
	};
}

/**
 * Retrieves the Snapchat account fetch function.
 *
 * @return {Function} The function to fetch the Snapchat account.
 */
export function getSnapchatAccount() {
	return fetchSnapchatAccount;
}

/**
 * Fetches the Snapchat account details information from the API.
 *
 * @return {Function} An async thunk function that takes a Redux-like dispatch object.
 */
export function getSnapchatAccountDetails() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/snapchat/account`,
			} );
			dispatch( receiveSnapchatAccountDetails( response ) );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error loading Snapchat account details info.',
					'snapchat-for-woo'
				)
			);
		}
	};
}

/**
 * Fetches the status of conversions tracking from the API.
 *
 * @return {Function} An async thunk function that takes a Redux-like dispatch object.
 */
export function getTrackConversions() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/snapchat/settings`,
			} );

			dispatch(
				receiveTrackConversionsStatus(
					Boolean( response.capi_enabled )
				)
			);
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error getting the conversions tracking status.',
					'snapchat-for-woo'
				)
			);
		}
	};
}

/**
 * Fetches the Snapchat setup information from the API and dispatches the result.
 *
 * @return {Function} An async thunk function that takes a Redux-like dispatch object.
 */
export function getSetup() {
	return fetchSetup;
}
