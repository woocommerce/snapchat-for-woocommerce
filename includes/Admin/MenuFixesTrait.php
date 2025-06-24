<?php
declare(strict_types=1);

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
		$defaults = [
			'capability' => 'manage_woocommerce',
			'position'   => null,
		];

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
			[ PageController::class, 'page_wrapper' ],
			$options['position']
		);

		PageController::get_instance()->connect_page( $options );
	}
}
