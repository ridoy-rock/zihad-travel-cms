<?php
/**
 * Inquiry form renderer.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Views;

use ZihadTravelCMS\Core\Assets;
use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Modules\Booking\InquiryFormHandler;
use ZihadTravelCMS\Modules\Booking\InquiryService;

defined( 'ABSPATH' ) || exit;

/**
 * The single render path for the inquiry form — shared verbatim by the
 * [ztc_inquiry_form] shortcode, the Elementor widget and the single
 * visa/tour templates. Markup lives in the theme-overridable
 * `frontend/parts/inquiry-form.php` part.
 *
 * The form works without JavaScript (admin-post + redirect + result
 * flag); frontend.js upgrades it to an inline REST submit.
 */
final class InquiryFormRenderer {

	/**
	 * Constructor.
	 *
	 * @param InquiryService $inquiries The inquiry pipeline (toggles, messages).
	 * @param Template       $template  Template renderer.
	 */
	public function __construct(
		private InquiryService $inquiries,
		private Template $template,
	) {}

	/**
	 * Render the form ('' when the type's inquiries are disabled).
	 *
	 * @param array<string, mixed> $args `type` (visa|tour), `post_id`
	 *                                   (related post, 0 = general),
	 *                                   `heading` (optional).
	 */
	public function render( array $args = array() ): string {
		$data = $this->data( $args );

		if ( array() === $data ) {
			return '';
		}

		wp_enqueue_style( Assets::STYLE_FRONTEND );
		wp_enqueue_script( Assets::SCRIPT_FRONTEND );

		return $this->template->get( 'frontend/parts/inquiry-form.php', $data );
	}

	/**
	 * Build the form view-model (empty when disabled in Booking
	 * settings — surfaces hide themselves).
	 *
	 * @param array<string, mixed> $args See render().
	 *
	 * @return array<string, mixed>
	 */
	public function data( array $args = array() ): array {
		$type = in_array( (string) ( $args['type'] ?? '' ), array( 'visa', 'tour' ), true )
			? (string) $args['type']
			: 'visa';

		if ( ! $this->inquiries->enabled( $type ) ) {
			return array();
		}

		$post_id = max( 0, (int) ( $args['post_id'] ?? 0 ) );

		$data = array(
			// Stable per-target anchor: the no-JS redirect jumps back here.
			'uid'             => 'ztc-inquiry-' . $type . '-' . $post_id,
			'type'            => $type,
			'post_id'         => $post_id,
			'heading'         => (string) ( $args['heading'] ?? $this->default_heading( $type ) ),
			'action'          => admin_url( 'admin-post.php' ),
			'form_action'     => InquiryFormHandler::ACTION,
			'status'          => $this->requested_status(),
			'success_message' => $this->inquiries->success_message(),
		);

		/**
		 * Filter the inquiry form view-model before rendering.
		 *
		 * @param array<string, mixed> $data View-model.
		 * @param array<string, mixed> $args Render arguments.
		 */
		return (array) apply_filters( 'ztc_inquiry_form_data', $data, $args );
	}

	/**
	 * The no-JS result flag from the redirect ('' on a fresh view).
	 */
	private function requested_status(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag set by our own redirect.
		$status = sanitize_key( (string) wp_unslash( $_GET['ztc_inquiry'] ?? '' ) );

		return in_array( $status, array( InquiryService::SENT, InquiryService::INVALID, InquiryService::LIMITED ), true )
			? $status
			: '';
	}

	/**
	 * Translated default heading per type.
	 *
	 * @param string $type Content type.
	 */
	private function default_heading( string $type ): string {
		return 'visa' === $type
			? __( 'Ask about this visa', 'zihad-travel-cms' )
			: __( 'Ask about this tour', 'zihad-travel-cms' );
	}
}
