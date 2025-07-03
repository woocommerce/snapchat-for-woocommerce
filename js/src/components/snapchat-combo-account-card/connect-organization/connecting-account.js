/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard from '~/components/account-card';
import LoadingLabel from '~/components/loading-label';

/**
 * Renders indication that the user is in the process of connecting an organization.
 */
const ConnectingAccount = () => {
	return (
		<AccountCard
			className="sfw-snapchat-combo-service-account-card--organization"
			title={ __(
				'Connecting your Snap Organization',
				'snapchat-for-woo'
			) }
			helper={ __(
				'This may take a few moments, please wait…',
				'snapchat-for-woo'
			) }
			indicator={
				<LoadingLabel
					text={ __( 'Connecting…', 'snapchat-for-woo' ) }
				/>
			}
		/>
	);
};

export default ConnectingAccount;
