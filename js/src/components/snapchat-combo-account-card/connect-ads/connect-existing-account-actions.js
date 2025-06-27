/**
 * Internal dependencies
 */
import DisconnectAccountButton from './disconnect-account-button';
import useExistingSnapchatAdsAccounts from '~/hooks/useExistingSnapchatAdsAccounts';

const ConnectExistingAccountActions = ( { isConnected, onDisconnected } ) => {
	const { data: existingAccounts } = useExistingSnapchatAdsAccounts();

	if ( isConnected && existingAccounts.length > 0 ) {
		return <DisconnectAccountButton onDisconnected={ onDisconnected } />;
	}
};

export default ConnectExistingAccountActions;
