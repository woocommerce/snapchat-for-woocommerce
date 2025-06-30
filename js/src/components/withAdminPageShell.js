/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * A higher-order component for wrapping the app shell on top of the SFW admin page.
 * Cross-page shared things could be handled here.
 *
 * @param {JSX.Element} Page Top-level admin page component to be wrapped by app shell.
 */
const withAdminPageShell = createHigherOrderComponent(
	( Page ) => ( props ) => {
		return (
			// sfw-admin-page is for scoping particular styles to a SFW admin page.
			<div className="sfw-admin-page">
				<Page { ...props } />
			</div>
		);
	},
	'withAdminPageShell'
);

export default withAdminPageShell;
