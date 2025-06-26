/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

/**
 * Account details.
 * @return {JSX.Element} JSX markup.
 */
const AccountDetails = () => {
	const { google } = useGoogleAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { googleMCAccount, isReady: isGoogleMCReady } = useGoogleMCAccount();

	return (
		<>
			<p>{ google.email }</p>
			<p>
				{ isGoogleMCReady &&
					sprintf(
						// Translators: %s is the Merchant Center ID
						__( 'Merchant Center ID: %s', 'snapchat-for-woo' ),
						googleMCAccount.id
					) }
			</p>
			<p>
				{ googleAdsAccount?.id > 0 &&
					sprintf(
						// Translators: %s is the Google Ads ID
						__( 'Google Ads ID: %s', 'snapchat-for-woo' ),
						googleAdsAccount.id
					) }
			</p>
		</>
	);
};

export default AccountDetails;
