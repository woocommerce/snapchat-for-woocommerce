/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getSnapchatAccountDetails';

/**
 * @typedef {import('../data/selectors').SnapchatAccountDetails} SnapchatAccountDetailsObject
 */

/**
 * @typedef {Object} SnapchatAccountDetailsState
 * @property {SnapchatAccountDetailsObject} details The Snapchat account details.
 * @property {Function} refetchSnapchatAccountDetails Function to refetch the Snapchat account details.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the Snapchat account details and its resolution status.
 * @return {SnapchatAccountDetailsState} The Snapchat account details and its state.
 */
const useSnapchatAccountDetails = () => {
	const dispatcher = useAppDispatch();
	const refetchSnapchatAccountDetails = useCallback( () => {
		dispatcher.invalidateResolution( selectorName, [] );
	}, [ dispatcher ] );

	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const details = selector[ selectorName ]();

			return {
				...details,
				refetchSnapchatAccountDetails,
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ refetchSnapchatAccountDetails ]
	);
};

export default useSnapchatAccountDetails;
