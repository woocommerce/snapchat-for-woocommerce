/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import ConnectedSnapchatAccountCard from './connected-snapchat-account-card';
import ConnectSnapchatAccountCard from './connect-snapchat-account-card';

const SnapchatAccountCard = ( { disabled = false } ) => {
	const { isConnected } = useSnapchatAccount();
	const { config_id: configId } = getQuery();

	if ( isConnected ) {
		return <ConnectedSnapchatAccountCard />;
	}

	return (
		<ConnectSnapchatAccountCard
			disabled={ disabled }
			configId={ configId }
		/>
	);
};

export default SnapchatAccountCard;
