/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath, getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppTabNav from '~/components/app-tab-nav';
import useMenuEffect from '~/hooks/useMenuEffect';

const tabs = [
	{
		key: 'dashboard',
		title: __( 'Dashboard', 'snapchat-for-woo' ),
		href: getNewPath( {}, '/snapchat/dashboard', {} ),
	},

	{
		key: 'settings',
		title: __( 'Settings', 'snapchat-for-woo' ),
		href: getNewPath( {}, '/snapchat/settings', {} ),
	},
];

const getSelectedTabKey = () => {
	const path = getPath();

	return tabs.find( ( el ) => path.includes( el.key ) )?.key;
};

const MainTabNav = () => {
	useMenuEffect();

	const selectedKey = getSelectedTabKey();

	return <AppTabNav tabs={ tabs } selectedKey={ selectedKey } />;
};
export default MainTabNav;
