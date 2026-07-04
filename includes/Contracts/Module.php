<?php
/**
 * Module contract.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Implemented by every feature module (Visa, Tour, Booking, AI…).
 *
 * A module is a self-contained feature package. The ModuleManager
 * resolves each module from the container, skips it when unavailable,
 * and calls register() so it can attach its hooks.
 */
interface Module extends Registrable {

	/**
	 * Unique module identifier, e.g. `tour` or `booking`.
	 */
	public function id(): string;

	/**
	 * Whether the module can run in the current environment.
	 *
	 * Use this to gate modules on external dependencies (e.g. the
	 * Elementor module returns false when Elementor is not active)
	 * or on plugin settings toggles.
	 */
	public function is_available(): bool;
}
