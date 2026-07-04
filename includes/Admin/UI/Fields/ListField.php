<?php
/**
 * List field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * An ordered list of plain strings (add / remove / reorder), saved as
 * `array<string>` to match the string-list meta schema. Backs fields
 * like required documents, benefits, highlights and inclusions.
 *
 * Args: `item_label` (label above each input, default "Item"),
 * `item_placeholder`, plus the repeater's `row_label` /
 * `button_label`.
 */
class ListField extends RepeaterField {

	/**
	 * Constructor.
	 *
	 * @param string               $name  Meta key, e.g. `ztc_benefits`.
	 * @param string               $label Translated field label.
	 * @param array<string, mixed> $args  Field options (sub-fields are preset).
	 */
	public function __construct( string $name, string $label, array $args = array() ) {
		$args['fields'] = array(
			array(
				'key'         => 'value',
				'label'       => (string) ( $args['item_label'] ?? __( 'Item', 'zihad-travel-cms' ) ),
				'type'        => 'text',
				'placeholder' => (string) ( $args['item_placeholder'] ?? '' ),
			),
		);

		$args['row_label']    ??= (string) ( $args['item_label'] ?? __( 'Item', 'zihad-travel-cms' ) );
		$args['button_label'] ??= __( 'Add item', 'zihad-travel-cms' );

		parent::__construct( $name, $label, $args );
	}

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'list';
	}

	/**
	 * Stored value is a flat string list; wrap it into repeater rows.
	 *
	 * {@inheritDoc}
	 */
	public function render( mixed $value ): void {
		$rows = array_map(
			static fn( mixed $item ): array => array( 'value' => (string) $item ),
			array_values( array_filter( (array) $value, 'is_scalar' ) )
		);

		parent::render( $rows );
	}

	/**
	 * Flatten submitted rows back to `array<string>`.
	 *
	 * {@inheritDoc}
	 */
	public function sanitize( mixed $value ): array {
		$items = array_map(
			static fn( array $row ): string => (string) ( $row['value'] ?? '' ),
			parent::sanitize( $value )
		);

		return array_values( array_filter( $items, static fn( string $item ): bool => '' !== $item ) );
	}
}
