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
			title={ __( 'Manage Snapchat Connection', 'snapchat-for-woo' ) }
			description={ __(
				'See your currently connected account or disconnect.',
				'snapchat-for-woo'
			) }
			{ ...props }
		/>
	);
}
