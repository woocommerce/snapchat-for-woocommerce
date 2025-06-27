/**
 * Internal dependencies
 */
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import AppSpinner from '~/components/app-spinner';
import AccountCard from '~/components/account-card';
import ConnectSnapchatComboAccountCard from './connect-snapchat-combo-account-card';
import ConnectedSnapchatComboAccountCard from './connected-snapchat-combo-account-card';
import './index.scss';

/**
 * Renders a card to connect, request full access, or display a connected Snapchat account.
 *
 * Please note that this component is only used on the onboarding flow.
 *
 * @param {Object} props React props
 * @param {boolean} [props.disabled=false] Whether display the Card in disabled style.
 */
export default function SnapchatComboAccountCard( { disabled = false } ) {
	const { snapchat, hasFinishedResolution } = useSnapchatAccount();

	if ( ! hasFinishedResolution ) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const isConnected = snapchat?.active === 'yes';

	if ( isConnected ) {
		return <ConnectedSnapchatComboAccountCard />;
	}

	return <ConnectSnapchatComboAccountCard disabled={ disabled } />;
}
