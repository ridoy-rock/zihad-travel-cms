<?php
/**
 * Textarea field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A multi-line plain-text input. Args: `rows` (default 4).
 */
class TextareaField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'textarea';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		printf(
			'<textarea class="large-text ztc-input" id="%1$s" name="%2$s" rows="%3$d" placeholder="%4$s"%5$s>%6$s</textarea>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name() ),
			(int) $this->arg( 'rows', 4 ),
			esc_attr( (string) $this->arg( 'placeholder', '' ) ),
			$this->describedby_attr(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
			esc_textarea( $this->to_string( $value ) )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): string {
		return sanitize_textarea_field( $this->to_string( $value ) );
	}
}
