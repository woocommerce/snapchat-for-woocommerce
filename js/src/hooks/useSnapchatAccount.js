/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { SNAPCHAT_ACCOUNT_STATUS } from '~/constants';

const selectorName = 'getSnapchatAccount';

const useSnapchatAccount = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );
		const account = selector[ selectorName ]();

		return {
			status: account?.status,
			isConnected: account?.status === SNAPCHAT_ACCOUNT_STATUS.CONNECTED,
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useSnapchatAccount;
