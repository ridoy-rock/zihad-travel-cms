<?php
/**
 * Plugin deactivation routine.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs once when the plugin is deactivated.
 *
 * Deactivation must be non-destructive: no data is deleted here.
 * Permanent cleanup lives in uninstall.php and only runs when the
 * user has opted in.
 */
final class Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public function run(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Remove any scheduled maintenance events.
		wp_clear_scheduled_hook( 'ztc_daily_maintenance' );

		// Remove the plugin's rewrite rules.
		flush_rewrite_rules();

		/**
		 * Fires after Zihad Travel CMS has been deactivated.
		 */
		do_action( 'ztc_deactivated' );
	}
}
