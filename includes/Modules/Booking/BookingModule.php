<?php
/**
 * Booking module (placeholder).
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Booking;

use ZihadTravelCMS\Modules\BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Future scope: booking/enquiry post type, availability, traveller
 * details, email notifications and payment-gateway integration. Runs
 * its own database migrations through Core\Upgrade when it lands.
 */
final class BookingModule extends BaseModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'booking';
	}
}
