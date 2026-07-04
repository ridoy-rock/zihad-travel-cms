<?php
/**
 * Core service provider.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Core;

use ZihadTravelCMS\Contracts\Mailer;
use ZihadTravelCMS\Contracts\Registrable;
use ZihadTravelCMS\Contracts\TranslationProvider;
use ZihadTravelCMS\Services\NotificationService;
use ZihadTravelCMS\Services\WpMailer;
use ZihadTravelCMS\Translations\SiteTranslationProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Registers cross-cutting core services: translations, assets,
 * notifications and version upgrades.
 */
final class CoreServiceProvider extends ServiceProvider {

	/**
	 * Core services attached to WordPress hooks on boot.
	 *
	 * @var array<class-string<Registrable>>
	 */
	private const SERVICES = array(
		I18n::class,
		Assets::class,
		Upgrade::class,
		NotificationService::class,
	);

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		foreach ( self::SERVICES as $service ) {
			$this->container->singleton( $service );
		}

		// Multi-language readiness: the null-object provider serves
		// single-language sites; WPML/Polylang adapters replace it via
		// this filter without touching repositories or services.
		$this->container->singleton(
			TranslationProvider::class,
			static function ( Container $container ): object {
				/**
				 * Filter the translation provider implementation.
				 *
				 * @param class-string<TranslationProvider> $provider Provider class name.
				 */
				$provider = (string) apply_filters( 'ztc_translation_provider', SiteTranslationProvider::class );

				return $container->get( $provider );
			}
		);

		// Outbound mail behind a contract: transactional/SMTP providers
		// replace the wp_mail() default via this filter without touching
		// any calling code.
		$this->container->singleton(
			Mailer::class,
			static function ( Container $container ): object {
				/**
				 * Filter the mailer implementation.
				 *
				 * @param class-string<Mailer> $mailer Mailer class name.
				 */
				$mailer = (string) apply_filters( 'ztc_mailer', WpMailer::class );

				return $container->get( $mailer );
			}
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function boot(): void {
		foreach ( self::SERVICES as $service ) {
			$instance = $this->container->get( $service );

			if ( $instance instanceof Registrable ) {
				$instance->register();
			}
		}
	}
}
