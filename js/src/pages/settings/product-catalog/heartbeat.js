/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { addAction, removeAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { EXPORT_REQUEST_KEY, EXPORT_RESPONSE_KEY } from './constants';

/**
 * @typedef {Object} HeartbeatProps
 * @property {Function} onTick Callback function to be called when there's a heartbeat tick.
 * @property {boolean} connectNow Flag to determine if the heartbeat should connect immediately.
 */

/**
 * Heartbeat component to manage the export status of the product catalog.
 * This component listens for heartbeat ticks to check the export status
 * and sends a request to initiate the export process.
 *
 * @param {HeartbeatProps} props The properties for the Heartbeat component.
 * @return {null} Returns null as this component does not render anything.
 */
const Heartbeat = ( { connectNow, onTick } ) => {
	const handleHeartbeatTick = useCallback(
		( data ) => {
			if ( ! data[ EXPORT_RESPONSE_KEY ] ) {
				return;
			}

			const response = data[ EXPORT_RESPONSE_KEY ];
			onTick( response );
		},
		[ onTick ]
	);

	const handleHeartbeatSend = useCallback( ( data ) => {
		data[ EXPORT_REQUEST_KEY ] = true;
		return data;
	}, [] );

	useEffect( () => {
		addAction(
			'heartbeat.tick',
			'snapchat-for-woo/heartbeatTick',
			handleHeartbeatTick
		);
		addAction(
			'heartbeat.send',
			'snapchat-for-woo/heartbeatSend',
			handleHeartbeatSend
		);

		return () => {
			removeAction( 'heartbeat.tick', 'snapchat-for-woo/heartbeatTick' );
			removeAction( 'heartbeat.send', 'snapchat-for-woo/heartbeatSend' );
		};
	}, [ handleHeartbeatTick, handleHeartbeatSend ] );

	useEffect( () => {
		if ( ! window?.wp?.heartbeat ) {
			return;
		}

		window.wp.heartbeat.interval( 10 );
	}, [] );

	useEffect( () => {
		if ( connectNow && window?.wp?.heartbeat ) {
			window.wp.heartbeat.connectNow();
		}
	}, [ connectNow ] );

	return null;
};

export default Heartbeat;
