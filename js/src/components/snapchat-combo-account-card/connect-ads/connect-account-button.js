/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';

/**
 * Clicking on the button to connect an existing Snapchat Ads account.
 *
 * @event sfw_ads_account_connect_button_click
 * @property {number} id The account ID to be connected.
 * @property {string} [context] Indicates the place where the button is located.
 * @property {string} [step] Indicates the step in the onboarding process.
 */

/**
 * Snapchat Ads account connection button.
 *
 * @param {Object} props Props.
 * @param {number} props.accountID The Snapchat Ads account ID to be connected.
 * @param {Object} props.restProps Rest props. Forwarded to AppButton.
 * @fires sfw_ads_account_connect_button_click when "Connect" button is clicked.
 * @return {JSX.Element} Snapchat Ads connect button component.
 */
const ConnectButton = ( { accountID, ...restProps } ) => {
	return (
		<AppButton
			isSecondary
			disabled={ ! accountID }
			eventName="sfw_ads_account_connect_button_click"
			eventProps={ {
				step: '1', // @TODO: review
			} }
			{ ...restProps }
		>
			{ __( 'Connect', 'snapchat-for-woo' ) }
		</AppButton>
	);
};

export default ConnectButton;
