<?php
/**
 * Search REST controller.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Search;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\RestApi\RestApiServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * GET /wp-json/ztc/v1/search — the endpoint behind the AJAX
 * search/filter UI, and public API for headless clients.
 *
 * Read-only and nonce-free by design: results contain only published
 * content, so responses are safely CDN/page-cacheable (Cache-Control
 * is set accordingly).
 */
final class SearchController {

	/**
	 * Constructor.
	 *
	 * @param SearchService $search Search service.
	 * @param Config        $config Plugin configuration (cache TTL).
	 */
	public function __construct(
		private SearchService $search,
		private Config $config,
	) {}

	/**
	 * Register the /search route.
	 */
	public function register_routes(): void {
		register_rest_route(
			RestApiServiceProvider::REST_NAMESPACE,
			'/search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'type'      => array(
						'type'    => 'string',
						'enum'    => array( 'tour', 'visa', 'country' ),
						'default' => 'tour',
					),
					's'         => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page'      => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page'  => array(
						'type'    => 'integer',
						'default' => 9,
						'minimum' => 1,
						'maximum' => 24,
					),
					'region'    => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'tour_type' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'visa_type' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'country'   => array(
						'type'    => 'integer',
						'default' => 0,
						'minimum' => 0,
					),
					'min_price' => array(
						'type'    => 'number',
						'default' => 0,
						'minimum' => 0,
					),
					'max_price' => array(
						'type'    => 'number',
						'default' => 0,
						'minimum' => 0,
					),
					'orderby'   => array(
						'type'    => 'string',
						'enum'    => array( 'date', 'price', 'title' ),
						'default' => 'date',
					),
				),
			)
		);
	}

	/**
	 * Handle a search request.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$response = new WP_REST_Response( $this->search->search( $request->get_params() ) );

		// Public, read-only data: let CDNs and proxies cache for the
		// TTL configured in Settings → Performance.
		$ttl = max( 0, (int) $this->config->get( 'performance.cache_ttl', 300 ) );
		$response->header( 'Cache-Control', 'public, max-age=' . $ttl );

		return $response;
	}
}
