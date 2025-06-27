/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';

export default function LinkedAccountsSectionWrapper( props ) {
	return (
		<Section
			title={ __( 'Linked accounts', 'snapchat-for-woo' ) }
			description={ __(
				'A WordPress.com account, and Snapchat account are required to use this extension in WooCommerce.',
				'snapchat-for-woo'
			) }
			{ ...props }
		/>
	);
}
