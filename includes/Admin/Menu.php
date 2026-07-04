<?php
/**
 * Plugin admin menu.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin;

use ZihadTravelCMS\Admin\Pages\AdminPage;
use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Helpers\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin's top-level "Travel CMS" menu. Every AdminPage
 * attaches beneath it. The top-level callback renders the dashboard
 * template (a placeholder until the Dashboard page is built).
 */
final class Menu implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param Template      $template  Template renderer.
	 * @param DashboardData $dashboard Dashboard view-model provider.
	 */
	public function __construct(
		private Template $template,
		private DashboardData $dashboard,
	) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		// Priority 9: the top-level menu must exist before pages attach at 10.
		add_action( 'admin_menu', array( $this, 'add_menu' ), 9 );
	}

	/**
	 * Add the top-level menu.
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'Zihad Travel CMS', 'zihad-travel-cms' ),
			__( 'Travel CMS', 'zihad-travel-cms' ),
			'manage_options',
			AdminPage::MENU_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-airplane',
			29
		);
	}

	/**
	 * Render the top-level (dashboard) screen.
	 */
	public function render_dashboard(): void {
		$this->template->render( 'admin/dashboard.php', $this->dashboard->stats() );
	}
}
