<?php
/**
 * Inquiry form view-model injection.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Booking;

use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Views\InquiryFormRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Puts the inquiry form's view-model into the single visa/tour
 * templates through the existing `ztc_template_view` seam — no
 * service or TemplateLoader changes. The templates render the
 * theme-overridable part only when the data is present (i.e. the type
 * is enabled in Booking settings).
 */
final class InquiryTemplateData implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param InquiryFormRenderer $renderer The shared form renderer.
	 */
	public function __construct( private InquiryFormRenderer $renderer ) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter( 'ztc_template_view', array( $this, 'inject' ), 10, 2 );
	}

	/**
	 * Add the `inquiry` view-model to single visa/tour views.
	 *
	 * @param array<string, mixed> $view View-model.
	 * @param string               $name Template name.
	 *
	 * @return array<string, mixed>
	 */
	public function inject( array $view, string $name ): array {
		$type = match ( $name ) {
			'single-visa.php' => 'visa',
			'single-tour.php' => 'tour',
			default           => '',
		};

		if ( '' === $type || array() === $view ) {
			return $view;
		}

		$view['inquiry'] = $this->renderer->data(
			array(
				'type'    => $type,
				'post_id' => (int) ( $view['id'] ?? 0 ),
			)
		);

		return $view;
	}
}
