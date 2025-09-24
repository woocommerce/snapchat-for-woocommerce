/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { noop } from 'lodash';
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { STORE_KEY } from '~/data';

export const recordStepperChangeEvent = noop;
export const recordStepContinueEvent = noop;

/**
 * Returns an event properties with base properties.
 * - gla_version: Plugin version
 * - gla_mc_id: Google Merchant Center account ID if connected
 * - gla_ads_id: Google Ads account ID if connected
 *
 * @param {Object} [eventProperties] The event properties to be included base properties.
 * @return {Object} Event properties with base event properties.
 */
export function addBaseEventProperties( eventProperties ) {
	const { slug } = sfwData;
	const { version, adAccountId } = select( STORE_KEY ).getGeneral();

	const mixedProperties = {
		...eventProperties,
		[ `${ slug }_version` ]: version,
	};

	if ( adAccountId ) {
		mixedProperties[ `${ slug }_ads_id` ] = adAccountId;
	}

	return mixedProperties;
}

/**
 * Record a tracking event with base properties.
 *
 * @param {string} eventName The name of the event to record.
 * @param {Object} [eventProperties] The event properties to include in the event.
 */
export function recordSfwEvent( eventName, eventProperties ) {
	recordEvent( eventName, addBaseEventProperties( eventProperties ) );
}

/**
 * Queue a tracking event with base properties.
 *
 * This allows you to delay tracking events that would otherwise cause a race condition.
 *
 * @param {string} eventName The name of the event to record.
 * @param {Object} [eventProperties] The event properties to include in the event.
 */
export function queueRecordSfwEvent( eventName, eventProperties ) {
	queueRecordEvent( eventName, addBaseEventProperties( eventProperties ) );
}
