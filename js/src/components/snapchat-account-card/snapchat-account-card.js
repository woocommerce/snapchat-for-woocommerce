/**
 * Internal dependencies
 */
import ConnectedSnapchatAccountCard from './connected-snapchat-account-card';
import ConnectSnapchatAccountCard from './connect-snapchat-account-card';

const SnapchatAccountCard = ( { disabled = false } ) => {
	// if ( jetpack.active === 'yes' ) {
	// 	return <ConnectedSnapchatAccountCard jetpack={ jetpack } />;
	// }

	return <ConnectSnapchatAccountCard disabled={ disabled } />;
};

export default SnapchatAccountCard;
