/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';
import { noop, pick } from 'lodash';
import { getQuery } from '@woocommerce/navigation';
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { sfwData } from '~/constants';
import { STORE_KEY } from '~/data';

export const recordStepperChangeEvent = noop;
export const recordStepContinueEvent = noop;

/**
 * Referrer type indicating a flow was entered from an in-product placement's CTA.
 */
export const REFERRER_TYPE_IN_PRODUCT_PLACEMENT = 'in_product_placement';

const REFERRER_QUERY_PROPERTIES = [ 'referrer_type', 'referrer_id' ];

/**
 * Picks up the `referrer_type`/`referrer_id` properties from the current URL, if present.
 *
 * @return {Object} The referrer query properties present on the current URL, if any.
 */
export function getReferrerQueryParams() {
	return pick( getQuery(), REFERRER_QUERY_PROPERTIES );
}

/**
 * Appends in-product placement referrer params (`referrer_type` and `referrer_id`) to a URL.
 *
 * @param {string} href        Original destination URL.
 * @param {string} placementId Identifier of the referring placement.
 * @return {string} `href` with `referrer_type` and `referrer_id` query params appended.
 */
export function withReferrer( href, placementId ) {
	return addQueryArgs( href, {
		referrer_type: REFERRER_TYPE_IN_PRODUCT_PLACEMENT,
		referrer_id: placementId,
	} );
}

/**
 * Returns an event properties with base properties.
 * - <slug>_version: Plugin version
 * - <slug>_ads_id: Snapchat ad account ID if connected
 * - referrer_type/referrer_id: Carried over from the current URL when the flow
 *   was entered from a referring CTA (see `withReferrer`), so downstream events
 *   can be attributed back to it.
 *
 * @param {Object} [eventProperties] The event properties to be included base properties.
 * @return {Object} Event properties with base event properties.
 */
export function addBaseEventProperties( eventProperties ) {
	if ( sfwData.sandboxMode ) {
		return eventProperties;
	}

	const { slug } = sfwData;
	const { version, adAccountId } = select( STORE_KEY ).getGeneral();

	const mixedProperties = {
		...getReferrerQueryParams(),
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
	if ( sfwData.sandboxMode ) {
		return;
	}

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
	if ( sfwData.sandboxMode ) {
		return;
	}

	queueRecordEvent( eventName, addBaseEventProperties( eventProperties ) );
}
