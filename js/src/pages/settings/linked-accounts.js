/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getGetStartedUrl } from '~/utils/urls';
import useAdminUrl from '~/hooks/useAdminUrl';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import useSnapchatAdsAccount from '~/hooks/useSnapchatAdsAccount';
import useSnapchatOrganization from '~/hooks/useSnapchatOrganization';
import AppButton from '~/components/app-button';
import SpinnerCard from '~/components/spinner-card';
import { ConnectedWPComAccountCard } from '~/components/wpcom-account-card';
import { ConnectedSnapchatAccountCard } from '~/components/snapchat-account-card';
import LinkedAccountsSectionWrapper from './linked-accounts-section-wrapper';
import DisconnectModal, { ALL_ACCOUNTS } from './disconnect-modal';

/**
 * Accounts are disconnected from the Setting page
 *
 * @event sfw_disconnected_accounts
 * @property {string} context (`all-accounts`|`ads-account`) - indicate which accounts have been disconnected.
 */

/**
 * @fires sfw_disconnected_accounts
 */
export default function LinkedAccounts() {
	const adminUrl = useAdminUrl();
	const { jetpack } = useJetpackAccount();
	const { snapchat } = useSnapchatAccount();
	const { snapchatAdsAccount } = useSnapchatAdsAccount();
	const { snapchatOrganization } = useSnapchatOrganization();

	const isLoading = ! (
		jetpack &&
		snapchat &&
		snapchatAdsAccount &&
		snapchatOrganization
	);

	const [ openedModal, setOpenedModal ] = useState( null );
	const openDisconnectAllAccountsModal = () => setOpenedModal( ALL_ACCOUNTS );
	const dismissModal = () => setOpenedModal( null );

	const handleDisconnected = () => {
		// Reload WC admin page to update the `sfwData` initiated from the static script.
		const nextPage =
			openedModal === ALL_ACCOUNTS
				? adminUrl + getGetStartedUrl()
				: window.location.href;

		window.location.href = nextPage;
	};

	return (
		<LinkedAccountsSectionWrapper>
			{ openedModal && (
				<DisconnectModal
					onRequestClose={ dismissModal }
					onDisconnected={ handleDisconnected }
					disconnectTarget={ openedModal }
				/>
			) }
			{ isLoading ? (
				<SpinnerCard />
			) : (
				<>
					<ConnectedWPComAccountCard jetpack={ jetpack } />
					<ConnectedSnapchatAccountCard
						snapchatAccount={ snapchat }
					/>

					<Flex justify="flex-end">
						<AppButton
							isPrimary
							isDestructive
							onClick={ openDisconnectAllAccountsModal }
						>
							{ __(
								'Disconnect from all accounts',
								'snapchat-for-woo'
							) }
						</AppButton>
					</Flex>
				</>
			) }
		</LinkedAccountsSectionWrapper>
	);
}
