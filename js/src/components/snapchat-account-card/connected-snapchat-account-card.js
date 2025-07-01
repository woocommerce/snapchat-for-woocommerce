/**
 * Internal dependencies
 */
import getConnectedJetpackInfo from '~/utils/getConnectedJetpackInfo';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';

const ConnectedSnapchatAccountCard = ( { jetpack } ) => {
	return (
		<AccountCard
			appearance={ APPEARANCE.SNAPCHAT }
			description={ getConnectedJetpackInfo( jetpack ) }
			indicator={ <ConnectedIconLabel /> }
		/>
	);
};

export default ConnectedSnapchatAccountCard;
