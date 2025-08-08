/**
 * External dependencies
 */
import { getHistory } from '@woocommerce/navigation';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { API_NAMESPACE } from '~/data/constants';
import { getOnboardingUrl } from '~/utils/urls';
import useApiFetchCallback from './useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

/**
 * @typedef {Object} UpsertSnapchatConfig
 * @property {Function} upsertSnapchatConfig Function to create or update the Snapchat account configuration.
 * @property {boolean} loading Indicates whether the upsert operation is in progress.
 */

/**
 * Set up a Snapchat account.
 * It fetches the Snapchat account configuration from the API and updates the state in the data store.
 *
 * @return {UpsertSnapchatConfig} An array containing the upsert function and a loading state.
 *
 * @see useApiFetchCallback
 */
const useUpsertSnapchatConfig = ( configId, productsToken ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { fetchSnapchatAccount, fetchSetup } = useAppDispatch();
	const [ loading, setLoading ] = useState( false );

	const [ fetchCreateAccount ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/snapchat/config`,
		method: 'POST',
		data: {
			id: configId,
			products_token: productsToken,
		},
	} );

	const upsertSnapchatConfig = useCallback( async () => {
		if ( ! configId || ! productsToken ) {
			return false;
		}

		setLoading( true );

		try {
			await fetchCreateAccount( { parse: false } );
		} catch ( e ) {
			createNotice( 'error', e.message );
		}

		// Update Snapchat account data in the data store after posting an account update.
		await fetchSnapchatAccount();
		await fetchSetup();

		// Remove the config_id from the URL.
		getHistory().replace( getOnboardingUrl() );

		setLoading( false );
	}, [
		createNotice,
		fetchCreateAccount,
		fetchSnapchatAccount,
		fetchSetup,
		configId,
		productsToken,
	] );

	return { upsertSnapchatConfig, loading };
};

export default useUpsertSnapchatConfig;
