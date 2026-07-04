<?php
/**
 * Plugin Health admin page.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\Pages;

use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Services\HealthService;

defined( 'ABSPATH' ) || exit;

/**
 * Shows the environment checks computed by HealthService: PHP,
 * WordPress, REST, rewrites, Elementor, cache and plugin version.
 */
final class HealthPage extends AdminPage {

	/**
	 * Constructor.
	 *
	 * @param Template      $template Template renderer.
	 * @param HealthService $health   Health service.
	 */
	public function __construct( Template $template, private HealthService $health ) {
		parent::__construct( $template );
	}

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'zihad-travel-cms-health';
	}

	/**
	 * {@inheritDoc}
	 */
	public function page_title(): string {
		return __( 'Plugin Health', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function menu_title(): string {
		return __( 'Health', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function position(): int {
		return 90; // Near the bottom of the plugin submenu.
	}

	/**
	 * {@inheritDoc}
	 */
	public function render(): void {
		$this->template->render(
			'admin/health.php',
			array( 'checks' => $this->health->checks() )
		);
	}
}
