/**
 * External dependencies
 */
import { setWith, clone } from 'lodash';

/**
 * Internal dependencies
 */
import TYPES from './action-types';

/**
 * @callback chainSet
 * @param {Array<string>|string} path The path of the property to set the new value.
 *   The `basePath`, which is called with `chainState`, would be contacted with `path` if it exists.
 *   Array `path` is used directly as each path properties without parsing.
 *   String `path` is parsed to an array of path properties by splitting '.' and property names enclosed in square brackets. Example: 'user.settings[darkMode].schedule'.
 * @param {*} value The value to set.
 * @return {ChainState} The instance.
 */

/**
 * @typedef {Object} ChainState
 * @property {chainSet} setIn A chainable function for setting value.
 * @property {()=>Object|Array} end Get back the updated new state after chaining calls.
 */

/**
 * A helper to chain multiple values setting into the same new state.
 *
 * Recursively creates a shallow copied new state from `state`,
 * and returns a chainable instance for setting values.
 * Objects are created for missing or `null` paths.
 *
 * Referenced and modified from https://github.com/lodash/lodash/issues/1696#issuecomment-328335502
 *
 * @param {Object|Array} state The state to create and set the new value.
 * @param {Array<string>|string} [basePath='']
 *   The base path to be contacted to the passed-in `path` when chaining calls.
 *   Use this when setting multiple values and don't want to repeat the base path multiple times.
 *
 * @return {ChainState} The chainable instance.
 */
function chainState( state, basePath = '' ) {
	const nextState = Object.assign( state.constructor(), state );
	const customizer = ( value ) => {
		if ( value === null || value === undefined ) {
			return {};
		}
		return clone( value );
	};
	// The `path` of lodash `setWith` can be either a string or an array.
	// Here combines `basePath` and `path` to the final path to be called with lodash `setWith`.
	const combineBasePath = ( path ) => {
		if ( basePath ) {
			if ( Array.isArray( basePath ) || Array.isArray( path ) ) {
				return [].concat( basePath, path );
			}
			return `${ basePath }.${ path }`;
		}
		return path;
	};

	return {
		setIn( path, value ) {
			const fullPath = combineBasePath( path );
			setWith( nextState, fullPath, value, customizer );
			return this;
		},
		end: () => nextState,
	};
}

/**
 * An immutable version of lodash `set` function with the same arguments.
 *
 * Recursively creates a shallow copied new state from `state`,
 * and sets the `value` at `path` of the new state.
 * Objects are created for missing or `null` paths.
 *
 * @param {Object|Array} state The state to create and set the new value.
 * @param {Array<string>|string} path The path of the property to set the new value.
 *   Array `path` is used directly as each path properties without parsing.
 *   String `path` is parsed to an array of path properties by splitting '.' and property names enclosed in square brackets. Example: 'user.settings[darkMode].schedule'.
 * @param {*} value The value to set.
 *
 * @return {Object|Array} The same type of passed-in `state` with placed `value` at `path` of the new state.
 */
function setIn( state, path, value ) {
	return chainState( state ).setIn( path, value ).end();
}

const reducer = ( state, action ) => {
	switch ( action.type ) {
		case TYPES.RECEIVE_ACCOUNTS_JETPACK: {
			const { account } = action;

			return setIn( state, 'accounts.jetpack', account );
		}

		case TYPES.RECEIVE_SETUP: {
			const { setup } = action;

			return setIn( state, 'setup', setup );
		}

		case TYPES.RECEIVE_SNAPCHAT_ACCOUNT: {
			const { snapchatAccount } = action;

			return setIn( state, 'accounts.snapchat', snapchatAccount );
		}

		case TYPES.RECEIVE_SNAPCHAT_ACCOUNT_DETAILS: {
			const { snapchatAccountDetails } = action;

			return setIn( state, 'snapchat', snapchatAccountDetails );
		}

		case TYPES.DISCONNECT_ACCOUNTS_SNAPCHAT: {
			return setIn( state, 'accounts.snapchat', null );
		}

		case TYPES.RECEIVE_SETTINGS: {
			const { settings } = action;
			return setIn( state, 'settings', settings );
		}

		// Page will be reloaded after all accounts have been disconnected, so no need to mutate state.
		case TYPES.DISCONNECT_ACCOUNTS_ALL:
		default:
			return state;
	}
};

export default reducer;
