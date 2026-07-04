<?php
/**
 * Repeater field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * An ordered list of structured rows (add / remove / reorder), saved
 * as an array of objects matching the object-list meta schema.
 *
 * Args:
 * - `fields`: sub-field definitions, each
 *   `array( 'key' => 'title', 'label' => '…', 'type' => 'text'|'textarea'|'url'|'number', 'rows' => int, 'placeholder' => '…' )`
 * - `row_label`: name for one row shown in the row header (default "Item").
 * - `button_label`: add-button text (default "Add item").
 *
 * Rows where every value is empty are dropped on save. New rows are
 * cloned client-side from a <template> with an `__i__` index
 * placeholder.
 */
class RepeaterField extends BaseField {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'repeater';
	}

	/**
	 * Repeaters are groups, not single inputs: use fieldset/legend.
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
		$rows = array_values( array_filter( (array) $value, 'is_array' ) );

		printf( '<div class="ztc-repeater ztc-repeater--%s" data-ztc-repeater>', esc_attr( $this->type() ) );

		echo '<div class="ztc-repeater__rows" data-ztc-repeater-rows>';
		foreach ( $rows as $index => $row ) {
			$this->render_repeater_row( (string) $index, $row );
		}
		echo '</div>';

		printf(
			'<button type="button" class="button" data-ztc-repeater-add%1$s>%2$s</button>',
			$this->describedby_attr(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped attribute.
			esc_html( (string) $this->arg( 'button_label', __( 'Add item', 'zihad-travel-cms' ) ) )
		);

		// Client-side row blueprint; __i__ is replaced with the next index.
		echo '<template data-ztc-repeater-template>';
		$this->render_repeater_row( '__i__', array() );
		echo '</template>';

		echo '</div>';
	}

	/**
	 * One repeater row: header (label, move/remove controls) + sub-fields.
	 *
	 * @param string               $index Row index or the `__i__` placeholder.
	 * @param array<string, mixed> $row   Row values.
	 */
	protected function render_repeater_row( string $index, array $row ): void {
		echo '<div class="ztc-repeater__row" data-ztc-repeater-row>';

		printf(
			'<div class="ztc-repeater__row-head"><span class="ztc-repeater__row-title">%1$s <span class="ztc-repeater__row-number" data-ztc-repeater-number></span></span><span class="ztc-repeater__row-actions"><button type="button" class="button-link" data-ztc-move="up" aria-label="%2$s"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button><button type="button" class="button-link" data-ztc-move="down" aria-label="%3$s"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button><button type="button" class="button-link button-link-delete" data-ztc-repeater-remove aria-label="%4$s"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></span></div>',
			esc_html( $this->row_label() ),
			esc_attr__( 'Move row up', 'zihad-travel-cms' ),
			esc_attr__( 'Move row down', 'zihad-travel-cms' ),
			esc_attr__( 'Remove row', 'zihad-travel-cms' )
		);

		echo '<div class="ztc-repeater__row-fields">';
		foreach ( $this->sub_fields() as $definition ) {
			$this->render_sub_field( $definition, $index, $row );
		}
		echo '</div></div>';
	}

	/**
	 * One sub-field inside a row.
	 *
	 * @param array<string, mixed> $definition Sub-field definition.
	 * @param string               $index      Row index or `__i__`.
	 * @param array<string, mixed> $row        Row values.
	 */
	protected function render_sub_field( array $definition, string $index, array $row ): void {
		$key   = (string) ( $definition['key'] ?? '' );
		$type  = (string) ( $definition['type'] ?? 'text' );
		$value = isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) ? (string) $row[ $key ] : '';

		$field_name = $this->input_name( '[' . $index . '][' . $key . ']' );
		$field_id   = $this->input_id( '-' . $index . '-' . str_replace( '_', '-', $key ) );

		echo '<div class="ztc-repeater__sub-field">';
		printf( '<label for="%1$s">%2$s</label>', esc_attr( $field_id ), esc_html( (string) ( $definition['label'] ?? $key ) ) );

		if ( 'textarea' === $type ) {
			printf(
				'<textarea class="large-text" id="%1$s" name="%2$s" rows="%3$d" placeholder="%4$s">%5$s</textarea>',
				esc_attr( $field_id ),
				esc_attr( $field_name ),
				(int) ( $definition['rows'] ?? 3 ),
				esc_attr( (string) ( $definition['placeholder'] ?? '' ) ),
				esc_textarea( $value )
			);
		} else {
			printf(
				'<input type="%1$s" class="regular-text" id="%2$s" name="%3$s" value="%4$s" placeholder="%5$s"%6$s>',
				esc_attr( 'number' === $type ? 'number' : ( 'url' === $type ? 'url' : 'text' ) ),
				esc_attr( $field_id ),
				esc_attr( $field_name ),
				esc_attr( $value ),
				esc_attr( (string) ( $definition['placeholder'] ?? '' ) ),
				'number' === $type ? ' step="any"' : ''
			);
		}

		echo '</div>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): array {
		$rows = array();

		foreach ( (array) $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean     = array();
			$has_value = false;

			foreach ( $this->sub_fields() as $definition ) {
				$key = (string) ( $definition['key'] ?? '' );
				$raw = isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) ? (string) $row[ $key ] : '';

				$clean[ $key ] = $this->sanitize_sub_value( (string) ( $definition['type'] ?? 'text' ), $raw );

				if ( '' !== $clean[ $key ] ) {
					$has_value = true;
				}
			}

			if ( $has_value ) {
				$rows[] = $clean;
			}
		}

		return $rows;
	}

	/**
	 * Sanitize one sub-field value by declared type. Textareas allow
	 * post-safe HTML, matching the object-list meta schema's 'rich'
	 * properties.
	 *
	 * @param string $type Sub-field type.
	 * @param string $raw  Raw value.
	 */
	protected function sanitize_sub_value( string $type, string $raw ): string {
		return match ( $type ) {
			'textarea' => wp_kses_post( $raw ),
			'url'      => esc_url_raw( $raw ),
			default    => sanitize_text_field( $raw ),
		};
	}

	/**
	 * Sub-field definitions.
	 *
	 * @return array<array<string, mixed>>
	 */
	protected function sub_fields(): array {
		return array_values( array_filter( (array) $this->arg( 'fields', array() ), 'is_array' ) );
	}

	/**
	 * Name for one row, shown in the row header.
	 */
	protected function row_label(): string {
		return (string) $this->arg( 'row_label', __( 'Item', 'zihad-travel-cms' ) );
	}
}
