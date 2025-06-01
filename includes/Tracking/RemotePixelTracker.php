<?php
namespace SnapchatForWoocommerce\Tracking;

use SnapchatForWoocommerce\Contracts\PixelTrackerInterface;

abstract class RemotePixelTracker implements PixelTrackerInterface {
	public function register_pixel_hooks(): void {
		add_action( 'wp_head', [ $this, 'inject_pixel' ] );
	}

	public function inject_pixel(): void {
		$snippet = $this->get_tracking_snippet();

		if ( $snippet ) {
			echo "\n<!-- Ad Partner Pixel -->\n" . $snippet . "\n<!-- End Ad Partner Pixel -->\n";
		}
	}

	/**
	 * Each Ad Partner implements how to fetch the actual snippet.
	 */
	abstract protected function get_tracking_snippet(): ?string;
}
