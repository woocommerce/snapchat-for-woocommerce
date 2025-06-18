/**
 * Internal dependencies
 */
import { SnapchatEvent } from './events';

/* global snapchatAdsData */

/**
 * Track an event using the global snaptr function.
 *
 * @param {string} eventName
 * @param {Object} eventParams
 * @throws Will throw an error if the global snaptr function is not available.
 */
export const trackEvent = ( eventName, eventParams ) => {
	if ( typeof snaptr !== 'function' ) {
		throw new Error( 'Function snaptr not implemented.' );
	}

	window.snaptr( 'track', eventName, {
		...eventParams,
	} );
};

/**
 * Formats data into a cart Item object.
 *
 * @param {Product} product
 * @param {number} quantity
 * @return {Item} Item object.
 */
export const getCartItemObject = ( product, quantity ) => {
	const item = {
		id: product.id,
		quantity,
	};

	if ( product?.prices?.price ) {
		item.price =
			parseInt( product.prices.price, 10 ) /
			10 ** product.prices.currency_minor_unit;
	}

	return item;
};

/**
 * Track an add_to_cart event.
 *
 * @param {Product} product
 * @param {number} quantity
 */
export const trackAddToCartEvent = ( product, quantity = 1 ) => {
	const { id, price } = getCartItemObject( product, quantity );

	const data = {
		item_ids: [ id ],
		number_items: parseInt( quantity ),
		price,
	};

	if ( snapchatAdsData.user_email ) {
		data.user_email = snapchatAdsData.user_email;
	}

	trackEvent( SnapchatEvent.ADD_CART, data );
};

/**
 * Formats a regular price into a price object.
 *
 * @param {number} price
 * @return {ProductPrices} Price object.
 */
export const getPriceObject = ( price ) => {
	return {
		price: Math.round( price * 10 ** snapchatAdsData.currency_minor_unit ),
		currency_minor_unit: snapchatAdsData.currency_minor_unit,
	};
};

/**
 * Formats a product object to include price from global data.
 *
 * @param {Product} product
 * @return {Product} Product object with optional fields added.
 */
export const getProductObject = ( product ) => {
	if ( snapchatAdsData.products[ product.id ] ) {
		product.prices = getPriceObject(
			snapchatAdsData.products[ product.id ].price
		);
	}
	return product;
};

/**
 * Updates product data with the retrieved variation.
 *
 * @param {Variation} variation
 */
export const retrievedVariation = ( variation ) => {
	if ( ! variation?.variation_id ) {
		return;
	}

	snapchatAdsData.products[ variation.variation_id ] = {
		price: variation.display_price,
	};
};
