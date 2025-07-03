/**
 * External dependencies
 */
import { registerStore, useDispatch, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { STORE_KEY } from './constants';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import { controls } from './controls';
import reducer from './reducer';

registerStore( STORE_KEY, {
	actions,
	selectors,
	resolvers,
	controls,
	reducer,
} );

// dispatch( STORE_KEY ).hydratePrefetchedData( sfwData?.initialWpData );

export { STORE_KEY };

export const useAppDispatch = () => {
	return useDispatch( STORE_KEY );
};
