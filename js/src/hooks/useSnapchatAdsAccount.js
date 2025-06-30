/**
 * Internal dependencies
 */
import { SNAPCHAT_ADS_ACCOUNT_STATUS } from '~/constants';

const useSnapchatAdsAccount = () => {
	const status = SNAPCHAT_ADS_ACCOUNT_STATUS.CONNECTED;

	return {
		snapchatAdsAccount: {
			id: '827-435-9912', // Example ID, replace with actual data fetching logic
			name: 'GSC Ads',
		},
		isReady: status === SNAPCHAT_ADS_ACCOUNT_STATUS.CONNECTED,
		refetchSnapchatAdsAccount: () => {
			// Logic to refetch the Snapchat Ads account data
			console.log( 'Refetching Snapchat Ads account data...' );
		},
		hasFinishedResolution: true,
	};
};

export default useSnapchatAdsAccount;
