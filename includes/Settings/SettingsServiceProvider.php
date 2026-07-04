<?php
/**
 * Settings service provider.
 *
 * @package ZihadTravelCMS
 */

declare(strict_types=1);

namespace ZihadTravelCMS\Settings;

use ZihadTravelCMS\Core\Config;
use ZihadTravelCMS\Core\ServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the settings data layer: the Settings API registration, the
 * shared structural sanitizer and the REST settings endpoint. The
 * settings *screen* lives in Admin\Pages\SettingsPage; every write
 * path (form, REST, WP-CLI) goes through SettingsSanitizer.
 */
final class SettingsServiceProvider extends ServiceProvider {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$this->container->singleton( SettingsSanitizer::class );
		$this->container->singleton( GlobalSettings::class );

		add_filter(
			'ztc_rest_controllers',
			static function ( array $controllers ): array {
				$controllers[] = SettingsController::class;

				return $controllers;
			}
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register the plugin option with the Settings API.
	 */
	public function register_settings(): void {
		register_setting(
			'ztc_settings_group',
			Config::OPTION_NAME,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( $this->container->get( SettingsSanitizer::class ), 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
	}
}
