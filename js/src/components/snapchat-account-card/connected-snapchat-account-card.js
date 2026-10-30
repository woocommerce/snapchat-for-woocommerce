/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

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
		return (
			<SwitchAccountButton
				isTertiary
				disabledInSandboxMode
				disableInSandboxModeLabel={ __(
					'Connecting a different account is disabled while sandbox mode is active.',
					'snapchat-for-woocommerce'
				) }
			/>
		);
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
