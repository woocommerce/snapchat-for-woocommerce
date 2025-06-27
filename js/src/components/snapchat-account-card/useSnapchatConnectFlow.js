/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useSnapchatAuthorization from '~/hooks/useSnapchatAuthorization';

/**
 * A hook that returns a connect handler that initiates Snapchat Connect with support for incremental OAuth process.
 *
 * The `handleConnect` handler is meant to be used in button click handler. Upon button click, the handler will:
 *
 * 1. Call `fetchSnapchatConnect` from `useSnapchatAuthorization` hook to get the Snapchat OAuth URL.
 * 2. Redirect the browser to the URL.
 * 3. If there is an error in the above process, it will display an error notice.
 *
 * @param {'setup'|'reconnect'} nextPageName Indicates the next page name mapped to the redirect URL when back from Snapchat authorization.
 * @param {string} [loginHint] Specify the email to be requested additional scopes. Set this parameter only if wants to request a partial oauth to Snapchat.
 * @see https://developers.google.com/identity/protocols/oauth2/openid-connect#login-hint
 * @return {Array} `[ handleConnect, result ]`
 * 		- `handleConnect` is meant to be used as button click handler.
 * 		- `result` is the same returned object from `useApiFetchCallback`.
 */
const useSnapchatConnectFlow = ( nextPageName, loginHint ) => {
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchSnapchatConnect, result ] = useSnapchatAuthorization(
		nextPageName,
		loginHint
	);

	const handleConnect = async () => {
		try {
			const { url } = await fetchSnapchatConnect();
			window.location.href = url;
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect your Snapchat account. Please try again later.',
					'snapchat-for-woo'
				)
			);
		}
	};

	return [ handleConnect, result ];
};

export default useSnapchatConnectFlow;
