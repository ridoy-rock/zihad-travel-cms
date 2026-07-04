<?php
/**
 * Checkbox field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A boolean checkbox. Args: `checkbox_label` (text beside the box,
 * defaults to the field label). Unchecked boxes are absent from the
 * request and sanitize to false.
 */
class CheckboxField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'checkbox';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		printf(
			'<label class="ztc-checkbox"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s%4$s> <span>%5$s</span></label>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name() ),
			! empty( $value ) ? ' checked' : '',
			$this->describedby_attr(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
			esc_html( (string) $this->arg( 'checkbox_label', $this->label ) )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): bool {
		return ! empty( $value );
	}
}
