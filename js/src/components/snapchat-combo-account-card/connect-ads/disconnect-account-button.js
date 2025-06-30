/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';

/**
 * Clicking on the button to disconnect the Snapchat Ads account.
 *
 * @event sfw_snapchat_ads_account_disconnect_button_click
 * @property {string} [context] Indicates the place where the button is located.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * Renders a button to disconnect the Snapchat Ads account.
 *
 * @fires sfw_snapchat_ads_account_disconnect_button_click When the user clicks on the button to disconnect the Snapchat Ads account.
 *
 * @param {Object} props React props.
 * @param {Function} [props.onDisconnected] Callback after the account is disconnected.
 */
const DisconnectAccountButton = ( { onDisconnected = noop } ) => {
	const { disconnectSnapchatAdsAccount } = useAppDispatch();
	const [ isDisconnecting, setDisconnecting ] = useState( false );

	const handleSwitch = () => {
		setDisconnecting( true );
		disconnectSnapchatAdsAccount( true )
			.then( () => onDisconnected() )
			.catch( () => setDisconnecting( false ) );
	};

	return (
		<AppButton
			isTertiary
			loading={ isDisconnecting }
			text={ __(
				'Or, connect to a different Snapchat Ads account',
				'snapchat-for-woo'
			) }
			eventName="sfw_snapchat_ads_account_disconnect_button_click"
			eventProps={ {
				step: '1', // @TODO: review
			} }
			onClick={ handleSwitch }
		/>
	);
};

export default DisconnectAccountButton;
