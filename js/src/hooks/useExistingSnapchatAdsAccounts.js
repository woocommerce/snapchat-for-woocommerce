/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getExistingSnapchatAdsAccounts';

const useExistingSnapchatAdsAccounts = ( organizationId ) => {
	return useSelect(
		( select ) => {
			if ( ! organizationId ) {
				return {
					existingSnapchatAdsAccounts: null,
					hasFinishedResolution: true,
				};
			}

			const selector = select( STORE_KEY );

			return {
				existingSnapchatAdsAccounts:
					selector[ selectorName ]( organizationId ),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[]
				),
			};
		},
		[ organizationId ]
	);
};

export default useExistingSnapchatAdsAccounts;
