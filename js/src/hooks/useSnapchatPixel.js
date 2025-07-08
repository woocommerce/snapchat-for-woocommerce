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

const selectorName = 'getSnapchatPixel';

const useSnapchatPixel = () => {
	const dispatcher = useAppDispatch();
	const refetchSnapchatPixel = useCallback( () => {
		dispatcher.invalidateResolution( selectorName, [] );
	}, [ dispatcher ] );

	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const pixel = selector[ selectorName ]();

			return {
				id: pixel?.id,
				refetchSnapchatPixel,
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ refetchSnapchatPixel ]
	);
};

export default useSnapchatPixel;
