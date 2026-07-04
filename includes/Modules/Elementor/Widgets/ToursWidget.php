<?php
/**
 * Tours grid widget.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor widget rendering a grid of tour cards.
 */
final class ToursWidget extends CardGridWidget {

	/**
	 * {@inheritDoc}
	 */
	public function get_name() {
		return 'ztc-tours';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title() {
		return __( 'Tours Grid', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function grid_type(): string {
		return 'tour';
	}
}
