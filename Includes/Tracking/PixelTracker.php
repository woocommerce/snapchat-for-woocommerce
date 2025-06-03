<?php

namespace SnapchatForWooCommerce\Tracking;

interface PixelTracker {
	public function maybe_inject_pixel(): void;
}
