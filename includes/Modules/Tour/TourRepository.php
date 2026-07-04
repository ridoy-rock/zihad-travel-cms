<?php
/**
 * Tour repository.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Tour;

use ZihadTravelCMS\Data\BaseRepository;
use ZihadTravelCMS\Modules\Country\RegionTaxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Data access for Tour posts. No business logic — that lives in
 * TourService.
 */
final class TourRepository extends BaseRepository {

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return TourPostType::NAME;
	}

	/**
	 * Tours for a destination country.
	 *
	 * @param int $country_id Country post ID.
	 * @param int $limit      Maximum results (-1 for all).
	 *
	 * @return array<\WP_Post>
	 */
	public function by_country( int $country_id, int $limit = -1 ): array {
		return $this->all(
			array(
				'posts_per_page' => $limit,
				'meta_key'       => TourMeta::COUNTRY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $country_id,       // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
	}

	/**
	 * Tours of a given type.
	 *
	 * @param int|string $type  Tour Type term ID or slug.
	 * @param int        $limit Maximum results (-1 for all).
	 *
	 * @return array<\WP_Post>
	 */
	public function by_type( int|string $type, int $limit = -1 ): array {
		return $this->all(
			array( 'posts_per_page' => $limit ) + $this->term_args( TourTypeTaxonomy::NAME, $type )
		);
	}

	/**
	 * Tours in a region.
	 *
	 * @param int|string $region Region term ID or slug.
	 * @param int        $limit  Maximum results (-1 for all).
	 *
	 * @return array<\WP_Post>
	 */
	public function by_region( int|string $region, int $limit = -1 ): array {
		return $this->all(
			array( 'posts_per_page' => $limit ) + $this->term_args( RegionTaxonomy::NAME, $region )
		);
	}
}
