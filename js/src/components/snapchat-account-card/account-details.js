/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import useSnapchatAccountDetails from '~/hooks/useSnapchatAccountDetails';
import './account-detail.scss';

const AccountDetails = () => {
	const {
		org_name: organizationName,
		ad_acc_id: adsId,
		ad_acc_name: adsName,
		pixel_id: pixelId,
		hasFinishedResolution,
	} = useSnapchatAccountDetails();

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	return (
		<div className="sfw-snapchat-account-details">
			<p>
				{ __( 'Organization:', 'snapchat-for-woo' ) }{ ' ' }
				{ organizationName }
			</p>
			<p>
				{ __( 'Ads Account:', 'snapchat-for-woo' ) } { adsName } (
				{ adsId })
			</p>
			<p>
				{ __( 'Pixel ID:', 'snapchat-for-woo' ) } { pixelId }
			</p>
		</div>
	);
};

export default AccountDetails;
