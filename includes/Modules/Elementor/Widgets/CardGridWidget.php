<?php
/**
 * Abstract card grid widget.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use ZihadTravelCMS\Views\GridRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Base for the Tours/Visas/Countries grid widgets. Rendering delegates
 * to the shared GridRenderer, so widgets, shortcodes and AJAX results
 * output identical cards.
 *
 * Widgets are instantiated by Elementor (no DI); the container is
 * reached through ztc() at this integration boundary only.
 */
abstract class CardGridWidget extends Widget_Base {

	/**
	 * The grid's content type: tour|visa|country.
	 */
	abstract protected function grid_type(): string;

	/**
	 * {@inheritDoc}
	 */
	public function get_categories() {
		return array( 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_keywords() {
		return array( 'travel', 'grid', 'cards', $this->grid_type() );
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
			'count',
			array(
				'label'   => __( 'Number of items', 'zihad-travel-cms' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 24,
				'default' => 6,
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => __( 'Columns', 'zihad-travel-cms' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'default' => '3',
			)
		);

		$this->add_control(
			'term',
			array(
				'label'       => __( 'Filter by type (slug)', 'zihad-travel-cms' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'A tour-type or visa-type slug, e.g. adventure.', 'zihad-travel-cms' ),
			)
		);

		$this->add_control(
			'region',
			array(
				'label'       => __( 'Filter by region (slug)', 'zihad-travel-cms' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'A region slug, e.g. southeast-asia.', 'zihad-travel-cms' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$html = ztc()->get( GridRenderer::class )->render(
			$this->grid_type(),
			array(
				'heading' => (string) ( $settings['heading'] ?? '' ),
				'count'   => (int) ( $settings['count'] ?? 6 ),
				'columns' => (int) ( $settings['columns'] ?? 3 ),
				'term'    => (string) ( $settings['term'] ?? '' ),
				'region'  => (string) ( $settings['region'] ?? '' ),
			)
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component output is escaped internally.
		echo $html;
	}
}
