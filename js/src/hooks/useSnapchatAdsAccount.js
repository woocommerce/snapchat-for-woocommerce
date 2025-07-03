/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { STORE_KEY } from '~/data/constants';
import { SNAPCHAT_ADS_ACCOUNT_STATUS } from '~/constants';

const selectorName = 'getSnapchatAdsAccount';

const useSnapchatAdsAccount = () => {
	const dispatcher = useAppDispatch();
	const refetchSnapchatAdsAccount = useCallback( () => {
		dispatcher.invalidateResolution( selectorName, [] );
	}, [ dispatcher ] );

	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const account = selector[ selectorName ]();

			return {
				id: account?.id,
				name: account?.name,
				status: account?.status,
				isConnected:
					account?.status === SNAPCHAT_ADS_ACCOUNT_STATUS.CONNECTED,
				refetchSnapchatAdsAccount,
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ refetchSnapchatAdsAccount ]
	);
};

export default useSnapchatAdsAccount;
