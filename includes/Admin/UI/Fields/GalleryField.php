<?php
/**
 * Gallery field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A multi-image picker backed by the WordPress media modal. Stores an
 * ordered array of attachment IDs (matching the int-list meta schema).
 * Images can be reordered with accessible move buttons.
 */
class GalleryField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'gallery';
	}

	/**
	 * Galleries are groups, not single inputs: use fieldset/legend.
	 *
	 * {@inheritDoc}
	 */
	public function render_row( mixed $value ): void {
		printf( '<fieldset class="ztc-field ztc-field--%s">', esc_attr( $this->type() ) );
		printf( '<legend class="ztc-field__label">%s</legend>', esc_html( $this->label ) );
		echo '<div class="ztc-field__control">';
		$this->render( $value );
		$this->render_description();
		echo '</div></fieldset>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		$ids = array_values( array_filter( array_map( 'absint', (array) $value ) ) );

		printf(
			'<div class="ztc-gallery" data-ztc-gallery data-modal-title="%s" data-modal-button="%s">',
			esc_attr( $this->label ),
			esc_attr__( 'Add to gallery', 'zihad-travel-cms' )
		);

		printf(
			'<input type="hidden" id="%1$s" name="%2$s" value="%3$s" data-ztc-gallery-input>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name() ),
			esc_attr( implode( ',', $ids ) )
		);

		echo '<ul class="ztc-gallery__items" data-ztc-gallery-items>';
		foreach ( $ids as $attachment_id ) {
			$this->render_item( $attachment_id );
		}
		echo '</ul>';

		printf(
			'<button type="button" class="button" data-ztc-gallery-add%1$s>%2$s</button>',
			$this->describedby_attr(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
			esc_html__( 'Add images', 'zihad-travel-cms' )
		);

		echo '</div>';
	}

	/**
	 * One gallery item with move/remove controls.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	protected function render_item( int $attachment_id ): void {
		printf( '<li class="ztc-gallery__item" data-ztc-gallery-item data-id="%d">', $attachment_id );
		echo wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'class' => 'ztc-gallery__thumb' ) );
		printf(
			'<span class="ztc-gallery__actions"><button type="button" class="button-link" data-ztc-move="up" aria-label="%1$s"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span></button><button type="button" class="button-link" data-ztc-move="down" aria-label="%2$s"><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button><button type="button" class="button-link button-link-delete" data-ztc-gallery-remove aria-label="%3$s"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></span>',
			esc_attr__( 'Move image earlier', 'zihad-travel-cms' ),
			esc_attr__( 'Move image later', 'zihad-travel-cms' ),
			esc_attr__( 'Remove image', 'zihad-travel-cms' )
		);
		echo '</li>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): array {
		$ids = is_array( $value ) ? $value : explode( ',', $this->to_string( $value ) );

		return array_values( array_filter( array_map( 'absint', array_filter( $ids, 'is_scalar' ) ) ) );
	}
}
