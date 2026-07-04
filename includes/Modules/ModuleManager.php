<?php
/**
 * Module manager.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules;

use ZihadTravelCMS\Contracts\Module;
use ZihadTravelCMS\Core\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Loads every feature module.
 *
 * Modules (Visa, Tour, Country, Search, Elementor, AI, Importer, SEO,
 * Booking…) are self-contained feature packages under
 * ZihadTravelCMS\Modules. Each is resolved from the container, skipped
 * when unavailable (missing dependency or disabled in settings) and
 * then registered.
 */
final class ModuleManager {

	/**
	 * Loaded modules, keyed by module id.
	 *
	 * @var array<string, Module>
	 */
	private array $modules = array();

	/**
	 * Constructor.
	 *
	 * @param Container $container The plugin container.
	 */
	public function __construct( private Container $container ) {}

	/**
	 * Resolve, filter and register every module.
	 */
	public function load(): void {
		foreach ( $this->module_classes() as $module_class ) {
			$module = $this->container->get( $module_class );

			if ( ! $module instanceof Module || ! $module->is_available() ) {
				continue;
			}

			$module->register();
			$this->modules[ $module->id() ] = $module;
		}

		/**
		 * Fires after all modules have been loaded.
		 *
		 * @param ModuleManager $manager The module manager.
		 */
		do_action( 'ztc_modules_loaded', $this );
	}

	/**
	 * The module classes shipped with the plugin.
	 *
	 * @return array<class-string<Module>>
	 */
	private function module_classes(): array {
		$modules = array(
			// Content modules.
			Country\CountryModule::class,
			Visa\VisaModule::class,
			Tour\TourModule::class,
			// Future modules (placeholders — scope in each class docblock).
			Search\SearchModule::class,
			Booking\BookingModule::class,
			Importer\ImporterModule::class,
			DemoData\DemoDataModule::class,
			Seo\SeoModule::class,
			Wizard\WizardModule::class,
			Ai\AiModule::class,
			Elementor\ElementorModule::class,
			Analytics\AnalyticsModule::class,
		);

		/**
		 * Filter the modules loaded by the plugin.
		 *
		 * Third-party code can add its own modules or remove built-in
		 * ones.
		 *
		 * @param array<class-string<Module>> $modules Module class names.
		 */
		return (array) apply_filters( 'ztc_modules', $modules );
	}

	/**
	 * Retrieve a loaded module by id.
	 *
	 * @param string $id Module id, e.g. `tour`.
	 */
	public function get( string $id ): ?Module {
		return $this->modules[ $id ] ?? null;
	}

	/**
	 * All loaded modules, keyed by id.
	 *
	 * @return array<string, Module>
	 */
	public function all(): array {
		return $this->modules;
	}
}
