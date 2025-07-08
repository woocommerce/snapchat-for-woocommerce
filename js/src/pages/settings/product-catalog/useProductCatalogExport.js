/**
 * Hook to manage product catalog export:
 * - Triggers CSV generation via AJAX
 * - Polls status via Heartbeat API
 */
import { useState, useRef, useCallback, useEffect } from '@wordpress/element';

const useProductCatalogExport = () => {
	const {
		csvExportAction,
		exportNonce,
		isExportInProgress,
		prefix,
	} = snapchatAdsAdminData;

	const [ exportStatus, setExportStatus ] = useState( {
		status: 'idle',
		fileUrl: '',
	} );

	const pollingRef = useRef( false );

	const request_key = `${prefix}check_export_status`;
	const response_key = `${prefix}export_status`;

	const stopPolling = useCallback( () => {
		jQuery( document ).off( 'heartbeat-send', onHeartbeatSend );
		jQuery( document ).off( 'heartbeat-tick', onHeartbeatTick );

		pollingRef.current = false;
	}, [] );

	const onHeartbeatSend = useCallback( ( event, data ) => {
		data[ request_key ] = true;
	}, [] );

	const onHeartbeatTick = useCallback( ( event, data ) => {
		if ( data[ response_key ] ) {
			setExportStatus( data[ response_key ] );

			if ( data[ response_key ].status === 'completed' ) {
				stopPolling();
			}
		}
	}, [ stopPolling ] );

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
			const res = await jQuery.post( ajaxurl, {
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
	}, [ startPolling ] );

	useEffect( () => {
		if ( isExportInProgress ) {
			startPolling();
			setExportStatus( { status: 'in-progress', fileUrl: '' } )
		}
	}, [ isExportInProgress, startPolling ] );

	return {
		isExportInProgress,
		exportStatus,
		generateCsv,
	};
};

export default useProductCatalogExport;
