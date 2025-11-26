/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { getHistory, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useMenuEffect from '~/hooks/useMenuEffect';
import LinkedAccounts from './linked-accounts';
import Section from '~/components/section';
import ProductCatalog from './product-catalog';
import ConversionsAPI from './conversions-api';
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import OnboardingSuccessModal from '~/components/onboarding-success-modal';
import { getOnboardingUrl } from '~/utils/urls';
import LegacyPluginActiveNotice from '~/components/legacy-plugin-active-notice';
import { sfwData } from '~/constants';
import './index.scss';

const Settings = () => {
	// Make the component highlight SFW entry in the WC legacy menu.
	useMenuEffect();
	const { isConnected, hasFinishedResolution } = useSnapchatAccount();

	// Show onboarding success guide modal by visiting the path with a specific query `onboarding=success`.
	// For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fsnapchat%2Fsettings&onboarding=success`.
	const isOnboardingSuccessModalOpen = getQuery()?.onboarding === 'success';

	useEffect( () => {
		if ( ! isConnected && hasFinishedResolution ) {
			getHistory().replace( getOnboardingUrl() );
		}
	}, [ isConnected, hasFinishedResolution ] );

	return (
		<div className="sfw-settings">
			{ isOnboardingSuccessModalOpen && <OnboardingSuccessModal /> }

			{ sfwData.isLegacyPluginActive && (
				<Section>
					<LegacyPluginActiveNotice />
				</Section>
			) }

			<Section
				title={ __( 'Product Catalog', 'snapchat-for-woocommerce' ) }
			>
				<ProductCatalog />
			</Section>

			<Section
				title={ __( 'Track Conversions', 'snapchat-for-woocommerce' ) }
				description={ __(
					'Manage how conversions are tracked on your site.',
					'snapchat-for-woocommerce'
				) }
			>
				<ConversionsAPI />
			</Section>

			<Section
				title={ __(
					'Manage Snapchat Connection',
					'snapchat-for-woocommerce'
				) }
				description={ __(
					'See your currently connected account or disconnect.',
					'snapchat-for-woocommerce'
				) }
			>
				<LinkedAccounts />
			</Section>
		</div>
	);
};

export default Settings;
