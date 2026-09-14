/**
 * External dependencies
 */
import { createRoot, lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

const SnapchatAdsPromo = lazy( () =>
	import(
		/* webpackChunkName: "snapchat-ads-promo" */ './snapchat-ads-promo'
	)
);

const init = () => {
	const metaBoxData = window.snapchatAdsMetaBoxData;

	if ( metaBoxData?.orderAttributionSource !== 'snapchat' ) {
		return;
	}

	const orderAttributionDetailsContainer = document.querySelector(
		'#woocommerce-order-source-data .woocommerce-order-attribution-details-container'
	);
	const orderAttributionBox = document.querySelector(
		'#woocommerce-order-source-data .inside'
	);

	if ( ! orderAttributionDetailsContainer && ! orderAttributionBox ) {
		return;
	}

	const mountNode = document.createElement( 'div' );
	mountNode.className = 'sfw-order-attribution-promo-root';

	createRoot( mountNode ).render(
		<Suspense fallback={ null }>
			<SnapchatAdsPromo />
		</Suspense>
	);

	// Fall back to prepending to the meta box body when the details container is absent.
	if ( orderAttributionDetailsContainer ) {
		orderAttributionDetailsContainer.insertAdjacentElement(
			'afterend',
			mountNode
		);

		return;
	}

	orderAttributionBox.prepend( mountNode );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
