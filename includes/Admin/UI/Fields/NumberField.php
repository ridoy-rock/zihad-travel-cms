<?php
/**
 * Number field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A numeric input. Args: `min`, `max`, `step` (default `any`).
 * Sanitizes to a float clamped to min/max.
 */
class NumberField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'number';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		$min = $this->arg( 'min' );
		$max = $this->arg( 'max' );

		printf(
			'<input type="number" class="small-text ztc-input" id="%1$s" name="%2$s" value="%3$s" step="%4$s"%5$s%6$s%7$s>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name() ),
			esc_attr( $this->to_string( $value ) ),
			esc_attr( (string) $this->arg( 'step', 'any' ) ),
			null !== $min ? ' min="' . esc_attr( (string) $min ) . '"' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
			null !== $max ? ' max="' . esc_attr( (string) $max ) . '"' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
			$this->describedby_attr() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): float {
		$number = (float) $this->to_string( $value );

		$min = $this->arg( 'min' );
		if ( null !== $min ) {
			$number = max( (float) $min, $number );
		}

		$max = $this->arg( 'max' );
		if ( null !== $max ) {
			$number = min( (float) $max, $number );
		}

		return $number;
	}
}
