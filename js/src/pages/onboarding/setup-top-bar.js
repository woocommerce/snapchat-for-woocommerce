/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TopBar from '~/components/stepper/top-bar';
import HelpIconButton from '~/components/help-icon-button';

const SetupTopBar = () => {
	return (
		<TopBar
			title={ __( 'Get started with Snapchat', 'snapchat-for-woo' ) }
			helpButton={ <HelpIconButton eventContext="setup-snapchat" /> }
			backHref={ getNewPath( {}, '/snapchat/start' ) }
		/>
	);
};

export default SetupTopBar;
