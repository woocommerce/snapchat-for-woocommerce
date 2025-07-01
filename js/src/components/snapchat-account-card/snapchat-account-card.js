/**
 * Internal dependencies
 */
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import ConnectedSnapchatAccountCard from './connected-snapchat-account-card';
import ConnectSnapchatAccountCard from './connect-snapchat-account-card';

const SnapchatAccountCard = ( { disabled = false } ) => {
	const { isConnected, email } = useSnapchatAccount();

	if ( isConnected ) {
		return <ConnectedSnapchatAccountCard email={ email } />;
	}

	return <ConnectSnapchatAccountCard disabled={ disabled } />;
};

export default SnapchatAccountCard;
