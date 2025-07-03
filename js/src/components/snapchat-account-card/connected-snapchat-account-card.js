/**
 * Internal dependencies
 */
import { SNAPCHAT_DESCRIPTION } from './constants';
import SwitchAccountButton from './switch-account-button';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';

const ConnectedSnapchatAccountCard = ( {
	organizationName,
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
			description={ organizationName || SNAPCHAT_DESCRIPTION }
			indicator={ <ConnectedIconLabel /> }
			actions={ getCardActions() }
		>
			{ children }
		</AccountCard>
	);
};

export default ConnectedSnapchatAccountCard;
