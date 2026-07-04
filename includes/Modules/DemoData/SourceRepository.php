<?php
/**
 * Demo data source repository.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Modules\DemoData;

use RuntimeException;
use ZihadTravelCMS\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the hand-curated generator inputs from /demo-data/sources —
 * the country seed catalog and the localized template bank. All demo
 * content lives in these data files, never in PHP.
 */
final class SourceRepository {

	/**
	 * Cached sources.
	 *
	 * @var array<string, array<mixed>>
	 */
	private array $cache = array();

	/**
	 * Constructor.
	 *
	 * @param Config $config Plugin configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * The 100+ country seeds.
	 *
	 * @return array<array<string, mixed>>
	 */
	public function seeds(): array {
		return array_values( array_filter( (array) ( $this->load( 'countries' )['seeds'] ?? array() ), 'is_array' ) );
	}

	/**
	 * The localized template bank.
	 *
	 * @return array<string, mixed>
	 */
	public function templates(): array {
		return $this->load( 'templates' );
	}

	/**
	 * Where generated demo files are written and read.
	 */
	public function output_dir(): string {
		/**
		 * Filter the demo-data directory (e.g. to generate into
		 * uploads when the plugin directory is read-only).
		 *
		 * @param string $dir Absolute directory path.
		 */
		return (string) apply_filters( 'ztc_demo_data_dir', $this->config->path( 'demo-data' ) );
	}

	/**
	 * The generated file for a content type.
	 *
	 * @param string $type country|visa|tour.
	 */
	public function output_file( string $type ): string {
		$names = array(
			'country' => 'countries.json',
			'visa'    => 'visas.json',
			'tour'    => 'tours.json',
		);

		return trailingslashit( $this->output_dir() ) . ( $names[ $type ] ?? $type . '.json' );
	}

	/**
	 * Load and validate one source file.
	 *
	 * @param string $name File name without extension.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws RuntimeException When the source is missing or invalid.
	 */
	private function load( string $name ): array {
		if ( isset( $this->cache[ $name ] ) ) {
			return $this->cache[ $name ];
		}

		$path = $this->config->path( 'demo-data/sources/' . $name . '.json' );

		if ( ! is_readable( $path ) ) {
			throw new RuntimeException( sprintf( 'Demo source "%s" is missing.', $path ) );
		}

		$data = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! is_array( $data ) ) {
			throw new RuntimeException( sprintf( 'Demo source "%s" contains invalid JSON: %s', $path, json_last_error_msg() ) );
		}

		$this->cache[ $name ] = $data;

		return $data;
	}
}
