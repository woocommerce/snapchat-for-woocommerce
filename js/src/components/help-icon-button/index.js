/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import './index.scss';

/**
 * "Help" button is clicked.
 *
 * @event sfw_help_click
 * @property {string} context Indicates the place where the button is located, e.g. `setup-ads`.
 */

/**
 * Renders a button with a help icon and "Help" text.
 * Upon click, it will open documentation page in a new tab,
 * and call `sfw_help_click` track event.
 *
 * @fires sfw_help_click
 *
 * @param {Object} props Props
 * @param {string} props.eventContext Context to be used in `sfw_help_click` track event.
 * @return {JSX.Element} The button.
 */
const HelpIconButton = ( { eventContext } ) => {
	return (
		<AppButton
			className="sfw-help-icon-button"
			href="https://woocommerce.com/document/snapchat-for-woocommerce/" // @TODO: Review
			target="_blank"
			eventName="sfw_help_click"
			eventProps={ {
				context: eventContext,
			} }
		>
			<GridiconHelpOutline />
			{ __( 'Help', 'snapchat-for-woocommerce' ) }
		</AppButton>
	);
};

export default HelpIconButton;
