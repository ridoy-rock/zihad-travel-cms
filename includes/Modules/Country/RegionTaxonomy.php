<?php
/**
 * Region taxonomy.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Country;

use ZihadTravelCMS\Modules\Tour\TourPostType;
use ZihadTravelCMS\Taxonomies\BaseTaxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Geographic regions (Europe, Southeast Asia…). Archives live at
 * /region/.
 *
 * Attached to both Countries and Tours so visitors can browse either
 * by region. It lives in the Country module because regions are
 * fundamentally geography.
 */
final class RegionTaxonomy extends BaseTaxonomy {

	/**
	 * The taxonomy name.
	 */
	public const NAME = 'ztc_region';

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
		return array( CountryPostType::NAME, TourPostType::NAME );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function singular_label(): string {
		return __( 'Region', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function plural_label(): string {
		return __( 'Regions', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function overrides(): array {
		return array(
			// The Country editor's General tab has a Region selector;
			// hide the classic-editor metabox so the UI isn't duplicated.
			'meta_box_cb' => false,
		);
	}
}
