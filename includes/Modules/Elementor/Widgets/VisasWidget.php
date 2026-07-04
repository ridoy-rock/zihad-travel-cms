<?php
/**
 * Visas grid widget.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor widget rendering a grid of visa cards.
 */
final class VisasWidget extends CardGridWidget {

	/**
	 * {@inheritDoc}
	 */
	public function get_name() {
		return 'ztc-visas';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title() {
		return __( 'Visas Grid', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function grid_type(): string {
		return 'visa';
	}
}
