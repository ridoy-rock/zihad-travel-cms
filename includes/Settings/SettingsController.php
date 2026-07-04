<?php
/**
 * Settings REST controller.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Settings;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\RestApi\RestApiServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Admin-only settings API under `ztc/v1`:
 *
 *  - GET  /settings — the full, defaults-merged settings tree.
 *  - POST /settings — partial update; the payload is merged over the
 *    current settings and shape-checked by SettingsSanitizer (same
 *    gate as the admin form).
 */
final class SettingsController {

	/**
	 * Constructor.
	 *
	 * @param Config            $config    Plugin configuration.
	 * @param SettingsSanitizer $sanitizer Structural sanitizer.
	 */
	public function __construct(
		private Config $config,
		private SettingsSanitizer $sanitizer,
	) {}

	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		$permission = static fn(): bool => current_user_can( 'manage_options' );

		register_rest_route(
			RestApiServiceProvider::REST_NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => $permission,
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => $permission,
					'args'                => array(
						'settings' => array(
							'type'     => 'object',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * The full settings tree.
	 */
	public function get_settings(): WP_REST_Response {
		return new WP_REST_Response( $this->config->all() );
	}

	/**
	 * Merge a partial update over the current settings.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function update_settings( WP_REST_Request $request ): WP_REST_Response {
		$incoming = $this->sanitizer->sanitize( $request->get_param( 'settings' ) );
		$merged   = array_replace_recursive( $this->config->all(), $incoming );

		update_option( Config::OPTION_NAME, $this->sanitizer->sanitize( $merged ) );
		$this->config->refresh();

		return new WP_REST_Response( $this->config->all() );
	}
}
