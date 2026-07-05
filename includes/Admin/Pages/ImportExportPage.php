<?php
/**
 * Import/Export admin page.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Admin\Pages;

use ZihadTravelCMS\Helpers\Template;
use ZihadTravelCMS\Modules\DemoData\DemoDataStatus;
use ZihadTravelCMS\Modules\Importer\ExportService;
use ZihadTravelCMS\Modules\Importer\ImportService;
use ZihadTravelCMS\Modules\Importer\MappingRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * The Import/Export screen: pick an uploaded CSV/JSON file, run the
 * batched import with a live progress bar and error log, or download
 * an export. Registered by the Importer module via `ztc_admin_pages`.
 */
final class ImportExportPage extends AdminPage {

	/**
	 * Constructor.
	 *
	 * @param Template        $template Template renderer.
	 * @param MappingRegistry $registry Mapping registry.
	 * @param DemoDataStatus  $demo     Truthful demo data state.
	 */
	public function __construct( Template $template, private MappingRegistry $registry, private DemoDataStatus $demo ) {
		parent::__construct( $template );
	}

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'zihad-travel-cms-import';
	}

	/**
	 * {@inheritDoc}
	 */
	public function page_title(): string {
		return __( 'Import / Export', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function menu_title(): string {
		return __( 'Import / Export', 'zihad-travel-cms' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function position(): int {
		return 80;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render(): void {
		wp_enqueue_media();

		$this->template->render(
			'admin/import-export.php',
			array(
				'types'   => $this->registry->types(),
				'modes'   => ImportService::MODES,
				'formats' => ExportService::FORMATS,
				'demo'    => $this->demo->status(),
			)
		);
	}
}
