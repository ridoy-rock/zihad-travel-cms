<?php
/**
 * Inquiry form Elementor widget.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use ZihadTravelCMS\Views\InquiryFormRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * The visa/tour inquiry form for Elementor. Rendering delegates to the
 * shared InquiryFormRenderer, so the widget, the [ztc_inquiry_form]
 * shortcode and the single templates output identical,
 * theme-overridable markup.
 *
 * Widgets are instantiated by Elementor (no DI); the container is
 * reached through ztc() at this integration boundary only.
 */
final class InquiryFormWidget extends Widget_Base {

	/**
	 * {@inheritDoc}
	 */
	public function get_name() {
		return 'ztc-inquiry-form';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title() {
		return __( 'Inquiry Form', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_categories() {
		return array( 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_keywords() {
		return array( 'travel', 'inquiry', 'booking', 'contact', 'form' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array( 'label' => __( 'Content', 'zihad-travel-cms' ) )
		);

		$this->add_control(
			'heading',
			array(
				'label' => __( 'Heading', 'zihad-travel-cms' ),
				'type'  => Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'inquiry_type',
			array(
				'label'   => __( 'Inquiry type', 'zihad-travel-cms' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'visa' => __( 'Visa', 'zihad-travel-cms' ),
					'tour' => __( 'Tour', 'zihad-travel-cms' ),
				),
				'default' => 'visa',
			)
		);

		$this->add_control(
			'post_id',
			array(
				'label'       => __( 'Related visa/tour ID', 'zihad-travel-cms' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'default'     => 0,
				'description' => __( '0 sends a general inquiry; on single templates the current post is used automatically.', 'zihad-travel-cms' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component output is escaped internally.
		echo ztc()->get( InquiryFormRenderer::class )->render(
			array(
				'heading' => (string) ( $settings['heading'] ?? '' ),
				'type'    => (string) ( $settings['inquiry_type'] ?? 'visa' ),
				'post_id' => (int) ( $settings['post_id'] ?? 0 ),
			)
		);
	}
}
