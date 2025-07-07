<?php
/**
 * Base controller for Settings REST endpoints in the Ad Partner for WooCommerce plugin.
 *
 * This abstract class provides common functionality for all REST controllers
 * within the settings namespace of the plugin. It handles permission checks
 * and response formatting, and should be extended by all plugin-specific
 * settings controllers instead of using WC_REST_Controller directly.
 *
 * @package SnapchatForWooCommerce\API\Site\Controllers
 */

namespace SnapchatForWooCommerce\API\Site\Controllers;

use WC_REST_Controller;
use SnapchatForWooCommerce\API\AdminPermissionsTrait;

/**
 * Abstract base controller for Ad Partner Settings REST API.
 *
 * Provides standardized permission checks and response wrapping for all
 * settings-related endpoints in the plugin.
 *
 * @since 0.1.0
 */
class RESTBaseController extends WC_REST_Controller {
	use AdminPermissionsTrait;
}
