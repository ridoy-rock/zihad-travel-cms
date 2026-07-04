<?php
/**
 * Abstract field component.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for every admin field component.
 *
 * A field maps one meta key to one control: it renders escaped markup
 * and sanitizes its own submitted value. Fields write to the meta keys
 * registered through BasePostMeta, so everything they save is
 * automatically REST-, Gutenberg- and Elementor-readable.
 *
 * Common args (all optional): `description`, `placeholder`,
 * `default`, plus per-field args documented on each class.
 */
abstract class BaseField {

	/**
	 * Constructor.
	 *
	 * @param string               $name  Meta key this field reads/writes, e.g. `ztc_capital`.
	 * @param string               $label Translated field label.
	 * @param array<string, mixed> $args  Field options.
	 */
	public function __construct(
		protected string $name,
		protected string $label,
		protected array $args = array(),
	) {}

	/**
	 * Field type slug, used as a CSS modifier, e.g. `text`.
	 */
	abstract public function type(): string;

	/**
	 * Output the control markup (escaped).
	 *
	 * @param mixed $value Current stored value.
	 */
	abstract public function render( mixed $value ): void;

	/**
	 * Sanitize the submitted value. Receives null when the field was
	 * absent from the request (e.g. unchecked checkboxes).
	 *
	 * @param mixed $value Raw (unslashed) submitted value.
	 */
	abstract public function sanitize( mixed $value ): mixed;

	/**
	 * The meta key this field manages.
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * Load the field's current value for a post.
	 *
	 * Defaults to post meta. Fields with different storage (e.g.
	 * TaxonomyField) override this together with save().
	 *
	 * @param \WP_Post $post The post being edited.
	 */
	public function value( \WP_Post $post ): mixed {
		return get_post_meta( $post->ID, $this->name, true );
	}

	/**
	 * Persist the sanitized value.
	 *
	 * @param int   $post_id The post ID.
	 * @param mixed $value   Sanitized value.
	 */
	public function save( int $post_id, mixed $value ): void {
		update_post_meta( $post_id, $this->name, $value );
	}

	/**
	 * Translated label.
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * Help text shown under the control.
	 */
	public function description(): string {
		return (string) ( $this->args['description'] ?? '' );
	}

	/**
	 * Render the full row: label, control, description.
	 *
	 * Group fields (repeaters, galleries) override this with a
	 * fieldset/legend wrapper for accessibility.
	 *
	 * @param mixed $value Current stored value.
	 */
	public function render_row( mixed $value ): void {
		printf( '<div class="ztc-field ztc-field--%s">', esc_attr( $this->type() ) );
		printf(
			'<label class="ztc-field__label" for="%s">%s</label>',
			esc_attr( $this->input_id() ),
			esc_html( $this->label )
		);
		echo '<div class="ztc-field__control">';
		$this->render( $value );
		$this->render_description();
		echo '</div></div>';
	}

	/**
	 * Read a field arg.
	 *
	 * @param string $key           Arg name.
	 * @param mixed  $default_value Fallback.
	 */
	protected function arg( string $key, mixed $default_value = null ): mixed {
		return $this->args[ $key ] ?? $default_value;
	}

	/**
	 * The submitted-form name for this field.
	 *
	 * @param string $suffix Optional suffix, e.g. `[]`.
	 */
	protected function input_name( string $suffix = '' ): string {
		return 'ztc_fields[' . $this->name . ']' . $suffix;
	}

	/**
	 * A stable element id for the control.
	 *
	 * @param string $suffix Optional suffix.
	 */
	protected function input_id( string $suffix = '' ): string {
		return 'ztc-field-' . str_replace( '_', '-', $this->name ) . $suffix;
	}

	/**
	 * Element id of the description paragraph.
	 */
	protected function description_id(): string {
		return $this->input_id( '-description' );
	}

	/**
	 * Pre-escaped aria-describedby attribute ('' when no description).
	 */
	protected function describedby_attr(): string {
		return '' !== $this->description()
			? ' aria-describedby="' . esc_attr( $this->description_id() ) . '"'
			: '';
	}

	/**
	 * Output the description paragraph.
	 */
	protected function render_description(): void {
		if ( '' !== $this->description() ) {
			printf(
				'<p class="description" id="%s">%s</p>',
				esc_attr( $this->description_id() ),
				esc_html( $this->description() )
			);
		}
	}

	/**
	 * Cast a raw submitted value to string safely.
	 *
	 * @param mixed $value Raw value.
	 */
	protected function to_string( mixed $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}
}
