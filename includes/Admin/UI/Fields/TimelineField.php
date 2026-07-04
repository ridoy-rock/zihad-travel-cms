<?php
/**
 * Timeline builder field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A repeater preset for ordered steps: title (text) + description
 * (rich textarea), rendered with step numbers. Backs itineraries
 * (`ztc_itinerary`) and application processes
 * (`ztc_application_process`).
 *
 * Args: `row_label` defaults to "Step" — pass "Day" for itineraries.
 */
class TimelineField extends RepeaterField {

	/**
	 * Constructor.
	 *
	 * @param string               $name  Meta key, e.g. `ztc_itinerary`.
	 * @param string               $label Translated field label.
	 * @param array<string, mixed> $args  Field options (sub-fields are preset).
	 */
	public function __construct( string $name, string $label, array $args = array() ) {
		$args['fields'] = array(
			array(
				'key'   => 'title',
				'label' => __( 'Title', 'zihad-travel-cms' ),
				'type'  => 'text',
			),
			array(
				'key'   => 'description',
				'label' => __( 'Description', 'zihad-travel-cms' ),
				'type'  => 'textarea',
				'rows'  => 4,
			),
		);

		$args['row_label']    ??= __( 'Step', 'zihad-travel-cms' );
		$args['button_label'] ??= __( 'Add step', 'zihad-travel-cms' );

		parent::__construct( $name, $label, $args );
	}

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'timeline';
	}
}
