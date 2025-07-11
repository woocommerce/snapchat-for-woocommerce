/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getJetpackAccount';

/**
 * @typedef {import('../data/selectors').JetpackAccount} JetpackObject
 */

/**
 * @typedef {Object} JetpackAccountState
 * @property {JetpackObject} jetpack The Jetpack account data.
 * @property {boolean} hasFinishedResolution Whether the resolution for the selector has finished.
 */

/**
 * Retrieves the Jetpack account data and its resolution status.
 *
 * @return {JetpackAccountState} The Jetpack account data and its state.
 */
const useJetpackAccount = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			jetpack: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useJetpackAccount;
