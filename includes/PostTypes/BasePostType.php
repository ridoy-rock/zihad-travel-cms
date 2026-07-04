<?php
/**
 * Abstract custom post type.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\PostTypes;

use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for every plugin post type.
 *
 * A concrete post type only declares its identity (name + labels) and,
 * when needed, overrides(); registration, label generation and the
 * per-post-type args filter are handled here.
 */
abstract class BasePostType implements Registrable {

	/**
	 * The post type name, e.g. `ztc_tour`. Max 20 characters.
	 */
	abstract public function post_type(): string;

	/**
	 * Translated singular label, e.g. "Tour".
	 */
	abstract protected function singular_label(): string;

	/**
	 * Translated plural label, e.g. "Tours".
	 */
	abstract protected function plural_label(): string;

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Register the post type with WordPress.
	 */
	public function register_post_type(): void {
		register_post_type( $this->post_type(), $this->args() );
	}

	/**
	 * Concrete post types override this to adjust default_args().
	 *
	 * @return array<string, mixed>
	 */
	protected function overrides(): array {
		return array();
	}

	/**
	 * The URL slug, e.g. `tour` for `ztc_tour`. Override per post type
	 * when the derived value is not right.
	 */
	protected function rewrite_slug(): string {
		return str_replace( array( 'ztc_', '_' ), array( '', '-' ), $this->post_type() );
	}

	/**
	 * Final registration args: defaults, then overrides, then filter.
	 *
	 * @return array<string, mixed>
	 */
	protected function args(): array {
		$overrides = $this->overrides();
		$args      = array_replace_recursive( $this->default_args(), $overrides );

		// Feature lists must replace wholesale, not merge by index —
		// otherwise a shorter override list keeps leftover defaults.
		if ( isset( $overrides['supports'] ) ) {
			$args['supports'] = $overrides['supports'];
		}

		/**
		 * Filter the registration args for a plugin post type.
		 *
		 * The dynamic portion of the hook name is the post type,
		 * e.g. `ztc_tour_post_type_args`.
		 *
		 * @param array<string, mixed> $args Registration args.
		 */
		return (array) apply_filters( $this->post_type() . '_post_type_args', $args );
	}

	/**
	 * Sensible defaults shared by all plugin post types.
	 *
	 * @return array<string, mixed>
	 */
	protected function default_args(): array {
		return array(
			'labels'        => $this->labels(),
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => true,
			'menu_position' => 30,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
			'rewrite'       => array(
				'slug'       => $this->rewrite_slug(),
				'with_front' => false,
			),
		);
	}

	/**
	 * Build the full label set from the singular and plural labels.
	 *
	 * @return array<string, string>
	 */
	protected function labels(): array {
		$singular = $this->singular_label();
		$plural   = $this->plural_label();

		return array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			/* translators: %s: singular post type label. */
			'add_new'            => sprintf( __( 'Add New %s', 'zihad-travel-cms' ), $singular ),
			/* translators: %s: singular post type label. */
			'add_new_item'       => sprintf( __( 'Add New %s', 'zihad-travel-cms' ), $singular ),
			/* translators: %s: singular post type label. */
			'edit_item'          => sprintf( __( 'Edit %s', 'zihad-travel-cms' ), $singular ),
			/* translators: %s: singular post type label. */
			'new_item'           => sprintf( __( 'New %s', 'zihad-travel-cms' ), $singular ),
			/* translators: %s: singular post type label. */
			'view_item'          => sprintf( __( 'View %s', 'zihad-travel-cms' ), $singular ),
			/* translators: %s: plural post type label. */
			'view_items'         => sprintf( __( 'View %s', 'zihad-travel-cms' ), $plural ),
			/* translators: %s: plural post type label. */
			'search_items'       => sprintf( __( 'Search %s', 'zihad-travel-cms' ), $plural ),
			/* translators: %s: plural post type label. */
			'not_found'          => sprintf( __( 'No %s found.', 'zihad-travel-cms' ), strtolower( $plural ) ),
			/* translators: %s: plural post type label. */
			'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'zihad-travel-cms' ), strtolower( $plural ) ),
			/* translators: %s: plural post type label. */
			'all_items'          => sprintf( __( 'All %s', 'zihad-travel-cms' ), $plural ),
			'item_published'     => sprintf(
				/* translators: %s: singular post type label. */
				__( '%s published.', 'zihad-travel-cms' ),
				$singular
			),
			'item_updated'       => sprintf(
				/* translators: %s: singular post type label. */
				__( '%s updated.', 'zihad-travel-cms' ),
				$singular
			),
		);
	}
}
