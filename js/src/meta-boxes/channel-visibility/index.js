/**
 * External dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot, lazy, Suspense } from '@wordpress/element';

const SnapchatAdsPromo = lazy( () =>
	import(
		/* webpackChunkName: "channel-visibility-snapchat-ads-promo" */ './snapchat-ads-promo'
	)
);

domReady( () => {
	if ( ! window.snapchatAdsMetaBoxData ) {
		return;
	}

	let mountEl = document.getElementById( 'snapchat-channel-visibility-box' );

	if ( ! mountEl ) {
		const inside = document.querySelector( '#channel_visibility .inside' );

		if ( ! inside ) {
			return;
		}

		mountEl = document.createElement( 'div' );
		mountEl.id = 'snapchat-channel-visibility-row';
		inside.appendChild( mountEl );
	}

	createRoot( mountEl ).render(
		<Suspense>
			<SnapchatAdsPromo />
		</Suspense>
	);
} );
