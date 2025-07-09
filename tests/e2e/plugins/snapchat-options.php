<?php
/**
 * Plugin Name: Snapchat Options
 */

use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;

if ( class_exists( Options::class ) ) {
	// Options::set( OptionDefaults::ORGANIZATION_ID, '0876b15f-518b-4e8d-93a7-2e83b8320a00' );
	// Options::set( OptionDefaults::ADS_ACCOUNT_ID, 'be1d1a65-e320-456f-8a49-68999aee27c5' );
	Options::set( OptionDefaults::PIXEL_ENABLED, 'yes' );
	Transients::set( TransientDefaults::PIXEL_SCRIPT, '<script>var x="https://sc-static.net/scevent.min.js";window.snaptr=function(){window.snaptr.queue.push(Array.from(arguments))},window.snaptr.queue=[],snaptr("track","PAGE_VIEW");</script>' );
}
