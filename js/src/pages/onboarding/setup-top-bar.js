/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useAdminUrl from '~/hooks/useAdminUrl';
import TopBar from '~/components/stepper/top-bar';
import HelpIconButton from '~/components/help-icon-button';

const SetupTopBar = () => {
	const adminUrl = useAdminUrl();

	return (
		<TopBar
			title={ __(
				'Get started with Snapchat',
				'snapchat-for-woocommerce'
			) }
			helpButton={ <HelpIconButton eventContext="setup-snapchat" /> }
			backHref={ adminUrl }
		/>
	);
};

export default SetupTopBar;
