<?php
/**
 * Importer module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer;

use ZihadTravelCMS\Admin\Pages\ImportExportPage;
use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * The generic import/export engine: CSV/JSON in, CSV/JSON out, batched
 * jobs with progress, resume, error logs and rollback — exposed over
 * REST (admin progress UI), WP-CLI and reusable by the demo-data
 * installer. Content types plug in via the `ztc_import_mappings`
 * filter.
 */
final class ImporterModule extends BaseModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'importer';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter(
			'ztc_rest_controllers',
			static function ( array $controllers ): array {
				$controllers[] = ImportController::class;

				return $controllers;
			}
		);

		add_filter(
			'ztc_admin_pages',
			static function ( array $pages ): array {
				$pages[] = ImportExportPage::class;

				return $pages;
			}
		);

		add_action(
			'cli_init',
			static function (): void {
				\WP_CLI::add_command( 'ztc', ztc()->get( ImportCliCommand::class ) );
			}
		);
	}
}
