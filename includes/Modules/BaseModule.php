<?php
/**
 * Abstract module.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules;

use ZihadTravelCMS\Contracts\Module;
use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for feature modules.
 *
 * A concrete module declares its Registrable components (post type,
 * taxonomy, meta, hooks…) and this class registers them. Modules are
 * available by default; override is_available() to gate on external
 * dependencies or settings toggles.
 */
abstract class BaseModule implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		foreach ( $this->components() as $component ) {
			$component->register();
		}
	}

	/**
	 * The Registrable components this module owns.
	 *
	 * @return array<Registrable>
	 */
	protected function components(): array {
		return array();
	}
}
