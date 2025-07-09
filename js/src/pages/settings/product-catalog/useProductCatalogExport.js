/**
 * External dependencies
 */
import { useState, useRef, useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';

/**
 * @typedef {Object} ExportStatus
 * @property {string} status The current status of the export (e.g., 'idle', 'in-progress', 'completed', 'error').
 * @property {string} fileUrl The URL of the exported CSV file, if available.
 */

/**
 * @typedef {Object} ProductCatalogExport
 * @property {boolean} isExportInProgress Indicates if an export is currently in progress.
 * @property {ExportStatus} exportStatus The current export status and file URL.
 * @property {Function} generateCsv Function to initiate the CSV export process.
 */

/**
 * Custom React hook to handle the export of a product catalog as a CSV file.
 * Integrates with WordPress Heartbeat API to poll export status and manages export state.
 *
 * @return {ProductCatalogExport} The current export status and functions to manage the export process.
 */
const useProductCatalogExport = () => {
	const { csvExportAction, exportNonce, isExportInProgress, prefix } =
		sfwData;

	const [ exportStatus, setExportStatus ] = useState( {
		status: 'idle',
		fileUrl: '',
	} );

	const pollingRef = useRef( false );

	const request_key = `${ prefix }check_export_status`;
	const response_key = `${ prefix }export_status`;

	const stopPolling = useCallback( () => {
		jQuery( document ).off( 'heartbeat-send', onHeartbeatSend );
		jQuery( document ).off( 'heartbeat-tick', onHeartbeatTick );

		pollingRef.current = false;
	}, [ onHeartbeatSend, onHeartbeatTick ] );

	const onHeartbeatSend = useCallback(
		( event, data ) => {
			data[ request_key ] = true;
		},
		[ request_key ]
	);

	const onHeartbeatTick = useCallback(
		( event, data ) => {
			if ( data[ response_key ] ) {
				setExportStatus( data[ response_key ] );

				if ( data[ response_key ].status === 'completed' ) {
					stopPolling();
				}
			}
		},
		[ stopPolling, response_key ]
	);

	const startPolling = useCallback( () => {
		if ( pollingRef.current ) {
			return;
		}
		pollingRef.current = true;

		jQuery( document ).on( 'heartbeat-send', onHeartbeatSend );
		jQuery( document ).on( 'heartbeat-tick', onHeartbeatTick );

		if ( window.wp?.heartbeat?.interval ) {
			window.wp.heartbeat.interval( 10 );
		}
	}, [ onHeartbeatSend, onHeartbeatTick ] );

	const generateCsv = useCallback( async () => {
		try {
			const res = await jQuery.post( window.ajaxurl, {
				action: csvExportAction,
				security: exportNonce,
			} );

			if ( res.success ) {
				setExportStatus( { status: 'in-progress', fileUrl: '' } );
				startPolling();
			} else {
				throw new Error( res.data?.message || 'Unknown error' );
			}
		} catch ( err ) {
			console.error( 'CSV generation failed:', err );
			setExportStatus( { status: 'error', fileUrl: '' } );
		}
	}, [ startPolling, csvExportAction, exportNonce ] );

	useEffect( () => {
		if ( isExportInProgress ) {
			startPolling();
			setExportStatus( { status: 'in-progress', fileUrl: '' } );
		}
	}, [ isExportInProgress, startPolling ] );

	return {
		isExportInProgress,
		exportStatus,
		generateCsv,
	};
};

export default useProductCatalogExport;
