/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { STORE_KEY } from '~/data';

export const recordSfwEvent = noop;
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
	const { version } = select( STORE_KEY ).getGeneral();

	const mixedProperties = {
		...eventProperties,
		[ `${ slug }_version` ]: version,
	};

	return mixedProperties;
}
