<?php
/**
 * Import mapping registry.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\Importer;

use ZihadTravelCMS\Contracts\ImportMapping;
use ZihadTravelCMS\Core\Container;
use ZihadTravelCMS\Modules\Country\CountryImportMapping;
use ZihadTravelCMS\Modules\Tour\TourImportMapping;
use ZihadTravelCMS\Modules\Visa\VisaImportMapping;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the available import mappings. Future modules register
 * theirs through the `ztc_import_mappings` filter and automatically
 * gain CSV/JSON import + export, REST and WP-CLI support.
 */
final class MappingRegistry {

	/**
	 * Loaded mappings, keyed by type.
	 *
	 * @var array<string, ImportMapping>|null
	 */
	private ?array $mappings = null;

	/**
	 * Constructor.
	 *
	 * @param Container $container The plugin container.
	 */
	public function __construct( private Container $container ) {}

	/**
	 * A mapping by type, or null.
	 *
	 * @param string $type Import type, e.g. `tour`.
	 */
	public function get( string $type ): ?ImportMapping {
		return $this->all()[ $type ] ?? null;
	}

	/**
	 * All mappings, keyed by type.
	 *
	 * @return array<string, ImportMapping>
	 */
	public function all(): array {
		if ( null !== $this->mappings ) {
			return $this->mappings;
		}

		$classes = array(
			CountryImportMapping::class,
			VisaImportMapping::class,
			TourImportMapping::class,
		);

		/**
		 * Filter the registered import mappings.
		 *
		 * @param array<class-string<ImportMapping>> $classes Mapping class names.
		 */
		$classes = (array) apply_filters( 'ztc_import_mappings', $classes );

		$this->mappings = array();

		foreach ( $classes as $class ) {
			$mapping = $this->container->get( $class );

			if ( $mapping instanceof ImportMapping ) {
				$this->mappings[ $mapping->type() ] = $mapping;
			}
		}

		return $this->mappings;
	}

	/**
	 * The available type ids.
	 *
	 * @return array<string>
	 */
	public function types(): array {
		return array_keys( $this->all() );
	}
}
