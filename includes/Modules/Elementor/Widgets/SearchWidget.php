<?php
/**
 * Travel search Elementor widget.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use ZihadTravelCMS\Views\SearchWidgetRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * The tabbed Visa/Tour search widget for Elementor. Rendering
 * delegates to the shared SearchWidgetRenderer, so the widget, the
 * [ztc_search_widget] shortcode and the homepage injection output
 * identical, theme-overridable markup.
 *
 * Widgets are instantiated by Elementor (no DI); the container is
 * reached through ztc() at this integration boundary only.
 */
final class SearchWidget extends Widget_Base {

	/**
	 * {@inheritDoc}
	 */
	public function get_name() {
		return 'ztc-search-widget';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title() {
		return __( 'Travel Search', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_icon() {
		return 'eicon-site-search';
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
		return array( 'travel', 'search', 'visa', 'tour', 'filter' );
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
			'tabs',
			array(
				'label'   => __( 'Tabs', 'zihad-travel-cms' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'visa,tour' => __( 'Visas & Tours', 'zihad-travel-cms' ),
					'visa'      => __( 'Visas only', 'zihad-travel-cms' ),
					'tour'      => __( 'Tours only', 'zihad-travel-cms' ),
				),
				'default' => 'visa,tour',
			)
		);

		$this->add_control(
			'default_tab',
			array(
				'label'   => __( 'Open tab', 'zihad-travel-cms' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'visa' => __( 'Visas', 'zihad-travel-cms' ),
					'tour' => __( 'Tours', 'zihad-travel-cms' ),
				),
				'default' => 'visa',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$html = ztc()->get( SearchWidgetRenderer::class )->render(
			array(
				'heading' => (string) ( $settings['heading'] ?? '' ),
				'tabs'    => (string) ( $settings['tabs'] ?? 'visa,tour' ),
				'default' => (string) ( $settings['default_tab'] ?? '' ),
			)
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component output is escaped internally.
		echo $html;
	}
}
