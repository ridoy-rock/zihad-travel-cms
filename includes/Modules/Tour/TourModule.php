<?php
/**
 * Tour module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Tour;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Tour packages: the Tour post type, its meta fields and the Tour Type
 * taxonomy. Data access via TourRepository, business logic via
 * TourService.
 */
final class TourModule extends BaseModule {

	/**
	 * Constructor.
	 *
	 * @param TourPostType     $post_type The Tour post type.
	 * @param TourTypeTaxonomy $tour_type The Tour Type taxonomy.
	 * @param TourMeta         $meta      The Tour meta fields.
	 * @param TourEditor       $editor    The tabbed Tour editor.
	 */
	public function __construct(
		private TourPostType $post_type,
		private TourTypeTaxonomy $tour_type,
		private TourMeta $meta,
		private TourEditor $editor,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'tour';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function components(): array {
		$components = array( $this->post_type, $this->tour_type, $this->meta );

		// The editor only exists in wp-admin.
		if ( is_admin() ) {
			$components[] = $this->editor;
		}

		return $components;
	}
}
