/**
 * Internal dependencies
 */
import {
	getHostBoxContainer,
	insertAlphabetically,
} from '~/meta-boxes/channel-visibility/utils';

const createContainer = ( html ) => {
	const container = document.createElement( 'div' );
	container.innerHTML = html;
	return container;
};

const createBox = ( service ) => {
	const box = document.createElement( 'div' );
	box.dataset.service = service;
	return box;
};

const getOrder = ( container ) =>
	Array.from( container.children ).map(
		( element ) => element.dataset.service || element.id
	);

describe( 'insertAlphabetically', () => {
	test( 'Appends the box when there are no other services', () => {
		const container = createContainer( '<div id="other"></div>' );

		insertAlphabetically( container, createBox( 'snapchat' ) );

		expect( getOrder( container ) ).toEqual( [ 'other', 'snapchat' ] );
	} );

	test( 'Inserts the box before the first service that sorts after it', () => {
		const container = createContainer(
			'<div data-service="alpha"></div><div data-service="zeta"></div>'
		);

		insertAlphabetically( container, createBox( 'snapchat' ) );

		expect( getOrder( container ) ).toEqual( [
			'alpha',
			'snapchat',
			'zeta',
		] );
	} );

	test( 'Inserts the box right after the last service when it sorts last', () => {
		const container = createContainer(
			'<div data-service="reddit"></div><div id="other"></div>'
		);

		insertAlphabetically( container, createBox( 'snapchat' ) );

		expect( getOrder( container ) ).toEqual( [
			'reddit',
			'snapchat',
			'other',
		] );
	} );

	test( 'Appends the box when it sorts after a service that is the last child', () => {
		const container = createContainer(
			'<div id="other"></div><div data-service="reddit"></div>'
		);

		insertAlphabetically( container, createBox( 'snapchat' ) );

		expect( getOrder( container ) ).toEqual( [
			'other',
			'reddit',
			'snapchat',
		] );
	} );

	test( 'Moves an existing box that is out of place', () => {
		const container = createContainer(
			'<div data-service="snapchat"></div><div data-service="reddit"></div>'
		);

		insertAlphabetically( container, container.lastElementChild );

		expect( getOrder( container ) ).toEqual( [ 'reddit', 'snapchat' ] );
	} );

	test( 'Does not move an existing box that is already in place', () => {
		const container = createContainer(
			'<div data-service="reddit"></div><div data-service="snapchat"></div>'
		);
		const insertBefore = jest.spyOn( container, 'insertBefore' );

		insertAlphabetically( container, container.firstElementChild );
		insertAlphabetically( container, container.lastElementChild );

		expect( insertBefore ).not.toHaveBeenCalled();
		expect( getOrder( container ) ).toEqual( [ 'reddit', 'snapchat' ] );
	} );
} );

describe( 'getHostBoxContainer', () => {
	const createMetaBox = ( id ) =>
		`<div id="${ id }" class="postbox"><div class="inside"></div></div>`;

	test( 'Returns the shared box when it exists', () => {
		const root = createContainer( createMetaBox( 'channel_visibility' ) );

		expect( getHostBoxContainer( root ) ).toBe(
			root.querySelector( '#channel_visibility .inside' )
		);
	} );

	test( "Returns Reddit's standalone box when there is no shared box", () => {
		const root = createContainer(
			createMetaBox( 'reddit-channel-visibility' )
		);

		expect( getHostBoxContainer( root ) ).toBe(
			root.querySelector( '#reddit-channel-visibility .inside' )
		);
	} );

	test( "Prefers the shared box over Reddit's standalone box", () => {
		const root = createContainer(
			createMetaBox( 'reddit-channel-visibility' ) +
				createMetaBox( 'channel_visibility' )
		);

		expect( getHostBoxContainer( root ) ).toBe(
			root.querySelector( '#channel_visibility .inside' )
		);
	} );

	test( 'Returns null when there is no box to join', () => {
		const root = createContainer( createMetaBox( 'other-box' ) );

		expect( getHostBoxContainer( root ) ).toBeNull();
	} );

	test( 'Looks in the document by default', () => {
		document.body.innerHTML = createMetaBox( 'reddit-channel-visibility' );

		expect( getHostBoxContainer() ).toBe(
			document.querySelector( '#reddit-channel-visibility .inside' )
		);

		document.body.innerHTML = '';
	} );
} );
