/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { SNAPCHAT_ACCOUNT_STATUS, SETUP_STATUS } from '~/constants';
import useSetup from './useSetup';

const selectorName = 'getSnapchatAccount';

/**
 * @typedef {import('../data/selectors').SnapchatAccount} SnapchatAccountObject
 */

/**
 * @typedef {Object} SnapchatAccountState
 * @property {SnapchatAccountObject} status The status of the Snapchat account.
 * @property {boolean} isConnected Whether the Snapchat account is connected and setup is complete.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the Snapchat account data and its resolution status.
 * @return {SnapchatAccountState} The Snapchat account data and its state.
 */
const useSnapchatAccount = () => {
	const { data } = useSetup();
	const setupComplete = data?.status === SETUP_STATUS.CONNECTED;

	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const account = selector[ selectorName ]();

			return {
				status: account?.status,
				isConnected:
					account?.status === SNAPCHAT_ACCOUNT_STATUS.CONNECTED &&
					setupComplete,
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ setupComplete ]
	);
};

export default useSnapchatAccount;
