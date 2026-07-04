<?php
/**
 * Code field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A monospace textarea for CSS/JS snippets. Unlike TextareaField it
 * must not strip characters like `<` or collapse whitespace, so the
 * value is stored verbatim apart from closing-tag injection guards —
 * the consumer (Frontend\Integrations) applies context-appropriate
 * escaping when printing.
 *
 * Only reachable through manage_options screens/endpoints.
 */
class CodeField extends TextareaField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'code';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		printf(
			'<textarea class="large-text code ztc-input ztc-code" id="%1$s" name="%2$s" rows="%3$d" spellcheck="false" placeholder="%4$s"%5$s>%6$s</textarea>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name() ),
			(int) $this->arg( 'rows', 10 ),
			esc_attr( (string) $this->arg( 'placeholder', '' ) ),
			$this->describedby_attr(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
			esc_textarea( $this->to_string( $value ) )
		);
	}

	/**
	 * Keep the snippet verbatim; only neutralize sequences that could
	 * break out of the <style>/<script> the consumer prints into.
	 *
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): string {
		$code = trim( $this->to_string( $value ) );

		return str_ireplace( array( '</script', '</style' ), '', $code );
	}
}
