/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import { act } from 'react';

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
	let container;
	let root;

	const render = () => {
		act( () => {
			root.render( <ChannelVisibilitySettings /> );
		} );
	};

	const getToggle = () => container.querySelector( 'input[type="checkbox"]' );

	const getHiddenInput = () =>
		container.querySelector(
			`input[type="hidden"][name="${ FIELD_NAME }"]`
		);

	beforeAll( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
	} );

	beforeEach( () => {
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
	} );

	afterEach( () => {
		act( () => root.unmount() );
		container.remove();
		delete window.snapchatAdsMetaBoxData;
	} );

	test( 'Renders a checked toggle when the product is set to sync and show', () => {
		setChannelVisibilityData();
		render();

		expect( getToggle().checked ).toBe( true );
		expect( getToggle().getAttribute( 'aria-label' ) ).toBe(
			'Channel visibility setting'
		);
		expect( getHiddenInput().value ).toBe( '1' );
	} );

	test( "Renders an unchecked toggle when set to don't sync and show", () => {
		setChannelVisibilityData( { product_catalog_item: '0' } );
		render();

		expect( getToggle().checked ).toBe( false );
		expect( getHiddenInput().value ).toBe( '0' );
	} );

	test( 'Defaults to checked when product_catalog_item is unset', () => {
		setChannelVisibilityData( { product_catalog_item: undefined } );
		render();

		expect( getToggle().checked ).toBe( true );
	} );

	test( 'Clicking the toggle flips the checked state and the submitted value', () => {
		setChannelVisibilityData();
		render();

		act( () => getToggle().click() );
		expect( getToggle().checked ).toBe( false );
		expect( getHiddenInput().value ).toBe( '0' );

		act( () => getToggle().click() );
		expect( getToggle().checked ).toBe( true );
		expect( getHiddenInput().value ).toBe( '1' );
	} );

	test( 'Unchecks and disables the toggle and shows a notice when the product is hidden from the catalog', () => {
		setChannelVisibilityData( { product_is_visible: '' } );
		render();

		expect( getToggle().checked ).toBe( false );
		expect( getToggle().disabled ).toBe( true );
		expect( getHiddenInput().value ).toBe( '0' );
		expect( container.textContent ).toContain( HIDDEN_NOTICE );
	} );

	test( 'Does not show the hidden-from-catalog notice when the product is visible', () => {
		setChannelVisibilityData();
		render();

		expect( container.textContent ).not.toContain( HIDDEN_NOTICE );
	} );
} );
