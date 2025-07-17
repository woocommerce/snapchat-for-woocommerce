/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { EXPORT_CSV_STATUS_ACTION } from './constants';

const { exportNonce } = sfwData;

/**
 * Custom hook for polling the export status of the product catalog.
 *
 * @param {boolean} isPolling - Whether to start polling.
 * @param {Function} onTick - Callback invoked with the export status response.
 */
const useExportPoller = ( isPolling, onTick ) => {
	const intervalRef = useRef( null );
	const hasPolledInitially = useRef( false );

	useEffect( () => {
		/**
		 * Polls the server for the current export status.
		 */
		const fetchStatus = async () => {
			try {
				const response = await window.jQuery.post( window.ajaxurl, {
					action: EXPORT_CSV_STATUS_ACTION,
					security: exportNonce,
				} );

				if ( response ) {
					onTick( response );
				}
			} catch ( error ) {}
		};

		if ( isPolling && ! intervalRef.current ) {
			if ( ! hasPolledInitially.current ) {
				hasPolledInitially.current = true;
				fetchStatus();
			}

			intervalRef.current = setInterval( fetchStatus, 5000 );
		}

		if ( ! isPolling && intervalRef.current ) {
			clearInterval( intervalRef.current );
			intervalRef.current = null;
			hasPolledInitially.current = false;
		}

		return () => {
			if ( intervalRef.current ) {
				clearInterval( intervalRef.current );
				intervalRef.current = null;
			}
			hasPolledInitially.current = false;
		};
	}, [ isPolling, onTick ] );
};

export default useExportPoller;
