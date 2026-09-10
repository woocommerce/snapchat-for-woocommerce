/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';

/**
 * Reads a single preference value from the `@wordpress/preferences` store.
 *
 * @param {string} key The preference key.
 * @return {*} The stored preference value.
 */
const usePreference = ( key ) => {
	return useSelect(
		( select ) =>
			select( preferencesStore ).get( PREFERENCES_STORE_NAMESPACE, key ),
		[ key ]
	);
};

export default usePreference;
