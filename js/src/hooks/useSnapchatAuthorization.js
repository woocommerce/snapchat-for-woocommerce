/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from './useApiFetchCallback';

/**
 * Request a Snapchat Oauth URL.
 *
 * @param {'setup'|'reconnect'} nextPageName Indicates the next page name mapped to the redirect URL when back from Snapchat authorization.
 * @return {Array} The same structure as `useApiFetchCallback`.
 */
export default function useSnapchatAuthorization( nextPageName ) {
	const fetchOption = useMemo( () => {
		const query = { next_page_name: nextPageName };
		const path = addQueryArgs(
			`${ API_NAMESPACE }/snapchat/connect`,
			query
		);
		return { path };
	}, [ nextPageName ] );

	return useApiFetchCallback( fetchOption );
}
