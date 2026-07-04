<?php
/**
 * Mailer contract.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Outbound mail abstraction.
 *
 * The plugin never calls wp_mail() directly — everything goes through
 * this contract, so future providers (SMTP services, transactional
 * APIs, queues) plug in by binding another implementation via the
 * `ztc_mailer` filter without touching any calling code.
 */
interface Mailer {

	/**
	 * Send one message.
	 *
	 * @param string        $to      Recipient address.
	 * @param string        $subject Subject line (plain text).
	 * @param string        $message Body (plain text).
	 * @param array<string> $headers Additional headers, e.g. Reply-To.
	 *
	 * @return bool Whether the message was accepted for delivery.
	 */
	public function send( string $to, string $subject, string $message, array $headers = array() ): bool;
}
