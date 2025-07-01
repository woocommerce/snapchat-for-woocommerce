/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getExistingSnapchatOrganizations';

const useExistingSnapchatOrganizations = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			existingSnapchatOrganizations: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useExistingSnapchatOrganizations;
