<?php
/**
 * Countries grid widget.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Elementor\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor widget rendering a grid of country cards.
 */
final class CountriesWidget extends CardGridWidget {

	/**
	 * {@inheritDoc}
	 */
	public function get_name() {
		return 'ztc-countries';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title() {
		return __( 'Countries Grid', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function grid_type(): string {
		return 'country';
	}
}
