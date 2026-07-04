<?php
/**
 * Booking / inquiry module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Booking;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Visa and tour inquiry forms: a private Inquiry post type, one shared
 * validation/persistence/notification pipeline (InquiryService), a
 * nonce-protected no-JS form handler plus a validated public REST
 * endpoint, and admin notifications through the Mailer contract.
 * Rendering is shared by the shortcode, the Elementor widget and the
 * single visa/tour templates via InquiryFormRenderer.
 *
 * Version 2.0 grows this module into real bookings (availability, payments) —
 * the CPT and pipeline are the foundation.
 */
final class BookingModule extends BaseModule {

	/**
	 * Constructor.
	 *
	 * @param InquiryPostType     $post_type     The Inquiry post type.
	 * @param InquiryMeta         $meta          The Inquiry meta fields.
	 * @param InquiryFormHandler  $form_handler  No-JS admin-post handler.
	 * @param InquiryTemplateData $template_data Single-template view data.
	 * @param InquiryColumns      $columns       Admin list columns.
	 */
	public function __construct(
		private InquiryPostType $post_type,
		private InquiryMeta $meta,
		private InquiryFormHandler $form_handler,
		private InquiryTemplateData $template_data,
		private InquiryColumns $columns,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'booking';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter(
			'ztc_rest_controllers',
			static function ( array $controllers ): array {
				$controllers[] = InquiryController::class;

				return $controllers;
			}
		);

		parent::register();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function components(): array {
		// The admin-post handler serves logged-out visitors too, so it
		// registers everywhere; columns are admin-only, the template
		// data frontend-only.
		$components = array( $this->post_type, $this->meta, $this->form_handler );

		$components[] = is_admin() ? $this->columns : $this->template_data;

		return $components;
	}
}
