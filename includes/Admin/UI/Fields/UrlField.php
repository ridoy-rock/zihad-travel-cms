<?php
/**
 * URL field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A URL input, sanitized with esc_url_raw().
 */
class UrlField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'url';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		printf(
			'<input type="url" class="regular-text code ztc-input" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s"%5$s>',
			esc_attr( $this->input_id() ),
			esc_attr( $this->input_name() ),
			esc_attr( $this->to_string( $value ) ),
			esc_attr( (string) $this->arg( 'placeholder', 'https://' ) ),
			$this->describedby_attr() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): string {
		return esc_url_raw( $this->to_string( $value ) );
	}
}
