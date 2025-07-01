/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';

const ConnectedSnapchatAccountCard = ( { email } ) => {
	return (
		<AccountCard
			appearance={ APPEARANCE.SNAPCHAT }
			description={ email }
			indicator={ <ConnectedIconLabel /> }
		/>
	);
};

export default ConnectedSnapchatAccountCard;
