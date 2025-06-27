<?php
/**
 * Trait for fixing Woo Admin submenu registration behavior.
 *
 * This trait provides a workaround for registering React-powered WooCommerce Admin pages
 * under existing classic menu slugs (like "Marketing") while preserving:
 * - Correct menu ordering
 * - Proper submenu slugs and paths
 * - Compatibility with Woo Admin's PageController system
 *
 * It is used when registering JS-based admin pages in WooCommerce that need to
 * appear in classic submenu hierarchies but are powered by the new `wc-admin` routing.
 *
 * Inspired by how the Google for WooCommerce plugin ensures its page appears under
 * the Marketing menu without breaking Woo Admin's path structure.
 *
 * @package SnapchatForWooCommerce\Admin
 * @since 0.1.0
 */

namespace SnapchatForWooCommerce\Admin;

use Automattic\WooCommerce\Admin\PageController;

/**
 * Trait MenuFixesTrait
 *
 * Adds Woo Admin React-powered submenu under Marketing with proper ordering and slug fix.
 *
 * @package SnapchatForWooCommerce\Admin
 */
trait MenuFixesTrait {
	/**
	 * Registers a React-powered page using classic `add_submenu_page` for compatibility.
	 *
	 * @param array $options {
	 *   @type string $id          ID for the page.
	 *   @type string $title       Title shown in menu.
	 *   @type string $parent      Slug of the parent menu.
	 *   @type string $path        URL path after wc-admin.
	 *   @type string $capability  User capability. Default: manage_woocommerce.
	 *   @type int|null $position  Position in submenu.
	 * }
	 */
	protected function register_classic_submenu_page( array $options ): void {
		$defaults = array(
			'capability' => 'manage_woocommerce',
			'position'   => null,
		);

		$options            = wp_parse_args( $options, $defaults );
		$options['js_page'] = true;

		if ( 0 !== strpos( $options['path'], PageController::PAGE_ROOT ) ) {
			$options['path'] = PageController::PAGE_ROOT . '&path=' . $options['path'];
		}

		add_submenu_page(
			$options['parent'],
			$options['title'],
			$options['title'],
			$options['capability'],
			$options['path'],
			array( PageController::class, 'page_wrapper' ),
			$options['position']
		);

		PageController::get_instance()->connect_page( $options );
	}
}
