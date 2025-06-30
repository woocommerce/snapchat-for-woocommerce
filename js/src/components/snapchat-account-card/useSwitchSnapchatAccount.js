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
 * A hook that returns a handler that initiates Snapchat account disconnect and connect,
 * to support switching to a different Snapchat account.
 * This will also disconnect the Ads account and organization.
 *
 * The `handleSwitch` handler is meant to be used in button click handler.
 * Upon button click, the handler will:
 *
 * 1. Display an info notice that the process is running and request the users to wait.
 * 2. Call `DELETE /snapchat/ads_accounts` API to disconnect the existing connected Ads account.
 * 3. Call `DELETE /snapchat/organizations` API to disconnect the existing connected organization.
 * 4. Call `GET /snapchat/connect` API to get the Snapchat OAuth URL.
 * 5. Redirect the browser to the URL.
 * 6. If there is an error in the above process, it will display an error notice.
 *
 * @return {Array} `[ handleSwitch, { loading } ]`
 * 		- `handleSwitch` is meant to be used as button click handler.
 * 		- `loading` is a state to indicate that the process is running.
 */
const useSwitchSnapchatAccount = () => {
	const { createNotice, removeNotice } = useDispatchCoreNotices();

	const [
		fetchSnapchatOrganizationDisconnect,
		{ loading: loadingSnapchatOrganizationDisconnect },
	] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/snapchat/organizations`,
		method: 'DELETE',
	} );

	const [
		fetchSnapchatAdsDisconnect,
		{ loading: loadingSnapchatAdsDisconnect },
	] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/snapchat/ads_accounts`,
		method: 'DELETE',
	} );

	/**
	 * Note: we are manually calling `DELETE /snapchat/connect` instead of using
	 * `disconnectSnapchatAccount` action from wp-data store
	 * because `disconnectSnapchatAccount` will cause a store update,
	 * and the UI will display the Connect card for a brief moment,
	 * before the browser redirects to the Snapchat Auth page.
	 */
	const [ fetchSnapchatDisconnect, { loading: loadingSnapchatDisconnect } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/snapchat/connect`,
			method: 'DELETE',
		} );

	const [
		fetchSnapchatConnect,
		{ loading: loadingSnapchatConnect, data: dataSnapchatConnect },
	] = useSnapchatAuthorization( 'setup' );

	const handleSwitch = async () => {
		const { notice } = await createNotice(
			'info',
			__(
				'Connecting to a different Snapchat account, please wait…',
				'snapchat-for-woo'
			)
		);

		try {
			await fetchSnapchatOrganizationDisconnect();
			await fetchSnapchatAdsDisconnect();
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
		loadingSnapchatOrganizationDisconnect ||
		loadingSnapchatAdsDisconnect ||
		loadingSnapchatDisconnect ||
		loadingSnapchatConnect ||
		dataSnapchatConnect;

	return [ handleSwitch, { loading } ];
};

export default useSwitchSnapchatAccount;
