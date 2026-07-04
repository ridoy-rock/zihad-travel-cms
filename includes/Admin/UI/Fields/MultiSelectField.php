<?php
/**
 * Multi-select field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A multiple-choice list. Args: `options` (value => label),
 * `size` (visible rows, default 6). Sanitizes to an array of known
 * option values, preserving selection order.
 */
class MultiSelectField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'multi-select';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		$current = array_map( 'strval', array_filter( (array) $value, 'is_scalar' ) );
		$options = $this->options();

		printf(
			'<select class="ztc-input" id="%1$s" name="%2$s" multiple size="%3$d"%4$s>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name( '[]' ) ),
			absint( min( count( $options ) > 0 ? count( $options ) : 1, (int) $this->arg( 'size', 6 ) ) ),
			$this->describedby_attr() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
		);

		foreach ( $options as $option_value => $option_label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( (string) $option_value ),
				in_array( (string) $option_value, $current, true ) ? ' selected' : '',
				esc_html( (string) $option_label )
			);
		}

		echo '</select>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): array {
		$known = array_map( 'strval', array_keys( $this->options() ) );
		$sent  = array_map( 'strval', array_filter( (array) $value, 'is_scalar' ) );

		return array_values( array_intersect( $sent, $known ) );
	}

	/**
	 * The choice list.
	 *
	 * @return array<string, string>
	 */
	protected function options(): array {
		return (array) $this->arg( 'options', array() );
	}
}
