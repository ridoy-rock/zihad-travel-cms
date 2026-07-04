<?php
/**
 * Media picker field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A single-attachment picker backed by the WordPress media modal.
 * Stores the attachment ID. Args: `media_type` ('image' by default;
 * use 'application/pdf' etc. for documents), `select_label`,
 * `remove_label`.
 *
 * Covers hero images, flags, icons and document uploads.
 */
class MediaField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'media';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		$attachment_id = absint( is_scalar( $value ) ? $value : 0 );

		printf(
			'<div class="ztc-media" data-ztc-media data-media-type="%s" data-modal-title="%s" data-modal-button="%s">',
			esc_attr( (string) $this->arg( 'media_type', 'image' ) ),
			esc_attr( $this->label ),
			esc_attr__( 'Use this file', 'zihad-travel-cms' )
		);

		printf(
			'<input type="hidden" id="%1$s" name="%2$s" value="%3$d" data-ztc-media-input>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name() ),
			absint( $attachment_id )
		);

		echo '<div class="ztc-media__preview" data-ztc-media-preview aria-live="polite">';
		if ( $attachment_id > 0 ) {
			$this->render_preview( $attachment_id );
		}
		echo '</div>';

		printf(
			'<button type="button" class="button" data-ztc-media-select%1$s>%2$s</button> <button type="button" class="button-link button-link-delete" data-ztc-media-remove%3$s>%4$s</button>',
			$this->describedby_attr(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
			esc_html( (string) $this->arg( 'select_label', __( 'Select', 'zihad-travel-cms' ) ) ),
			$attachment_id > 0 ? '' : ' hidden',
			esc_html( (string) $this->arg( 'remove_label', __( 'Remove', 'zihad-travel-cms' ) ) )
		);

		echo '</div>';
	}

	/**
	 * Preview markup: a thumbnail for images, a filename link for
	 * documents.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	protected function render_preview( int $attachment_id ): void {
		if ( wp_attachment_is_image( $attachment_id ) ) {
			echo wp_get_attachment_image( $attachment_id, 'medium', false, array( 'class' => 'ztc-media__image' ) );

			return;
		}

		printf(
			'<span class="ztc-media__file"><span class="dashicons dashicons-media-default" aria-hidden="true"></span> %s</span>',
			esc_html( get_the_title( $attachment_id ) )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): int {
		return absint( is_scalar( $value ) ? $value : 0 );
	}
}
