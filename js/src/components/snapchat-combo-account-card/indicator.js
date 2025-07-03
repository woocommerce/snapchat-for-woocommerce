/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConnectedIconLabel from '~/components/connected-icon-label';
import LoadingLabel from '~/components/loading-label';
import useSnapchatAccountReady from '~/hooks/useSnapchatAccountReady';

/**
 * Account creation indicator.
 * Displays a loading indicator when accounts are being created or a connected icon when accounts are connected.
 * @param {Object} props Component props.
 * @param {boolean} props.showSpinner Whether to display a spinner.
 * @return {JSX.Element|null} Indicator component.
 */
const Indicator = ( { showSpinner } ) => {
	const { isSnapchatAccountReady } = useSnapchatAccountReady();

	if ( showSpinner ) {
		return <LoadingLabel text={ __( 'Creating…', 'snapchat-for-woo' ) } />;
	}

	if ( isSnapchatAccountReady ) {
		return <ConnectedIconLabel />;
	}

	return null;
};

export default Indicator;
