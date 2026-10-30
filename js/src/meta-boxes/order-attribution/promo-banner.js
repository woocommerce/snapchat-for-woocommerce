/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
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
	<Flex className="sfw-order-attribution-promo" direction="column" gap={ 3 }>
		<FlexBlock>
			<Flex gap={ 2 } align="center">
				<FlexItem>
					<img
						className="sfw-order-attribution-promo__logo"
						src={ snapchatLogoURL }
						alt={ __(
							'Snapchat Logo',
							'snapchat-for-woocommerce'
						) }
						width={ 24 }
						height={ 24 }
					/>
				</FlexItem>
				<FlexBlock>
					<h3 className="sfw-order-attribution-promo__title">
						{ title }
					</h3>
				</FlexBlock>
			</Flex>
		</FlexBlock>
		<FlexBlock>
			<p>{ body }</p>
		</FlexBlock>
		<FlexBlock>{ cta }</FlexBlock>
	</Flex>
);

export default PromoBanner;
