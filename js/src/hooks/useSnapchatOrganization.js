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

const selectorName = 'getSnapchatOrganization';

const useSnapchatOrganization = () => {
	const dispatcher = useAppDispatch();
	const refetchSnapchatOrganization = useCallback( () => {
		dispatcher.invalidateResolution( selectorName, [] );
	}, [ dispatcher ] );

	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const organization = selector[ selectorName ]();

			return {
				id: organization?.id,
				name: organization?.name,
				refetchSnapchatOrganization,
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ refetchSnapchatOrganization ]
	);
};

export default useSnapchatOrganization;
