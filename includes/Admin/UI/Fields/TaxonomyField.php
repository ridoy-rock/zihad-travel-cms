<?php
/**
 * Taxonomy field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A term selector that reads and writes a taxonomy instead of post
 * meta — the field's name doubles as the taxonomy unless a `taxonomy`
 * arg is passed. Renders a multi-select of all terms; saves through
 * wp_set_object_terms(), which validates term IDs.
 *
 * Args: `taxonomy` (defaults to the field name), `size` (visible
 * rows, default 6).
 */
class TaxonomyField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'taxonomy';
	}

	/**
	 * Current term IDs, loaded from the taxonomy (not meta).
	 *
	 * {@inheritDoc}
	 */
	public function value( \WP_Post $post ): mixed {
		$term_ids = wp_get_object_terms( $post->ID, $this->taxonomy(), array( 'fields' => 'ids' ) );

		return is_wp_error( $term_ids ) ? array() : array_map( 'intval', $term_ids );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		$selected = array_map( 'intval', array_filter( (array) $value, 'is_scalar' ) );
		$terms    = get_terms(
			array(
				'taxonomy'   => $this->taxonomy(),
				'hide_empty' => false,
			)
		);
		$terms    = is_wp_error( $terms ) ? array() : $terms;

		if ( array() === $terms ) {
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'No terms exist yet. Add some under this post type\'s taxonomy menu first.', 'zihad-travel-cms' )
			);

			return;
		}

		printf(
			'<select class="ztc-input" id="%1$s" name="%2$s" multiple size="%3$d"%4$s>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name( '[]' ) ),
			absint( min( count( $terms ), (int) $this->arg( 'size', 6 ) ) ),
			$this->describedby_attr() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
		);

		foreach ( $terms as $term ) {
			printf(
				'<option value="%1$d"%2$s>%3$s</option>',
				(int) $term->term_id,
				in_array( (int) $term->term_id, $selected, true ) ? ' selected' : '',
				esc_html( $term->name )
			);
		}

		echo '</select>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): array {
		return array_values( array_filter( array_map( 'absint', array_filter( (array) $value, 'is_scalar' ) ) ) );
	}

	/**
	 * Terms save to the taxonomy, not meta. wp_set_object_terms()
	 * rejects unknown term IDs.
	 *
	 * {@inheritDoc}
	 */
	public function save( int $post_id, mixed $value ): void {
		wp_set_object_terms( $post_id, array_map( 'intval', (array) $value ), $this->taxonomy(), false );
	}

	/**
	 * The taxonomy this field manages.
	 */
	protected function taxonomy(): string {
		return (string) $this->arg( 'taxonomy', $this->name );
	}
}
