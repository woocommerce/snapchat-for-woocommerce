<?php
/**
 * REST controller for managing Jetpack connection state.
 *
 * This controller allows connecting, disconnecting, and verifying
 * Jetpack (WordPress.com) connection status. It supports routing merchants
 * to appropriate authorization URLs and stores consent state in WordPress options.
 *
 * @package SnapchatForWooCommerce\API\Site\Controllers
 */

namespace SnapchatForWooCommerce\API\Site\Controllers;

use SnapchatForWooCommerce\Config;
use Automattic\Jetpack\Connection\Manager;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;
use WP_REST_Request as Request;

/**
 * Controller for the Jetpack connection REST endpoints.
 *
 * @since 0.1.0
 */
class JetpackAccountController extends RESTBaseController {

	/**
	 * WCS proxy request client.
	 *
	 * @var WcsClient
	 */
	protected WcsClient $wcs;

	/**
	 * Jetpack connection manager.
	 *
	 * @var Manager
	 */
	protected $manager;

	/**
	 * Cached connection state.
	 *
	 * @var bool
	 */
	private $jetpack_connected_state;

	/**
	 * Mapping of logical page names to redirect paths.
	 *
	 * @var string[]
	 */
	private const NEXT_PATH_MAPPING = array(
		'setup-snapchat' => '/snapchat/setup',
		'reconnect'      => '/snapchat/settings&subpath=/reconnect-wpcom-account',
	);

	/**
	 * Constructor.
	 *
	 * @param WcsClient $wcs    WCS proxy request client.
	 * @param Manager   $manager Jetpack connection manager.
	 */
	public function __construct( WcsClient $wcs, Manager $manager ) {
		$this->wcs     = $wcs;
		$this->manager = $manager;
	}

	/**
	 * Registers REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			Config::REST_NAMESPACE . '/jetpack',
			'/connect',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => $this->get_connect_callback(),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->get_connect_params(),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => $this->get_disconnect_callback(),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'connect_callback_schema' ),
			)
		);

		register_rest_route(
			Config::REST_NAMESPACE . '/jetpack',
			'/connected',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => $this->get_connected_callback(),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Returns callback to initiate Jetpack connection.
	 *
	 * @return callable
	 */
	protected function get_connect_callback(): callable {
		return function ( Request $request ) {
			$result = $this->manager->is_connected()
				? $this->manager->reconnect()
				: $this->manager->register();

			if ( is_wp_error( $result ) ) {
				return rest_ensure_response(
					array(
						'status'  => 'error',
						'message' => $result->get_error_message(),
					)
				);
			}

			$next     = $request->get_param( 'next_page_name' );
			$path     = self::NEXT_PATH_MAPPING[ $next ];
			$redirect = admin_url( "admin.php?page=wc-admin&path={$path}" );
			$auth_url = $this->manager->get_authorization_url( null, $redirect );

			$auth_url = esc_url(
				add_query_arg(
					array( 'from' => 'snapchat-for-woocommerce' ),
					$auth_url
				),
				null,
				'db'
			);

			return rest_ensure_response( array( 'url' => $auth_url ) );
		};
	}

	/**
	 * Returns schema for the connect endpoint.
	 *
	 * @return array
	 */
	public function connect_callback_schema(): array {
		return array(
			'title'      => 'jetpack_account',
			'type'       => 'object',
			'properties' => array(
				'url' => array(
					'type'        => 'string',
					'description' => __( 'The URL for making a connection to Jetpack (wordpress.com).', 'snapchat-for-woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Returns allowed query parameters for the connect request.
	 *
	 * @return array
	 */
	protected function get_connect_params(): array {
		return array(
			'context'        => $this->get_context_param( array( 'default' => 'view' ) ),
			'next_page_name' => array(
				'description'       => __( 'Indicates the next page name mapped to the redirect URL when back from Jetpack authorization.', 'snapchat-for-woocommerce' ),
				'type'              => 'string',
				'default'           => array_key_first( self::NEXT_PATH_MAPPING ),
				'enum'              => array_keys( self::NEXT_PATH_MAPPING ),
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Returns callback to disconnect from Jetpack.
	 *
	 * @return callable
	 */
	protected function get_disconnect_callback(): callable {
		return function () {
			$this->manager->remove_connection();
			Options::delete( OptionDefaults::WP_TOS_ACCEPTED );
			Options::delete( OptionDefaults::IS_JETPACK_CONNECTED );

			return rest_ensure_response(
				array(
					'status'  => 'success',
					'message' => __( 'Successfully disconnected.', 'snapchat-for-woocommerce' ),
				)
			);
		};
	}

	/**
	 * Returns callback to check Jetpack connection status.
	 *
	 * @return callable
	 */
	protected function get_connected_callback(): callable {
		return function () {
			Options::set( OptionDefaults::IS_JETPACK_CONNECTED, $this->is_jetpack_connected() ? 'yes' : 'no' );

			$user_data = $this->get_jetpack_user_data();

			return rest_ensure_response(
				array(
					'active'      => $this->display_boolean( $this->is_jetpack_connected() ),
					'owner'       => $this->display_boolean( $this->is_jetpack_connection_owner() ),
					'displayName' => $user_data['display_name'] ?? '',
					'email'       => $user_data['email'] ?? '',
				)
			);
		};
	}

	/**
	 * Determines if Jetpack is connected and token is valid.
	 *
	 * @return bool
	 */
	protected function is_jetpack_connected(): bool {
		if ( null !== $this->jetpack_connected_state ) {
			return $this->jetpack_connected_state;
		}

		if ( ! $this->manager->has_connected_owner() || ! $this->manager->is_connected() ) {
			$this->jetpack_connected_state = false;
			return false;
		}

		$this->jetpack_connected_state = $this->manager->get_tokens()->validate_blog_token();
		return $this->jetpack_connected_state;
	}

	/**
	 * Determines if the current user is the Jetpack connection owner.
	 *
	 * @return bool
	 */
	protected function is_jetpack_connection_owner() {
		return $this->manager->is_connection_owner();
	}

	/**
	 * Converts boolean to a string.
	 *
	 * @param bool $value Boolean value.
	 * @return string 'yes' or 'no'
	 */
	protected function display_boolean( bool $value ): string {
		return $value ? 'yes' : 'no';
	}

	/**
	 * Returns Jetpack user data for the connected account.
	 *
	 * @return array
	 */
	protected function get_jetpack_user_data(): array {
		$user_data = $this->manager->get_connected_user_data();
		return is_array( $user_data ) ? $user_data : array();
	}

	/**
	 * Logs TOS acceptance to WCS and stores local option.
	 */
	protected function log_wp_tos_accepted(): void {
		$user = wp_get_current_user();
		$this->mark_tos_accepted( $user->user_email );
		Options::set( OptionDefaults::WP_TOS_ACCEPTED, 'yes' );
	}

	/**
	 * Sends a request to WCS to log TOS acceptance.
	 *
	 * @param string $email Email address.
	 * @return mixed
	 */
	public function mark_tos_accepted( string $email ) {
		return $this->wcs->proxy_post(
			'tos',
			array(
				'body' => wp_json_encode(
					array( 'email' => $email )
				),
			)
		);
	}
}
