<?php
/**
 * Abstract post meta registration.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\PostTypes;

use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for a post type's meta fields.
 *
 * Fields registered here are the single source of truth for a post
 * type's data: typed, sanitized, exposed to the REST API (Gutenberg,
 * Elementor dynamic tags, headless clients) and editable only by users
 * who can edit the post. A concrete class declares its fields
 * declaratively via the field helpers; admin metabox UIs come later
 * and simply read/write these keys.
 */
abstract class BasePostMeta implements Registrable {

	/**
	 * The post type these fields belong to, e.g. `ztc_tour`.
	 */
	abstract public function post_type(): string;

	/**
	 * Field definitions: meta key => register_post_meta() args.
	 *
	 * Build each definition with the field helpers, e.g.
	 * `'ztc_capital' => $this->string_field()`.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	abstract protected function fields(): array;

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_fields' ) );
	}

	/**
	 * Register every field with WordPress.
	 */
	public function register_fields(): void {
		foreach ( $this->fields() as $key => $args ) {
			register_post_meta( $this->post_type(), $key, $args + $this->default_args() );
		}
	}

	/**
	 * Args shared by every field.
	 *
	 * @return array<string, mixed>
	 */
	protected function default_args(): array {
		return array(
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => array( $this, 'can_edit' ),
		);
	}

	/**
	 * Meta auth callback: only users who can edit the post may write.
	 *
	 * @param bool   $allowed  Whether the user can add the post meta.
	 * @param string $meta_key The meta key.
	 * @param int    $post_id  The post ID.
	 */
	public function can_edit( bool $allowed, string $meta_key, int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}

	// -----------------------------------------------------------------
	// Field helpers.
	// -----------------------------------------------------------------

	/**
	 * A plain single-line text field.
	 *
	 * @return array<string, mixed>
	 */
	protected function string_field(): array {
		return array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		);
	}

	/**
	 * A multi-line plain-text field (newlines kept, no HTML).
	 *
	 * @return array<string, mixed>
	 */
	protected function textarea_field(): array {
		return array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_textarea_field',
		);
	}

	/**
	 * An email address field.
	 *
	 * @return array<string, mixed>
	 */
	protected function email_field(): array {
		return array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_email',
		);
	}

	/**
	 * A true/false field.
	 *
	 * @return array<string, mixed>
	 */
	protected function bool_field(): array {
		return array(
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
		);
	}

	/**
	 * A multi-line / rich text field (post-safe HTML allowed).
	 *
	 * @return array<string, mixed>
	 */
	protected function rich_text_field(): array {
		return array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
		);
	}

	/**
	 * A URL field.
	 *
	 * @return array<string, mixed>
	 */
	protected function url_field(): array {
		return array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		);
	}

	/**
	 * A non-negative integer field (IDs, counts, attachment IDs).
	 *
	 * @return array<string, mixed>
	 */
	protected function int_field(): array {
		return array(
			'type'              => 'integer',
			'default'           => 0,
			'sanitize_callback' => 'absint',
		);
	}

	/**
	 * A decimal number field (prices, coordinates).
	 *
	 * @return array<string, mixed>
	 */
	protected function number_field(): array {
		return array(
			'type'              => 'number',
			'default'           => 0,
			'sanitize_callback' => static fn( mixed $value ): float => (float) $value,
		);
	}

	/**
	 * An ordered list of plain strings.
	 *
	 * @return array<string, mixed>
	 */
	protected function string_list_field(): array {
		return array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => array( $this, 'sanitize_string_list' ),
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		);
	}

	/**
	 * An ordered list of non-negative integers (e.g. attachment IDs).
	 *
	 * @return array<string, mixed>
	 */
	protected function int_list_field(): array {
		return array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => array( $this, 'sanitize_int_list' ),
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
			),
		);
	}

	/**
	 * An ordered list of objects with fixed string properties,
	 * e.g. FAQ rows or itinerary days.
	 *
	 * @param array<string, string> $properties Property name => 'text' or 'rich'.
	 *
	 * @return array<string, mixed>
	 */
	protected function object_list_field( array $properties ): array {
		return array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => fn( mixed $value ): array => $this->sanitize_object_list( $value, $properties ),
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array_map(
							static fn(): array => array( 'type' => 'string' ),
							$properties
						),
						'additionalProperties' => false,
					),
				),
			),
		);
	}

	/**
	 * A single object with fixed string properties, e.g. an SEO block.
	 *
	 * @param array<string, string> $properties Property name => 'text' or 'rich'.
	 *
	 * @return array<string, mixed>
	 */
	protected function object_field( array $properties ): array {
		return array(
			'type'              => 'object',
			'default'           => array(),
			'sanitize_callback' => fn( mixed $value ): array => $this->sanitize_object( $value, $properties ),
			'show_in_rest'      => array(
				'schema' => array(
					'type'                 => 'object',
					'properties'           => array_map(
						static fn(): array => array( 'type' => 'string' ),
						$properties
					),
					'additionalProperties' => false,
				),
			),
		);
	}

	/**
	 * The shared SEO block (title, description, keywords, robots,
	 * canonical). Rendered into the document head by the SEO module.
	 *
	 * @return array<string, mixed>
	 */
	protected function seo_field(): array {
		return $this->object_field(
			array(
				'title'       => 'text',
				'description' => 'text',
				'keywords'    => 'text',
				'robots'      => 'text',
				'canonical'   => 'url',
			)
		);
	}

	// -----------------------------------------------------------------
	// Sanitizers.
	// -----------------------------------------------------------------

	/**
	 * Sanitize a list of strings, dropping empty entries.
	 *
	 * @param mixed $value Raw meta value.
	 *
	 * @return array<string>
	 */
	public function sanitize_string_list( mixed $value ): array {
		$items = array_map(
			static fn( mixed $item ): string => sanitize_text_field( (string) $item ),
			(array) $value
		);

		return array_values( array_filter( $items, static fn( string $item ): bool => '' !== $item ) );
	}

	/**
	 * Sanitize a list of non-negative integers, dropping zeros.
	 *
	 * @param mixed $value Raw meta value.
	 *
	 * @return array<int>
	 */
	public function sanitize_int_list( mixed $value ): array {
		$items = array_map(
			static fn( mixed $item ): int => absint( is_scalar( $item ) ? $item : 0 ),
			(array) $value
		);

		return array_values( array_filter( $items ) );
	}

	/**
	 * Sanitize a list of objects against a fixed property map.
	 *
	 * @param mixed                 $value      Raw meta value.
	 * @param array<string, string> $properties Property name => 'text' or 'rich'.
	 *
	 * @return array<array<string, string>>
	 */
	protected function sanitize_object_list( mixed $value, array $properties ): array {
		$clean = array();

		foreach ( (array) $value as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$clean[] = $this->sanitize_object( $item, $properties );
		}

		return $clean;
	}

	/**
	 * Sanitize one object against a fixed property map. Unknown
	 * properties are stripped.
	 *
	 * @param mixed                 $value      Raw meta value.
	 * @param array<string, string> $properties Property name => 'text', 'rich' or 'url'.
	 *
	 * @return array<string, string>
	 */
	protected function sanitize_object( mixed $value, array $properties ): array {
		$value = is_array( $value ) ? $value : array();
		$clean = array();

		foreach ( $properties as $property => $kind ) {
			$raw = isset( $value[ $property ] ) && is_scalar( $value[ $property ] )
				? (string) $value[ $property ]
				: '';

			$clean[ $property ] = match ( $kind ) {
				'rich'  => wp_kses_post( $raw ),
				'url'   => esc_url_raw( $raw ),
				default => sanitize_text_field( $raw ),
			};
		}

		return $clean;
	}
}
