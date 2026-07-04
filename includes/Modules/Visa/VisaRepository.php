<?php
/**
 * Visa repository.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Visa;

use ZihadTravelCMS\Data\BaseRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Data access for Visa posts. No business logic — that lives in
 * VisaService.
 */
final class VisaRepository extends BaseRepository {

	/**
	 * {@inheritDoc}
	 */
	public function post_type(): string {
		return VisaPostType::NAME;
	}

	/**
	 * Visas for a destination country.
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
				'meta_key'       => VisaMeta::COUNTRY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $country_id,       // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
	}

	/**
	 * Visas of a given type.
	 *
	 * @param int|string $type  Visa Type term ID or slug.
	 * @param int        $limit Maximum results (-1 for all).
	 *
	 * @return array<\WP_Post>
	 */
	public function by_type( int|string $type, int $limit = -1 ): array {
		return $this->all(
			array( 'posts_per_page' => $limit ) + $this->term_args( VisaTypeTaxonomy::NAME, $type )
		);
	}
}
