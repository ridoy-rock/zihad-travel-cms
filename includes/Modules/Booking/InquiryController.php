<?php
/**
 * Inquiry REST controller.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Booking;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use ZihadTravelCMS\RestApi\RestApiServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * POST /wp-json/ztc/v1/inquiry — the endpoint behind the enhanced
 * (JavaScript) form submit, and the public API for headless frontends.
 *
 * Deliberately public (visitors submit inquiries), so protection comes
 * from the pipeline itself: strict arg validation below, per-field
 * sanitization, the honeypot `website` arg (must stay empty) and the
 * per-IP rate limit inside InquiryService.
 */
final class InquiryController {

	/**
	 * Constructor.
	 *
	 * @param InquiryService $inquiries The shared inquiry pipeline.
	 */
	public function __construct( private InquiryService $inquiries ) {}

	/**
	 * Register the /inquiry route.
	 */
	public function register_routes(): void {
		register_rest_route(
			RestApiServiceProvider::REST_NAMESPACE,
			'/inquiry',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true', // Public submissions; spam controls live in the pipeline.
				'args'                => array(
					'name'    => array(
						'description'       => __( 'Visitor name.', 'zihad-travel-cms' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'email'   => array(
						'description'       => __( 'Visitor email address.', 'zihad-travel-cms' ),
						'type'              => 'string',
						'format'            => 'email',
						'required'          => true,
						'sanitize_callback' => 'sanitize_email',
					),
					'phone'   => array(
						'description'       => __( 'Optional phone/WhatsApp number.', 'zihad-travel-cms' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'message' => array(
						'description'       => __( 'The inquiry message (max 5000 characters).', 'zihad-travel-cms' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'type'    => array(
						'description' => __( 'Inquiry type.', 'zihad-travel-cms' ),
						'type'        => 'string',
						'enum'        => array( 'visa', 'tour' ),
						'required'    => true,
					),
					'post_id' => array(
						'description' => __( 'Related visa/tour post ID (0 for a general inquiry).', 'zihad-travel-cms' ),
						'type'        => 'integer',
						'default'     => 0,
						'minimum'     => 0,
					),
					'website' => array(
						'description'       => __( 'Honeypot — must be left empty.', 'zihad-travel-cms' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Handle a submission through the shared pipeline.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$result = $this->inquiries->submit( $request->get_params() );

		$response = new WP_REST_Response(
			array(
				'success' => InquiryService::SENT === $result['status'],
				'message' => $result['message'],
				'errors'  => $result['errors'],
			)
		);

		if ( InquiryService::LIMITED === $result['status'] ) {
			$response->set_status( 429 );
		} elseif ( InquiryService::INVALID === $result['status'] ) {
			$response->set_status( 400 );
		}

		return $response;
	}
}
