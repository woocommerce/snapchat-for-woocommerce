/**
 * Internal dependencies
 */
import DisconnectAccountButton from './disconnect-account-button';
import useExistingSnapchatOrganizations from '~/hooks/useExistingSnapchatOrganizations';

const ConnectExistingAccountActions = ( { isConnected, onDisconnected } ) => {
	const { existingSnapchatOrganizations } =
		useExistingSnapchatOrganizations();

	if ( isConnected && existingSnapchatOrganizations.length > 0 ) {
		return <DisconnectAccountButton onDisconnected={ onDisconnected } />;
	}
};

export default ConnectExistingAccountActions;
