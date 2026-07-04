<?php
/**
 * SEO field group.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * The shared SEO block: meta title, meta description and keywords,
 * saved as one object matching the `ztc_seo` meta schema. Reused by
 * every module's SEO tab; the future SEO module renders these values
 * into head tags.
 */
class SeoField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'seo';
	}

	/**
	 * SEO is a group, not a single input: use fieldset/legend.
	 *
	 * {@inheritDoc}
	 */
	public function render_row( mixed $value ): void {
		printf( '<fieldset class="ztc-field ztc-field--%s">', esc_attr( $this->type() ) );
		printf( '<legend class="ztc-field__label">%s</legend>', esc_html( $this->label ) );
		echo '<div class="ztc-field__control">';
		$this->render( $value );
		$this->render_description();
		echo '</div></fieldset>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		$value = is_array( $value ) ? $value : array();

		echo '<div class="ztc-seo">';

		$this->render_part(
			'title',
			__( 'Meta Title', 'zihad-travel-cms' ),
			(string) ( $value['title'] ?? '' ),
			__( 'Leave empty to use the post title.', 'zihad-travel-cms' )
		);

		$this->render_part(
			'description',
			__( 'Meta Description', 'zihad-travel-cms' ),
			(string) ( $value['description'] ?? '' ),
			__( 'Aim for 150–160 characters.', 'zihad-travel-cms' ),
			true
		);

		$this->render_part(
			'keywords',
			__( 'Keywords', 'zihad-travel-cms' ),
			(string) ( $value['keywords'] ?? '' ),
			__( 'Comma-separated.', 'zihad-travel-cms' )
		);

		echo '</div>';
	}

	/**
	 * One labelled input inside the group.
	 *
	 * @param string $key      Object property (title|description|keywords).
	 * @param string $label    Translated label.
	 * @param string $value    Current value.
	 * @param string $hint     Help text.
	 * @param bool   $textarea Render as textarea.
	 */
	private function render_part( string $key, string $label, string $value, string $hint, bool $textarea = false ): void {
		$id   = $this->input_id( '-' . $key );
		$name = $this->input_name( '[' . $key . ']' );

		echo '<div class="ztc-seo__part">';
		printf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_html( $label ) );

		if ( $textarea ) {
			printf(
				'<textarea class="large-text" id="%1$s" name="%2$s" rows="3" aria-describedby="%3$s">%4$s</textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $id . '-hint' ),
				esc_textarea( $value )
			);
		} else {
			printf(
				'<input type="text" class="large-text" id="%1$s" name="%2$s" value="%3$s" aria-describedby="%4$s">',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value ),
				esc_attr( $id . '-hint' )
			);
		}

		printf( '<p class="description" id="%1$s">%2$s</p>', esc_attr( $id . '-hint' ), esc_html( $hint ) );
		echo '</div>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): array {
		$value = is_array( $value ) ? $value : array();

		return array(
			'title'       => sanitize_text_field( $this->to_string( $value['title'] ?? '' ) ),
			'description' => sanitize_textarea_field( $this->to_string( $value['description'] ?? '' ) ),
			'keywords'    => sanitize_text_field( $this->to_string( $value['keywords'] ?? '' ) ),
		);
	}
}
