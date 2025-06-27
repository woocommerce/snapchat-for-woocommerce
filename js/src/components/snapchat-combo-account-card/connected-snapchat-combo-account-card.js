/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectAds from './connect-ads';
import ConnectOrganization from './connect-organization';
import AccountDetails from './account-details';
import Indicator from './indicator';
import SpinnerCard from '~/components/spinner-card';
import useExistingSnapchatAdsAccounts from '~/hooks/useExistingSnapchatAdsAccounts';
import useExistingSnapchatOrganizations from '~/hooks/useExistingSnapchatOrganizations';
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import useSnapchatAdsAccount from '~/hooks/useSnapchatAdsAccount';
import useSnapchatOrganization from '~/hooks/useSnapchatOrganization';
import AppButton from '~/components/app-button';
import {
	SNAPCHAT_ADS_ACCOUNT_STATUS,
	SNAPCHAT_ORGANIZATION_ACCOUNT_STATUS,
} from '~/constants';
import { SwitchAccountButton } from '~/components/snapchat-account-card';
import './connected-snapchat-combo-account-card.scss';

/**
 * Renders a Snapchat account card UI with connected account information.
 */
const ConnectedSnapchatComboAccountCard = () => {
	const [ editMode, setEditMode ] = useState( false );
	const { snapchat } = useSnapchatAccount();
	const { status: snapchatAdsAccountStatus } = useSnapchatAdsAccount();
	const { status: snapchatOrganizationStatus } = useSnapchatOrganization();
	const { data: existingSnapchatAdsAccounts } =
		useExistingSnapchatAdsAccounts();
	const { data: existingSnapchatOrganizations } =
		useExistingSnapchatOrganizations();

	const { invalidateResolution } = useAppDispatch();

	const hasExistingSnapchatAdsAccounts =
		existingSnapchatAdsAccounts?.length > 0;
	const hasExistingSnapchatOrganizations =
		existingSnapchatOrganizations?.length > 0;

	const handleCancelClick = () => {
		setEditMode( false );
	};

	const handleEditClick = () => {
		setEditMode( true );
	};

	const hasSnapchatAdsConnection =
		snapchatAdsAccountStatus === SNAPCHAT_ADS_ACCOUNT_STATUS.CONNECTED;
	const hasSnapchatOrganizationConnection =
		snapchatOrganizationStatus ===
		SNAPCHAT_ORGANIZATION_ACCOUNT_STATUS.CONNECTED;

	const canShowConnectOrganization =
		hasExistingSnapchatOrganizations || hasSnapchatOrganizationConnection;
	const showConnectOrganization =
		canShowConnectOrganization &&
		( editMode || ! hasSnapchatOrganizationConnection );

	const canShowConnectAds =
		hasSnapchatAdsConnection || hasExistingSnapchatAdsAccounts;
	const showConnectAds =
		canShowConnectAds && ( editMode || ! hasSnapchatAdsConnection );

	// When Ads and Org are disconnected in edit mode, exit edit mode.
	useEffect( () => {
		if (
			editMode &&
			! hasSnapchatAdsConnection &&
			! hasSnapchatOrganizationConnection
		) {
			setEditMode( false );
		}
	}, [
		editMode,
		hasSnapchatAdsConnection,
		hasSnapchatOrganizationConnection,
	] );

	const switchAccountButton = (
		<SwitchAccountButton
			isTertiary
			text={ __(
				'Or, connect to a different Snapchat account',
				'snapchat-for-woo'
			) }
		/>
	);

	const getCardActions = () => {
		if ( editMode ) {
			return (
				<div className="sfw-snapchat-combo-account-card__description-actions">
					{ switchAccountButton }
					<AppButton isTertiary onClick={ handleCancelClick }>
						{ __( 'Cancel', 'snapchat-for-woo' ) }
					</AppButton>
				</div>
			);
		}

		// When not in edit mode, only show the edit button if clicking the
		// button would change the visibility of the ConnectAds or ConnectOrganization cards.
		return (
			<div className="sfw-snapchat-combo-account-card__description-actions">
				{ ( showConnectAds || ! canShowConnectAds ) &&
				( showConnectOrganization || ! canShowConnectOrganization ) ? (
					switchAccountButton
				) : (
					<AppButton
						isTertiary
						text={ __( 'Edit', 'snapchat-for-woo' ) }
						onClick={ handleEditClick }
					/>
				) }
			</div>
		);
	};

	// Show the spinner if there's an account creation in progress and account should not be claimed.
	// If we are not showing the ConnectMC screen, for e.g when we are creating the first account,
	// then show the spinner in the Google combo card while the Ads account is being claimed.
	// const showSpinner =
	// 	( Boolean( creatingWhich ) && ! shouldClaimGoogleAdsAccount ) ||
	// 	( ! showConnectAds && finalizeAdsAccountCreation );
	const showSpinner = false;

	return (
		<div className="sfw-snapchat-combo-account-card-wrapper">
			<AccountCard
				appearance={ APPEARANCE.SNAPCHAT }
				alignIcon="top"
				className="sfw-snapchat-combo-account-card sfw-snapchat-combo-account-card--connected sfw-snapchat-combo-service-account-card--snapchat"
				description={ <AccountDetails /> }
				actions={ getCardActions() }
				indicator={ <Indicator showSpinner={ showSpinner } /> }
				expandedDetail
			/>

			{ showConnectOrganization && <ConnectOrganization /> }

			{ showConnectAds && <ConnectAds /> }
		</div>
	);
};

export default ConnectedSnapchatComboAccountCard;
