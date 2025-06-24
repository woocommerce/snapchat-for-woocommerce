/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TopBar from '~/components/stepper/top-bar';
import HelpIconButton from '~/components/help-icon-button';


const SetupTopBar = () => {
	return (
		<TopBar
			title={ __( 'Set up your campaign', 'snapchat-for-woo' ) }
			helpButton={ <HelpIconButton eventContext="setup-ads" /> }
			backHref={ getNewPath( {}, '/snapchat/start' ) }
		/>
	);
};

export default SetupTopBar;
