<?php
/**
 * Import/export REST controller.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer;

use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use ZihadTravelCMS\RestApi\RestApiServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Admin-only REST endpoints under `ztc/v1`:
 *
 *  - POST /import/start    {type, media_id, mode, rollback_on_failure}
 *  - POST /import/process  {job_id, batch} — called in a loop by the
 *                          progress bar; safe to re-call after an
 *                          interruption (resume).
 *  - GET  /import/status   {job_id}
 *  - GET  /import/jobs
 *  - POST /import/rollback {job_id}
 *  - GET  /export          {type, format} → {filename, mime, body}
 *
 * Files are referenced by media library attachment ID — never by raw
 * path — so REST callers can only import files they uploaded.
 */
final class ImportController {

	/**
	 * Constructor.
	 *
	 * @param ImportService $import Import engine.
	 * @param ExportService $export Export engine.
	 * @param JobRepository $jobs   Job repository.
	 */
	public function __construct(
		private ImportService $import,
		private ExportService $export,
		private JobRepository $jobs,
	) {}

	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		$namespace  = RestApiServiceProvider::REST_NAMESPACE;
		$permission = static fn(): bool => current_user_can( 'manage_options' );

		register_rest_route(
			$namespace,
			'/import/start',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'start' ),
				'permission_callback' => $permission,
				'args'                => array(
					'type'                => array(
						'type'     => 'string',
						'required' => true,
					),
					'media_id'            => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
					),
					'mode'                => array(
						'type'    => 'string',
						'enum'    => ImportService::MODES,
						'default' => 'upsert',
					),
					'rollback_on_failure' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/import/process',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process' ),
				'permission_callback' => $permission,
				'args'                => array(
					'job_id' => array(
						'type'     => 'string',
						'required' => true,
					),
					'batch'  => array(
						'type'    => 'integer',
						'default' => 20,
						'minimum' => 1,
						'maximum' => 100,
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/import/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'status' ),
				'permission_callback' => $permission,
				'args'                => array(
					'job_id' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/import/jobs',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'jobs' ),
				'permission_callback' => $permission,
			)
		);

		register_rest_route(
			$namespace,
			'/import/rollback',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rollback' ),
				'permission_callback' => $permission,
				'args'                => array(
					'job_id' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/export',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'do_export' ),
				'permission_callback' => $permission,
				'args'                => array(
					'type'   => array(
						'type'     => 'string',
						'required' => true,
					),
					'format' => array(
						'type'    => 'string',
						'enum'    => ExportService::FORMATS,
						'default' => 'json',
					),
				),
			)
		);
	}

	/**
	 * Start a job from an uploaded media file.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function start( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$file = (string) get_attached_file( (int) $request->get_param( 'media_id' ) );

		if ( '' === $file ) {
			return new WP_Error( 'ztc_import_file', __( 'The uploaded file could not be found.', 'zihad-travel-cms' ), array( 'status' => 400 ) );
		}

		return $this->guard(
			fn(): array => $this->import->start(
				(string) $request->get_param( 'type' ),
				$file,
				(string) $request->get_param( 'mode' ),
				(bool) $request->get_param( 'rollback_on_failure' )
			)->to_array()
		);
	}

	/**
	 * Process the next batch.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function process( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->guard(
			fn(): array => $this->import->process(
				(string) $request->get_param( 'job_id' ),
				(int) $request->get_param( 'batch' )
			)->to_array()
		);
	}

	/**
	 * A job's current state (progress + error log).
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function status( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$job = $this->jobs->find( (string) $request->get_param( 'job_id' ) );

		if ( null === $job ) {
			return new WP_Error( 'ztc_import_job', __( 'Unknown import job.', 'zihad-travel-cms' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $job->to_array() );
	}

	/**
	 * All known jobs.
	 */
	public function jobs(): WP_REST_Response {
		return new WP_REST_Response(
			array_map( static fn( ImportJob $job ): array => $job->to_array(), $this->jobs->all() )
		);
	}

	/**
	 * Roll a job back.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function rollback( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->guard(
			fn(): array => $this->import->rollback( (string) $request->get_param( 'job_id' ) )->to_array()
		);
	}

	/**
	 * Export as {filename, mime, body} for a client-side download.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function do_export( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		return $this->guard(
			fn(): array => $this->export->export(
				(string) $request->get_param( 'type' ),
				(string) $request->get_param( 'format' )
			)
		);
	}

	/**
	 * Convert engine exceptions into proper REST errors.
	 *
	 * @param callable $operation Operation producing the response payload array.
	 */
	private function guard( callable $operation ): WP_REST_Response|WP_Error {
		try {
			return new WP_REST_Response( $operation() );
		} catch ( Throwable $e ) {
			return new WP_Error( 'ztc_import_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}
}
