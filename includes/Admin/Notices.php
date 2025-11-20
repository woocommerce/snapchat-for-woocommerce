<?php
/**
 * Displays admin notices for Snapchat for WooCommerce.
 *
 * This class is responsible for displaying admin notices for the plugin.
 *
 * @package SnapchatForWooCommerce\Admin
 * @since x.x.x
 */

namespace SnapchatForWooCommerce\Admin;

use SnapchatForWooCommerce\Utils\Helper;

/**
 * Handles admin notices for Snapchat for WooCommerce.
 *
 * Registers the `admin_notices` action to display admin notices for the plugin.
 *
 * @since x.x.x
 */
class Notices {

	/**
	 * Registers WordPress admin-side hooks.
	 *
	 * Hooks into `admin_notices` to display admin notices for the plugin.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_notices', array( $this, 'maybe_display_notice_about_legacy_snapchat_plugin' ) );
	}

	/**
	 * Displays a notice for the uninstall the legacy Snapchat plugin.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function maybe_display_notice_about_legacy_snapchat_plugin(): void {
		// Bail if the legacy Snapchat plugin is not active.
		if ( ! Helper::is_legacy_snapchat_plugin_active() ) {
			return;
		}

		$notice_message = sprintf(
			/* translators: %1$s and %2$s are placeholders for the link to the documentation. */
			__( 'You currently have two Snapchat plugins installed. Having both plugins active can cause reporting issues. Please uninstall the \'Snapchat Pixel for WooCommerce\' (Legacy Plugin) by following the steps %1$shere%2$s.', 'snapchat-for-woocommerce' ),
			'<a href="https://woocommerce.com/document/snapchat-for-woocommerce/#section-4" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				echo wp_kses(
					$notice_message,
					array(
						'a' => array(
							'href'   => true,
							'target' => true,
							'rel'    => true,
						),
					)
				);
				?>
			</p>
		</div>
		<?php
	}
}
