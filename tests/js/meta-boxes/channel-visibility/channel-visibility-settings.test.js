/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ChannelVisibilitySettings from '~/meta-boxes/channel-visibility/channel-visibility-settings';

const FIELD_NAME = 'snapchat_for_woocommerce_product_catalog_item';
const HIDDEN_NOTICE =
	'This product cannot be shown on any channel because it is hidden from your store catalog.';

/**
 * Sets `window.snapchatAdsMetaBoxData`, merging overrides over sensible
 * defaults. Values are strings, as `wp_localize_script()` delivers them.
 *
 * @param {Object} overrides Overrides for the meta box payload.
 */
function setChannelVisibilityData( overrides = {} ) {
	window.snapchatAdsMetaBoxData = {
		onboardingComplete: '1',
		field_name: FIELD_NAME,
		product_catalog_item: '1',
		product_is_visible: '1',
		...overrides,
	};
}

describe( 'ChannelVisibilitySettings', () => {
	afterEach( () => {
		delete window.snapchatAdsMetaBoxData;
	} );

	test( 'Renders a checked toggle when the product is set to sync and show', () => {
		setChannelVisibilityData();
		const { container } = render( <ChannelVisibilitySettings /> );

		const toggle = screen.getByRole( 'checkbox', {
			name: 'Sync to Snapchat',
		} );

		const hiddenInput = container.querySelector(
			`input[name="${ FIELD_NAME }"]`
		);

		expect( toggle ).toBeChecked();
		expect( hiddenInput ).toHaveValue( '1' );
	} );

	test( "Renders an unchecked toggle when set to don't sync and show", () => {
		setChannelVisibilityData( { product_catalog_item: '0' } );
		const { container } = render( <ChannelVisibilitySettings /> );

		const hiddenInput = container.querySelector(
			`input[name="${ FIELD_NAME }"]`
		);

		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
		expect( hiddenInput ).toHaveValue( '0' );
	} );

	test( 'Defaults to checked when product_catalog_item is unset', () => {
		setChannelVisibilityData( { product_catalog_item: undefined } );
		render( <ChannelVisibilitySettings /> );

		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );

	test( 'Clicking the toggle flips the checked state and the submitted value', () => {
		setChannelVisibilityData();
		const { container } = render( <ChannelVisibilitySettings /> );

		const hiddenInput = container.querySelector(
			`input[name="${ FIELD_NAME }"]`
		);
		const toggle = screen.getByRole( 'checkbox' );

		fireEvent.click( toggle );
		expect( toggle ).not.toBeChecked();
		expect( hiddenInput ).toHaveValue( '0' );

		fireEvent.click( toggle );
		expect( toggle ).toBeChecked();
		expect( hiddenInput ).toHaveValue( '1' );
	} );

	test( 'Unchecks and disables the toggle and shows a notice when the product is hidden from the catalog', () => {
		setChannelVisibilityData( { product_is_visible: '' } );
		const { container } = render( <ChannelVisibilitySettings /> );

		const toggle = screen.getByRole( 'checkbox' );

		expect( toggle ).not.toBeChecked();
		expect( toggle ).toBeDisabled();
		expect(
			within( container ).getByText( HIDDEN_NOTICE )
		).toBeInTheDocument();
	} );

	test( 'Disables the hidden input when the product is hidden from the catalog, so saving keeps the stored opt-in', () => {
		setChannelVisibilityData( {
			product_is_visible: '',
			product_catalog_item: '1',
		} );
		const { container } = render( <ChannelVisibilitySettings /> );

		expect(
			container.querySelector( `input[name="${ FIELD_NAME }"]` )
		).toBeDisabled();
	} );

	test( 'Keeps the hidden input enabled when the product is visible', () => {
		setChannelVisibilityData();
		const { container } = render( <ChannelVisibilitySettings /> );

		expect(
			container.querySelector( `input[name="${ FIELD_NAME }"]` )
		).toBeEnabled();
	} );

	test( 'Does not show the hidden-from-catalog notice when the product is visible', () => {
		setChannelVisibilityData();
		const { container } = render( <ChannelVisibilitySettings /> );

		// Scoped to the container because `Notice` also announces its text in an
		// `aria-live` region on `document.body` that outlives the previous test.
		expect(
			within( container ).queryByText( HIDDEN_NOTICE )
		).not.toBeInTheDocument();
	} );
} );
