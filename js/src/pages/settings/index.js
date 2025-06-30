/**
 * Internal dependencies
 */
import useMenuEffect from '~/hooks/useMenuEffect';
import LinkedAccounts from './linked-accounts';
import MainTabNav from '~/components/main-tab-nav';
import './index.scss';

const Settings = () => {
	// Make the component highlight SFW entry in the WC legacy menu.
	useMenuEffect();

	return (
		<div className="sfw-settings">
			<MainTabNav />
			<LinkedAccounts />
		</div>
	);
};

export default Settings;
