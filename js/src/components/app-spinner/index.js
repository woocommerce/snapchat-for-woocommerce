/**
 * External dependencies
 */
import { Spinner } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Display a centered spinner.
 */
const AppSpinner = () => {
	return (
		<div
			className="swf-app-spinner"
			role="status"
			aria-label={ __( 'Loading…', 'snapchat-for-woo' ) }
		>
			<Spinner />
		</div>
	);
};

export default AppSpinner;
