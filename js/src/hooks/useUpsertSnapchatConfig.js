/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from './useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

/**
 * Set up a Snapchat account.
 * It fetches the Snapchat account configuration from the API and updates the state in the data store.
 *
 * @return {Array} An array containing the upsert function and a loading state.
 *
 * @see useApiFetchCallback
 */
const useUpsertSnapchatConfig = ( configId ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { fetchSnapchatAccount, fetchSetup } = useAppDispatch();
	const [ loading, setLoading ] = useState( false );

	const [ fetchCreateAccount ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/snapchat/config`,
		method: 'POST',
		data: {
			id: configId,
		},
	} );

	const upsertSnapchatConfig = useCallback( async () => {
		if ( ! configId ) {
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

		setLoading( false );
	}, [
		createNotice,
		fetchCreateAccount,
		fetchSnapchatAccount,
		fetchSetup,
		configId,
	] );

	return { upsertSnapchatConfig, loading };
};

export default useUpsertSnapchatConfig;
