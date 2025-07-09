/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppDocumentationLink from '~/components/app-documentation-link';
import AccountCard from '~/components/account-card';
import './index.scss';

const ProductCatalog = () => {
	return (
		<AccountCard
			className="sfw-product-catalog"
			title={ __( 'Export Product Catalog', 'snapchat-for-woo' ) }
			description={ __(
				'Last exported on July 1 at 7:52 PM.',
				'snapchat-for-woo'
			) }
			indicator={
				<Flex spacing={ 4 }>
					<AppButton variant="secondary">
						{ __( 'Regenerate CSV', 'snapchat-for-woo' ) }
					</AppButton>
					<AppButton variant="primary">
						{ __( 'Download CSV', 'snapchat-for-woo' ) }
					</AppButton>
				</Flex>
			}
		>
			<div className="sfw-product-catalog__help">
				<p>
					{ __(
						'You can download the latest CSV or regenerate it if you’ve made changes.',
						'snapchat-for-woo'
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'Need help? Learn how to <link>upload</link> your CSV to Snapchat.',
							'snapchat-for-woo'
						),
						{
							link: (
								<AppDocumentationLink
									context="settings"
									linkId="csv-learn-more"
									href="https://tbd"
								/>
							),
						}
					) }
				</p>
			</div>
		</AccountCard>
	);
};

export default ProductCatalog;
