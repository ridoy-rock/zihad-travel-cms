<?php
/**
 * FAQ builder field.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\UI\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * A repeater preset for FAQs: question (text) + answer (rich
 * textarea). Saves rows matching the `ztc_faq` meta schema.
 */
class FaqField extends RepeaterField {

	/**
	 * Constructor.
	 *
	 * @param string               $name  Meta key, e.g. `ztc_faq`.
	 * @param string               $label Translated field label.
	 * @param array<string, mixed> $args  Field options (sub-fields are preset).
	 */
	public function __construct( string $name, string $label, array $args = array() ) {
		$args['fields'] = array(
			array(
				'key'   => 'question',
				'label' => __( 'Question', 'zihad-travel-cms' ),
				'type'  => 'text',
			),
			array(
				'key'   => 'answer',
				'label' => __( 'Answer', 'zihad-travel-cms' ),
				'type'  => 'textarea',
				'rows'  => 4,
			),
		);

		$args['row_label']    ??= __( 'FAQ', 'zihad-travel-cms' );
		$args['button_label'] ??= __( 'Add FAQ', 'zihad-travel-cms' );

		parent::__construct( $name, $label, $args );
	}

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'faq';
	}
}
