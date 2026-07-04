<?php
/**
 * Toggle field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A boolean switch. Same semantics as CheckboxField, rendered as an
 * accessible switch (role="switch", styled track/thumb).
 */
class ToggleField extends CheckboxField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'toggle';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		printf(
			'<label class="ztc-toggle"><input type="checkbox" role="switch" id="%1$s" name="%2$s" value="1"%3$s%4$s><span class="ztc-toggle__track" aria-hidden="true"></span> <span class="ztc-toggle__text">%5$s</span></label>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name() ),
			! empty( $value ) ? ' checked' : '',
			$this->describedby_attr(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
			esc_html( (string) $this->arg( 'checkbox_label', $this->label ) )
		);
	}
}
