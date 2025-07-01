/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './constants';
import { handleApiError } from '~/utils/handleError';
import {
	receiveJetpackAccount,
	receiveSnapchatAdsAccount,
	receiveSnapchatOrganization,
	fetchSnapchatAccount,
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
 * Fetches the Snapchat Ads account information from the API.
 *
 * @return {Function} An async thunk function that takes a Redux-like dispatch object.
 */
export function getSnapchatAdsAccount() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/snapchat/ads_account`,
			} );
			dispatch( receiveSnapchatAdsAccount( response ) );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error loading Snapchat Ads account info.',
					'snapchat-for-woo'
				)
			);
		}
	};
}

/**
 * Fetches the Snapchat Organization information from the API.
 *
 * @return {Function} An async thunk function that takes a Redux-like dispatch object.
 */
export function getSnapchatOrganization() {
	return async function ( { dispatch } ) {
		try {
			const response = await apiFetch( {
				path: `${ API_NAMESPACE }/snapchat/organization`,
			} );
			dispatch( receiveSnapchatOrganization( response ) );
		} catch ( error ) {
			handleApiError(
				error,
				__(
					'There was an error loading Snapchat Organization info.',
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
