/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useSnapchatAuthorization from '~/hooks/useSnapchatAuthorization';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

/**
 * Custom React hook to handle switching Snapchat accounts within the WooCommerce plugin.
 *
 * This hook provides a function to disconnect the current Snapchat account and initiate
 * the connection flow for a new Snapchat account. It manages loading states and user notifications.
 *
 * @return {Array} `[ handleSwitch, { loading } ]`
 *     - handleSwitch: Function to trigger the account switch process.
 *     - An object with a `loading` boolean indicating if the process is ongoing.
 */
const useSwitchSnapchatAccount = () => {
	const { createNotice, removeNotice } = useDispatchCoreNotices();

	const [ fetchSnapchatDisconnect, { loading: loadingSnapchatDisconnect } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/snapchat/connection`,
			method: 'DELETE',
		} );

	const [
		fetchSnapchatConnect,
		{ loading: loadingSnapchatConnect, data: dataSnapchatConnect },
	] = useSnapchatAuthorization( 'setup-snapchat' );

	const handleSwitch = async () => {
		const { notice } = await createNotice(
			'info',
			__(
				'Connecting to a different Snapchat account, please wait…',
				'snapchat-for-woo'
			)
		);

		try {
			await fetchSnapchatDisconnect();
			const { url } = await fetchSnapchatConnect();
			window.location.href = url;
		} catch ( error ) {
			removeNotice( notice.id );
			createNotice(
				'error',
				__(
					'Unable to connect to a different Snapchat account. Please try again later.',
					'snapchat-for-woo'
				)
			);
		}
	};

	const loading =
		loadingSnapchatDisconnect ||
		loadingSnapchatConnect ||
		dataSnapchatConnect;

	return [ handleSwitch, { loading } ];
};

export default useSwitchSnapchatAccount;
