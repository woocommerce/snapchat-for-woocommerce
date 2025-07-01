/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectAds from './connect-ads';
import ConnectOrganization from './connect-organization';
import AccountDetails from './account-details';
import Indicator from './indicator';
import SpinnerCard from '~/components/spinner-card';
import useExistingSnapchatAdsAccounts from '~/hooks/useExistingSnapchatAdsAccounts';
import useExistingSnapchatOrganizations from '~/hooks/useExistingSnapchatOrganizations';
import useSnapchatAdsAccount from '~/hooks/useSnapchatAdsAccount';
import useSnapchatOrganization from '~/hooks/useSnapchatOrganization';
import AppButton from '~/components/app-button';
import { SwitchAccountButton } from '~/components/snapchat-account-card';
import './connected-snapchat-combo-account-card.scss';

/**
 * Renders a Snapchat account card UI with connected account information.
 */
const ConnectedSnapchatComboAccountCard = () => {
	const [ editMode, setEditMode ] = useState( false );
	const { id: organizationId, isConnected: isOrganizationConnected } =
		useSnapchatOrganization();
	const { isConnected: isAdsAccountConnected } = useSnapchatAdsAccount();
	const { existingSnapchatAdsAccounts } =
		useExistingSnapchatAdsAccounts( organizationId );
	const { existingSnapchatOrganizations } =
		useExistingSnapchatOrganizations();

	const handleCancelClick = () => {
		setEditMode( false );
	};

	const handleEditClick = () => {
		setEditMode( true );
	};

	const showConnectOrganization =
		editMode ||
		( existingSnapchatOrganizations?.length > 1 &&
			! isOrganizationConnected );
	const showConnectAds =
		editMode ||
		( existingSnapchatAdsAccounts?.length > 1 && ! isAdsAccountConnected );

	// const canShowConnectOrganization =
	// 	hasExistingSnapchatOrganizations || isAdsAccountConnected;
	// const showConnectOrganization =
	// 	canShowConnectOrganization && ( editMode || ! isAdsAccountConnected );

	// const canShowConnectAds =
	// 	isAdsAccountConnected || hasExistingSnapchatAdsAccounts;
	// const showConnectAds =
	// 	canShowConnectAds && ( editMode || ! isAdsAccountConnected );

	// When Ads and Org are disconnected in edit mode, exit edit mode.
	useEffect( () => {
		if (
			editMode &&
			! isAdsAccountConnected &&
			! isOrganizationConnected
		) {
			setEditMode( false );
		}
	}, [ editMode, isAdsAccountConnected, isOrganizationConnected ] );

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
				{ showConnectAds && showConnectOrganization ? (
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

	// @TODO: review
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
