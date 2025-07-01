/**
 * Internal dependencies
 */
import TYPES from './action-types';

export function receiveJetpackAccount( account ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_JETPACK,
		account,
	};
}

export function receiveExistingSnapchatOrganizations( snapchatOrganizations ) {
	return {
		type: TYPES.RECEIVE_EXISTING_SNAPCHAT_ORGANIZATIONS,
		snapchatOrganizations,
	};
}

export function receiveExistingSnapchatAdsAccounts(
	snapchatAdsAccounts,
	organizationId
) {
	return {
		type: TYPES.RECEIVE_EXISTING_SNAPCHAT_ADS_ACCOUNTS,
		snapchatAdsAccounts,
		organizationId,
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
