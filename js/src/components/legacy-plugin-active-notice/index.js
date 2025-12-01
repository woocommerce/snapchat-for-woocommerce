/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import AppDocumentationLink from '../app-documentation-link';

/**
 * When the documentation link is clicked.
 *
 * @event sfw_documentation_link_click
 */

/**
 * React component that displays a warning notice with link to docs on how to uninstall the legacy plugin
 * if the legacy plugin is active.
 *
 * @fires sfw_documentation_link_click with `{ context: "legacy-plugin-active-notice", link_id: "legacy-plugin-active-notice", href: "https://woocommerce.com/document/snapchat-for-woocommerce/#section-4" }`
 * @return {JSX.Element|null} A warning notice with a link to docs on how to uninstall the legacy plugin, or null if the legacy plugin is not active.
 */
const LegacyPluginActiveNotice = () => {
	const { isLegacyPluginActive } = sfwData;
	if ( ! isLegacyPluginActive ) {
		return null;
	}

	return (
		<Notice status="warning" isDismissible={ false }>
			{ createInterpolateElement(
				__(
					"You currently have two Snapchat plugins installed. Having both plugins active can cause reporting issues. Please uninstall the 'Snapchat Pixel for WooCommerce' (Legacy Plugin) by following the steps <link>here</link>.",
					'snapchat-for-woocommerce'
				),
				{
					link: (
						<AppDocumentationLink
							context="legacy-plugin-active-notice"
							linkId="legacy-plugin-active-notice"
							href="https://woocommerce.com/document/snapchat-for-woocommerce/#section-4"
						/>
					),
				}
			) }
		</Notice>
	);
};

export default LegacyPluginActiveNotice;
