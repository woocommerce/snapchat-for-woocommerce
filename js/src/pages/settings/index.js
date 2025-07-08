/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useMenuEffect from '~/hooks/useMenuEffect';
import LinkedAccounts from './linked-accounts';
import MainTabNav from '~/components/main-tab-nav';
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
			<LinkedAccounts />
		</div>
	);
};

export default Settings;
