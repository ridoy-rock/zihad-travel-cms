<?php
/**
 * Demo data REST controller.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\DemoData;

use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use ZihadTravelCMS\RestApi\RestApiServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Admin-only endpoints under `ztc/v1`:
 *
 *  - POST /demo/generate {locale} — (re)build the demo JSON files.
 *  - POST /demo/start {type}      — start one demo import job; the
 *    client then drives the importer's own /import/process loop, so
 *    progress, error logs and resume come for free.
 */
final class DemoDataController {

	/**
	 * Constructor.
	 *
	 * @param DemoContentGenerator $generator The generator.
	 * @param DemoDataInstaller    $installer The installer.
	 */
	public function __construct(
		private DemoContentGenerator $generator,
		private DemoDataInstaller $installer,
	) {}

	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		$permission = static fn(): bool => current_user_can( 'manage_options' );

		register_rest_route(
			RestApiServiceProvider::REST_NAMESPACE,
			'/demo/generate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate' ),
				'permission_callback' => $permission,
				'args'                => array(
					'locale' => array(
						'type'    => 'string',
						'enum'    => DemoContentGenerator::LOCALES,
						'default' => 'en',
					),
				),
			)
		);

		register_rest_route(
			RestApiServiceProvider::REST_NAMESPACE,
			'/demo/start',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'start' ),
				'permission_callback' => $permission,
				'args'                => array(
					'type' => array(
						'type'     => 'string',
						'enum'     => DemoDataInstaller::TYPES,
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Regenerate the demo files.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function generate( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->guard( fn(): array => $this->generator->generate( (string) $request->get_param( 'locale' ) ) );
	}

	/**
	 * Start one demo import job.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function start( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->guard( fn(): array => $this->installer->start( (string) $request->get_param( 'type' ) )->to_array() );
	}

	/**
	 * Convert exceptions into REST errors.
	 *
	 * @param callable $operation Operation producing the response payload array.
	 */
	private function guard( callable $operation ): WP_REST_Response|WP_Error {
		try {
			return new WP_REST_Response( $operation() );
		} catch ( Throwable $e ) {
			return new WP_Error( 'ztc_demo_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}
}
