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
import ProductCatalog from './product-catalog';
import ConversionsAPI from './conversions-api';
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
				<ConversionsAPI />
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
