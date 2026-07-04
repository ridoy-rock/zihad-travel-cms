<?php
/**
 * Default wp_mail() mailer.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Services;

use ZihadTravelCMS\Contracts\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * The default Mailer: a thin wrapper over wp_mail(), so whatever SMTP
 * plugin the site runs keeps working. Swap the implementation through
 * the `ztc_mailer` filter.
 */
final class WpMailer implements Mailer {

	/**
	 * {@inheritDoc}
	 */
	public function send( string $to, string $subject, string $message, array $headers = array() ): bool {
		return wp_mail( $to, $subject, $message, $headers );
	}
}
