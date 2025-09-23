/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import AppButton from '~/components/app-button';
import SpinnerCard from '~/components/spinner-card';
import Section from '~/components/section';
import { ConnectedSnapchatAccountCard } from '~/components/snapchat-account-card';
import DisconnectModal, { SNAPCHAT_ACCOUNT } from './disconnect-modal';
import { queueRecordSfwEvent } from '~/utils/tracks';

/**
 * Accounts are disconnected from the Setting page
 *
 * @event sfw_disconnected_accounts
 * @property {string} context (`all-accounts`|`snapchat-account`) - indicate which accounts have been disconnected.
 */

/**
 * When the "Disconnect Snapchat account" button is clicked.
 *
 * @event sfw_disconnect_snapchat_button_click
 */

/**
 * @fires sfw_disconnected_accounts
 * @fires sfw_disconnect_snapchat_button_click
 */
export default function LinkedAccounts() {
	const { hasFinishedResolution: hasResolvedSnapchatAccount } =
		useSnapchatAccount();

	const [ openedModal, setOpenedModal ] = useState( null );
	const openDisconnectAdsAccountModal = () =>
		setOpenedModal( SNAPCHAT_ACCOUNT );
	const dismissModal = () => setOpenedModal( null );

	const handleDisconnected = () => {
		queueRecordSfwEvent( 'sfw_disconnected_accounts', {
			context: openedModal,
		} );

		// Reload WC admin page to update the `sfwData` initiated from the static script.
		window.location.reload();
	};

	return (
		<>
			{ openedModal && (
				<DisconnectModal
					onRequestClose={ dismissModal }
					onDisconnected={ handleDisconnected }
					disconnectTarget={ openedModal }
				/>
			) }

			{ ! hasResolvedSnapchatAccount && <SpinnerCard /> }

			{ hasResolvedSnapchatAccount && (
				<ConnectedSnapchatAccountCard hideAccountSwitch>
					<Section.Card.Footer>
						<AppButton
							isDestructive
							isLink
							onClick={ openDisconnectAdsAccountModal }
							eventName="sfw_disconnect_snapchat_button_click"
						>
							{ __(
								'Disconnect Snapchat account',
								'snapchat-for-woocommerce'
							) }
						</AppButton>
					</Section.Card.Footer>
				</ConnectedSnapchatAccountCard>
			) }
		</>
	);
}
