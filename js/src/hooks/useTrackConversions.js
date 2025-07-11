/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getTrackConversions';

/**
 * @typedef {Object} TrackConversions
 * @property {boolean} isEnabled Whether conversions tracking is enabled.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the enabled state and resolution status for the conversions tracking feature.
 *
 * @return {TrackConversions} The data and its state.
 */
const useTrackConversions = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			isEnabled: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useTrackConversions;
