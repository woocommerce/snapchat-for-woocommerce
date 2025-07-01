/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';
import Section from '~/components/section';
import SwitchAccountButton from './switch-account-button';

/**
 * Renders a Snapchat account card UI with connected account information.
 * It also provides a switch button that lets user connect with another Snapchat account.
 *
 * @param {Object} props React props.
 * @param {{ email: string }} props.snapchatAccount A data payload object containing the user's Snapchat account email.
 * @param {JSX.Element} [props.helper] Helper content below the Snapchat account email.
 * @param {boolean} [props.hideAccountSwitch=false] Indicate whether hide the account switch block at the card footer.
 */
const ConnectedSnapchatAccountCard = ( {
	snapchatAccount,
	helper,
	hideAccountSwitch = false,
} ) => {
	return (
		<AccountCard
			appearance={ APPEARANCE.SNAPCHAT }
			description={ snapchatAccount.email }
			helper={ helper }
			indicator={ <ConnectedIconLabel /> }
		>
			{ ! hideAccountSwitch && (
				<Section.Card.Footer>
					<SwitchAccountButton />
				</Section.Card.Footer>
			) }
		</AccountCard>
	);
};

export default ConnectedSnapchatAccountCard;
