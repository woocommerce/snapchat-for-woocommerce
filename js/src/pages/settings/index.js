/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useMenuEffect from '~/hooks/useMenuEffect';
import LinkedAccounts from './linked-accounts';
import Section from '~/components/section';
import MainTabNav from '~/components/main-tab-nav';
import ProductCatalog from './product-catalog';
import TrackConversions from './track-conversions';
import useSnapchatAccount from '~/hooks/useSnapchatAccount';
import { getOnboardingUrl } from '~/utils/urls';
import './index.scss';

const Settings = () => {
	// Make the component highlight SFW entry in the WC legacy menu.
	useMenuEffect();

	const { isConnected, hasFinishedResolution } = useSnapchatAccount();

	useEffect( () => {
		if ( ! isConnected && hasFinishedResolution ) {
			getHistory().replace( getOnboardingUrl() );
		}
	}, [ isConnected, hasFinishedResolution ] );

	return (
		<div className="sfw-settings">
			<MainTabNav />

			<Section title={ __( 'Product Catalog', 'snapchat-for-woo' ) }>
				<ProductCatalog />
			</Section>

			<Section
				title={ __( 'Track Conversions', 'snapchat-for-woo' ) }
				description={ __(
					'Manage how conversions are tracked on your site.',
					'snapchat-for-woo'
				) }
			>
				<TrackConversions />
			</Section>

			<Section
				title={ __( 'Manage Snapchat Connection', 'snapchat-for-woo' ) }
				description={ __(
					'See your currently connected account or disconnect.',
					'snapchat-for-woo'
				) }
			>
				<LinkedAccounts />
			</Section>
		</div>
	);
};

export default Settings;
