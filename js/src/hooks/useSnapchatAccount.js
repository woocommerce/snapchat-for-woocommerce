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
