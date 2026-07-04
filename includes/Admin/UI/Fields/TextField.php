<?php
/**
 * Text field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A single-line text input.
 */
class TextField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'text';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		printf(
			'<input type="text" class="regular-text ztc-input" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s"%5$s>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name() ),
			esc_attr( $this->to_string( $value ) ),
			esc_attr( (string) $this->arg( 'placeholder', '' ) ),
			$this->describedby_attr() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): string {
		return sanitize_text_field( $this->to_string( $value ) );
	}
}
