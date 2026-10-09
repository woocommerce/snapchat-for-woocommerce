/**
 * External dependencies
 */
import {
	Flex,
	FlexBlock,
	FlexItem,
	Notice,
	SelectControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import snapchatLogoURL from '~/images/logo/snapchat.svg';

const {
	channelVisibility: {
		field_name: fieldName,
		product_catalog_item: productCatalogItem,
		product_is_visible: productIsVisible,
		options: syncOptions,
	} = {},
} = window.snapchatAdsMetaBoxData || {};

/**
 * Channel Visibility Settings component.
 *
 * Renders an uncontrolled SelectControl that participates in the WC product
 * form submission via its `name` attribute, so the choice saves with the
 * standard product save.
 *
 * @return {JSX.Element} The Channel Visibility Settings component.
 */
const ChannelVisibilitySettings = () => {
	const catalogValue = productCatalogItem || '1';
	const defaultValue = productIsVisible ? catalogValue : '0';

	const [ value, setValue ] = useState( defaultValue );

	return (
		<Flex direction="column" gap={ 4 } className="sfw-channel-visibility">
			<FlexBlock>
				<Flex gap={ 2 } align="center" justify="flex-start">
					<FlexItem>
						<Flex gap={ 2 } align="center">
							<FlexItem>
								<img
									className="sfw-channel-visibility__logo"
									src={ snapchatLogoURL }
									alt={ __(
										'Snapchat Logo',
										'snapchat-for-woocommerce'
									) }
									width={ 16 }
									height={ 16 }
								/>
							</FlexItem>
							<FlexItem>
								{ __( 'Snapchat', 'snapchat-for-woocommerce' ) }
							</FlexItem>
						</Flex>
					</FlexItem>

					<FlexBlock>
						<SelectControl
							aria-label={ __(
								'Channel visibility setting',
								'snapchat-for-woocommerce'
							) }
							name={ fieldName }
							options={ syncOptions }
							value={ value }
							onChange={ setValue }
							disabled={ ! productIsVisible }
							__nextHasNoMarginBottom
						/>
					</FlexBlock>
				</Flex>
			</FlexBlock>

			{ ! productIsVisible && (
				<FlexBlock>
					<Notice status="info" isDismissible={ false }>
						<p>
							{ __(
								'This product cannot be shown on any channel because it is hidden from your store catalog.',
								'snapchat-for-woocommerce'
							) }
						</p>
					</Notice>
				</FlexBlock>
			) }
		</Flex>
	);
};

export default ChannelVisibilitySettings;
