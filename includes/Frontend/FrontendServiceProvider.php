<?php
/**
 * Frontend service provider.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Frontend;

use ZihadTravelCMS\Core\ServiceProvider;
use ZihadTravelCMS\Views\GridRenderer;
use ZihadTravelCMS\Views\SearchFormData;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the frontend engine: template routing and shortcodes.
 *
 * Shortcodes register everywhere (they must render in REST responses
 * and Elementor editor previews); template routing is frontend-only.
 */
final class FrontendServiceProvider extends ServiceProvider {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$this->container->singleton( GridRenderer::class );
		$this->container->singleton( SearchFormData::class );
		$this->container->singleton( TemplateLoader::class );
		$this->container->singleton( Shortcodes::class );
	}

	/**
	 * {@inheritDoc}
	 */
	public function boot(): void {
		$this->container->get( Shortcodes::class )->register();

		if ( is_admin() ) {
			return;
		}

		$this->container->get( TemplateLoader::class )->register();
	}
}
