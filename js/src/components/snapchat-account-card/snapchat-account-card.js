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
	const {
		config_id: configId,
		products_token: productsToken,
		sfw_oauth_state: oauthState,
	} = getQuery();

	if ( isConnected ) {
		return <ConnectedSnapchatAccountCard />;
	}

	return (
		<ConnectSnapchatAccountCard
			disabled={ disabled }
			configId={ configId }
			productsToken={ productsToken }
			oauthState={ oauthState }
		/>
	);
};

export default SnapchatAccountCard;
