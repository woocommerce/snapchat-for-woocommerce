<?php
namespace SnapchatForWoocommerce\Contracts;

interface PixelTrackerInterface {
	public function register_pixel_hooks(): void;
}
