/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import useSnapchatOrganization from '~/hooks/useSnapchatOrganization';
import useSnapchatAdsAccount from '~/hooks/useSnapchatAdsAccount';
import useSnapchatPixel from '~/hooks/useSnapchatPixel';
import './account-detail.scss';

const AccountDetails = () => {
	const {
		name: organizationName,
		hasFinishedResolution: hasResolvedOrganization,
	} = useSnapchatOrganization();
	const {
		id: adsId,
		name: adsName,
		hasFinishedResolution: hasResolvedAds,
	} = useSnapchatAdsAccount();
	const { id: pixelId, hasFinishedResolution: hasResolvedPixel } =
		useSnapchatPixel();

	if ( ! hasResolvedOrganization || ! hasResolvedAds || ! hasResolvedPixel ) {
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
