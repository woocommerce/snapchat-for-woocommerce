/**
 * External dependencies
 */
import { createReduxStore, register, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { STORE_KEY } from './constants';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import reducer from './reducer';

const store = createReduxStore( STORE_KEY, {
	actions,
	selectors,
	resolvers,
	reducer,
	initialState: {
		general: {
			version: null,
		},
		setup: {
			status: sfwData.status,
			step: sfwData.step,
		},
		accounts: {
			jetpack: null,
			snapchat: null,
			ads: null,
			organization: null,
			pixel: null,
		},
	},
} );
register( store );

export const useAppDispatch = () => {
	return useDispatch( STORE_KEY );
};
