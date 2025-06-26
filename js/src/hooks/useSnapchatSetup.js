/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Get Snapchat setup info.
 */
const useSnapchatSetup = () => {
	return useAppSelectDispatch( 'getSnapchatSetup' );
};

export default useSnapchatSetup;
