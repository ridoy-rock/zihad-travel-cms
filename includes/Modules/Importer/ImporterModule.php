<?php
/**
 * Importer module (placeholder).
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Future scope: CSV/JSON import of countries, visas and tours, plus a
 * WP Travel Engine migration path so agencies can switch to this
 * plugin without re-entering content. Progress reporting goes through
 * NotificationService.
 */
final class ImporterModule extends BaseModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'importer';
	}
}
