<?php
/**
 * Plugin Name: Snapchat Options
 * Description: This plugin is used to seed Options and Transients to test the Snapchat for WooCommerce plugin.
 */
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\TransientDefaults;
use SnapchatForWooCommerce\Utils\Storage\Transients;

if ( class_exists( Options::class ) && defined( 'E2E_CONTEXT' ) ) {
	Transients::set( TransientDefaults::PIXEL_SCRIPT, '<script>var x="https://sc-static.net/scevent.min.js";window.snaptr=function(){window.snaptr.queue.push(Array.from(arguments))},window.snaptr.queue=[],snaptr("track","PAGE_VIEW");</script>' );
}
