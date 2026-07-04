<?php
/**
 * Tour Type taxonomy.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Tour;

use ZihadTravelCMS\Taxonomies\BaseTaxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Tour categories (Adventure, Honeymoon, Group…). Archives live at
 * /tour-type/.
 */
final class TourTypeTaxonomy extends BaseTaxonomy {

	/**
	 * The taxonomy name.
	 */
	public const NAME = 'ztc_tour_type';

	/**
	 * {@inheritDoc}
	 */
	public function taxonomy(): string {
		return self::NAME;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function object_types(): array {
		return array( TourPostType::NAME );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function singular_label(): string {
		return __( 'Tour Type', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function plural_label(): string {
		return __( 'Tour Types', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function overrides(): array {
		return array(
			// The Tour editor's General tab has a Tour Type selector;
			// hide the classic-editor metabox so the UI isn't duplicated.
			'meta_box_cb' => false,
		);
	}
}
