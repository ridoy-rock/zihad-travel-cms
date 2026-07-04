<?php
/**
 * Abstract post repository.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Data;

use WP_Post;
use WP_Query;
use ZihadTravelCMS\Contracts\TranslationProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for every module repository.
 *
 * Repositories are the ONLY layer that touches WordPress data APIs
 * (get_post, WP_Query, post meta, terms). They contain no business
 * logic — formatting, fallbacks and composition live in the module's
 * Service class.
 *
 * Queries run with `suppress_filters => false` so multilingual plugins
 * can filter them; language-specific lookups go through the injected
 * TranslationProvider.
 */
abstract class BaseRepository {

	/**
	 * Constructor.
	 *
	 * @param TranslationProvider $translations Translation provider.
	 */
	public function __construct( protected TranslationProvider $translations ) {}

	/**
	 * The post type this repository manages, e.g. `ztc_tour`.
	 */
	abstract public function post_type(): string;

	/**
	 * Find a post by ID. Returns null when the post does not exist or
	 * belongs to another post type.
	 *
	 * @param int $post_id Post ID.
	 */
	public function find( int $post_id ): ?WP_Post {
		$post = get_post( $post_id );

		return ( $post instanceof WP_Post && $this->post_type() === $post->post_type ) ? $post : null;
	}

	/**
	 * Find a published post by slug.
	 *
	 * @param string $slug Post slug.
	 */
	public function find_by_slug( string $slug ): ?WP_Post {
		$posts = $this->all(
			array(
				'name'           => $slug,
				'posts_per_page' => 1,
			)
		);

		return $posts[0] ?? null;
	}

	/**
	 * Retrieve posts.
	 *
	 * @param array<string, mixed> $args Optional overrides for get_posts().
	 *
	 * @return array<WP_Post>
	 */
	public function all( array $args = array() ): array {
		return get_posts( $args + $this->default_args() );
	}

	/**
	 * Run a full WP_Query (pagination, found_rows) for archive-style
	 * listings.
	 *
	 * @param array<string, mixed> $args Optional overrides.
	 */
	public function query( array $args = array() ): WP_Query {
		return new WP_Query( $args + $this->default_args() );
	}

	/**
	 * Number of published posts.
	 */
	public function count(): int {
		$counts = wp_count_posts( $this->post_type() );

		return (int) ( $counts->publish ?? 0 );
	}

	/**
	 * Read a single meta value. Fields registered through BasePostMeta
	 * fall back to their registered defaults automatically.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 */
	public function meta( int $post_id, string $key ): mixed {
		return get_post_meta( $post_id, $key, true );
	}

	/**
	 * Write a single meta value. The sanitize callback registered for
	 * the key runs automatically.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   New value.
	 */
	public function save_meta( int $post_id, string $key, mixed $value ): void {
		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * The post's terms in a taxonomy.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return array<\WP_Term>
	 */
	public function terms( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );

		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * Resolve a post to its translation in the given (or current)
	 * language. On single-language sites this is the identity.
	 *
	 * @param int         $post_id  Post ID.
	 * @param string|null $language Target language, or null for the current one.
	 */
	public function in_language( int $post_id, ?string $language = null ): int {
		return $this->translations->translated_post_id(
			$post_id,
			$language ?? $this->translations->current_language()
		);
	}

	/**
	 * Query args for filtering by a taxonomy term.
	 *
	 * @param string     $taxonomy Taxonomy name.
	 * @param int|string $term     Term ID or slug.
	 *
	 * @return array<string, mixed>
	 */
	protected function term_args( string $taxonomy, int|string $term ): array {
		return array(
			'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $taxonomy,
					'field'    => is_int( $term ) ? 'term_id' : 'slug',
					'terms'    => $term,
				),
			),
		);
	}

	/**
	 * Query args shared by every lookup.
	 *
	 * @return array<string, mixed>
	 */
	protected function default_args(): array {
		return array(
			'post_type'        => $this->post_type(),
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'suppress_filters' => false,
		);
	}
}
