<?php
/**
 * Demo data module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\DemoData;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * The demo content generator + installer. Generation is pure data
 * transformation (sources → importer JSON); installation is a thin
 * wrapper over the Importer module.
 */
final class DemoDataModule extends BaseModule {

	/**
	 * Constructor.
	 *
	 * @param DemoDataActions $actions Resume/reset admin actions.
	 */
	public function __construct( private DemoDataActions $actions ) {}

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'demo-data';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter(
			'ztc_rest_controllers',
			static function ( array $controllers ): array {
				$controllers[] = DemoDataController::class;

				return $controllers;
			}
		);

		add_action(
			'cli_init',
			static function (): void {
				\WP_CLI::add_command( 'ztc demo', ztc()->get( DemoCliCommand::class ) );
			}
		);

		// Informational flag only — DemoDataStatus computes the real
		// installed state from record counts; this survives for
		// anything third-party that read the option.
		add_action(
			'ztc_import_batch_processed',
			static function ( object $job ): void {
				if (
					$job->is_finished()
					&& str_contains( (string) $job->file, 'demo-data' )
					&& ( $job->created + $job->updated ) > 0
				) {
					update_option( 'ztc_demo_installed', 1, false );
				}
			}
		);

		// Resume/reset run through admin-post.php, which loads wp-admin
		// for logged-in users regardless of screen.
		if ( is_admin() ) {
			$this->actions->register();
		}
	}
}
