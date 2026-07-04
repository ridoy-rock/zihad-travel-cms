<?php
/**
 * Duration field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A days/nights pair saved as one object (`{days, nights}`), matching
 * the duration meta schema. Values are stored as digit strings so
 * templates can render them verbatim ("5 Days / 4 Nights").
 */
class DurationField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'duration';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		$value  = is_array( $value ) ? $value : array();
		$days   = (string) ( $value['days'] ?? '' );
		$nights = (string) ( $value['nights'] ?? '' );

		echo '<div class="ztc-duration d-flex gap-2">';

		printf(
			'<span class="ztc-duration__part"><label for="%1$s">%2$s</label> <input type="number" class="small-text" id="%1$s" name="%3$s" value="%4$s" min="0" step="1"%5$s></span>',
			esc_attr( $this->input_id( '-days' ) ),
			esc_html__( 'Days', 'zihad-travel-cms' ),
			esc_attr( $this->input_name( '[days]' ) ),
			esc_attr( $days ),
			$this->describedby_attr() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
		);

		printf(
			'<span class="ztc-duration__part"><label for="%1$s">%2$s</label> <input type="number" class="small-text" id="%1$s" name="%3$s" value="%4$s" min="0" step="1"></span>',
			esc_attr( $this->input_id( '-nights' ) ),
			esc_html__( 'Nights', 'zihad-travel-cms' ),
			esc_attr( $this->input_name( '[nights]' ) ),
			esc_attr( $nights )
		);

		echo '</div>';
	}

	/**
	 * The row label points at the days input.
	 *
	 * {@inheritDoc}
	 */
	public function render_row( mixed $value ): void {
		printf( '<div class="ztc-field ztc-field--%s">', esc_attr( $this->type() ) );
		printf(
			'<label class="ztc-field__label" for="%s">%s</label>',
			esc_attr( $this->input_id( '-days' ) ),
			esc_html( $this->label )
		);
		echo '<div class="ztc-field__control">';
		$this->render( $value );
		$this->render_description();
		echo '</div></div>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): array {
		$value = is_array( $value ) ? $value : array();

		return array(
			'days'   => $this->digits( $value['days'] ?? '' ),
			'nights' => $this->digits( $value['nights'] ?? '' ),
		);
	}

	/**
	 * A non-negative integer as string, or '' when unset.
	 *
	 * @param mixed $raw Raw submitted value.
	 */
	private function digits( mixed $raw ): string {
		$raw = trim( $this->to_string( $raw ) );

		return '' === $raw ? '' : (string) absint( $raw );
	}
}
