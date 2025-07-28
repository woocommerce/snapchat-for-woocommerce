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

/**
 * Registers a REST API endpoint to switch the current WordPress theme.
 * This is used in E2E tests to toggle between classic and block themes.
 *
 * Route: POST /wp-json/snapchat-e2e/v1/switch-theme
 * Body:  { "theme": "twentytwentyfour" }
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'snapchat-e2e/v1', '/switch-theme', array(
		'methods'             => 'POST',
		'callback'            => 'snapchat_e2e_switch_theme_callback',
		'permission_callback' => '__return_true',
		'args'                => array(
			'theme' => array(
				'type'     => 'string',
				'required' => true,
			),
		),
	) );
} );

/**
 * Switches the active theme to the given one.
 *
 * @param WP_REST_Request $request REST request object.
 * @return WP_REST_Response
 */
function snapchat_e2e_switch_theme_callback( WP_REST_Request $request ) {
	if ( ! defined( 'E2E_CONTEXT' ) || ! E2E_CONTEXT ) {
		return new WP_REST_Response( array(
			'error' => 'Endpoint only allowed in E2E context.',
		), 403 );
	}

	$theme_slug = $request->get_param( 'theme' );

	if ( ! wp_get_theme( $theme_slug )->exists() ) {
		return new WP_REST_Response( array(
			'error' => "Theme '$theme_slug' does not exist.",
		), 400 );
	}

	switch_theme( $theme_slug );

	return new WP_REST_Response( array(
		'success' => true,
		'message' => "Theme switched to '$theme_slug'.",
	) );
}
