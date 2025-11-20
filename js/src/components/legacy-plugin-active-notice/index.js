/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import './index.scss';

/**
 * React component that displays a warning notice with link to docs on how to uninstall the legacy plugin
 * if the legacy plugin is active.
 *
 * @return {JSX.Element|null} A warning notice with a link to docs on how to uninstall the legacy plugin, or null if the legacy plugin is not active.
 */
const LegacyPluginActiveNotice = () => {
	const [ isDismissed, setIsDismissed ] = useState( false );
	const { isLegacyPluginActive } = sfwData;
	if ( ! isLegacyPluginActive || isDismissed ) {
		return null;
	}

	return (
		<Notice
			status="warning"
			isDismissible={ true }
			onDismiss={ () => setIsDismissed( true ) }
			className="sfw-legacy-plugin-active-notice"
		>
			<p>
				{ createInterpolateElement(
					__(
						'You currently have two Snapchat plugins installed. Having both plugins active can cause reporting issues. Please uninstall the \'Snapchat Pixel for WooCommerce\' (Legacy Plugin) by following the steps <link>here</link>.',
						'snapchat-for-woocommerce'
					),
					{
						link: (
							// eslint-disable-next-line jsx-a11y/anchor-has-content
							<a
								target="_blank"
								rel="external noreferrer noopener"
								href="https://woocommerce.com/document/snapchat-for-woocommerce/#section-4"
							/>
						),
					}
				) }
			</p>
		</Notice>
	);
};

export default LegacyPluginActiveNotice;
