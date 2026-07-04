<?php
/**
 * Registrable contract.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Implemented by every service that hooks into WordPress.
 */
interface Registrable {

	/**
	 * Attach the service's hooks. Called once during plugin boot.
	 */
	public function register(): void;
}
