/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import useSwitchSnapchatAccount from '~/hooks/useSwitchSnapchatAccount';

/**
 * Clicking on the "connect to a different Snapchat account" button.
 *
 * @event sfw_snapchat_account_connect_different_account_button_click
 */

/**
 * Renders a switch button that lets user connect with another Snapchat account.
 *
 * @fires sfw_snapchat_account_connect_different_account_button_click
 * @param {Object} props React props
 * @param {string} [props.text="Or, connect to a different Snapchat account"] Text to display on the button
 */
const SwitchAccountButton = ( {
	text = __(
		'Or, connect to a different Snapchat account',
		'snapchat-for-woocommerce'
	),
	...restProps
} ) => {
	const [ handleSwitch, { loading } ] = useSwitchSnapchatAccount();

	return (
		<AppButton
			isLink
			disabled={ loading }
			text={ text }
			eventName="sfw_snapchat_account_connect_different_account_button_click"
			onClick={ handleSwitch }
			{ ...restProps }
		/>
	);
};

export default SwitchAccountButton;
