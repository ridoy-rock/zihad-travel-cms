<?php
/**
 * Version management and upgrade pipeline.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Core;

use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Detects version changes and runs ordered upgrade routines.
 *
 * Future releases add entries to migrations(); each routine runs once,
 * in version order, when a site updates past that version. This is
 * where database schema changes, option migrations and data backfills
 * belong.
 */
final class Upgrade implements Registrable {

	/**
	 * Constructor.
	 *
	 * @param Config $config Plugin configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'maybe_upgrade' ), 5 );
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), PHP_INT_MAX );
	}

	/**
	 * Run pending upgrade routines when the plugin version has changed.
	 */
	public function maybe_upgrade(): void {
		$installed = (string) get_option( Config::VERSION_OPTION, '' );
		$current   = $this->config->version();

		if ( $installed === $current ) {
			return;
		}

		// Fresh install: the Activator already seeded a clean state, so
		// record the version without replaying historical migrations.
		if ( '' === $installed ) {
			update_option( Config::VERSION_OPTION, $current );
			return;
		}

		foreach ( $this->migrations() as $version => $routine ) {
			if ( version_compare( $installed, (string) $version, '<' ) ) {
				$routine();
			}
		}

		update_option( Config::VERSION_OPTION, $current );

		// New rewrite rules may ship with any release.
		update_option( 'ztc_flush_rewrite_rules', 1 );

		/**
		 * Fires after the plugin has upgraded to a new version.
		 *
		 * @param string $installed Previously installed version ('' on first install).
		 * @param string $current   Newly installed version.
		 */
		do_action( 'ztc_upgraded', $installed, $current );
	}

	/**
	 * Upgrade routines, keyed by the version that introduced them.
	 *
	 * Example for a future release:
	 *
	 *     return array(
	 *         '1.1.0' => array( $this, 'upgrade_1_1_0' ),
	 *     );
	 *
	 * @return array<string, callable>
	 */
	private function migrations(): array {
		return array();
	}

	/**
	 * Flush rewrite rules once, after activation or an upgrade.
	 *
	 * Runs at the very end of `init` so every custom post type and
	 * taxonomy is registered before the flush.
	 */
	public function maybe_flush_rewrite_rules(): void {
		if ( ! get_option( 'ztc_flush_rewrite_rules' ) ) {
			return;
		}

		delete_option( 'ztc_flush_rewrite_rules' );
		flush_rewrite_rules();
	}
}
