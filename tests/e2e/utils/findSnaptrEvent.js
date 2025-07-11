/**
 * Finds a specific Snapchat Pixel event in the mocked `snaptr.queue`.
 *
 * This utility is used in E2E tests to locate a tracked event (e.g. 'PAGE_VIEW', 'ADD_CART')
 * from the global `snaptr.queue`, which is populated by the mocked Snapchat pixel script:
 *
 * ```html
 * <script>
 *   var x="https://sc-static.net/scevent.min.js";
 *   window.snaptr=function(){window.snaptr.queue.push(Array.from(arguments))},
 *   window.snaptr.queue=[],snaptr("track","PAGE_VIEW");
 * </script>
 * ```
 *
 * @param {Array<Array>} queue - The mocked `snaptr.queue`, an array of event calls.
 * @param {string} eventName - The name of the event to find (e.g., 'PAGE_VIEW').
 * @return {Array|null} The matched event call (e.g., `['track', 'PAGE_VIEW']`), or `null` if not found.
 */
export function findSnaptrEvent( queue, eventName ) {
	return (
		queue.find( ( [ command, name ] ) => {
			return command === 'track' && name === eventName;
		} ) || null
	);
}
