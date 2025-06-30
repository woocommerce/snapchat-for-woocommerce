/**
 * Internal dependencies
 */
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import AppSpinner from '~/components/app-spinner';
import AccountCard from '~/components/account-card';
import ConnectedSnapchatAccountCard from './connected-snapchat-account-card';
import ConnectSnapchatAccountCard from './connect-snapchat-account-card';

/**
 * Renders a card to connect, request full access, or display a connected Snapchat account.
 *
 * Please note that this component is only used on the Reconnection page.
 * For the onboarding flow, the `SnapchatComboAccountCard` component is used instead.
 */
export default function SnapchatAccountCard() {
	const { snapchat, scope, hasFinishedResolution } = useSnapchatAccount();

	if ( ! hasFinishedResolution ) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const isConnected = snapchat?.active === 'yes';

	if ( isConnected && scope.reconnectionRequired ) {
		return <ConnectedSnapchatAccountCard snapchatAccount={ snapchat } />;
	}

	return <ConnectSnapchatAccountCard />;
}
