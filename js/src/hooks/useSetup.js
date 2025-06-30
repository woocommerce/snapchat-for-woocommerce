/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Get setup info.
 */
const useSetup = () => {
	return useAppSelectDispatch( 'getSetup' );
};

export default useSetup;
