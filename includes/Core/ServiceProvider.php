<?php
/**
 * Service provider base class.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for all service providers.
 *
 * Providers are the composition root of the plugin. Each one owns a
 * bounded area (core, admin, frontend, REST, modules…) and runs in two
 * phases:
 *
 *  1. register() — bind services into the container. No hooks, no work.
 *  2. boot()     — attach hooks. Every provider's register() has run by
 *                  now, so cross-provider dependencies are safe.
 */
abstract class ServiceProvider {

	/**
	 * The plugin container.
	 *
	 * @var Container
	 */
	protected Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container The plugin container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Bind services into the container. Must not attach hooks.
	 */
	public function register(): void {}

	/**
	 * Attach hooks. Runs after every provider has registered.
	 */
	public function boot(): void {}
}
