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

export function receiveJetpackAccount( account ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_JETPACK,
		account,
	};
}

export function receiveSnapchatAdsAccount( snapchatAdsAccount ) {
	return {
		type: TYPES.RECEIVE_SNAPCHAT_ADS_ACCOUNT,
		snapchatAdsAccount,
	};
}

export function receiveSnapchatOrganization( snapchatOrganization ) {
	return {
		type: TYPES.RECEIVE_SNAPCHAT_ORGANIZATION,
		snapchatOrganization,
	};
}

export function receiveSnapchatAccount( snapchatAccount ) {
	return {
		type: TYPES.RECEIVE_SNAPCHAT_ACCOUNT,
		snapchatAccount,
	};
}

export async function fetchSnapchatAccount( { dispatch } ) {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/snapchat/connection`,
		} );

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
