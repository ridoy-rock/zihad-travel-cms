<?php
/**
 * Analytics module (placeholder).
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Analytics;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Future scope: view counts for tours/visas, WhatsApp click tracking,
 * enquiry conversion stats and an admin dashboard widget. Aggregates
 * into a custom table (created via Core\Upgrade migrations) rather
 * than post meta.
 */
final class AnalyticsModule extends BaseModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'analytics';
	}
}
