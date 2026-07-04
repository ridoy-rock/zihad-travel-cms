<?php
/**
 * Country repository.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Country;

use ZihadTravelCMS\Data\BaseRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Data access for Country posts. No business logic — that lives in
 * CountryService.
 */
final class CountryRepository extends BaseRepository {

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return CountryPostType::NAME;
	}

	/**
	 * Countries in a region.
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
