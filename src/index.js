/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { Dropdown } from '@wordpress/components';
import * as Woo from '@woocommerce/components';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

const MyExamplePage = () => (
	<Fragment>
		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Search', 'snapchat-for-woocommerce')}
			/>
			<Woo.Search
				type="products"
				placeholder="Search for something"
				selected={[]}
				onChange={(items) => setInlineSelect(items)}
				inlineTags
			/>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Dropdown', 'snapchat-for-woocommerce')}
			/>
			<Dropdown
				renderToggle={({ isOpen, onToggle }) => (
					<Woo.DropdownButton
						onClick={onToggle}
						isOpen={isOpen}
						labels={['Dropdown']}
					/>
				)}
				renderContent={() => <p>Dropdown content here</p>}
			/>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Pill shaped container', 'snapchat-for-woocommerce')}
			/>
			<Woo.Pill className={'pill'}>
				{__('Pill Shape Container', 'snapchat-for-woocommerce')}
			</Woo.Pill>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Spinner', 'snapchat-for-woocommerce')}
			/>
			<Woo.H>I am a spinner!</Woo.H>
			<Woo.Spinner />
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Datepicker', 'snapchat-for-woocommerce')}
			/>
			<Woo.DatePicker
				text={__('I am a datepicker!', 'snapchat-for-woocommerce')}
				dateFormat={'MM/DD/YYYY'}
			/>
		</Woo.Section>
	</Fragment>
);

addFilter(
	'woocommerce_admin_pages_list',
	'snapchat-for-woocommerce',
	(pages) => {
		pages.push({
			container: MyExamplePage,
			path: '/snapchat-for-woocommerce',
			breadcrumbs: [
				__('Snapchat For Woocommerce', 'snapchat-for-woocommerce'),
			],
			navArgs: {
				id: 'snapchat_for_woocommerce',
			},
		});

		return pages;
	}
);
