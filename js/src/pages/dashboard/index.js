/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MainTabNav from '~/components/main-tab-nav';

const Dashboard = () => {
	return (
		<div className="sfw-dashboard">
			<MainTabNav />

			<h1>{ __( 'Dashboard', 'snapchat-for-woo' ) }</h1>
			<p>
				{ __(
					'Welcome to the Snapchat for WooCommerce Dashboard!',
					'snapchat-for-woo'
				) }
			</p>
		</div>
	);
};

export default Dashboard;
