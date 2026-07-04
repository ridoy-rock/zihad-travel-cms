<?php
/**
 * Plugin activation routine.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs once when the plugin is activated.
 *
 * Invoked through the Plugin::activate() bridge so it stays injectable.
 */
final class Activator {

	/**
	 * Constructor.
	 *
	 * @param Config $config Plugin configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * Activate the plugin.
	 *
	 * Seeds default options and schedules a rewrite-rules flush for the
	 * next request, after the custom post types have been registered.
	 */
	public function run(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// add_option() never overwrites, so re-activation keeps user data.
		add_option( Config::VERSION_OPTION, $this->config->version() );
		add_option( 'ztc_installed_at', time() );
		add_option( Config::OPTION_NAME, $this->config->defaults() );

		// Post types are not registered during activation, so flushing
		// here would be a no-op. The Upgrade service flushes at the end
		// of the next `init` once the CPTs exist.
		update_option( 'ztc_flush_rewrite_rules', 1 );

		/**
		 * Fires after Zihad Travel CMS has been activated.
		 */
		do_action( 'ztc_activated' );
	}
}
