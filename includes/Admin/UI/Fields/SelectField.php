<?php
/**
 * Select field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A single-choice dropdown. Args: `options` (value => label),
 * `default` (returned when the submitted value is not a known option),
 * `placeholder` (empty first option label).
 */
class SelectField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'select';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		$current = $this->to_string( $value );

		printf(
			'<select class="ztc-input" id="%1$s" name="%2$s"%3$s>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name() ),
			$this->describedby_attr() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
		);

		$placeholder = (string) $this->arg( 'placeholder', '' );
		if ( '' !== $placeholder ) {
			printf( '<option value="">%s</option>', esc_html( $placeholder ) );
		}

		foreach ( $this->options() as $option_value => $option_label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( (string) $option_value ),
				(string) $option_value === $current ? ' selected' : '',
				esc_html( (string) $option_label )
			);
		}

		echo '</select>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): string {
		$value = $this->to_string( $value );

		return array_key_exists( $value, $this->options() ) ? $value : (string) $this->arg( 'default', '' );
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
