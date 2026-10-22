/**
 * External dependencies
 */
import {
	Flex,
	FlexBlock,
	FlexItem,
	FormToggle,
	Notice,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import snapchatLogoURL from '~/images/logo/snapchat.svg';

/**
 * Channel Visibility Settings component.
 *
 * Renders a toggle whose state a hidden input carries into the WC product form
 * submission, so the choice saves with the standard product save.
 *
 * @return {JSX.Element} The Channel Visibility Settings component.
 */
const ChannelVisibilitySettings = () => {
	const {
		field_name: fieldName,
		product_catalog_item: productCatalogItem,
		product_is_visible: productIsVisible,
	} = window.snapchatAdsMetaBoxData || {};
	const catalogValue = productCatalogItem || '1';
	const defaultChecked = productIsVisible && catalogValue === '1';

	const [ checked, setChecked ] = useState( defaultChecked );

	return (
		<Flex
			className="sfw-channel-visibility sfw-channel-visibility--settings"
			direction="column"
			gap={ 4 }
		>
			<Flex direction="column" gap={ 4 }>
				<FlexBlock>
					<Flex gap={ 2 } align="center">
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
									{ __(
										'Snapchat',
										'snapchat-for-woocommerce'
									) }
								</FlexItem>
							</Flex>
						</FlexItem>

						<FlexItem>
							{ /*
							 * Unchecked checkboxes are not sent via POST, so
							 * this hidden input submits the toggle state. It is
							 * disabled while the product is hidden from the store
							 * catalog, so saving then keeps the stored opt-in.
							 */ }
							<input
								type="hidden"
								name={ fieldName }
								value={ checked ? '1' : '0' }
								disabled={ ! productIsVisible }
							/>
							<FormToggle
								aria-label={ __(
									'Sync to Snapchat',
									'snapchat-for-woocommerce'
								) }
								value="1"
								checked={ checked }
								onChange={ ( event ) =>
									setChecked( event.target.checked )
								}
								disabled={ ! productIsVisible }
							/>
						</FlexItem>
					</Flex>
				</FlexBlock>

				{ ! productIsVisible && (
					<FlexBlock>
						<Notice
							className="sfw-channel-visibility__notice"
							status="info"
							isDismissible={ false }
						>
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
		</Flex>
	);
};

export default ChannelVisibilitySettings;
