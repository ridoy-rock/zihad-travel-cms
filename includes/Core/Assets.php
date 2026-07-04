<?php
/**
 * Centralized asset management.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Core;

use ZihadTravelCMS\Contracts\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every admin and frontend style/script in one place.
 *
 * Assets are only *registered* here; each screen, template or module
 * enqueues the handles it needs, so nothing loads on pages that do
 * not use it.
 */
final class Assets implements Registrable {

	public const STYLE_ADMIN     = 'ztc-admin';
	public const STYLE_BOOTSTRAP = 'ztc-bootstrap';
	public const STYLE_FRONTEND  = 'ztc-frontend';

	public const SCRIPT_ADMIN     = 'ztc-admin';
	public const SCRIPT_BOOTSTRAP = 'ztc-bootstrap';
	public const SCRIPT_FRONTEND  = 'ztc-frontend';

	private const BOOTSTRAP_VERSION = '5.3.3';

	/**
	 * Constructor.
	 *
	 * @param Config $config Plugin configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
	}

	/**
	 * Register admin assets and enqueue them on plugin screens only.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function register_admin_assets( string $hook_suffix ): void {
		wp_register_style(
			self::STYLE_ADMIN,
			$this->config->url( 'assets/css/admin.css' ),
			array(),
			$this->config->version()
		);

		wp_register_script(
			self::SCRIPT_ADMIN,
			$this->config->url( 'assets/js/admin.js' ),
			array(),
			$this->config->version(),
			true
		);

		if ( $this->is_plugin_screen( $hook_suffix ) ) {
			wp_enqueue_style( self::STYLE_ADMIN );
			wp_enqueue_script( self::SCRIPT_ADMIN );

			wp_localize_script(
				self::SCRIPT_ADMIN,
				'ztcAdmin',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'restUrl' => esc_url_raw( rest_url( 'ztc/v1' ) ),
					'nonce'   => wp_create_nonce( 'ztc_admin' ),
				)
			);
		}
	}

	/**
	 * Register frontend assets (Bootstrap 5 + plugin bundle).
	 *
	 * Nothing is enqueued yet — frontend templates and modules enqueue
	 * these handles once they are built.
	 */
	public function register_frontend_assets(): void {
		wp_register_style(
			self::STYLE_BOOTSTRAP,
			$this->config->url( 'assets/vendor/bootstrap/bootstrap.min.css' ),
			array(),
			self::BOOTSTRAP_VERSION
		);

		wp_register_script(
			self::SCRIPT_BOOTSTRAP,
			$this->config->url( 'assets/vendor/bootstrap/bootstrap.bundle.min.js' ),
			array(),
			self::BOOTSTRAP_VERSION,
			true
		);

		wp_register_style(
			self::STYLE_FRONTEND,
			$this->config->url( 'assets/css/frontend.css' ),
			array( self::STYLE_BOOTSTRAP ),
			$this->config->version()
		);

		wp_register_script(
			self::SCRIPT_FRONTEND,
			$this->config->url( 'assets/js/frontend.js' ),
			array( self::SCRIPT_BOOTSTRAP ),
			$this->config->version(),
			true
		);

		// No nonce here on purpose: the search endpoint is public and
		// read-only, which keeps rendered pages fully page-cacheable.
		wp_localize_script(
			self::SCRIPT_FRONTEND,
			'ztcFrontend',
			array(
				'restUrl' => esc_url_raw( rest_url( 'ztc/v1' ) ),
			)
		);
	}

	/**
	 * Whether the current admin screen belongs to this plugin.
	 *
	 * Covers the dashboard/settings pages and every plugin post type
	 * edit screen.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	private function is_plugin_screen( string $hook_suffix ): bool {
		if ( str_contains( $hook_suffix, 'zihad-travel-cms' ) ) {
			return true;
		}

		$screen = get_current_screen();

		return $screen && str_starts_with( (string) $screen->post_type, 'ztc_' );
	}
}
