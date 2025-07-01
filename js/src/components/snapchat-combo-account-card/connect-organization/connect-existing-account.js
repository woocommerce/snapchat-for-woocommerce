/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AccountCard from '~/components/account-card';
import ConnectExistingAccountActions from './connect-existing-account-actions';
import LoadingLabel from '~/components/loading-label';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useSnapchatOrganization from '~/hooks/useSnapchatOrganization';
import OrganizationSelectControl from '~/components/organization-select-control';
import ConnectedIconLabel from '~/components/connected-icon-label';
import ConnectAccountButton from './connect-account-button';

/**
 * Renders an account card to connect to an existing Snapchat Organization.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onCreateClick Callback when clicking on the button to create a new account
 */
const ConnectExistingAccount = ( { onCreateClick } ) => {
	const [ value, setValue ] = useState();
	const [ isLoading, setLoading ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { fetchSnapchatOrganizationStatus } = useAppDispatch();
	const {
		id: snapchatOrganizationId,
		isConnected,
		hasFinishedResolution,
		refetchSnapchatOrganization,
	} = useSnapchatOrganization();
	const [ connectSnapchatOrganization ] = useApiFetchCallback( {
		path: '/wc/sfw/organization',
		method: 'POST',
		data: { id: value },
	} );

	useEffect( () => {
		if ( isConnected ) {
			setValue( snapchatOrganizationId );
		}
	}, [ snapchatOrganizationId, isConnected ] );

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		setLoading( true );
		try {
			await connectSnapchatOrganization();
			await fetchSnapchatOrganizationStatus();
			await refetchSnapchatOrganization();
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Snapchat Ads organization. Please try again later.',
					'snapchat-for-woo'
				)
			);
		} finally {
			setLoading( false );
		}
	};

	const handleDisconnected = () => {
		/*
		 * Prevent the `value` from staying on the unclaimed and disconnected account ID.
		 * Please note that the reset works because the `OrganizationSelectControl` happens to
		 * switch between two different `AppSelectControls` so that `autoSelectFirstOption`
		 * can be triggered again.
		 */
		setValue( undefined );
	};

	const getIndicator = () => {
		if ( ! hasFinishedResolution ) {
			return <LoadingLabel />;
		}

		if ( isLoading ) {
			return (
				<LoadingLabel
					text={ __( 'Connecting…', 'snapchat-for-woo' ) }
				/>
			);
		}

		if ( isConnected ) {
			return <ConnectedIconLabel />;
		}

		return (
			<ConnectAccountButton
				onClick={ handleConnectClick }
				accountID={ value }
			/>
		);
	};

	return (
		<AccountCard
			className="sfw-snapchat-combo-account-card sfw-snapchat-combo-service-account-card--ads"
			title={ __(
				'1. Connect to existing organisation',
				'snapchat-for-woo'
			) }
			helper={ __(
				'Used to access your Snapchat Business account.',
				'snapchat-for-woo'
			) }
			alignIndicator="toDetail"
			indicator={ getIndicator() }
			detail={
				<OrganizationSelectControl
					// Setting `key` is to ensure that `autoSelectFirstOption` will be
					// triggered after disconnecting, so that the automatically selected
					// account can call back to this component.
					key={ Boolean( value ) }
					value={ value }
					onChange={ setValue }
					autoSelectFirstOption
					nonInteractive={ isConnected }
				/>
			}
			actions={
				<ConnectExistingAccountActions
					disabled={ isLoading }
					isConnected={ isConnected }
					onCreateNewClick={ onCreateClick }
					onDisconnected={ handleDisconnected }
				/>
			}
		/>
	);
};

export default ConnectExistingAccount;
