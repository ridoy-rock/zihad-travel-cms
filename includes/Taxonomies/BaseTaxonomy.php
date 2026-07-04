<?php
/**
 * Abstract taxonomy.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Taxonomies;

use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for every plugin taxonomy.
 *
 * A concrete taxonomy only declares its identity (name, object types,
 * labels) and, when needed, overrides(); registration and label
 * generation are handled here.
 */
abstract class BaseTaxonomy implements Registrable {

	/**
	 * The taxonomy name, e.g. `ztc_tour_type`. Max 32 characters.
	 */
	abstract public function taxonomy(): string;

	/**
	 * The post types this taxonomy attaches to, e.g. `array( 'ztc_tour' )`.
	 *
	 * @return array<string>
	 */
	abstract protected function object_types(): array;

	/**
	 * Translated singular label, e.g. "Tour Type".
	 */
	abstract protected function singular_label(): string;

	/**
	 * Translated plural label, e.g. "Tour Types".
	 */
	abstract protected function plural_label(): string;

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the taxonomy with WordPress.
	 */
	public function register_taxonomy(): void {
		register_taxonomy( $this->taxonomy(), $this->object_types(), $this->args() );
	}

	/**
	 * Concrete taxonomies override this to adjust default_args().
	 *
	 * @return array<string, mixed>
	 */
	protected function overrides(): array {
		return array();
	}

	/**
	 * The URL slug, e.g. `tour-type` for `ztc_tour_type`.
	 */
	protected function rewrite_slug(): string {
		return str_replace( array( 'ztc_', '_' ), array( '', '-' ), $this->taxonomy() );
	}

	/**
	 * Final registration args: defaults, then overrides, then filter.
	 *
	 * @return array<string, mixed>
	 */
	protected function args(): array {
		$args = array_replace_recursive( $this->default_args(), $this->overrides() );

		/**
		 * Filter the registration args for a plugin taxonomy.
		 *
		 * The dynamic portion of the hook name is the taxonomy,
		 * e.g. `ztc_tour_type_taxonomy_args`.
		 *
		 * @param array<string, mixed> $args Registration args.
		 */
		return (array) apply_filters( $this->taxonomy() . '_taxonomy_args', $args );
	}

	/**
	 * Sensible defaults shared by all plugin taxonomies.
	 *
	 * @return array<string, mixed>
	 */
	protected function default_args(): array {
		return array(
			'labels'            => $this->labels(),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
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
			'name'          => $plural,
			'singular_name' => $singular,
			'menu_name'     => $plural,
			/* translators: %s: plural taxonomy label. */
			'all_items'     => sprintf( __( 'All %s', 'zihad-travel-cms' ), $plural ),
			/* translators: %s: singular taxonomy label. */
			'edit_item'     => sprintf( __( 'Edit %s', 'zihad-travel-cms' ), $singular ),
			/* translators: %s: singular taxonomy label. */
			'view_item'     => sprintf( __( 'View %s', 'zihad-travel-cms' ), $singular ),
			/* translators: %s: singular taxonomy label. */
			'update_item'   => sprintf( __( 'Update %s', 'zihad-travel-cms' ), $singular ),
			/* translators: %s: singular taxonomy label. */
			'add_new_item'  => sprintf( __( 'Add New %s', 'zihad-travel-cms' ), $singular ),
			/* translators: %s: singular taxonomy label. */
			'new_item_name' => sprintf( __( 'New %s Name', 'zihad-travel-cms' ), $singular ),
			/* translators: %s: plural taxonomy label. */
			'search_items'  => sprintf( __( 'Search %s', 'zihad-travel-cms' ), $plural ),
			/* translators: %s: plural taxonomy label. */
			'not_found'     => sprintf( __( 'No %s found.', 'zihad-travel-cms' ), strtolower( $plural ) ),
		);
	}
}
