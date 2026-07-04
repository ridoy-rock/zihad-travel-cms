<?php
/**
 * Plugin health service.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Services;

use ZihadTravelCMS\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Computes the environment checks shown on the Plugin Health page.
 *
 * Pure logic — rendering lives in templates/admin/health.php, so the
 * same checks can back a REST endpoint or WP-CLI command later.
 */
final class HealthService {

	public const STATUS_GOOD     = 'good';
	public const STATUS_WARNING  = 'warning';
	public const STATUS_CRITICAL = 'critical';

	private const REST_TRANSIENT = 'ztc_health_rest';

	/**
	 * Constructor.
	 *
	 * @param Config $config Plugin configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * All health checks, keyed by check id.
	 *
	 * @return array<string, array{label: string, value: string, status: string, status_label: string}>
	 */
	public function checks(): array {
		return array(
			'php'       => $this->php_check(),
			'wordpress' => $this->wordpress_check(),
			'rest'      => $this->rest_check(),
			'rewrites'  => $this->rewrites_check(),
			'elementor' => $this->elementor_check(),
			'cache'     => $this->cache_check(),
			'plugin'    => $this->plugin_check(),
		);
	}

	/**
	 * PHP version check.
	 *
	 * @return array{label: string, value: string, status: string, status_label: string}
	 */
	private function php_check(): array {
		$ok = version_compare( PHP_VERSION, $this->config->min_php(), '>=' );

		return $this->check(
			__( 'PHP Version', 'zihad-travel-cms' ),
			PHP_VERSION,
			$ok ? self::STATUS_GOOD : self::STATUS_CRITICAL
		);
	}

	/**
	 * WordPress version check.
	 *
	 * @return array{label: string, value: string, status: string, status_label: string}
	 */
	private function wordpress_check(): array {
		$version = get_bloginfo( 'version' );
		$ok      = version_compare( $version, $this->config->min_wp(), '>=' );

		return $this->check(
			__( 'WordPress Version', 'zihad-travel-cms' ),
			$version,
			$ok ? self::STATUS_GOOD : self::STATUS_CRITICAL
		);
	}

	/**
	 * REST API loopback check (result cached for five minutes).
	 *
	 * @return array{label: string, value: string, status: string, status_label: string}
	 */
	private function rest_check(): array {
		$cached = get_transient( self::REST_TRANSIENT );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$label    = __( 'REST API', 'zihad-travel-cms' );
		$response = wp_remote_get( rest_url(), array( 'timeout' => 5 ) );

		if ( is_wp_error( $response ) ) {
			$check = $this->check( $label, $response->get_error_message(), self::STATUS_CRITICAL );
		} else {
			$code  = (int) wp_remote_retrieve_response_code( $response );
			$check = 200 === $code
				? $this->check( $label, __( 'Reachable', 'zihad-travel-cms' ), self::STATUS_GOOD )
				/* translators: %d: HTTP status code. */
				: $this->check( $label, sprintf( __( 'Unexpected response (HTTP %d)', 'zihad-travel-cms' ), $code ), self::STATUS_WARNING );
		}

		set_transient( self::REST_TRANSIENT, $check, 5 * MINUTE_IN_SECONDS );

		return $check;
	}

	/**
	 * Permalink / rewrite rules check.
	 *
	 * @return array{label: string, value: string, status: string, status_label: string}
	 */
	private function rewrites_check(): array {
		$label = __( 'Rewrite Rules', 'zihad-travel-cms' );

		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			return $this->check(
				$label,
				__( 'Plain permalinks are enabled — pretty URLs like /tour/ will not work.', 'zihad-travel-cms' ),
				self::STATUS_CRITICAL
			);
		}

		if ( get_option( 'ztc_flush_rewrite_rules' ) ) {
			return $this->check(
				$label,
				__( 'A rewrite flush is pending; it completes on the next page load.', 'zihad-travel-cms' ),
				self::STATUS_WARNING
			);
		}

		return $this->check( $label, __( 'Pretty permalinks active', 'zihad-travel-cms' ), self::STATUS_GOOD );
	}

	/**
	 * Elementor availability check (optional integration).
	 *
	 * @return array{label: string, value: string, status: string, status_label: string}
	 */
	private function elementor_check(): array {
		$label = __( 'Elementor', 'zihad-travel-cms' );

		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			return $this->check( $label, ELEMENTOR_VERSION, self::STATUS_GOOD );
		}

		return $this->check(
			$label,
			__( 'Not active (optional — required only for Elementor widgets)', 'zihad-travel-cms' ),
			self::STATUS_WARNING
		);
	}

	/**
	 * Object cache check.
	 *
	 * @return array{label: string, value: string, status: string, status_label: string}
	 */
	private function cache_check(): array {
		$label = __( 'Object Cache', 'zihad-travel-cms' );

		if ( wp_using_ext_object_cache() ) {
			return $this->check( $label, __( 'Persistent object cache active', 'zihad-travel-cms' ), self::STATUS_GOOD );
		}

		return $this->check(
			$label,
			__( 'No persistent object cache detected (recommended for large sites)', 'zihad-travel-cms' ),
			self::STATUS_WARNING
		);
	}

	/**
	 * Plugin version / database version check.
	 *
	 * @return array{label: string, value: string, status: string, status_label: string}
	 */
	private function plugin_check(): array {
		$label      = __( 'Plugin Version', 'zihad-travel-cms' );
		$db_version = (string) get_option( Config::VERSION_OPTION, '' );

		if ( '' !== $db_version && $db_version !== $this->config->version() ) {
			return $this->check(
				$label,
				sprintf(
					/* translators: 1: plugin file version, 2: database version. */
					__( '%1$s (database is at %2$s — an upgrade will run on the next page load)', 'zihad-travel-cms' ),
					$this->config->version(),
					$db_version
				),
				self::STATUS_WARNING
			);
		}

		return $this->check( $label, $this->config->version(), self::STATUS_GOOD );
	}

	/**
	 * Build one check row.
	 *
	 * @param string $label  Human label.
	 * @param string $value  Current value or explanation.
	 * @param string $status One of the STATUS_* constants.
	 *
	 * @return array{label: string, value: string, status: string, status_label: string}
	 */
	private function check( string $label, string $value, string $status ): array {
		$status_labels = array(
			self::STATUS_GOOD     => __( 'Good', 'zihad-travel-cms' ),
			self::STATUS_WARNING  => __( 'Warning', 'zihad-travel-cms' ),
			self::STATUS_CRITICAL => __( 'Critical', 'zihad-travel-cms' ),
		);

		return array(
			'label'        => $label,
			'value'        => $value,
			'status'       => $status,
			'status_label' => $status_labels[ $status ] ?? $status,
		);
	}
}
