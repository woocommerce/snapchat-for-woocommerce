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
