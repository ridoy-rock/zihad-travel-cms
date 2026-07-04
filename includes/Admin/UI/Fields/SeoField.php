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
 * The shared SEO block: meta title, meta description, keywords, robots
 * directive and canonical override, saved as one object matching the
 * `ztc_seo` meta schema. Reused by every module's SEO tab; the SEO
 * module renders these values into head tags.
 */
class SeoField extends BaseField {

	/**
	 * Allowed robots directive values ('' = index,follow default).
	 */
	public const ROBOTS_VALUES = array( '', 'noindex', 'nofollow', 'noindex,nofollow' );

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

		$this->render_robots( (string) ( $value['robots'] ?? '' ) );

		$this->render_part(
			'canonical',
			__( 'Canonical URL', 'zihad-travel-cms' ),
			(string) ( $value['canonical'] ?? '' ),
			__( 'Leave empty to use the permalink.', 'zihad-travel-cms' )
		);

		echo '</div>';
	}

	/**
	 * The robots directive select.
	 *
	 * @param string $current Stored robots value.
	 */
	private function render_robots( string $current ): void {
		$id   = $this->input_id( '-robots' );
		$name = $this->input_name( '[robots]' );

		$options = array(
			''                 => __( 'Index, follow (default)', 'zihad-travel-cms' ),
			'noindex'          => __( 'No index', 'zihad-travel-cms' ),
			'nofollow'         => __( 'No follow', 'zihad-travel-cms' ),
			'noindex,nofollow' => __( 'No index, no follow', 'zihad-travel-cms' ),
		);

		echo '<div class="ztc-seo__part">';
		printf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_html__( 'Robots', 'zihad-travel-cms' ) );
		printf( '<select class="ztc-input" id="%1$s" name="%2$s" aria-describedby="%3$s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $id . '-hint' ) );

		foreach ( $options as $option_value => $option_label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $option_value ),
				$option_value === $current ? ' selected' : '',
				esc_html( $option_label )
			);
		}

		echo '</select>';
		printf(
			'<p class="description" id="%1$s">%2$s</p>',
			esc_attr( $id . '-hint' ),
			esc_html__( 'Tell search engines how to treat this page.', 'zihad-travel-cms' )
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
		$value  = is_array( $value ) ? $value : array();
		$robots = sanitize_text_field( $this->to_string( $value['robots'] ?? '' ) );

		return array(
			'title'       => sanitize_text_field( $this->to_string( $value['title'] ?? '' ) ),
			'description' => sanitize_textarea_field( $this->to_string( $value['description'] ?? '' ) ),
			'keywords'    => sanitize_text_field( $this->to_string( $value['keywords'] ?? '' ) ),
			'robots'      => in_array( $robots, self::ROBOTS_VALUES, true ) ? $robots : '',
			'canonical'   => esc_url_raw( $this->to_string( $value['canonical'] ?? '' ) ),
		);
	}
}
