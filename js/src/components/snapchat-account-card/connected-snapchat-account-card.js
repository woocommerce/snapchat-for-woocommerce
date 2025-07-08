/**
 * Internal dependencies
 */
import AccountDetails from './account-details';
import SwitchAccountButton from './switch-account-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';

const ConnectedSnapchatAccountCard = ( {
	hideAccountSwitch = false,
	children,
} ) => {
	const getCardActions = () => {
		if ( hideAccountSwitch ) {
			return null;
		}
		return <SwitchAccountButton isTertiary />;
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.SNAPCHAT }
			description={ <AccountDetails /> }
			indicator={ <ConnectedIconLabel /> }
			actions={ getCardActions() }
		>
			{ children }
		</AccountCard>
	);
};

export default ConnectedSnapchatAccountCard;
