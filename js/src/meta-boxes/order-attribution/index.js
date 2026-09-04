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

// Inline script data injected on the order edit screen.
const metaBoxData = window.snapchatAdsMetaBoxData;

/**
 * Resolves the element the promo should be mounted into.
 *
 * Inserts a sibling right after the Order Attribution details container so the
 * promo appears below the attribution information. Falls back to prepending to
 * the meta box body when the details container is not present.
 *
 * @return {HTMLElement|null} The mount node, or null when no insertion point exists.
 */
const getMountNode = () => {
	const mountNode = document.createElement( 'div' );
	mountNode.className = 'sfw-order-attribution-promo-root';

	const detailsContainer = document.querySelector(
		'#woocommerce-order-source-data .woocommerce-order-attribution-details-container'
	);

	if ( detailsContainer ) {
		detailsContainer.after( mountNode );
		return mountNode;
	}

	const metaBoxBody = document.querySelector(
		'#woocommerce-order-source-data .inside'
	);

	if ( metaBoxBody ) {
		metaBoxBody.prepend( mountNode );
		return mountNode;
	}

	return null;
};

const init = () => {
	if ( metaBoxData?.orderAttributionSource !== 'snapchat' ) {
		return;
	}

	const mountNode = getMountNode();

	if ( ! mountNode ) {
		return;
	}

	createRoot( mountNode ).render(
		<Suspense fallback={ null }>
			<SnapchatAdsPromo
				onboardingComplete={ metaBoxData.onboardingComplete }
				hasCampaign={ metaBoxData.hasCampaign }
				createCampaignUrl={ metaBoxData.urls?.createCampaign }
			/>
		</Suspense>
	);
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
