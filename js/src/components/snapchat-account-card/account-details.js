/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useSnapchatAccountDetails from '~/hooks/useSnapchatAccountDetails';
import './account-detail.scss';

const AccountDetails = () => {
	const {
		org_name: organizationName,
		ad_acc_id: adsId,
		ad_acc_name: adsName,
		pixel_id: pixelId,
	} = useSnapchatAccountDetails();

	return (
		<div className="sfw-snapchat-account-details">
			{ organizationName && (
				<p>
					{ __( 'Organization:', 'snapchat-for-woocommerce' ) }{ ' ' }
					{ organizationName }
				</p>
			) }

			{ adsId && adsName && (
				<p>
					{ __( 'Ads Account:', 'snapchat-for-woocommerce' ) }{ ' ' }
					{ adsName } ({ adsId })
				</p>
			) }

			{ pixelId && (
				<p>
					{ __( 'Pixel ID:', 'snapchat-for-woocommerce' ) }{ ' ' }
					{ pixelId }
				</p>
			) }
		</div>
	);
};

export default AccountDetails;
