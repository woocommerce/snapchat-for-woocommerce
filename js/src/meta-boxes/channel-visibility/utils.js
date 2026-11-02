/**
 * Selectors for the `.inside` element of each Channel visibility box Snapchat can join,
 * in order of preference: the shared box, then Reddit for WooCommerce's standalone box.
 */
const HOST_BOX_SELECTORS = [
	'#channel_visibility .inside',
	'#reddit-channel-visibility .inside',
];

/**
 * Finds the existing Channel visibility box that Snapchat should add its row to,
 * when Snapchat didn't register its own box.
 *
 * @param {HTMLElement} [root] Where to look. Defaults to `document`.
 * @return {HTMLElement|null} The box's `.inside` element, or `null` if there's none.
 */
export const getHostBoxContainer = ( root = document ) => {
	for ( const selector of HOST_BOX_SELECTORS ) {
		const container = root.querySelector( selector );

		if ( container ) {
			return container;
		}
	}

	return null;
};

/**
 * Inserts an extension box into the shared channel visibility meta box at
 * its alphabetical position among the other boxes, by `data-service`.
 *
 * Every extension that renders into this meta box tags its top-level box with
 * `data-service` and places it with this function, so the boxes are always
 * in order and each new box only needs to find its own slot. A box that is
 * already in the container (e.g. server-rendered) is only moved when it is out
 * of place. Elements without `data-service` are ignored.
 *
 * @param {HTMLElement} container The meta box `.inside` element.
 * @param {HTMLElement} box The box to insert, with a `data-service` attribute.
 */
export const insertAlphabetically = ( container, box ) => {
	const services = Array.from( container.children ).filter(
		( element ) => element !== box && element.dataset.service
	);

	if ( ! services.length ) {
		if ( box.parentElement !== container ) {
			container.append( box );
		}
		return;
	}

	const next = services.find(
		( element ) =>
			element.dataset.service.localeCompare( box.dataset.service ) > 0
	);
	const reference = next ?? services[ services.length - 1 ].nextSibling;

	if (
		box.parentElement === container &&
		( reference === box || box.nextSibling === reference )
	) {
		return;
	}

	container.insertBefore( box, reference );
};
