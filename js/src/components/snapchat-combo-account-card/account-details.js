/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import useSnapchatAdsAccount from '~/hooks/useSnapchatAdsAccount';
import useSnapchatOrganization from '~/hooks/useSnapchatOrganization';

/**
 * Account details.
 * @return {JSX.Element} JSX markup.
 */
const AccountDetails = () => {
	const { email } = useSnapchatAccount();
	const {
		name: snapchatOrganizationName,
		isConnected: isSnapchatOrganizationConnected,
	} = useSnapchatOrganization();
	const { snapchatAdsAccount, isConnected: isSnapchatAdsAccountConnected } =
		useSnapchatAdsAccount();

	return (
		<>
			<p>{ email }</p>
			<p>
				{ isSnapchatOrganizationConnected &&
					sprintf(
						// Translators: %s is the Organization name
						__( 'Organization: %s', 'snapchat-for-woo' ),
						snapchatOrganizationName
					) }
			</p>
			<p>
				{ isSnapchatAdsAccountConnected &&
					sprintf(
						// Translators: %1$s is the Ads Account name, %2$s is the Ads Account ID
						__( 'Ads Account: %1$s (%2$s)', 'snapchat-for-woo' ),
						snapchatAdsAccount.name,
						snapchatAdsAccount.id
					) }
			</p>
		</>
	);
};

export default AccountDetails;
