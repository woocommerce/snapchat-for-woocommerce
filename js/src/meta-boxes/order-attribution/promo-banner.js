/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import snapchatLogoURL from '~/images/logo/snapchat.svg';

/**
 * Renders a single promo banner within the Order Attribution meta box.
 *
 * @param {Object} props Component props.
 * @param {string} props.title Banner heading.
 * @param {string} props.body Banner body copy.
 * @param {JSX.Element} props.cta The call-to-action button.
 * @return {JSX.Element} The promo banner.
 */
const PromoBanner = ( { title, body, cta } ) => (
	<div className="sfw-order-attribution-promo">
		<div className="sfw-order-attribution-promo__header">
			<img
				className="sfw-order-attribution-promo__logo"
				src={ snapchatLogoURL }
				alt={ __( 'Snapchat', 'snapchat-for-woocommerce' ) }
				width="24"
				height="24"
			/>
			<h3 className="sfw-order-attribution-promo__title">{ title }</h3>
		</div>
		<p className="sfw-order-attribution-promo__body">{ body }</p>
		{ cta }
	</div>
);

export default PromoBanner;
