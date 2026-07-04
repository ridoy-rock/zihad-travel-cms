<?php
/**
 * No-JS archive filters.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Search;

use WP_Query;
use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Modules\Country\CountryPostType;
use ZihadTravelCMS\Modules\Country\RegionTaxonomy;
use ZihadTravelCMS\Modules\Tour\TourPostType;
use ZihadTravelCMS\Modules\Tour\TourTypeTaxonomy;
use ZihadTravelCMS\Modules\Visa\VisaPostType;
use ZihadTravelCMS\Modules\Visa\VisaTypeTaxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Applies the search filter parameters to the plugin archives' main
 * query, so the search widget and archive forms filter identically
 * with JavaScript disabled — and every filtered result set has a
 * plain, shareable URL. The clauses come from
 * SearchService::filter_clauses(), the same translation the REST
 * endpoint uses; nothing is duplicated.
 */
final class ArchiveFilters implements Registrable {

	/**
	 * GET parameters the archives accept (everything else is ignored).
	 */
	private const PARAMS = array( 'region', 'tour_type', 'visa_type', 'country', 'min_price', 'max_price', 'duration', 'budget' );

	/**
	 * Constructor.
	 *
	 * @param SearchService $search The shared clause builder.
	 */
	public function __construct( private SearchService $search ) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'pre_get_posts', array( $this, 'apply' ) );
	}

	/**
	 * Merge the request's filter clauses into a plugin archive's main
	 * query.
	 *
	 * @param WP_Query $query The query being prepared.
	 */
	public function apply( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$type = $this->archive_type( $query );

		if ( '' === $type ) {
			return;
		}

		$params = $this->params();

		if ( array() === $params ) {
			return;
		}

		$clauses = $this->search->filter_clauses( $type, $params );

		// get() returns '' for unset vars — keep only real clauses.
		if ( array() !== $clauses['tax_query'] ) {
			$existing = array_filter( (array) $query->get( 'tax_query' ), 'is_array' );
			$query->set( 'tax_query', array_merge( $existing, $clauses['tax_query'] ) );
		}

		if ( array() !== $clauses['meta_query'] ) {
			$existing = array_filter( (array) $query->get( 'meta_query' ), 'is_array' );
			$query->set( 'meta_query', array_merge( $existing, $clauses['meta_query'] ) );
		}
	}

	/**
	 * The content type of a plugin archive query ('' elsewhere).
	 *
	 * @param WP_Query $query The query being prepared.
	 */
	private function archive_type( WP_Query $query ): string {
		if ( $query->is_post_type_archive( TourPostType::NAME ) || $query->is_tax( TourTypeTaxonomy::NAME ) ) {
			return 'tour';
		}

		if ( $query->is_post_type_archive( VisaPostType::NAME ) || $query->is_tax( VisaTypeTaxonomy::NAME ) ) {
			return 'visa';
		}

		if ( $query->is_post_type_archive( CountryPostType::NAME ) || $query->is_tax( RegionTaxonomy::NAME ) ) {
			return 'country';
		}

		return '';
	}

	/**
	 * The whitelisted, sanitized filter parameters present on this
	 * request. Public read-only filtering — deliberately nonce-free so
	 * filtered archive URLs stay shareable and cacheable.
	 *
	 * @return array<string, string>
	 */
	private function params(): array {
		$params = array();

		foreach ( self::PARAMS as $param ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only filtering.
			$value = sanitize_text_field( (string) wp_unslash( $_GET[ $param ] ?? '' ) );

			if ( '' !== $value && '0' !== $value ) {
				$params[ $param ] = $value;
			}
		}

		return $params;
	}
}
