import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';

import {
	Onboarding
} from './pages';
import withAdminPageShell from '~/components/withAdminPageShell';


const StartPage = () => {
	return (
		<div className="snapchat-start-page">
			<h1>{ __( 'Welcome to Snapchat for WooCommerce!', 'snapchat-for-woocommerce' ) }</h1>
			<p>{ __( 'This is a dummy onboarding page located at /snapchat/start.', 'snapchat-for-woocommerce' ) }</p>
		</div>
	);
};

addFilter( 'woocommerce_admin_pages_list', 'snapchat-for-woocommerce', ( pages ) => {
	const breadcrumbs = [
		[ '', __( 'WooCommerce', 'snapchat-for-woocommerce' ) ],
		[ '/marketing', __( 'Marketing', 'snapchat-for-woocommerce' ) ],
		__( 'Snapchat for WooCommerce', 'snapchat-for-woocommerce' ),
	];

	const adminPages = [
		{
			container: withAdminPageShell( Onboarding ),
			path: '/snapchat/onboarding',
			breadcrumbs,
		}
	];

	adminPages.forEach( ( page ) => {
		pages.push( page );
	} );

	return pages;
});
