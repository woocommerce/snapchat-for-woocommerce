/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';
import AppButton from '~/components/app-button';
import useUpsertSnapchatConfig from '~/hooks/useUpsertSnapchatConfig';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

/**
 * Clicking on the button to connect a Snapchat account.
 *
 * @event sfw_snapchat_account_connect_button_click
 * @property {string} context (`setup-snapchat`|`reconnect`) - indicates from which page the button was clicked.
 */

/**
 * @fires sfw_snapchat_account_connect_button_click
 */
const ConnectSnapchatAccountCard = ( { disabled, configId } ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { upsertSnapchatConfig, loading: loadingUpsertSnapchatConfig } =
		useUpsertSnapchatConfig( configId );
	const nextPageName = sfwData?.snapchatSetupComplete
		? 'reconnect'
		: 'setup-snapchat';
	const query = { next_page_name: nextPageName };
	const path = addQueryArgs( `${ API_NAMESPACE }/snapchat/connect`, query );
	const [ fetchSnapchatConnect, { loading, data } ] = useApiFetchCallback( {
		path,
	} );

	useEffect( () => {
		if ( configId ) {
			upsertSnapchatConfig( configId );
		}
	}, [ configId, upsertSnapchatConfig ] );

	const handleConnectClick = async () => {
		try {
			const d = await fetchSnapchatConnect();
			window.location.href = d.url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Snapchat account. Please try again later.',
					'snapchat-for-woo'
				)
			);
		}
	};

	const getIndicator = () => {
		if ( loadingUpsertSnapchatConfig ) {
			return (
				<AppButton
					loading
					text={ __( 'Connecting…', 'snapchat-for-woo' ) }
				/>
			);
		}

		return (
			<AppButton
				isSecondary
				disabled={ disabled }
				loading={ loading || data }
				eventName="sfw_snapchat_account_connect_button_click"
				eventProps={ { context: nextPageName } }
				onClick={ handleConnectClick }
			>
				{ __( 'Connect', 'snapchat-for-woo' ) }
			</AppButton>
		);
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.SNAPCHAT }
			disabled={ disabled }
			description={ __(
				'Connect your Snapchat Business Account to sync your catalog and run Dynamic Ads.',
				'snapchat-for-woo'
			) }
			indicator={ getIndicator() }
		/>
	);
};

export default ConnectSnapchatAccountCard;
