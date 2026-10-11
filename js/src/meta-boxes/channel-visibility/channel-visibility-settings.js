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
 * Renders an uncontrolled toggle that participates in the WC product form
 * submission via its `name` attribute, so the choice saves with the standard
 * product save.
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
								{ __( 'Snapchat', 'snapchat-for-woocommerce' ) }
							</FlexItem>
						</Flex>
					</FlexItem>

					<FlexItem>
						{ /*
						 * Unchecked checkboxes are not sent via POST.
						 * This hidden input submits the value whenever
						 * the control is on the page, mirroring the toggle state.
						 */ }
						<input
							type="hidden"
							name={ fieldName }
							value={ checked ? '1' : '0' }
						/>
						<FormToggle
							aria-label={ __(
								'Channel visibility setting',
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
	);
};

export default ChannelVisibilitySettings;
