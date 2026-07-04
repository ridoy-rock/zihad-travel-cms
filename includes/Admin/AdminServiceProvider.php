<?php
/**
 * Admin service provider.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin;

use ZihadTravelCMS\Admin\Pages\AdminPage;
use ZihadTravelCMS\Core\ServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Registers everything that only exists in wp-admin: the plugin menu,
 * its pages, and later list-table columns and metaboxes.
 */
final class AdminServiceProvider extends ServiceProvider {

	/**
	 * Admin page classes, in menu order.
	 *
	 * The Dashboard and Settings pages are added here when they are
	 * built.
	 *
	 * @return array<class-string<AdminPage>>
	 */
	private function pages(): array {
		$pages = array(
			Pages\SettingsPage::class,
			Pages\HealthPage::class,
		);

		/**
		 * Filter the admin pages registered by the plugin.
		 *
		 * @param array<class-string<AdminPage>> $pages Admin page class names.
		 */
		return (array) apply_filters( 'ztc_admin_pages', $pages );
	}

	/**
	 * {@inheritDoc}
	 */
	public function boot(): void {
		if ( ! is_admin() ) {
			return;
		}

		$this->container->get( Menu::class )->register();

		foreach ( $this->pages() as $page_class ) {
			$page = $this->container->get( $page_class );

			if ( $page instanceof AdminPage ) {
				$page->register();
			}
		}
	}
}
