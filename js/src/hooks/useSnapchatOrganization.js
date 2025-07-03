/**
 * Internal dependencies
 */
import { SNAPCHAT_ORGANIZATION_ACCOUNT_STATUS } from '~/constants';

const useSnapchatOrganization = () => {
	const status = SNAPCHAT_ORGANIZATION_ACCOUNT_STATUS.CONNECTED;

	return {
		snapchatOrganization: {
			id: '123-456-7890', // Example ID, replace with actual data fetching logic
			name: 'Snapchat Organization',
		},
		isReady: status === SNAPCHAT_ORGANIZATION_ACCOUNT_STATUS.CONNECTED,
		hasFinishedResolution: true,
	};
};

export default useSnapchatOrganization;
