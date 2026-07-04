<?php
/**
 * Search service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Search;

use WP_Query;
use ZihadTravelCMS\Modules\Country\RegionTaxonomy;
use ZihadTravelCMS\Modules\Tour\TourMeta;
use ZihadTravelCMS\Modules\Tour\TourTypeTaxonomy;
use ZihadTravelCMS\Modules\Visa\VisaMeta;
use ZihadTravelCMS\Modules\Visa\VisaTypeTaxonomy;
use ZihadTravelCMS\Views\GridRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Keyword search + filtering across tours, visas and countries.
 *
 * Business logic only: translates request parameters into a WP_Query
 * and shapes the result. The REST layer lives in SearchController;
 * the card HTML comes from the shared GridRenderer, so AJAX results
 * are pixel-identical to server-rendered grids.
 */
final class SearchService {

	/**
	 * Constructor.
	 *
	 * @param GridRenderer $grids Card grid renderer.
	 */
	public function __construct( private GridRenderer $grids ) {}

	/**
	 * Run a search.
	 *
	 * @param array<string, mixed> $params Request parameters (validated
	 *                                     by the REST args schema).
	 *
	 * @return array{items: array<array<string, mixed>>, total: int, pages: int, page: int}
	 */
	public function search( array $params ): array {
		$type = isset( GridRenderer::TYPES[ $params['type'] ?? '' ] ) ? (string) $params['type'] : 'tour';
		$page = max( 1, (int) ( $params['page'] ?? 1 ) );

		$query = new WP_Query( $this->query_args( $type, $page, $params ) );

		$items = array();

		foreach ( $query->posts as $post ) {
			$items[] = array(
				'id'    => (int) $post->ID,
				'title' => (string) $post->post_title,
				'url'   => (string) get_permalink( $post ),
				'html'  => $this->grids->card_for( $type, (int) $post->ID ),
			);
		}

		return array(
			'items' => $items,
			'total' => (int) $query->found_posts,
			'pages' => (int) $query->max_num_pages,
			'page'  => $page,
		);
	}

	/**
	 * Translate request parameters into WP_Query args.
	 *
	 * @param string               $type   Content type: tour|visa|country.
	 * @param int                  $page   Requested page.
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return array<string, mixed>
	 */
	private function query_args( string $type, int $page, array $params ): array {
		$args = array(
			'post_type'      => GridRenderer::TYPES[ $type ],
			'post_status'    => 'publish',
			'paged'          => $page,
			'posts_per_page' => max( 1, min( 24, (int) ( $params['per_page'] ?? 9 ) ) ),
		);

		$keyword = trim( (string) ( $params['s'] ?? '' ) );
		if ( '' !== $keyword ) {
			$args['s'] = $keyword;
		}

		$clauses = $this->filter_clauses( $type, $params );

		if ( array() !== $clauses['tax_query'] ) {
			$args['tax_query'] = $clauses['tax_query']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		if ( array() !== $clauses['meta_query'] ) {
			$args['meta_query'] = $clauses['meta_query']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		if ( 'price' === ( $params['orderby'] ?? '' ) && 'tour' === $type ) {
			$args['meta_key'] = TourMeta::PRICE; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'ASC';
		} elseif ( 'title' === ( $params['orderby'] ?? '' ) ) {
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
		}

		/**
		 * Filter the search WP_Query args.
		 *
		 * @param array<string, mixed> $args   Query args.
		 * @param array<string, mixed> $params Request parameters.
		 */
		return (array) apply_filters( 'ztc_search_query_args', $args, $params );
	}

	/**
	 * The taxonomy and meta clauses for a set of filter parameters.
	 *
	 * Public so other query paths (the archive no-JS filters) apply
	 * exactly the same rules as the REST search — one translation of
	 * request parameters into SQL clauses, everywhere.
	 *
	 * @param string               $type   Content type: tour|visa|country.
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return array{tax_query: array<array<string, mixed>>, meta_query: array<array<string, mixed>>}
	 */
	public function filter_clauses( string $type, array $params ): array {
		return array(
			'tax_query'  => $this->tax_query( $type, $params ),  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'meta_query' => $this->meta_query( $type, $params ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);
	}

	/**
	 * Taxonomy clauses for the requested filters.
	 *
	 * @param string               $type   Content type.
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return array<array<string, mixed>>
	 */
	private function tax_query( string $type, array $params ): array {
		$clauses = array();

		$region = (string) ( $params['region'] ?? '' );
		if ( '' !== $region && in_array( $type, array( 'tour', 'country' ), true ) ) {
			$clauses[] = $this->term_clause( RegionTaxonomy::NAME, $region );
		}

		$tour_type = (string) ( $params['tour_type'] ?? '' );
		if ( '' !== $tour_type && 'tour' === $type ) {
			$clauses[] = $this->term_clause( TourTypeTaxonomy::NAME, $tour_type );
		}

		$visa_type = (string) ( $params['visa_type'] ?? '' );
		if ( '' !== $visa_type && 'visa' === $type ) {
			$clauses[] = $this->term_clause( VisaTypeTaxonomy::NAME, $visa_type );
		}

		return $clauses;
	}

	/**
	 * Meta clauses: country relation and tour price range.
	 *
	 * @param string               $type   Content type.
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return array<array<string, mixed>>
	 */
	private function meta_query( string $type, array $params ): array {
		$clauses = array();

		$country = (int) ( $params['country'] ?? 0 );
		if ( $country > 0 && in_array( $type, array( 'tour', 'visa' ), true ) ) {
			$clauses[] = array(
				'key'   => 'tour' === $type ? TourMeta::COUNTRY : VisaMeta::COUNTRY,
				'value' => $country,
			);
		}

		if ( 'tour' === $type ) {
			$min = (float) ( $params['min_price'] ?? 0 );
			$max = (float) ( $params['max_price'] ?? 0 );

			if ( $min > 0 || $max > 0 ) {
				$clauses[] = $this->range_clause( TourMeta::PRICE, $min, $max );
			}

			// Single-select ranges ("min-max", 0 = open end) posted by
			// the search widget's budget and duration dropdowns.
			$budget = $this->parse_range( (string) ( $params['budget'] ?? '' ) );
			if ( null !== $budget ) {
				$clauses[] = $this->range_clause( TourMeta::PRICE, $budget[0], $budget[1] );
			}

			$duration = $this->parse_range( (string) ( $params['duration'] ?? '' ) );
			if ( null !== $duration ) {
				$clauses[] = $this->range_clause( TourMeta::DURATION_DAYS, $duration[0], $duration[1] );
			}
		}

		return $clauses;
	}

	/**
	 * A NUMERIC range clause; a zero maximum means "and up".
	 *
	 * @param string $key Meta key.
	 * @param float  $min Lower bound.
	 * @param float  $max Upper bound (0 = open).
	 *
	 * @return array<string, mixed>
	 */
	private function range_clause( string $key, float $min, float $max ): array {
		return array(
			'key'     => $key,
			'value'   => $max > 0 ? array( $min, $max ) : $min,
			'compare' => $max > 0 ? 'BETWEEN' : '>=',
			'type'    => 'NUMERIC',
		);
	}

	/**
	 * Parse a "min-max" range string (e.g. `500-1000`, `15-0` for
	 * open-ended). Returns null for anything malformed or empty.
	 *
	 * @param string $value Raw range value.
	 *
	 * @return array{0: float, 1: float}|null
	 */
	private function parse_range( string $value ): ?array {
		if ( ! preg_match( '/^(\d+(?:\.\d+)?)-(\d+(?:\.\d+)?)$/', $value, $matches ) ) {
			return null;
		}

		$min = (float) $matches[1];
		$max = (float) $matches[2];

		if ( $min <= 0 && $max <= 0 ) {
			return null;
		}

		return array( $min, $max );
	}

	/**
	 * One taxonomy clause matching a term by slug.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $slug     Term slug.
	 *
	 * @return array<string, mixed>
	 */
	private function term_clause( string $taxonomy, string $slug ): array {
		return array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => $slug,
		);
	}
}
