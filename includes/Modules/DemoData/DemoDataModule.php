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
	}
}
