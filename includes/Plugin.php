<?php
/**
 * Main plugin kernel.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS;

use ZihadTravelCMS\Core\Container;
use ZihadTravelCMS\Core\ServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * The plugin kernel.
 *
 * Owns the DI container and boots the service providers in two phases
 * (register all, then boot all). The kernel itself contains no feature
 * logic — features live in providers and modules.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * The DI container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Instantiated service providers.
	 *
	 * @var array<ServiceProvider>
	 */
	private array $providers = array();

	/**
	 * Whether boot() has already run.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Retrieve the kernel instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {
		$this->container = new Container();

		// Core self-bindings available before any provider runs.
		$this->container->instance( Container::class, $this->container );
		$this->container->instance( self::class, $this );
		$this->container->singleton( Core\Config::class );
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Boot the plugin. Runs on `plugins_loaded`.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		foreach ( $this->provider_classes() as $class ) {
			$this->providers[] = new $class( $this->container );
		}

		foreach ( $this->providers as $provider ) {
			$provider->register();
		}

		foreach ( $this->providers as $provider ) {
			$provider->boot();
		}

		/**
		 * Fires after all Zihad Travel CMS providers have booted.
		 *
		 * @param Plugin $plugin The plugin kernel.
		 */
		do_action( 'ztc_booted', $this );
	}

	/**
	 * The service providers that compose the plugin.
	 *
	 * @return array<class-string<ServiceProvider>>
	 */
	private function provider_classes(): array {
		$providers = array(
			Core\CoreServiceProvider::class,
			Settings\SettingsServiceProvider::class,
			Admin\AdminServiceProvider::class,
			Frontend\FrontendServiceProvider::class,
			RestApi\RestApiServiceProvider::class,
			Modules\ModulesServiceProvider::class,
		);

		/**
		 * Filter the service providers booted by the plugin.
		 *
		 * @param array<class-string<ServiceProvider>> $providers Provider class names.
		 */
		return (array) apply_filters( 'ztc_service_providers', $providers );
	}

	/**
	 * The plugin container.
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $id Class or interface name.
	 */
	public function get( string $id ): object {
		return $this->container->get( $id );
	}

	/**
	 * Activation hook bridge.
	 *
	 * Static because register_activation_hook() needs a plain callable;
	 * all real work happens in the injectable Activator service.
	 */
	public static function activate(): void {
		self::instance()->get( Core\Activator::class )->run();
	}

	/**
	 * Deactivation hook bridge.
	 */
	public static function deactivate(): void {
		self::instance()->get( Core\Deactivator::class )->run();
	}
}
