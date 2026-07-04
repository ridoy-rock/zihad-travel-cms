<?php
/**
 * Modules service provider.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules;

use ZihadTravelCMS\Core\ServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the module system.
 *
 * Runs last in the provider list so every module can rely on core,
 * admin, settings and REST services being bound.
 */
final class ModulesServiceProvider extends ServiceProvider {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$this->container->singleton( ModuleManager::class );
	}

	/**
	 * {@inheritDoc}
	 */
	public function boot(): void {
		$this->container->get( ModuleManager::class )->load();
	}
}
