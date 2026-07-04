<?php
/**
 * CTA widget.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use ZihadTravelCMS\Views\Cards\CtaCard;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor widget wrapping the CTA card. Empty controls fall back to
 * the agency's global settings (WhatsApp, phone, defaults).
 */
final class CtaWidget extends Widget_Base {

	/**
	 * {@inheritDoc}
	 */
	public function get_name() {
		return 'ztc-cta';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title() {
		return __( 'Travel CTA', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_icon() {
		return 'eicon-call-to-action';
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
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array( 'label' => __( 'Content', 'zihad-travel-cms' ) )
		);

		$this->add_control(
			'title',
			array(
				'label' => __( 'Title', 'zihad-travel-cms' ),
				'type'  => Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'text',
			array(
				'label' => __( 'Text', 'zihad-travel-cms' ),
				'type'  => Controls_Manager::TEXTAREA,
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label' => __( 'Button Text', 'zihad-travel-cms' ),
				'type'  => Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'button_url',
			array(
				'label' => __( 'Button URL', 'zihad-travel-cms' ),
				'type'  => Controls_Manager::URL,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$options = array_filter(
			array(
				'title'       => (string) ( $settings['title'] ?? '' ),
				'text'        => (string) ( $settings['text'] ?? '' ),
				'button_text' => (string) ( $settings['button_text'] ?? '' ),
				'button_url'  => (string) ( $settings['button_url']['url'] ?? '' ),
			),
			static fn( string $value ): bool => '' !== $value
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component output is escaped internally.
		echo ztc()->get( CtaCard::class )->render( 0, $options );
	}
}
