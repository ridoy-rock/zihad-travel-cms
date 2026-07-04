<?php
/**
 * REST API service provider.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\RestApi;

use ZihadTravelCMS\Core\ServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin's REST namespace and route controllers.
 *
 * All plugin routes live under `ztc/v1`. Controllers are added to
 * controllers() as endpoints are built (search, availability,
 * bookings…); each controller registers its own routes and handles
 * its own permission checks.
 */
final class RestApiServiceProvider extends ServiceProvider {

	/**
	 * The plugin REST namespace.
	 */
	public const REST_NAMESPACE = 'ztc/v1';

	/**
	 * Route controller classes.
	 *
	 * @return array<class-string>
	 */
	private function controllers(): array {
		$controllers = array();

		/**
		 * Filter the REST controllers registered by the plugin.
		 *
		 * @param array<class-string> $controllers Controller class names.
		 */
		return (array) apply_filters( 'ztc_rest_controllers', $controllers );
	}

	/**
	 * {@inheritDoc}
	 */
	public function boot(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Instantiate each controller and let it register its routes.
	 */
	public function register_routes(): void {
		foreach ( $this->controllers() as $controller_class ) {
			$controller = $this->container->get( $controller_class );

			if ( method_exists( $controller, 'register_routes' ) ) {
				$controller->register_routes();
			}
		}
	}
}
