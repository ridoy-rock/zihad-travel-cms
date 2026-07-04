<?php
/**
 * Rich editor field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A WordPress visual editor (TinyMCE + quicktags). Post-safe HTML is
 * allowed. Args: `rows` (default 8), `media_buttons` (default false),
 * `teeny` (default true — compact toolbar).
 */
class RichEditorField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'rich-editor';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		wp_editor(
			$this->to_string( $value ),
			$this->editor_id(),
			array(
				'textarea_name' => $this->input_name(),
				'textarea_rows' => (int) $this->arg( 'rows', 8 ),
				'media_buttons' => (bool) $this->arg( 'media_buttons', false ),
				'teeny'         => (bool) $this->arg( 'teeny', true ),
			)
		);

		$this->render_description();
	}

	/**
	 * Description renders inside render(); the row must not duplicate it.
	 *
	 * {@inheritDoc}
	 */
	public function render_row( mixed $value ): void {
		printf( '<div class="ztc-field ztc-field--%s">', esc_attr( $this->type() ) );
		printf(
			'<label class="ztc-field__label" for="%s">%s</label>',
			esc_attr( $this->editor_id() ),
			esc_html( $this->label )
		);
		echo '<div class="ztc-field__control">';
		$this->render( $value );
		echo '</div></div>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): string {
		return wp_kses_post( $this->to_string( $value ) );
	}

	/**
	 * The wp_editor() ids may only contain lowercase letters and
	 * underscores.
	 */
	private function editor_id(): string {
		return 'ztc_editor_field_' . strtolower( (string) preg_replace( '/[^a-z0-9_]/', '_', $this->name ) );
	}
}
