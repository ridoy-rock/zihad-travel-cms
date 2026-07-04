<?php
/**
 * Inquiry form handler (no-JS path).
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Booking;

use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * The progressive-enhancement baseline: the inquiry form POSTs to
 * admin-post.php (logged-in and logged-out), runs through the same
 * pipeline as the REST endpoint, and redirects back to the page with a
 * result flag the form part renders. Nonce-protected — JavaScript only
 * ever improves on this, it never replaces it.
 */
final class InquiryFormHandler implements Registrable {

	public const ACTION = 'ztc_inquiry';

	/**
	 * Constructor.
	 *
	 * @param InquiryService $inquiries The shared inquiry pipeline.
	 */
	public function __construct( private InquiryService $inquiries ) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Validate the nonce, run the pipeline and bounce back to the form.
	 */
	public function handle(): void {
		if ( false === check_admin_referer( self::ACTION ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- nonce checked above; the pipeline sanitizes every field.
		$result = $this->inquiries->submit( wp_unslash( $_POST ) );

		// Jump back to the exact form instance (stable anchor).
		$anchor = sprintf(
			'#ztc-inquiry-%s-%d',
			sanitize_key( is_scalar( $_POST['type'] ?? '' ) ? (string) $_POST['type'] : '' ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			absint( is_scalar( $_POST['post_id'] ?? 0 ) ? (string) $_POST['post_id'] : '0' ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);

		$back = wp_get_referer();
		$back = is_string( $back ) && '' !== $back ? $back : home_url( '/' );

		wp_safe_redirect( add_query_arg( 'ztc_inquiry', $result['status'], $back ) . $anchor );
		exit;
	}
}
