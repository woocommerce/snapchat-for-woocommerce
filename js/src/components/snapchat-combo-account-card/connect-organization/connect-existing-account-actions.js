/**
 * Internal dependencies
 */
import DisconnectAccountButton from './disconnect-account-button';
import useExistingSnapchatOrganizations from '~/hooks/useExistingSnapchatOrganizations';

const ConnectExistingAccountActions = ( { isConnected, onDisconnected } ) => {
	const { data: existingAccounts } = useExistingSnapchatOrganizations();

	if ( isConnected && existingAccounts.length > 0 ) {
		return <DisconnectAccountButton onDisconnected={ onDisconnected } />;
	}
};

export default ConnectExistingAccountActions;
